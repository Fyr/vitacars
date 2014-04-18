<?php
App::uses('AdminController', 'Controller');
class AdminUsersController extends AdminController {
    public $name = 'AdminUsers';
    public $uses = array('User');
    
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
		if ($this->request->is('post') || $this->request->is('put')) {
			if ($id) {
				$this->request->data('User.id', $id);
			}
			if ($this->User->save($this->request->data)) {
				$id = $this->User->id;
				$baseRoute = array('action' => 'index');
				return $this->redirect(($this->request->data('apply')) ? $baseRoute : array($id));
			}
		} elseif ($id) {
			$user = $this->User->findById($id);
			$user['User']['password'] = '';
			$this->request->data = $user;
		}
    }
}
