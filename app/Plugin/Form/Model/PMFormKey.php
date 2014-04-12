<?
App::uses('AppModel', 'Model');
class PMFormKey extends AppModel {
	public $useTable = 'form_keys';
	
	public $hasOne = array(
		'FormField' => array(
			'foreignKey' => 'field_id'
		)
	);
}
