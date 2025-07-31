<?php
App::uses('AdminController', 'Controller');
App::uses('Task', 'Model');
App::uses('User', 'Model');
App::uses('PHTime', 'Core.View/Helper');
class AdminTasksController extends AdminController {
    public $name = 'AdminTasks';
    public $uses = array('Task', 'User');
	public $helpers = array('Core.PHTime');

	private function checkLoadCountersRights($task) {
		if ($this->isAdmin() || (AuthComponent::user('load_counters') && $task === 'UploadCounters')) {
			return true;
		}

		return false;
	}

    public function beforeFilter() {
		if ($this->request->action == 'task' && Hash::get($this->request->params, 'pass.0') == 'UploadCounters') {
			if (!($this->isAdmin() || AuthComponent::user('load_counters'))) {
				$this->redirect(array('controller' => 'Admin', 'action' => 'index'));
				return;
			}
		} elseif (!$this->isAdmin()) {
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
			'order' => array('Task.id' => 'desc')
		);
		$data = $this->PCTableGrid->paginate('Task');

		$aUsers = $this->User->find('list', array(
			'fields' => array('id', 'username'),
			'conditions' => array('id' => array_unique(Hash::extract($data, '{n}.Task.user_id')))
		));
		$aUsers[0] = __('System');

		$aChildTasks = $this->Task->findAllByParentId(Hash::extract($data, '{n}.Task.id'), null, array('id' => 'asc'));
		$aChildTasks = Hash::combine($aChildTasks, '{n}.Task.id', '{n}.Task', '{n}.Task.parent_id');

		$aCached = array();
		$aHangs = array();
		$aRunStatus = array(Task::CREATED, Task::RUN, Task::ABORT);
		foreach($data as &$row) {
			$id = $row['Task']['id'];
			$aCached[$id] = Cache::read($id, 'tasks');
			if ($aCached[$id]) {
				// если есть кэш - получаем инфу о зависании задачи по таймауту
				if (Hash::get($this->Task->getProgressInfo($id), 'hangs')) {
					$aHangs[$id] = true;
				}
			} elseif (in_array($row['Task']['status'], $aRunStatus)) {
				// если задача по статусу выполняется, а кэша нет - это тоже не нормально
				$aHangs[$id] = false;
			}
			if (isset($aChildTasks[$id])) {
				foreach ($aChildTasks[$id] as $_id => $task) {
					$aCached[$_id] = Cache::read($_id, 'tasks');
					if (!$aCached[$_id] && in_array($row['Task']['status'], $aRunStatus) && in_array($task['status'], $aRunStatus)) {
						// если ПОДзадача по статусу выполняется, а кэша у нее нет - это не нормально для осн.задачи
						// бывают случаи когда подзадача выполняется (exception), а онс.задача имеет статус ERROR - добавил проверку на статус осн.задачи
						$aHangs[$id] = false;
					}
				}
			}
		}

		$aTaskOptions = $this->Task->getOptions(false);
		$aMainTaskOptions = $this->Task->getOptions(true);
		$this->set(compact('data', 'aUsers', 'aTaskOptions', 'aMainTaskOptions', 'aChildTasks', 'aCached', 'aHangs'));
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
