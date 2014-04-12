<?
App::uses('AppModel', 'Model');
class FormField extends AppModel {
	
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
