<?php
App::uses('AdminController', 'Controller');
class AdminUploadCsvController extends AdminController {
    public $name = 'AdminUploadCsv';
    public $layout = 'admin';
    public $uses = array('Form.FormField', 'Form.FormValues', 'Article');
    
    public function isAuthorized($user) {
    	if (!$this->isAdmin()) {
    		$this->redirect('/admin/');
    		return false;
    	}
    	return parent::isAuthorized($user);
    }
    
    public function index() {

    }
    
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
                
                /* Просуммируем данные по одинаковым номерам */
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
                    /* Обновляем поле количество */
                    $productId = $this->FormValues->find('first', array(
                        'fields' => array('object_id', 'id'), 
                        'conditions' => array(
                            'field_id' => 5,
                            'value LIKE' => '%'.$key.'%'
                        )
                    ));
                    if ($productId) {
                        /* Изменим кол-во */
                        $this->Article->save(array('id' => $productId['FormValues']['object_id'], 'count' => $usedNumbers[$key][1]));
                    
                        /* Изменим кол-во по данным с ключами */
                        unset($keys[0]);
                        unset($keys[1]);
                        foreach ($keys as $key_ => $value_) {
                            $field_id = $this->FormField->find('first', array(
                                'fields' => array('id'), 
                                'conditions' => array('key' => $value_)
                            ));
                            if ($field_id ) {
                                $row = $this->FormValues->find('first', array(
                                    'fields' => array('id'),
                                    'conditions' => array('field_id' => $field_id['FormField']['id'], 'object_id' => $productId['FormValues']['object_id'])));
                                $this->FormValues->save(array('id' => $row['FormValues']['id'], 'value' => $usedNumbers[$key][$key_]));
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
}

