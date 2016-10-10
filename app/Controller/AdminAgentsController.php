<?php
App::uses('AdminController', 'Controller');
class AdminAgentsController extends AdminController {
    public $name = 'AdminAgents';
    public $components = array('Auth', 'Table.PCTableGrid', 'Article.PCArticle');
    public $uses = array('Agent', 'Form.FormField', 'Brand');
    
    public function beforeRender() {
		$this->currMenu = 'Orders';
    	parent::beforeRender();
    	$this->set('objectType', 'Agent');
    }
    
    public function index() {
    	$this->paginate = array(
    		'fields' => array('id', 'title', 'email', 'phone', 'active')
    	);
    	$this->PCTableGrid->paginate('Agent');
    }
    
    public function edit($id = 0) {
    	$this->PCArticle->setModel('Agent')->edit(&$id, &$lSaved);
		if ($lSaved) {
			$id = $this->Agent->id;
			$baseRoute = array('action' => 'index');
			return $this->redirect(($this->request->data('apply')) ? $baseRoute : array($id));
		}
    }
}
