<?php
App::uses('AppController', 'Controller');
App::uses('UserLog', 'Model');
App::uses('EventType', 'Model');
App::uses('User', 'Model');
class AdminAuthController extends AppController {
	public $name = 'AdminAuth';
	public $components = array('Core.PCAuth');
	public $uses = array('UserLog', 'EvenType', 'User');
	public $layout = 'login';

	public function login() {
		if ($this->request->is('post')) {
			if ($this->Auth->login()) {
				$data = array(
					'event_type' => EventType::LOGIN,
					'user_id' => AuthComponent::user('id'),
					'ip' => $_SERVER['REMOTE_ADDR'],
					'host' => gethostbyaddr($_SERVER['REMOTE_ADDR'])
				);
				$this->UserLog->save($data);
				$this->Session->write('checkMsgTime', '2017-09-26 00:00:00'); // to show all unread messages when user loggs in
				return $this->redirect($this->Auth->redirect());
			} else {
				$user = $this->User->findByUsername($this->request->data('User.username'));
				$data = array(
					'event_type' => EventType::LOGIN_FAIL,
					'user_id' => ($user) ? $user['User']['id'] : 0,
					'ip' => $_SERVER['REMOTE_ADDR'],
					'host' => gethostbyaddr($_SERVER['REMOTE_ADDR']),
					'xdata' => 'Username: '.$this->request->data('User.username')
				);
				$this->UserLog->save($data);
				$this->Session->setFlash(AUTH_ERROR, null, null, 'auth');
			}
		}
	}

	public function logout() {
		$data = array(
			'event_type' => EventType::LOGOUT,
			'user_id' => AuthComponent::user('id'),
			'ip' => $_SERVER['REMOTE_ADDR'],
			'host' => gethostbyaddr($_SERVER['REMOTE_ADDR'])
		);
		$this->UserLog->save($data);
		$this->redirect($this->Auth->logout());
	}

}
