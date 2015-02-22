<?php
App::uses('AdminController', 'Controller');
class AdminReportsController extends AdminController {
    public $name = 'AdminReports';
    public $uses = array('ProductRemain');
    
    public function beforeFilter() {
		if (!$this->isAdmin()) {
			$this->redirect(array('controller' => 'Admin', 'action' => 'index'));
			return;
		}
		parent::beforeFilter();
	}
	
    public function index() {
    }
    
    public function sales() {
    	if ($this->request->is('post')) {
	    	$rows = $this->ProductRemain->sales($this->request->data('date'), $this->request->data('date2'));
	    	$this->set(compact('rows'));
	    	
	    	if ($this->request->data('print')) {
	    		$this->layout = 'print_xls';
	    		$this->render('sales_print');
	    	}
    	}
    }
}
