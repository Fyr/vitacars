<?php
App::uses('AdminController', 'Controller');
class AdminMessagesController extends AdminController {
    public $name = 'AdminMessages';
    public $components = array('Auth', 'Table.PCTableGrid', 'Article.PCArticle');
	public $helpers = array('Core.PHTime');
    public $uses = array('Notify', 'User', 'Message');

    public function beforeRender() {
    	parent::beforeRender();
    	$this->set('objectType', 'Notify');
    }

    public function index() {
		if (!$this->isAdmin()) {
			$this->redirect(array('controller' => 'AdminMessages', 'action' => 'messageList'));
			return;
		}
    	$this->paginate = array(
    		'fields' => array('id', 'created', 'title', 'sent')
    	);
    	$this->PCTableGrid->paginate('Notify');
    }

	private function _sentMessages($notify, $user_ids) {
		$id = Hash::get($notify, 'Notify.id');
		try {
			$this->Message->trxBegin();
			foreach($user_ids as $user_id) {
				$this->Message->clear();
				$this->Message->save(array(
					'user_id' => $user_id,
					'title' => Hash::get($notify, 'Notify.title'),
					'body' => Hash::get($notify, 'Notify.body'),
					'active' => 1,
					'notify_id' => $id
				));
			}

			$sent = date('Y-m-d H:i:s');
			$this->Notify->save(compact('id', 'sent'));

			$this->Message->trxCommit();
			$this->setFlash(__('Message have been successfully sent to %s users', count($user_ids)), 'success');
		} catch (Exception $e) {
			$this->Message->trxRollback();
			$this->setFlash(__('Error! %s', $e->getMessage()), 'error');
		}
	}

    public function edit($id = 0) {
		if (!$this->isAdmin()) {
			$this->redirect(array('controller' => 'AdminMessages', 'action' => 'messageList'));
			return;
		}

    	$this->PCArticle->setModel('Notify')->edit($id, $lSaved);
		if ($lSaved) {
			$id = $this->Notify->id;
			if ($this->request->data('send')) {
				// send message
				$row = $this->Notify->findById($id);
				$user_ids = Hash::get($row, 'Notify.users');
				if ($user_ids) {
					$this->_sentMessages($row, explode(',', $user_ids));
				} else {
					$this->setFlash(__('You have specified no recipients!'), 'error');
				}
			}
			$baseRoute = array('action' => 'index');
			return $this->redirect(($this->request->data('send')) ? $baseRoute : array($id));
		}
		$this->paginate = array(
    		'User' => array(
    			'fields' => array('id', 'username'),
    		)
    	);
    	$this->PCTableGrid->paginate('User');
    }

	public function messageList() {
		$this->paginate = array(
			'fields' => array('created', 'title', 'active'),
			'conditions' => array('user_id' => $this->currUser('id'))
		);
		$data = $this->PCTableGrid->paginate('Message');
		$this->set(compact('data'));
	}

	public function view($id) {
		$message = $this->Message->findByIdAndUserId($id, $this->currUser('id'));
		if (!$message) {
			return $this->redirect(array('action' => 'messageList'));
		}
		$this->Message->save(array('id' => $id, 'active' => false));
		$this->set('message', $message);
	}
}
