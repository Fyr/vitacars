<?php
App::uses('AdminController', 'Controller');
class AdminUsersController extends AdminController {
    public $name = 'AdminUsers';
    public $components = array('Auth', 'Table.PCTableGrid', 'Article.PCArticle');
	public $uses = array('User', 'Form.PMFormField', 'Brand');
    
    public function beforeFilter() {
		if (!$this->isAdmin()) {
			$this->redirect(array('controller' => 'Admin', 'action' => 'index'));
			return;
		}
		parent::beforeFilter();
	}
    
    public function beforeRender() {
    	parent::beforeRender();
    	$this->set('objectType', 'User');
    }
    
    public function index() {
    	$this->paginate = array(
    		'fields' => array('id', 'created', 'username', 'active')
    	);
    	$this->PCTableGrid->paginate('User');
    }
    
    public function edit($id = 0) {
    	if ($id) {
			if ($this->request->is(array('put', 'post')) && !$this->request->data('User.password')) {
				unset($this->request->data['User']['password']);
			}
		}
    	$this->PCArticle->setModel('User')->edit(&$id, &$lSaved);
		if ($lSaved) {
			$id = $this->User->id;
			if ($id == AuthComponent::user('id')) {
				// перечитать данные для текущего юзера
				$user = $this->User->findById($id);
				$this->Auth->login($user['User']);
			}
			$baseRoute = array('action' => 'index');
			return $this->redirect(($this->request->data('apply')) ? $baseRoute : array($id));
		}
		if ($id) {
			$this->request->data('User.password', '');
		}
		$this->paginate = array(
			'PMFormField' => array(
				'fields' => array('id', 'field_type', 'label'),
    			'limit' => 100
			),
    		'Brand' => array(
    			'fields' => array('id', 'title')
    		)
    	);
    	$this->PCTableGrid->paginate('Brand');
		$this->PCTableGrid->paginate('PMFormField');
    }
}
