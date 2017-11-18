<?php
App::uses('Component', 'Controller');
App::uses('Category', 'Model');

class TaskCrossnumParserComponent extends Component {

	protected $_;

	public function initialize(Controller $controller) {
		$this->_ = $controller;
	}

	public function preProcess() {
		$user_id = AuthComponent::user('id');
		if ($this->_->request->is(array('post', 'put'))) {
			$id = $this->_->Task->add($user_id, 'CrossnumParser', $this->_->request->data('Filter'));
			$this->_->Task->runBkg($id);
			return true;
		}
		$this->_->loadModel('Category');
		$this->_->set('aCategoryOptions', $this->_->Category->find('list', array('order' => 'sorting ASC')));
		return false;
	}

	public function postProcess($aID) {
		$this->_->setFlash(__('%s products have been successfully added', count($aID)).'. '.__('Download %s', '<a href="/files/crossnumparser.csv">CSV</a>'), 'success');
		$this->_->redirect(array('controller' => 'AdminTasks', 'action' => 'task', 'CrossnumParser'));
	}
}