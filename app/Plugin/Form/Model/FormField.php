<?
App::uses('AppModel', 'Model');
class FormField extends AppModel {
	public $validate = array(
            'key' => array(
                'rule' => '/^[A-Z]+[0-9]+$/',
                'allowEmpty' => true,
                'message' => 'Неверный формат ключа. Пример: A1, B1, AA1, BB1'
            ),
            'formula' => array(
                'rule' => '/^[A-Z]+[0-9]+ [\+\-\*\/] [0-9]+$/',
                'allowEmpty' => true,
                'message' => 'Неверный формат формулы. Пример: A1 * 100. Допускаются занки + - * /'
            ),
            'sort_order' => array(
                'rule' => '/^[0-9]+$/',
                'allowEmpty' => false,
                'message' => 'Введите сортировку'
            )
        );
        
	public function beforeDelete($cascade = true) {
		App::uses('PMFormValue', 'Form.Model');
		$this->PMFormValue = new PMFormValue();
		$this->PMFormValue->deleteAll(array('PMFormValue.field_id' => $this->id));
		
		App::uses('PMFormKey', 'Form.Model');
		$this->PMFormKey = new PMFormKey();
		$this->PMFormKey->deleteAll(array('PMFormKey.field_id' => $this->id));
		return true;
	}
}
