<?
App::uses('AppModel', 'Model');
App::uses('PMFormField', 'Form.Model');
App::uses('PMFormConst', 'Form.Model');
App::uses('FieldTypes', 'Form.Vendor');
class PMFormData extends AppModel {
	public $useTable = 'form_data';

	protected $fieldsData = array();
	protected $PMFormField, $PMFormConst;

	public function getValues($object_type, $object_id = '') {
		return $this->getObjectList($object_type, $object_id);
	}
	
	protected function _getAllFields() {
		if ($this->fieldsData) {
			return $this->fieldsData;
		}
		foreach($this->data['PMFormData'] as $key => $val) {
			if (strpos($key, 'fk_') !== false) {
				$field_id = str_replace('fk_', '', $key);
				$param = $this->PMFormField->findById($field_id);
				if ($param) {
					$this->fieldsData = $this->PMFormField->getObjectList($param['PMFormField']['object_type'], $param['PMFormField']['object_id']);
					return $this->fieldsData;
				} else {
					throw new Exception('Incorrect key '.$key.' for PMFormDield while saveing PMFormData');
				}
			}
		}
		return array();
	}
	
	/*
	public function beforeSave() {
		
		 * При сохранении параметров формы для корректного вычисления формул 
		 * необходимо, чтобы сабмитились ВСЕ параметры
		 * Либо нужно заменять недостающие параметры нулями или пустыми строками согласно их типу
		 * Это нужно для сохранения пересчитанных формул сразу
		 * А сохранять формы нужно сразу, т.к.:
		 * 1. Мы снимаем нагрузку при выводе данных (не нужен пересчет при выводе)
		 * 2. Если какие-то поля не входят в SELECT * FROM form_data, 
		 *    их не нужно все равно вычитывать для вычисления формул
		 		
		if (isset($this->data['PMFormData']) && is_array($this->data['PMFormData'])) {
			$this->loadModel('Form.PMFormField');
			$aFormFields = $this->_getAllFields();
			$aData = array();
			$aFormula = array();
			foreach($aFormFields as $row) {
				$field_id = $row['PMFormField']['id'];
				if ($row['PMFormField']['field_type'] == FieldTypes::FORMULA) {
					$aFormula['fk_'.$field_id] = $row['PMFormField'];
				}
				if ($row['PMFormField']['key']) {
					$aData[$row['PMFormField']['key']] = Hash::get($this->data, 'PMFormData.fk_'.$field_id);
				}
				if ($row['PMFormField']['field_type'] == FieldTypes::MULTISELECT && is_array($this->data['PMFormData']['fk_'.$field_id])) {
					$this->data['PMFormData']['fk_'.$field_id] = implode(',', $this->data['PMFormData']['fk_'.$field_id]);
				}
			}
			if ($aFormula) {
				foreach($aFormula as $formula) {
					$field_id = $formula['id'];
					$this->data['PMFormData']['fk_'.$field_id] = $this->PMFormField->calcFormula($formula['options'], $aData);
				}
			}
		}
		return true;
	}
	*/
	public function saveData($data, $aFormFields) {
		foreach($aFormFields as $row) {
			$field_id = $row['PMFormField']['id'];
			if ($row['PMFormField']['field_type'] == FieldTypes::MULTISELECT && is_array($data['PMFormData']['fk_'.$field_id])) {
				$data['PMFormData']['fk_'.$field_id] = implode(',', $data['PMFormData']['fk_'.$field_id]);
			}
		}
		if ($this->save($data)) {
			return $this->recalcFormula($this->id, $aFormFields);
		}
		return false;
	}

	public function _recalcFormula($data, $aFormFields, $aConst) {
		$aData = array();
		$aFormula = array();
		foreach($aFormFields as $row) {
			$field_id = $row['PMFormField']['id'];
			if ($row['PMFormField']['field_type'] == FieldTypes::FORMULA) {
				$aFormula['fk_'.$field_id] = $row['PMFormField'];
			}
			if ($row['PMFormField']['key']) {
				$aData[$row['PMFormField']['key']] = Hash::get($data, 'PMFormData.fk_'.$field_id);
			}
		}

		$aData = array_merge($aData, $aConst);

		$_data = array('PMFormData' => array('id' => $data['PMFormData']['id'], 'recalc' => 1));
		$_ret = true;
		if ($aFormula) {
			$this->PMFormField = $this->loadModel('Form.PMFormField');
			foreach($aFormula as $formula) {
				$field_id = $formula['id'];
				$_data['PMFormData']['fk_'.$field_id] = $this->PMFormField->calcFormula($formula['options'], $aData);
			}
			$_ret = $this->save($_data);
		}
		return $_ret;
	}
	
	public function recalcFormula($id, $aFormFields) {
		$data = $this->findById($id);

		$this->PMFormConst = $this->loadModel('Form.PMFormConst');
		$fields = array('key', 'value');
		$conditions = array('PMFormConst.object_type' => 'SubcategoryParam');
		$aConst = $this->PMFormConst->find('list', compact('fields', 'conditions'));

		return $this->_recalcFormula($data, $aFormFields, $aConst);
	}
}
