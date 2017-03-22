<?php
App::uses('Component', 'Controller');

class TaskUploadNewProductsComponent extends Component {

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
			$recalc_formula = is_array($status) && in_array('recalc_formula', array_values($status));

			$params = array('csv_file' => $_file, 'fieldRights' => $this->_->_getRights(), 'recalc_formula' => $recalc_formula);
			$id = $this->_->Task->add($user_id, 'UploadNewProducts', $params);
			$this->_->Task->runBkg($id);
			return true;
		}
		return false;
	}

	public function postProcess($aID) {
		$user_id = AuthComponent::user('id');
		$this->_->setFlash(__('%s products have been successfully updated', count($aID)), 'success');
		$route = array(
			'controller' => 'AdminProducts',
			'action' => 'index',
			'sort' => 'Product.id',
			'direction' => 'asc',
			'limit' => (count($aID) > 1000) ? 1000 : count($aID)
		);
		if (count($aID) > 50) {
			$file = Configure::read('tmp_dir').'user_products_'.$user_id.'.tmp';
			file_put_contents($file, implode("\r\n", $aID));
			$route['Product.id'] = 'list';
		} else {
			$route['Product.id'] = implode(',', $aID);
		}
		$this->_->redirect($route);
	}
}