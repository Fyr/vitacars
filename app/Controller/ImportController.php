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
			 * Считаем что запросы могут приходить параллельно.
			 * Поэтому вполне вероятно что будет выполнено 2 запроса одновременно,
			 * причем не известно на каком этапе одного запроса может запуститься другой.
			 * Посему мы добавляем все таски, а последний выполненный таск сам запускает след. если таковые есть
			 */
			$this->_addTask($file, $user_id);
			$conditions = array('task_name' => self::TASK_NAME, 'status' => array(Task::RUN));
			$task = $this->Task->find('first', compact('conditions'));
			if ($task) {
				echo 'RUN';
				return;
			}

			// нет выполняемых тасков - запускаем последнюю задачу (она удалит другие если нужно)
			$conditions = array('task_name' => self::TASK_NAME, 'status' => array(Task::CREATED));
			$order = array('Task.id' => 'DESC');
			$task = $this->Task->find('first', compact('conditions', 'order'));
			if ($task) {
				$this->Logger->write('RUN BKG', am(array('TaskID' => $task['Task']['id']), unserialize($task['Task']['params'])));
				$this->Task->runBkg($task['Task']['id']);
				echo 'SUCCESS';
				return;
			}

			echo 'ERROR! NO TASK';

		} catch (Exception $e) {
			$this->Logger->write('ERROR', array('File' => $file, 'Error' => $e->getMessage()));
			echo 'ERROR! ' . $e->getMessage();
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
		$csv_file = $_path . DS . $file;
		rename($fullPath, $csv_file);

		$params = compact('csv_file');
		$id = $this->Task->add($user_id, self::TASK_NAME, $params);
		$this->Logger->write('ADD TO QUEUE', array('TaskID' => $id, 'File' => $params['csv_file']));
	}

	private function _getFilePath($file) {
		list($fileDate) = explode('_', str_replace('dlt_mgr', '', $file));
		$path = substr($fileDate, 0, 4).DS.substr($fileDate, 4, 2).DS.substr($fileDate, 6, 2);
		return $path;
	}
	
}
