<?
App::uses('AppModel', 'Model');
class EventType extends AppModel {
	const LOGIN = 1;
	const LOGOUT = 2;
	const LOGIN_FAIL = 3;

	static public function getTypes($id = false) {
		$aTypes = array(
			self::LOGIN => __('Login into system'),
			self::LOGOUT => __('Logout from system'),
			self::LOGIN_FAIL => __('Authorization failed'),
		);
		return ($id) ? Hash::get($aTypes, $id) : $aTypes;
	}
}
