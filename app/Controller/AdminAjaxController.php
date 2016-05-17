<?php
App::uses('AppController', 'Controller');
App::uses('PAjaxController', 'Core.Controller');
App::uses('Task', 'Model');
class AdminAjaxController extends PAjaxController {
	public $name = 'AdminAjax';
	public $components = array('Core.PCAuth');
	public $uses = array('Task');

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

	public function getTaskStatus($id) {
		$task = $this->Task->getData($id);
		if ($task) {
			$task['progress'] = $this->Task->getProgressInfo($id);
			$subtask_id = $this->Task->getData($id, 'subtask_id');
			if ($subtask_id) {
				$subtask = $this->Task->getData($subtask_id);
				$subtask['progress'] = $this->Task->getProgressInfo($subtask_id);
				$task['subtask'] = $subtask;
			}
		}
		$this->setResponse($task);
	}

	public function taskAbort($id) {
		$task = $this->Task->getData($id);
		if ($task) {
			$this->Task->setStatus($id, Task::ABORT);
		}
		/*
		$time = time();
		while ($this->Task->getStatus($id) == Task::ABORT && (time() - $time) < 30 ) { // wait 30 secs for task aborting
			fdebug(array($this->Task->getStatus($id), time() - $time));
		}
		$this->setResponse($this->Task->getData($id));
		*/
		$this->setResponse(true);
	}
}
