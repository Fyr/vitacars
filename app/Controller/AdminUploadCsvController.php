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
                unset($fileCsv[0]);
                $csv = array();
                foreach ($fileCsv as $key => $value) {
                    $a = explode(';', $fileCsv[$key]);
                    if (isset($csv[$a[0]])) {
                        $csv[$a[0]] += intval($a[1]);
                    } else {
                        $csv[$a[0]] = intval($a[1]); 
                    }
                }
                // обновление количества в БД
                foreach ($csv as $key => $value) {
                    $productId = $this->FormValues->find('first', array(
                        'fields' => array('object_id'), 
                        'conditions' => array(
                            'field_id' => 5,
                            'value LIKE' => '%'.$key.'%'
                        )
                    ));
                    if ($productId) {
                        $this->Article->save(array('id' => $productId['FormValues']['object_id'], 'count' => $value));
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

