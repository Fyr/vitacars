<?php
App::uses('AppModel', 'Model');
class System extends AppModel {
	public $useTable = false;

	public function getMySQLServerInfo() {
		$status = $this->query('SHOW STATUS');
		$aUsedVars = array(
			'Aborted_connects',
			'Bytes_received',
			'Bytes_sent',
			'Connections',
			'Queries',
			'Threads_connected',
			'Uptime'
		);
		foreach($status as $i => $row) {
			if (in_array($row['STATUS']['Variable_name'], $aUsedVars)) {
				$status[$row['STATUS']['Variable_name']] = $row['STATUS']['Value'];
			}
			unset($status[$i]);
		}

		// надо вычислить queries per sec - аналог загрузки ЦП
		return $status;
	}

	public function getCPUUsage() {

	}
}
