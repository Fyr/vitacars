<?php
App::uses('AppModel', 'Model');
class Agent extends AppModel {

	public function getOptions($lAgent2 = 0) {
		$conditions = array('active' => 1);
		if ($lAgent2 == 1) {
			$conditions['agent'] = 1;
		} elseif ($lAgent2 == 2) {
			$conditions['agent2'] = 1;
		}
		$order = 'title';
		return $this->find('list', compact('conditions', 'order'));
	}
}
