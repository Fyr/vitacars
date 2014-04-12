<?php
App::uses('Helper', 'View');
class AppHelper extends Helper {

	public function viewVar($path) {
		return Hash::get($this->_View->viewVars, $path);
	}
}
