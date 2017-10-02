<?php
App::uses('AppModel', 'Model');
App::uses('EventType', 'Model');
class UserLog extends AppModel {

	public function getLoginTime($user_id) {
		$conditions = array(
			'user_id' => $user_id,
			'event_type' => EventType::LOGIN
		);
		$order = array('created' => 'desc');
		$row = $this->find('first', compact('conditions', 'order'));
		return $row['UserLog']['created'];
	}
}
