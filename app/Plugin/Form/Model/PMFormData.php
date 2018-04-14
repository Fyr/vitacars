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
	 * @param $aFormFields - список полей (цен), которые надо пересчитать
	 * @param $aPriceData - введенные ранее занчения
	 * @param $aKurs - текущие курсы
	 * @return array - массив пересчитанных данных
	 */
	private function _recalcKurs($aFormFields, $aPriceData, $aKurs, $data)
	{
		$changedPrices = array();
		$this->PriceHistory = $this->loadModel('PriceHistory');
		$this->FormPrice = $this->loadModel('FormPrice');
		foreach ($aFormFields as $row) {
			$fk_id = $row['id'];

			$price_data = $aPriceData[$fk_id];
			$old_price = $data['PMFormData']['fk_' . $fk_id];

			$_kursKey = $price_data['currency_from'] . '_' . $row['price_currency'];
			$kurs = (isset($aKurs[$_kursKey]) && $aPriceData[$fk_id]['currency_from']) ? $aKurs[$_kursKey] : 1;
			$new_price = $price_data['koeff'] * $price_data['price'] * $kurs;

			// округляем до 2х знаков: почему-то есть баг с округлением до 4 знаков и в form_price - 2 знака
			$old_price = round($old_price, 2);
			$new_price = round($new_price, 2);
			if ($old_price != $new_price) { // цена изменилась при пересчете
				// достаточно сохранить новый курс
				$this->FormPrice->clear();
				$this->FormPrice->save(array('id' => $price_data['id'], 'kurs' => $kurs));

				// сохраняем историю изменения цены
				$product_id = $price_data['product_id'];
				$this->PriceHistory->clear();
				$this->PriceHistory->save(compact('product_id', 'fk_id', 'old_price', 'new_price'));

				$changedPrices['fk_' . $fk_id] = $new_price;
			}
		}
		return $changedPrices;
	}

	/**
	 * Пересчет всех формул по одной записи
	 * @param $data - PMFormData
	 * @param $aFormFields - PMFormFields
	 * @param $aConst - PMFormConst
	 * @param array $aPriceData - FormPrice
	 * @return bool|mixed
	 */
	private function _recalcFormula($data, $aFormFields, $aConst, $aPriceData = array())
	{
		$this->PMFormField = $this->loadModel('Form.PMFormField');

		/*
		    Правило перебивания цен такое:
		Если цена рассчитывается по формуле, то ее конечное значение может быть перебито введенным
		(возможно с другой валютой, тогда рассчитываем ее по курсу "валюта" -> "родная (конечная)" валюта
		Это значит что мы сначала должны рассчитать все цены, а потом поменять их значение, если они были перебиты

			Алг-тм пересчета по курсу такой:
		1. Обрабатываем цены, которые должны быть введены.
		   Если эта цена была перебита с другой валютой и курс изменился (оптимизация)
		   то пересчитываем по соотв. курсу и записываем в конечные данные
		2. Вычисляем все формулы
		   Нельзя исключать цены из вычислений, т.к. на базе их могут быть другие вычисления
		3. Перебиваем цены с формулой, которые уже были перебиты
		   Если у цены другая валюта, пересчитываем по курсу
		   Цену записываем в конечные данные
		*/

		foreach ($aFormFields as &$_row) {
			// т.к. формула хранится в сериализованном виде - распаковываем
			$_row = $this->PMFormField->unpackOptions($_row['PMFormField']);
			if (isset($_row['formula']) && !$_row['formula']) {
				unset($_row['formula']);
			}
			if (isset($_row['price_formula']) && !$_row['price_formula']) {
				unset($_row['price_formula']);
			}
		}

		$aFormula = array(); // формулы для пересчета
		$aPrices = array(); // цены вводимые вручную, которые надо пересчитать
		$aCalcPrices = array(); // рассчитываемые цены, которые надо перебить
		$aKurs = array(); // текущие курсы
		$dataKeys = array(); // переменные для пересчета формул
		foreach($aFormFields as $row) {
			$fk_id = $row['id'];
			if ($row['field_type'] == FieldTypes::PRICE) {
				if (isset($aPriceData[$fk_id])) { // цена введена и у нее есть валюта пересчета
					if (isset($row['price_formula'])) { // цену нужно рассчитывать по формуле
						// добавляем в пересчет по курсу после вычислений
						$aCalcPrices[] = $row;
					} else {
						// добавляем в пересчет по курсу перед вычислениями
						$aPrices[] = $row;
					}
				}
			}
			if (isset($row['price_formula']) || isset($row['formula'])) {
				$aFormula['fk_' . $fk_id] = $row; // добавляем в формулы на пересчет
			}
		}

		// формируем массив констант (переменных для формул) и массив курсов
		foreach ($aConst as $row) {
			if ($key = $row['PMFormConst']['key']) {
				$dataKeys[$key] = $row['PMFormConst']['value'];
			}
			if ($row['PMFormConst']['is_price_kurs']) {
				$key = $row['PMFormConst']['price_kurs_from'] . '_' . $row['PMFormConst']['price_kurs_to'];
				$aKurs[$key] = floatval($row['PMFormConst']['value']);
			}
		}

		// 1. Обрабатываем цены, которые должны быть введены.
		if ($aPrices) {
			$aNewPrices = $this->_recalcKurs($aPrices, $aPriceData, $aKurs, $data);
			$data['PMFormData'] = array_merge($data['PMFormData'], $aNewPrices);
		}

		// 2. Вычисляем все формулы
		if ($aFormula) { // если есть формулы для пересчета - пересчитываем
			// сливаем все данные с ключами в одну кучу для пересчета
			foreach ($aFormFields as $row) {
				$fk_id = $row['id'];
				if ($row['key']) {
					$dataKeys[$row['key']] = $data['PMFormData']['fk_' . $fk_id];
				}
			}

			foreach ($aFormula as $row) {
				$data['PMFormData']['fk_' . $row['id']] = $this->PMFormField->calcFormula($row, $dataKeys);
			}
			$data['PMFormData']['recalc'] = 1; // признак того, что формула пересчитана
		}

		if ($aCalcPrices) {
			$aNewPrices = $this->_recalcKurs($aCalcPrices, $aPriceData, $aKurs, $data);
			$data['PMFormData'] = array_merge($data['PMFormData'], $aNewPrices);
		}

		// сохраняем запись по продукту, если что-то поменялось
		$_ret = true;
		if ($aPrices || $aFormula || $aCalcPrices) {
			$_ret = $this->save($data);
		}
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
			$aConst = $this->PMFormConst->find('all');
		}

		if (!$aPriceData) {
			$this->FormPrice = $this->loadModel('FormPrice');
			$aPriceData = $this->FormPrice->getProductPrices($data['PMFormData']['object_id']);
		}

		return $this->_recalcFormula($data, $aFormFields, $aConst, $aPriceData);
	}
}
