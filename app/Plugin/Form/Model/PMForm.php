<?
App::uses('AppModel', 'Model');
App::uses('PMFormKey', 'Form.Model');
App::uses('PMFormValue', 'Form.Model');
App::uses('FormField', 'Form.Model');
class PMForm extends AppModel {
	public $useTable = 'forms';
	
	public $hasMany = array(
		'FormKey' => array(
			'classname' => 'Form.PMFormKey',
			'foreignKey' => 'form_id',
			'dependent' => true
		)
	);
	
	public function getFormKeys($formID) {
		$form = $this->findById($formID);
		return Hash::extract($form, 'FormKey.{n}.field_id');
	}
	
	public function bindFields($formID, $aFieldID = array()) {
		$aID = $this->getFormKeys($formID);
		
		$delID = array_values(array_diff($aID, $aFieldID)); // fields to remove from form
		$this->PMFormValue = new PMFormValue();
		$this->PMFormValue->deleteAll(array('PMFormValue.form_id' => $formID, 'PMFormValue.field_id' => $delID)); // remove values from all forms of this type
		$this->FormKey->deleteAll(array('form_id' => $formID, 'field_id' => $delID));
		
		$addID = array_diff($aFieldID, $aID); // fields to add to form
		foreach($addID as $id) {
			$this->FormKey->clear();
			$this->FormKey->save(array('form_id' => $formID, 'field_id' => $id));
		}
	}
	
	public function getFields($object_type, $object_id) {
		$this->FormField = new FormField();
		$form = $this->FormKey->find('all', array(
			'fields' => array('PMForm.*', 'FormField.*'),
			'joins' => array(
				array(
					'table' => $this->useTable,
					'alias' => 'PMForm',
					'conditions' => array('FormKey.form_id = PMForm.id')
				),
				array(
					'table' => $this->FormField->useTable,
					'alias' => 'FormField',
					'conditions' => array('FormKey.field_id = FormField.id')
				)
			),
			'conditions' => array('PMForm.object_type' => $object_type, 'PMForm.object_id' => $object_id)
		));
		// fdebug($form);
		return $form;
	}
}
