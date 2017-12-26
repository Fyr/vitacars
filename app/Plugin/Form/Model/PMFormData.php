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
	/*
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
	*/

	/**
	 * Пересчет всех формул по записи
	 * @param $data - PMFormData
	 * @param $aFormFields - PMFormField
	 * @param $aConst - PMFormConst
	 * @param array $aPriceData - FormPrice
	 * @return bool|mixed
	 */
	private function _recalcFormula($data, $aFormFields, $aConst, $aPriceData = array())
	{
		$this->PMFormField = $this->loadModel('Form.PMFormField');
		$aData = array();
		$aFormula = array();
		$fkPrices = array();

		// пересчитываем цены по курсу в исходные для формул ячейки
		if ($aPriceData) {
			foreach ($aPriceData as $row) {
				$data['PMFormData']['fk_' . $row['fk_id']] = $row['price'] * $row['kurs'] * $row['koeff'];
				$fkPrices[] = $row['fk_id']; // формируем список полей, цена у которых была перебита вручную (формулу не надо пересчитывать)
			}
		}

		foreach($aFormFields as $row) {
			$row = $this->PMFormField->unpackOptions($row['PMFormField']);
			if ($row['field_type'] == FieldTypes::PRICE && !$row['price_formula'] && !isset($aPriceData[$row['id']])) {
				fdebug($row['id'], 'tmp4.log');
				$data['PMFormData']['fk_' . $row['id']] = 0;
			}

			if ($row['field_type'] == FieldTypes::FORMULA
				|| ($row['field_type'] == FieldTypes::PRICE && $row['price_formula'] && !in_array($row['id'], $fkPrices))
			) {
				$aFormula['fk_' . $row['id']] = $row;
			}
			if ($row['key']) {
				$aData[$row['key']] = Hash::get($data, 'PMFormData.fk_' . $row['id']);
			}
		}

		$aData = array_merge($aData, $aConst);

		// $_data = array('PMFormData' => array('id' => $data['PMFormData']['id'], 'recalc' => 1));
		$_ret = true;
		if ($aFormula) {
			$data['PMFormData']['recalc'] = 1;
			foreach ($aFormula as $row) {
				$data['PMFormData']['fk_' . $row['id']] = $this->PMFormField->calcFormula($row, $aData);
			}
			$_ret = $this->save($data);
		}
		return $_ret;
	}

	public function recalcFormula($id, $aFormFields = array(), $aConst = array(), $aPriceData = array())
	{
		$data = $this->findById($id);

		if (!$aFormFields) {
			$this->PMFormField = $this->loadModel('Form.PMFormField');
			$aFormFields = $this->PMFormField->getObjectList('SubcategoryParam', '', 'PMFormField.sort_order');
		}

		if (!$aConst) {
			$this->PMFormConst = $this->loadModel('Form.PMFormConst');
			$aConst = $this->PMFormConst->getData();
		}

		if (!$aPriceData) {
			$this->FormPrice = $this->loadModel('FormPrice');
			$aPriceData = $this->FormPrice->getProductPrices($data['PMFormData']['object_id']);
		}

		return $this->_recalcFormula($data, $aFormFields, $aConst, $aPriceData);
	}
}
