<?php
App::uses('AdminController', 'Controller');
class AdminUtilsController extends AdminController {
    public $name = 'AdminUtils';
    
    public function beforeFilter() {
		if (!$this->isAdmin()) {
			$this->redirect(array('controller' => 'Admin', 'action' => 'index'));
			return;
		}
		parent::beforeFilter();
	}
	
    public function index() {
    }
}
