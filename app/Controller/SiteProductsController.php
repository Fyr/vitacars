<?php
App::uses('AppController', 'Controller');
App::uses('SiteController', 'Controller');
class SiteProductsController extends SiteController {
	public $name = 'SiteProducts';
	public $uses = array('Product', 'Form.PMFormValue');

	public function index() {
		$this->pageTitle = __('Products');
		$this->paginate = array(
			'conditions' => array('Product.published' => 1),
			'limit' => 10, 
			'order' => 'Product.created DESC'
		);
		$this->paginate['conditions'] = array_merge($this->paginate['conditions'], $this->postConditions($this->params->query['data']));
		$this->set('products', $this->paginate('Product'));
	}
	
	public function view($id) {
		$article = $this->Product->findById($id);
		$this->pageTitle = $article['Product']['title'];
		$this->set('article', $article);
		$this->set('techParams', $this->PMFormValue->getValues('ProductParam', $id));
		$this->set('aMedia', $this->Media->getObjectList('Product', $id));
	}
}
