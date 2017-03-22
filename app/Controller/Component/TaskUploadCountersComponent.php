<?php
App::uses('Component', 'Controller');

class TaskUploadCountersComponent extends Component {

	protected $_;

	public function initialize(Controller $controller) {
		$this->_ = $controller;
	}

	public function preProcess() {
		$user_id = AuthComponent::user('id');
		if ($file = Hash::get($_FILES, 'csv_file.tmp_name')) {
			$_file = Configure::read('tmp_dir').basename($file, '.tmp').'.csv';
			move_uploaded_file($file, $_file);

			$status = $this->_->request->data('UploadCsv.status');
			$set_zero = is_array($status) && in_array('set_zero', array_values($status));

			$params = array('csv_file' => $_file, 'fieldRights' => $this->_->_getRights(), 'set_zero' => $set_zero);
			$id = $this->_->Task->add($user_id, 'UploadCounters', $params);
			$this->_->Task->runBkg($id);
			return true;
		}
		return false;
	}

	public function postProcess($aID) {
		$user_id = AuthComponent::user('id');
		// $aID = $this->_->Task->getData($task_id, 'xdata');
		$this->_->setFlash(__('%s products have been successfully updated', count($aID)), 'success');
		if (count($aID) > 50) {
			$file = Configure::read('tmp_dir').'user_products_'.$user_id.'.tmp';
			file_put_contents($file, implode("\r\n", $aID));
			$this->_->redirect(array('controller' => 'AdminProducts', 'action' => 'index', 'Product.id' => 'list'));
		} else {
			$this->_->redirect(array('controller' => 'AdminProducts', 'action' => 'index', 'Product.id' => implode(',', $aID)));
		}
	}
}