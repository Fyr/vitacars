<?
App::uses('AppModel', 'Model');
class PMFormConst extends AppModel {
	public $useTable = 'form_const';
	
	public $validate = array(
		'key' => array(
			'rule' => '/^[A-Z]+[A-Z0-9_]*$/',
			'allowEmpty' => true,
			'message' => 'Неверный формат ключа. Пример: A1, B1, AA1, BB1, CCC'
		),
		'value' => array(
			'rule' => '/^[0-9\.]+$/',
			'required' => true,
			'message' => 'Допускаются только цифры и точка'
		),
		'sort_order' => array(
			'rule' => '/^[0-9]+$/',
			'allowEmpty' => false,
			'message' => 'Введите сортировку'
		)
	);
	
}
