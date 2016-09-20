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
        if ($this->request->is(array('post', 'put'))) {
        	$this->request->data('Settings.id', 1);
			$gpz_brands = $this->request->data('Settings.gpz_brands');
			if (is_array($gpz_brands)) {
				$this->request->data('Settings.gpz_brands', implode(',', $gpz_brands));
			}
        	$this->Settings->save($this->request->data);
        	$this->setFlash(__('Settings have been successfully saved'), 'success');
        	$this->redirect(array('action' => 'index'));
        }
        $this->request->data = $this->Settings->getData();
		$this->loadModel('Brand');
		$this->set('aBrandOptions', $this->Brand->find('list', array('order' => 'sorting DESC')));
    }
/*
	public function index($tpl = '') {
		if ($this->request->is('post') || $this->request->is('put')) {
			$this->request->data('Settings.id', 1);
			$this->Settings->save($this->request->data);
			$this->setFlash(__('Settings have been saved'), 'success');
			return $this->redirect(array('action' => 'index', $tpl));
		}
		$this->request->data = $this->Settings->getData();
		$this->set('tpl', $tpl);
	}
*/
}
