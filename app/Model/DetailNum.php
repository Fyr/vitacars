<?php
App::uses('AppModel', 'Model');
class DetailNum extends AppModel {

	public function strip($q) {
		return str_replace(array('.', '-', '/', '\\'), '', $q);
	}
}
