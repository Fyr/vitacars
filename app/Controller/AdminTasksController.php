<?php
App::uses('AdminController', 'Controller');
App::uses('Task', 'Model');
App::uses('User', 'Model');
App::uses('PHTime', 'Core.View/Helper');
class AdminTasksController extends AdminController {
    public $name = 'AdminTasks';
    public $uses = array('Task', 'User');
	public $helpers = array('Core.PHTime');
    
    public function beforeFilter() {
		if (!$this->isAdmin()) {
			$this->redirect(array('controller' => 'Admin', 'action' => 'index'));
			return;
		}
		parent::beforeFilter();
		if ($this->request->action == 'index') {
			$this->currMenu = 'System';
		}
	}

	public function index() {
		$this->paginate = array(
			'conditions' => array('Task.parent_id' => 0),
			'fields' => array('created', 'user_id', 'task_name', 'progress', 'total', 'exec_time', 'xdata', 'status', 'active'),
			'order' => array('Task.created' => 'desc')
		);
		$data = $this->PCTableGrid->paginate('Task');

		$aUsers = $this->User->find('list', array(
			'fields' => array('id', 'username'),
			'conditions' => array('id' => array_unique(Hash::extract($data, '{n}.Task.user_id')))
		));
		$aUsers[0] = __('System');

		$aTaskOptions = $this->Task->getOptions();
		$aChildTasks = $this->Task->findAllByParentId(Hash::extract($data, '{n}.Task.id'), null, array('id' => 'asc'));
		$aChildTasks = Hash::combine($aChildTasks, '{n}.Task.id', '{n}.Task', '{n}.Task.parent_id');

		$aCached = array();
		$aHangs = array();
		foreach($data as &$row) {
			$id = $row['Task']['id'];
			$aCached[$id] = Cache::read($id, 'tasks');

			$lRun = in_array($row['Task']['status'], array(Task::CREATED, Task::RUN, Task::ABORT));
			if ($aCached[$id]) {
				// TODO: запихивать в $row progressInfo и анализировать ЕГО
				// if (!$lRun || !isset($aCached[$id]['modified']) || (isset($aCached[$id]['modified']) && (time() - $aCached[$id]['modified']) > Task::TIMEOUT)) {
				if (Hash::get($this->Task->getProgressInfo($id), 'hangs')) {
					$aHangs[$id] = true;
				}
				if ($lRun && isset($aChildTasks[$id])) {
					//foreach($aChildTasks[$id] as $childTask)
				}
				//}
			} elseif ($lRun) {
				$aHangs[$id] = false;
			}
		}

		$this->set(compact('data', 'aUsers', 'aTaskOptions', 'aChildTasks', 'aCached', 'aHangs'));
	}
    
    public function task($taskName) {
		$title = $this->Task->getTitle($taskName);
		$task = $this->Task->getActiveTask($taskName);
		$avgTime = $this->Task->avgExecTime($taskName);
		$taskComponent = $this->Components->load('Task'.$taskName);
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
				$this->redirect(array('action' => 'task', $taskName));
			}  elseif ($status == Task::ERROR) {
				$this->Task->close($id);
				$this->setFlash(__('Process execution error! %s', $xdata), 'error');
				$this->redirect(array('action' => 'task', $taskName));
			}

			$task = $this->Task->getFullData($id);
			if (!isset($task['subtask']) && in_array($taskName, array('UploadCounters', 'UploadNewProducts'))) {
				$task['subtask'] = true;
			}
		} else {
			if ($taskComponent->preProcess()) {
				sleep(1); // дать время на запуск таска
				$this->redirect(array('action' => 'task', $taskName));
			}
		}
		$this->set(compact('taskName', 'title', 'task', 'avgTime'));
    }

	public function terminate($task_id) {
		$this->autoRender = false;
		$this->Task->close($task_id, Task::TERMINATED);
		$this->redirect(array('action' => 'index'));
	}
}
