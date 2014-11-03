<?php
App::uses('AdminController', 'Controller');
App::uses('Product', 'Model');
App::uses('PMFormValue', 'Form.Model');
App::uses('FormField', 'Form.Model');
class AdminUploadCsvController extends AdminController {
    public $name = 'AdminUploadCsv';
    public $layout = 'admin';
    public $uses = array('Product', 'Form.PMFormValue', 'Form.FormField');
    
    const CSV_DIV = ';';
    private $errLine = 0;
    
	public function isAuthorized($user) {
		if (!$this->isAdmin()) {
			$this->redirect('/admin/');
			return false;
		}
		return parent::isAuthorized($user);
	}
    
	public function index() {
		try {
			if (isset($_FILES['csv_file']) && is_array($_FILES['csv_file']) && isset($_FILES['csv_file']['tmp_name']) && $_FILES['csv_file']['tmp_name']) {
				$aData = $this->_parseCsv($_FILES['csv_file']['tmp_name']);
				$this->_updateParams($aData['keys'], $this->_getCounters($aData['data']));
				
				// Получить данные для редиректа
				// list($numberKey) = array_keys($aData['data'][0]);
				$numberKey = $aData['keys'][0];
				$aNumbers = Hash::extract($aData['data'], '{n}.'.$numberKey);
				
				$this->Session->setFlash(__('File have been successfully uploaded'), 'default', array(), 'success');
				$this->redirect(array('controller' => 'AdminProducts', 'action' => 'index', 'Product.detail_num' => '*'.implode(' ', $aNumbers).'*'));
			}
		} catch (Exception $e) {
			$this->Session->setFlash(__($e->getMessage(), $this->errLine), 'default', array(), 'error');
			$this->redirect(array('controller' => 'AdminUploadCsv', 'action' => 'index'));
		}
	}
	
	/**
	 * Получить данные из CSV файла в виде ассоц.массива 
	 *
	 * @param str $file
	 * @return array
	 */
	private function _parseCsv($file) {
		$file = file($file);
		if (!($file && is_array($file) && count($file) > 1)) {
			throw new Exception('Incorrect file content');
		}
		
		$keys = explode(self::CSV_DIV, trim($file[0]));
		unset($file[0]);
		
		$aData = array();
		$this->errLine = 1;
		foreach($file as $row) {
			$this->errLine++;
			$_row = explode(self::CSV_DIV, trim($row));
			if (count($keys) !== count($_row)) {
				throw new Exception('Incorrect file format (Line %s)');
			}
			$aData[] = array_combine($keys, $_row);
		}
		
		return array('keys' => $keys, 'data' => $aData);
	}
	
	/**
	 * Проинициализировать счетчики в зав-ти от ID продукта
	 *
	 * @param unknown_type $aData
	 */
	private function _getCounters($aData) {
		$aParams = array();
		foreach($aData as $row) {
			list($number) = array_values($row);
			$params = $this->Product->find('all', array(
				'fields' => array('id'),
				'conditions' => array(
					'detail_num LIKE ' => '%'.trim($number).'%'
				)
			));
			/*
			$params = $this->PMFormValue->find('all', array(
				'fields' => array('object_id'),
				'conditions' => array(
					'field_id' => Product::NUM_DETAIL,
					'value LIKE ' => '%'.trim($number).'%'
				)
			));
			*/
			array_shift($row); // исключить 1й ключ из обрабатываемой строки (номер детали)
			foreach($params as $param) {
				$object_id = Hash::get($param, 'Product.id');
				if ($object_id) {
					if (!isset($aParams[$object_id])) {
						$aParams[$object_id] = array();
					}
					foreach($row as $counter => $count) {
						if (isset($aParams[$object_id][$counter])) {
							$aParams[$object_id][$counter]+= intval($count);
						} else {
							$aParams[$object_id][$counter] = intval($count);
						}
					}
				}
			}
			
		}
		return $aParams;
	}
	
