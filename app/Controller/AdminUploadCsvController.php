<?php
App::uses('AdminController', 'Controller');
App::uses('Product', 'Model');
App::uses('PMFormValue', 'Form.Model');
class AdminUploadCsvController extends AdminController {
    public $name = 'AdminUploadCsv';
    public $layout = 'admin';
    public $uses = array('Product', 'Form.PMFormValue');
    
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
		/*
		$_FILES['csv_file'] = array('tmp_name' => 'd:\book1.csv');
		$this->upload();
		*/
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
		
		return $aData;
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
			$param = $this->PMFormValue->find('first', array(
				'fields' => array('object_id'),
				'conditions' => array(
					'field_id' => Product::NUM_DETAIL,
					'value LIKE ' => '%'.trim($number).'%'
				)
			));
			
			$object_id = Hash::get($param, 'PMFormValue.object_id');
			if ($object_id) {
				if (!isset($aParams[$object_id])) {
					$aParams[$object_id] = array();
				}
				array_shift($row); // исключить 1й ключ из обрабатываемой строки (номер детали)
				foreach($row as $counter => $count) {
					if (isset($aParams[$object_id][$counter])) {
						$aParams[$object_id][$counter]+= intval($count);
					} else {
						$aParams[$object_id][$counter] = intval($count);
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
	private function _updateParams($aParams) {
		foreach($aParams as $object_id => $row) {
			foreach($row as $counter => $val) {
				$param = $this->PMFormValue->find('first', array(
					'fields' => array('id'),
					'conditions' => array(
						'PMFormValue.object_id' => $object_id,
						'FormField.key' => $counter
					)
				));
				$data = array('value' => $val);
				if ($id = Hash::get($param, 'PMFormValue.id')) {
					$data['id'] = $id;
				}
				$this->PMFormValue->save($data);
			}
		}
	}

	public function upload() {
		try {
			if ( !(isset($_FILES['csv_file']) && is_array($_FILES['csv_file']) && isset($_FILES['csv_file']['tmp_name']) && $_FILES['csv_file']['tmp_name']) ) {
				throw new Exception('Error file upload');
			}
			
			$aData = $this->_parseCsv($_FILES['csv_file']['tmp_name']);
			$this->_updateParams($this->_getCounters($aData));
			
			$this->Session->setFlash(__('File have been successfully uploaded'), 'default', array(), 'success');
		} catch (Exception $e) {
			$this->Session->setFlash(__($e->getMessage(), $this->errLine), 'default', array(), 'error');
		}
		
		// Получить данные для редиректа
		list($numberKey) = array_keys($aData[0]);
		$aNumbers = Hash::extract($aData, '{n}.'.$numberKey);
		
		$this->redirect(array('controller' => 'AdminProducts', 'action' => 'index', 'Param3.value' => '*'.implode(' ', $aNumbers).'*'));
	}
    
	/*
    public function upload() {
        if($_FILES['csv_file']['name']){
            $fileCsv = file($_FILES['csv_file']['tmp_name']);
            if ($fileCsv) {
                // обработка файла
                $csv = array();
                foreach ($fileCsv as $key => $value) {
                    $a = explode(';', $fileCsv[$key]);
                    foreach ($a as $key_ => $value_) {
                        $csv[$key][$key_] = trim($value_);
                    }
                }

                $keys = $csv[0];
                unset($csv[0]);
                
                // Просуммируем данные по одинаковым номерам
                $usedNumbers = array();
                foreach ($csv as $number) {
                    if (isset($usedNumbers[$number[0]])) {
                        foreach ($number as $key => $value) {
                            if ($key) {
                                $usedNumbers[$number[0]][$key] = intval($value) + $usedNumbers[$number[0]][$key];
                            }
                        }
                    } else {
                        foreach ($number as $key => $value) {
                            if ($key) {
                                $usedNumbers[$number[0]][$key] = intval($value);
                            }
                        }
                        
                    }
                }
                foreach ($usedNumbers as $key => $value) {
                    //Найдем ID продукта по номеру
                    $productId = $this->FormValues->find('first', array(
                        'fields' => array('object_id', 'id'), 
                        'conditions' => array(
                            'field_id' => 5,
                            'value LIKE' => '%'.trim($key).'%'
                        )
                    ));
                    if ($productId) {
						//Изменим кол-во по данным с ключами
                        unset($keys[0]);
                        foreach ($keys as $key_ => $value_) {
                            $field_id = $this->FormField->find('first', array(
                                'fields' => array('id'), 
                                'conditions' => array('key' => $value_)
                            ));
                            if ($field_id) {
                                $row = $this->FormValues->find('first', array(
                                    'fields' => array('id'),
                                    'conditions' => array('field_id' => $field_id['FormField']['id'], 'object_id' => $productId['FormValues']['object_id'])
								));
								if (!$row) {
								    $this->FormValues->create();
								    $this->FormValues->save(array(
									'object_type' => 'ProductParam', 
									'object_id' => $productId['FormValues']['object_id'], 
									'value' => $usedNumbers[$key][$key_],
									'form_id' => 1,
									'field_id' => $field_id['FormField']['id']
								    ));
								} else {
								    $this->FormValues->save(array('id' => $row['FormValues']['id'], 'value' => $usedNumbers[$key][$key_]));
								}
                            }
                        }
                    }
                }
                $this->Session->setFlash(__('File successfully downloaded and read'));
            } else {
                $this->Session->setFlash(__('Error file upload'));
            }
        }
		$this->redirect(array('controller' => 'AdminUploadCsv', 'action' => 'index'));
    }
    */
}

