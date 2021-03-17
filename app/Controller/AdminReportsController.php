<?php
App::uses('AdminController', 'Controller');
class AdminReportsController extends AdminController {
    public $name = 'AdminReports';
	public $uses = array('ProductRemain', 'Product', 'SearchHistory');
    
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

	public function search() {
		if ($this->request->is('post')) {
			$result = $this->SearchHistory->getProducts($this->request->data('date'), $this->request->data('date2'));
			if ($result) {
				$product_ids = Hash::extract($result['rows'], '{n}.product_id');
				$aProducts = $this->Product->findAllById($product_ids);
				$result['aProducts'] = Hash::combine($aProducts, '{n}.Product.id', '{n}');
				$this->set($result);
				$fName = Configure::read('tmp_dir').'user_products_'.$this->Auth->user('id').'.tmp';
				file_put_contents($fName, implode("\r\n", $product_ids));
			}
		}
	}
}