	/**
	 * Обновить счетчики по ID продукта
	 *
	 * @param array $aParams
	 */
	private function _updateParams($keys, $aParams) {
		// Считать инфу о колонках
		array_shift($keys); // исключить 1й ключ из обрабатываемой строки (номер детали)
		$aKeys = array();
		$aRowKeys = $this->FormField->find('all', array('conditions' => array('FormField.key' => $keys)));
		foreach($aRowKeys as $keyInfo) {
			$keyInfo = $keyInfo['FormField'];
			$aKeys[$keyInfo['key']] = $keyInfo;
		}
		
		// перед сохранением очистить столбцы
		$this->PMFormValue->updateAll(array('value' => '\'&nbsp;\''), array(
			'FormField.key' => $keys
		));
		
		foreach($aParams as $object_id => $row) {
			foreach($row as $counter => $value) {
				$param = $this->PMFormValue->find('first', array(
					'fields' => array('id'),
					'conditions' => array(
						'PMFormValue.object_id' => $object_id,
						'FormField.key' => $counter
					)
				));
				$data = array('value' => $value);
				if ($id = Hash::get($param, 'PMFormValue.id')) {
					$data['id'] = $id;
				} else {
					// если запись не найдена - добавить ее с полной инфой
					$object_type = 'ProductParam';
					$form_id = 1;
					$field_id = $aKeys[$counter]['id'];
					
					$data = compact('object_type', 'object_id', 'form_id', 'field_id', 'value');
					
				}
				$this->PMFormValue->create();
				$data['value'] = $data['value'] ? $data['value'] : '&nbsp;'; //?????
				$this->PMFormValue->save($data);
			}
		}
	}

	protected function _createProducts($aData) {
		// $aStatic = array('title', 'title_rus', 'detail_num', 'code', 'page_id', 'published', 'active', 'count', 'brand_id', 'cat_id', 'subcat_id');
		
		$aFormFields = $this->FormField->find('list', array('fields' => array('key', 'id'), 'conditions' => array('FormField.key IS NOT NULL AND FormField.key <> ""')));
		$aID = array();
		$this->errLine = 1;
		foreach($aData['data'] as $row) {
			$this->errLine++;
			$this->Product->clear();
			$row['object_type'] = 'Product';
			if (!$this->Product->save($row)) {
				throw new Exception('Cannot create product (Line %s)');
			}
			
			foreach($aFormFields as $key => $id) {
				if (isset($row[$key])) {
					$this->PMFormValue->clear();
					$res = $this->PMFormValue->save(array(
						'object_type' => 'ProductParam', 
						'object_id' => $this->Product->id, 
						'form_id' => 1, 
						'field_id' => $id, 
						'value' => $row[$key]
					));
					if (!$res) {
						throw new Exception('Cannot create form field (Line %s)');
					}
				}
			}
			$aID[] = $this->Product->id;
		}
		return $aID;
	}
    
	public function uploadNewProducts() {
		try {
			if (isset($_FILES['csv_file']) && is_array($_FILES['csv_file']) && isset($_FILES['csv_file']['tmp_name']) && $_FILES['csv_file']['tmp_name'] ) {
				$aData = $this->_parseCsv($_FILES['csv_file']['tmp_name']);
				
				$this->Product->getDataSource()->begin();
				$aID = $this->_createProducts($aData);
				$this->Product->getDataSource()->commit();
				
				$this->Session->setFlash(__('%s products have been successfully uploaded', count($aID)), 'default', array(), 'success');
				$this->redirect(array('controller' => 'AdminProducts', 'action' => 'index', 'Product.id' => implode(',', $aID)));
			}
		} catch (Exception $e) {
			$this->Product->getDataSource()->rollback();
			$this->Session->setFlash(__($e->getMessage(), $this->errLine), 'default', array(), 'error');
			$this->redirect(array('controller' => 'AdminUploadCsv', 'action' => 'uploadNewProducts'));
		}
	}
}

