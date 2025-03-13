<?php
App::uses('Component', 'Controller');
App::uses('HtmlHelper', 'View/Helper');

class TaskUploadCountersComponent extends Component {

	protected $_;
	private $Html;

	public function initialize(Controller $controller) {
		$this->_ = $controller;
		$this->Html = new HtmlHelper(new View());
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

	private function getReportLink($msg, $xdata) {
		return $this->Html->link($msg, '/'.$xdata['error_report']);
	}

	public function postProcess($xdata) {
		$user_id = AuthComponent::user('id');
		$aID = $xdata['product_ids'];
		if ($xdata['errors']) {
			$aMsgs = array(
				__('%s products have been successfully updated', count($aID).'/'.$xdata['total']),
				__('Found: %s products with errors', $this->getReportLink($xdata['errors'], $xdata))
					.'. '.__('Download %s', $this->getReportLink(__('Error report'), $xdata))
			);
			$this->_->setFlash(implode('<br />', $aMsgs), 'error');
		} else {
			$this->_->setFlash(__('%s products have been successfully updated', count($aID)), 'success');
		}

		$route = array(
			'controller' => 'AdminProducts',
			'action' => 'index',
			'sort' => 'Product.id',
			'direction' => 'asc',
		);

		$route['limit'] = (count($aID) > 1000) ? 1000 : count($aID);
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