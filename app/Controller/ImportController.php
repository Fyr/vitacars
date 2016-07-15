<?php
App::uses('AdminController', 'Controller');
App::uses('AppModel', 'Model');
App::uses('Task', 'Model');
App::uses('Logger', 'Model');
class ImportController extends AppController {
	public $name = 'Import';
	public $uses = array('Task', 'Logger');
	public $autoRender = false;
	
	const TASK_NAME = 'Import1C';

	public function index($file = '') {
		$user_id = 0;
		$this->Logger->init(Configure::read('import.log'));
		try {
			/**
			 * Во время отработки запроса может придти еще несколько.
			 * Считаем что запросы могут выполняться параллельно.
			 * Поэтому вполне вероятно что будет выполнено 2 запроса одновременно,
			 * причем не известно на каком этапе одного запроса может запуститься другой.
			 * Почему мы сначала добавляем все таски, а потом убиваем все кроме последнего
			 */
			$this->_addTask($file, $user_id);

			$conditions = array('task_name' => self::TASK_NAME, 'status' => array(Task::CREATED, Task::RUN));
			$order = array('Task.id' => 'DESC');
			$tasks = $this->Task->find('all', compact('conditions', 'order'));
			if ($tasks) {
				$lastTask = array_shift($tasks);
				// запускаем последний созданный таск если он еще не запущен
				if ($lastTask['Task']['status'] == Task::CREATED) {
					$this->Logger->write('RUN BKG', am(array('TaskID' => $lastTask['Task']['id']), unserialize($lastTask['Task']['params'])));
					$this->Task->runBkg($lastTask['Task']['id']);
				}
				// остальные таски - прерываем
				foreach($tasks as $task) {
					$this->Logger->write('ABORT', am(array('TaskID' => $task['Task']['id']), unserialize($task['Task']['params'])));
					$this->Task->setStatus($task['Task']['id'], Task::ABORT);
				}

				sleep(1); // даем время на прерывание тасков

				// остальные таски - закрываем
				$conditions = array('id <> ' => $lastTask['Task']['id'], 'task_name' => self::TASK_NAME, 'status' => array(Task::ABORTED, Task::DONE), 'active' => 1);
				$tasks = $this->Task->find('all', compact('conditions'));
				foreach ($tasks as $task) {
					$this->Logger->write('CLOSE', am(array('TaskID' => $task['Task']['id']), unserialize($task['Task']['params'])));
					$this->Task->close($task['Task']['id']);
				}
			}

		} catch (Exception $e) {
			$this->Logger->write('ERROR', array('File' => $file, 'Error' => $e->getMessage()));
			echo 'ERROR';
		}
	}

	private function _addTask($file, $user_id) {
		if (!$file) {
			throw new Exception(__('No file passed'));
		}

		if (substr($file, 0, 7) !== 'dlt_mgr') {
			throw new Exception(__('Incorrect file name `%s`', $file));
		}

		$fullPath = Configure::read('import.folder').$file;
		if (!file_exists($fullPath)) {
			throw new Exception(__('File does not exist `%s`', $fullPath));
		}

		$path = explode(DS, $this->_getFilePath($file));
		$_path = Configure::read('import.folder').DS.$path[0];
		if (!file_exists($_path)) {
			mkdir($_path);
		}
		$_path.= DS.$path[1];
		if (!file_exists($_path)) {
			mkdir($_path);
		}
		$_path.= DS.$path[2];
		if (!file_exists($_path)) {
			mkdir($_path);
		}
		rename($fullPath, $_path.DS.$file);

		$params = array('csv_file' => $_path.DS.$file);
		$id = $this->Task->add($user_id, self::TASK_NAME, $params);
		$this->Logger->write('ADD TO QUEUE', array('TaskID' => $id, 'File' => $params['csv_file']));
	}

