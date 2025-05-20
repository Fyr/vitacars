<?php
App::uses('AdminController', 'Controller');
App::uses('Client', 'Model');
class AdminClientsController extends AdminController {
    public $name = 'AdminClients';
    public $components = array('Auth', 'Table.PCTableGrid', 'Article.PCArticle');
	public $uses = array('Client');

    public function beforeRender() {
		$this->currMenu = 'Clients';
    	parent::beforeRender();
    	$this->set('objectType', 'Client');
    }

    public function index() {
    	$this->paginate = array(
    		'fields' => array('id', 'created', 'modified', 'zone', 'group_id', 'email', 'fio', 'phone', 'ClientCompany.*', 'active'),
    		'conditions' => array('Client.group_id <> ' => Client::GROUP_ADMIN),
    		'order' => array('Client.created' => 'DESC')
    	);
    	$aRows = $this->PCTableGrid->paginate('Client');
    	$this->set('aRows', $aRows);
    }

    public function edit($id = 0) {
        if ($this->request->is(array('post', 'put'))) {
            $this->request->data('Client.username', $this->request->data('Client.email'));
        }
    	$this->PCArticle->setModel('Client')->edit(&$id, &$lSaved);
		if ($lSaved) {
			$id = $this->Client->id;
			$baseRoute = array('action' => 'index');
			return $this->redirect(($this->request->data('apply')) ? $baseRoute : array($id));
		}
    }
}
