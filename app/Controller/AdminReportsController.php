<?php
App::uses('AdminController', 'Controller');
class AdminReportsController extends AdminController {
    public $name = 'AdminReports';
	public $uses = array('ProductRemain', 'Product');
    
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
			$aProducts = $this->Product->findAllById(array_unique(Hash::extract($rows, '{n}.ProductRemain.product_id')));
			$aProducts = Hash::combine($aProducts, '{n}.Product.id', '{n}');
			$this->set(compact('rows', 'aProducts'));
	    	
	    	if ($this->request->data('print')) {
	    		$this->layout = 'print_xls';
	    		$this->render('sales_print');
	    	}
    	}
    }
}
