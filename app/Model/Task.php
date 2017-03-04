<?
App::uses('AppModel', 'Model');
class Task extends AppModel {
	/*
	const CREATED = 0;
	const RUN = 1;
	const DONE = 2;
	const ABORT = 3;
	const ABORTED = 4;
	const ERROR = 5;
	const CLOSED = 6;
	*/
	const CREATED = 'CREATED';
	const RUN = 'RUN';
	const DONE = 'DONE';
	const ABORT = 'ABORT';
	const ABORTED = 'ABORTED';
	const ERROR = 'ERROR';
	const CLOSED = 'CLOSED';

	public function getTitle($task_name) {
		$aTitle = array(
			'UploadCounters' => __('Upload counters'),
			'UploadCounters_readCsv' => __('Process CSV file'),
			'UploadCounters_initCounters' => __('UploadCounters_initCounters'),
			'UploadCounters_updateCounters' => __('UploadCounters_updateCounters'),
			'UploadCounters_updateRest' => __('UploadCounters_updateRest'),
			'UploadNewProducts' => __('Upload new products'),
			'UploadNewProducts_readCsv' => __('Process CSV file'),
			'UploadCounters_checkProducts' => __('Check products data'),
			'UploadCounters_createProducts' => __('Create products and needed data'),
			'TestProgress' => 'Test progress task executing...',
			'TestProgress_task1' => '1. Test task 1 executing...',
			'TestProgress_task2' => '2. Test task 2 executing...',
			'TestProgress_task3' => '3. Test task 3 executing...',
		);
		return Hash::get($aTitle, $task_name);
	}

	public function add($user_id, $task_name, $aParams = array(), $parent_id = 0) {
		$this->clear();
		$params = serialize($aParams);
		$active = 1;
		$status = self::CREATED;
		$this->save(compact('task_name', 'status', 'params', 'parent_id', 'user_id', 'active'));

		// кешируем только необходимую инфу
		$task = array(
			'id' => $this->id,
			'task_name' => $this->getTitle($task_name),
			'created' => time(),
			'status' => self::CREATED,
			'progress' => 0, 'total' => 0
		);
		Cache::write($this->id, $task, 'tasks');
		return $this->id;
	}

	public function runBkg($task_id) {
		if (TEST_ENV) {
			fdebug('../Console/cake.bat BkgService execTask '.$task_id."\r\n", 'run.bat', false);
		} else {
			system("../Console/cake BkgService execTask {$task_id} < /dev/null > task.log &");
		}
	}

	public function getTask($task, $status = null) {
		$conditions = (is_numeric($task)) ? array('id' => $task) :  array('task_name' => $task);
		if ($status) {
			$conditions['status'] = $status;
		}
		return $this->find('first', compact('conditions'));
	}

	public function getActiveTask($task) {
		$conditions = (is_numeric($task)) ? array('id' => $task) :  array('task_name' => $task);
		$conditions['active'] = 1;
		return $this->find('first', compact('conditions'));
	}

	public function setData($id, $key, $val) {
		$task = Cache::read($id, 'tasks');
		$task[$key] = $val;
		Cache::write($id, $task, 'tasks');
	}

	public function getData($id, $key = '') {
		$task = Cache::read($id, 'tasks');
		return ($key) ? Hash::get($task, $key) : $task;
	}

	public function saveStatus($id) {
		$task = $this->getData($id);

		$data = array(
			'id' => $id,
			'status' => $task['status'],
			'progress' => floor($task['progress']),
			'total' => $task['total'],
			'xdata' => (isset($task['xdata']) && $task['xdata']) ? serialize($task['xdata']) : ''
		);
		if ($task['status'] == self::DONE) {
			$data['exec_time'] = time() - Hash::get($task, 'created');
		}
		$this->clear();
		$this->save($data);
	}

	public function setStatus($id, $status) {
		$this->setData($id, 'status', $status);
		$this->saveStatus($id);
	}

	public function getStatus($id) {
		return $this->getData($id, 'status');
	}

	public function setProgress($id, $progress, $total = 0) {
		$this->setData($id, 'progress', $progress);
		if ($total) {
			$this->setData($id, 'total', $total);
		}
	}

	public function getProgress($id) {
		return $this->getData($id, 'progress');
	}

	public function getProgressInfo($id) {
		$task = $this->getData($id);
		$progress = Hash::get($task, 'progress');
		$total = Hash::get($task, 'total');
		$percent = ($total > 0) ? round($progress * 100 / $total) : 0;

		$exec_time = time() - Hash::get($task, 'created');
		$avg_speed = ($exec_time > 0) ? $progress / $exec_time : 0;
		$time_finish = ($progress > 0) ? ($total - $progress) * $exec_time / $progress : 0;
		/*
		if ($subtask_id = $this->getData($id, 'subtask_id')) {
			$subtask = $this->getProgressInfo($subtask_id);
			$_time_finish = $subtask['time_finish'];
			$time_finish = ($time_finish < $_time_finish) ? $_time_finish : $time_finish;
		}
		*/
		$progress = floor($progress);
		$avg_speed = round($avg_speed, 2);
		$time_finish = round($time_finish);
		return compact('progress', 'total', 'percent', 'exec_time', 'avg_speed', 'time_finish');
	}

	public function getFullData($id) {
		$task = $this->getData($id);
		$task['progress'] = $this->getProgressInfo($id);
		$subtask_id = $this->getData($id, 'subtask_id');
		if ($subtask_id) {
			$subtask = $this->getData($subtask_id);
			$subtask['progress'] = $this->getProgressInfo($subtask_id);
			$task['subtask'] = $subtask;
		}
		return $task;
	}

	public function close($id) {
		$this->clear();
		$this->save(array('id' => $id, 'active' => 0));
		if (Cache::read($id, 'tasks')) { // safe deleting cache
			Cache::delete($id, 'tasks');
		}

		$this->closeSubtasks($id);
	}

	public function closeSubtasks($id) {
		$subtasks = $this->find('all', array('conditions' => array('parent_id' => $id)));
		if ($subtasks) {
			$ids = Hash::extract($subtasks, '{n}.Task.id');
			foreach($ids as $id) {
				$this->close($id);
			}
		}
	}

	public function avgExecTime($task_name) {
		$tasks = $this->getTableName();
		$DONE = self::DONE;
		$res = $this->query("SELECT AVG(exec_time) AS avg_exe_time FROM {$tasks} WHERE task_name = '{$task_name}' AND parent_id = 0 AND status = '{$DONE}'");
		return Hash::get($res, '0.0.avg_exe_time');
	}

}
