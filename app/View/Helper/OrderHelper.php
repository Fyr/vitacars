<?php
App::uses('AppHelper', 'View/Helper');
class OrderHelper extends AppHelper {

	public function getUuid($order) {
		return '1000'.Hash::get($order, 'SiteOrder.id');
	}
}
