<?php
App::uses('AdminController', 'Controller');
class AdminSettingsController extends AdminController {
    public $name = 'AdminSettings';
    public $uses = array('Settings');
    
    public function beforeFilter() {
		if (!$this->isAdmin()) {
			$this->redirect(array('controller' => 'Admin', 'action' => 'index'));
			return;
		}
		parent::beforeFilter();
	}
    
    public function index() {
        if ($this->request->is('post') || $this->request->is('put')) {
        	$this->request->data('Settings.id', 1);
        	$this->Settings->save($this->request->data);
        	$this->Session->setFlash(__('Settings have been successfully saved'), 'default', array(), 'success');
        	$this->redirect(array('action' => 'index'));
        }
        $this->request->data = $this->Settings->getData();
    }
}
