<?php
App::uses('AdminController', 'Controller');
App::uses('AppModel', 'Model');
App::uses('Product', 'Model');
class ImportController extends AppController {
	public $name = 'Import';
	public $uses = array('Product', 'Form.PMFormData', 'ProductRemain');
	
	const CSV_DIV = ';';
		
	public function index($file = '') {
		$this->autoRender = false;
		try {
			if (!$file) {
				throw new Exception(__('No file passed'));
			}	
			
			$fullPath = Configure::read('import.folder').$file;
			if (!file_exists($fullPath)) {
				throw new Exception(__('File does not exist `%s`', $fullPath));
			}
			
			$this->_writeLog('IMPORT', $file.' Size: '.filesize($fullPath).'(bytes) Path: '.$fullPath);
			
			$this->Product->getDataSource()->begin();
			$data = $this->_parseCsv($fullPath);
			$this->_processImport($data);
			$this->Product->getDataSource()->commit();
			
			$this->_writeLog('PROCESSED', count($data['data']).' product(s)');
			echo 'SUCCESS';
		} catch (Exception $e) {
			$this->Product->getDataSource()->rollback();
			$this->_writeLog('ERROR', $e->getMessage());
			echo 'ERROR';
		}
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
			if (!$product) {
				throw new Exception(__('Incorrect product code `%s`', $_data['code']));
			}
			$key = $data['keys'][1];
			$this->PMFormData->save(array('id' => $product['PMFormData']['id'], $key => $product['PMFormData'][$key] + $_data[$key]));
			
			$this->ProductRemain->clear();
			$this->ProductRemain->save(array('product_id' => $product['Product']['id'], 'remain' => $_data[$key]));
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
	
	private function _writeLog($actionType, $data){
		$string = date('d-m-Y H:i:s').' '.$actionType.' '.$data;
		file_put_contents(Configure::read('import.log'), $string."\r\n", FILE_APPEND);
	}
}