	/*
	public function index_2($file = '') {
		$this->autoRender = false;
		try {
			$this->Product->trxBegin();
			$this->Product->unbindModel(array(
				'belongsTo' => array('Category', 'Subcategory', 'Brand'),
				'hasOne' => array('Media', 'Seo', 'Search')
			), false);

			if (!$file) {
				throw new Exception(__('No file passed'));
			}

			if (substr($file, 0, 7) !== 'dlt_mgr') {
				throw new Exception(__('Incorrect file name `%s`', $file));
			}

			$fullPath = Configure::read('import.folder').$file;
			if (!file_exists($fullPath)) {
				throw new Exception(__('File does not exist `%s`', $fullPath));
			}

			$this->_writeLog('IMPORT', $file.' Size: '.filesize($fullPath).'(bytes) Path: '.$fullPath);
			
			$data = $this->_parseCsv($fullPath);
			$this->_processImport($data);
			$this->Product->trxCommit();
			
			$this->_writeLog('PROCESSED', count($data['data']).' product(s)');
			
			$path = explode(DS, $this->_getFilePath($file));
			$_path = Configure::read('import.folder').DS.$path[0];
			if (!file_exists($_path)) {
				mkdir($_path);
			}
			$_path.= DS.$path[1];
			if (!file_exists($_path)) {
				mkdir($_path);
			}
			$_path.= DS.$path[2];
			if (!file_exists($_path)) {
				mkdir($_path);
			}
			rename($fullPath, $_path.DS.$file);
			
			echo 'SUCCESS';
		} catch (Exception $e) {
			$this->Product->trxRollback();
			$this->_writeLog('ERROR', $e->getMessage());
			echo 'ERROR';
		}
	}
	*/
	private function _getFilePath($file) {
		list($fileDate) = explode('_', str_replace('dlt_mgr', '', $file));
		$path = substr($fileDate, 0, 4).DS.substr($fileDate, 4, 2).DS.substr($fileDate, 6, 2);
		return $path;
	}
	
	private function _processImport($data) {
		if (!(isset($data['keys']) && $data['keys'])) {
			throw new Exception(__('Incorrect CSV headers'));
		}
		
		$paramA1 = 'fk_'.Configure::read('Params.A1');
		$paramA2 = 'fk_'.Configure::read('Params.A2');
		if (!($data['keys'][0] == 'code' && ($data['keys'][1] == $paramA1 || $data['keys'][1] == $paramA2))) {
			throw new Exception(__('Incorrect header keys: %s', print_r($data['keys'], true)));
		}
		
		if (!(isset($data['data']) && $data['data'])) {
			throw new Exception(__('Incorrect CSV data'));
		}
		
		foreach($data['data'] as $_data) {
			$product = $this->Product->findByCode($_data['code']);
			$key = $data['keys'][1];
			$logData = array(
				'code' => $_data['code'], 
				'fk_n' => $data['keys'][1],
				'val' => $_data[$key],
				'data' => implode(';', $_data),
				'status' => 'ERROR'
			);
			if ($product) {
				$this->PMFormData->save(array('id' => $product['PMFormData']['id'], $key => $_data[$key]));
				
				$logData['product_id'] = $product['Product']['id'];
				$logData['form_data_id'] = $product['PMFormData']['id'];
				$logData['status'] = 'OK';
			}
			$this->ImportLog->clear();
			$this->ImportLog->save($logData);
		}
	}
	
	private function _parseCsv($file) {
		$file = mb_convert_encoding(trim(file_get_contents($file)), 'utf-8', 'cp1251');
		$file = str_replace("\r\n", "\n", $file);
		$file = str_replace(array('   ', '  '), ' ', $file);
		$file = explode("\n", $file);
		if (!($file && is_array($file) && count($file) > 1)) {
			throw new Exception('Incorrect file content');
		}
		
		$keys = explode(self::CSV_DIV, trim($file[0]));
		unset($file[0]);
		
		$aData = array();
		$errLine = 1;
		foreach($file as $row) {
			$errLine++;
			$_row = explode(self::CSV_DIV, trim($row));
			if (count($keys) !== count($_row)) {
				throw new Exception(__('Incorrect file format (Line %s)', $errLine));
			}
			$aData[] = array_combine($keys, $_row);
		}
		
		return array('keys' => $keys, 'data' => $aData);
	}
	
}
