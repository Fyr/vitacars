<?php
App::uses('AdminController', 'Controller');
App::uses('Task', 'Model');
class AdminTasksController extends AdminController {
    public $name = 'AdminTasks';
    public $uses = array('Task');
    
    public function beforeFilter() {
		if (!$this->isAdmin()) {
			$this->redirect(array('controller' => 'Admin', 'action' => 'index'));
			return;
		}
		parent::beforeFilter();
	}
    
    public function index($taskName) {
		$title = $this->Task->getTitle($taskName);
		$task = $this->Task->getActiveTask($taskName);
		$avgTime = $this->Task->avgExecTime($taskName);
		$taskComponent = $this->Components->load($taskName.'Task');
		$taskComponent->initialize($this);
		if ($task) {
			$id = Hash::get($task, 'Task.id');
			$status = $this->Task->getStatus($id);
			$xdata = $this->Task->getData($id, 'xdata');
			if ($status == Task::DONE) {
				$this->Task->close($id);
				$taskComponent->postProcess($xdata);
			} elseif ($status == Task::ABORTED) {
				$this->Task->close($id);
				$this->setFlash(__('Processing was aborted by user'), 'error');
				$this->redirect(array('action' => 'index', $taskName));
			}  elseif ($status == Task::ERROR) {
				$this->Task->close($id);
				$this->setFlash(__('Process execution error! %s', $xdata), 'error');
				$this->redirect(array('action' => 'index', $taskName));
			}

			$task = $this->Task->getFullData($id);
			if (!isset($task['subtask']) && in_array($taskName, array('UploadCounters', 'UploadNewProducts'))) {
				$task['subtask'] = true;
			}
		} else {
			if ($taskComponent->preProcess()) {
				sleep(1); // дать время на запуск таска
				$this->redirect(array('action' => 'index', $taskName));
			}
		}
		$this->set(compact('taskName', 'title', 'task', 'avgTime'));
    }

}
