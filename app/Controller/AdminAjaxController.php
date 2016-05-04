<?php
App::uses('AppController', 'Controller');
App::uses('PAjaxController', 'Core.Controller');
class AdminAjaxController extends PAjaxController {
	public $name = 'AdminAjax';
	public $components = array('Core.PCAuth');

	public function recalcStart() {
		$this->loadModel('Form.PMFormData');
		$this->PMFormData->updateAll(array('recalc' => 0));
		$this->runBkg('recalc_formula');
		$this->setResponse(true);
	}

	public function recalcStatus() {
		$this->loadModel('Form.PMFormData');
		$progress = $this->PMFormData->find('count', array('conditions' => array('recalc' => 1)));
		$total = $this->PMFormData->find('count');
		$this->setResponse(compact('progress', 'total'));
	}
}
