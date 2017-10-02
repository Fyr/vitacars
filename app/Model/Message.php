<?php
App::uses('AppModel', 'Model');
class Message extends AppModel {

    public function getRecentMessages($user_id, $datetime) {
        $conditions = array(
            'user_id' => $user_id,
            'created >' => $datetime,
            'active' => true
        );
        return $this->find('all', compact('conditions'));
    }
}
