<?php
App::uses('AdminController', 'Controller');
App::uses('UserLog', 'Model');
App::uses('EventType', 'Model');
App::uses('User', 'Model');
class AdminUserLogsController extends AdminController {
    public $name = 'AdminUserLogs';
    public $components = array('Auth', 'Table.PCTableGrid', 'Article.PCArticle');
    public $uses = array('UserLog', 'User', 'EventType');
    
    public function beforeFilter() {
		if (!$this->isAdmin()) {
			$this->redirect(array('controller' => 'Admin', 'action' => 'index'));
			return;
		}
		parent::beforeFilter();
	}
    
    public function index() {
    	$this->paginate = array(
    		'fields' => array('created', 'event_type', 'user_id', 'ip', 'host', 'xdata')
    	);
    	$aRows = $this->PCTableGrid->paginate('UserLog');
		$this->set('aUsers', $this->User->find('list', array('fields' => array('id', 'username'))));
    }
    
}
