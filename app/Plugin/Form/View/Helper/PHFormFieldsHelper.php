<?php
/**
 * Wrapper for standart Form helper
 * Customizes default HTML inputs
 */
App::uses('FieldTypes', 'Form.Vendor');
App::uses('FormHelper', 'View/Helper');
App::uses('PHFormHelper', 'Form.View/Helper');
class PHFormFieldsHelper extends AppHelper {
	public $helpers = array('Form.PHForm');
	
	private function _inputName($i, $key) {
		return sprintf('data[PMFormValue][%d][%s]', $i, $key);
	}
	
	private function _inputID($i, $key) {
		return 'PMFormValue'.ucfirst($key).'_'.$i;
	}
	
	private function _options($i, $key) {
		return array(
			'id' => $this->_inputID($i, $key), // id must be unique for HTML doc
			'name' => $this->_inputName($i, $key),
		);
	}
	
	private function _inputOptions($field) {
		$aDefaultOptions = array(
			FieldTypes::STRING => array('class' => 'input-large', 'type' => 'text'),
			FieldTypes::INT => array('class' => 'input-medium', 'type' => 'text')
		);
		$key = ($field['key']) ? $field['key'] : 'value';
		
		$options = array('label' => array('text' => $field['label'], 'class' => 'control-label'));
		if (isset($aDefaultOptions[$field['field_type']])) {
			$options = array_merge($aDefaultOptions[$field['field_type']], $options);
		}
		
		return $options; // array_merge($this->_options($i, $key), $options);
	}
	
	public function render($form, $values) {
		$html = '';
		$_values = array_combine(
			Hash::extract($values, '{n}.PMFormValue.field_id'), 
			Hash::extract($values, '{n}.PMFormValue.value')
		);
		$_ids = array_combine(
			Hash::extract($values, '{n}.PMFormValue.field_id'), 
			Hash::extract($values, '{n}.PMFormValue.id')
		);
		foreach($form as $i => $row) {
			$field = $row['FormField'];
			$value = Hash::get($_values, $field['id']);
			$html.= $this->PHForm->input('PMFormValue.value', array_merge(
				$this->_options($i, 'value'), $this->_inputOptions($field), array('value' => $value)
			));
			if ($value) {
				$html.= $this->PHForm->hidden('PMFormValue.id', array_merge(
					$this->_options($i, 'id'), array('value' => Hash::get($_ids, $field['id']))
				));
			}
			$html.= $this->PHForm->hidden('PMFormValue.field_id', array_merge(
				$this->_options($i, 'field_id'), array('value' => $field['id'])
			));
		}
		return $html;
	}
}