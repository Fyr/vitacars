<?php
App::uses('AdminController', 'Controller');
class AdminExportController extends AdminController {
    public $name = 'AdminExport';

	public function beforeFilter() {
		if (!$this->isAdmin()) {
			$this->redirect(array('controller' => 'Admin', 'action' => 'index'));
			return;
		}
		parent::beforeFilter();
	}
	
	public function progress() {
	}
	
}
