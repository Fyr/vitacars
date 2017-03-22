<?php
App::uses('Component', 'Controller');
App::uses('Brand', 'Model');
App::uses('Category', 'Model');

class TaskProductDescrComponent extends Component {

	protected $_;

	public function initialize(Controller $controller) {
		$this->_ = $controller;
	}

	public function preProcess() {
		if ($this->_->request->is(array('post', 'put'))) {
			$id = $this->_->Task->add(0, 'ProductDescr', $this->_->request->data('Filter'));
			$this->_->Task->runBkg($id);
			return true;
		}
		$this->_->loadModel('Brand');
		$this->_->set('aBrandOptions', $this->_->Brand->find('list', array('order' => 'sorting ASC')));

		$this->_->loadModel('Category');
		$this->_->set('aCategoryOptions', $this->_->Category->find('list', array('order' => 'sorting ASC')));
		return false;
	}

	public function postProcess($aID) {
		$this->_->setFlash(__('%s products have been successfully updated', count($aID)), 'success');
		$this->_->redirect(array('controller' => 'AdminTasks', 'action' => 'task', 'ProductDescr'));
	}
}