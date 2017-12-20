<?
App::uses('AppModel', 'Model');
App::uses('FieldTypes', 'Form.Vendor');
App::uses('Logger', 'Model');
class PMFormField extends AppModel {
	public $useTable = 'form_fields';
	
	public $validate = array(
		'key' => array(
			'rule' => '/^[A-Za-z]+[A-Za-z0-9_]*$/',
			'allowEmpty' => true,
			'message' => 'Неверный формат ключа. Пример: a1, B1, AA1, Bb_1, Price, Price_USD1'
		),
		'sort_order' => array(
			'rule' => '/^[0-9]+$/',
			'allowEmpty' => false,
			'message' => 'Введите сортировку'
		)
	);
	
	protected $PMFormData, $Logger;

	protected function _afterInit() {
		$this->Logger = new Logger();
		$this->Logger->init('form_field');
	}

	/*
	public function afterFind($results, $primary = false) {
		foreach($results as &$_row) {
			if (is_array($_row) && isset($_row[$this->alias])) {
				$row = $_row[$this->alias];
				if (isset($row['field_type']) && $row['field_type'] == FieldTypes::FORMULA) {
					if (isset($row['options']) && $row['options']) {
						$_row[$this->alias] = array_merge($_row[$this->alias], $this->unpackFormulaOptions($row['options']));
					}
				}
			}
		}
		return $results;
	}
	*/

	public function afterSave($created, $options = array()) {
		$this->PMFormData = $this->loadModel('Form.PMFormData');
		$sql_field = sprintf(FieldTypes::getSqlTypes($this->data['PMFormField']['field_type']), $this->id);
		$created = $created || (isset($options['forceCreate']) && $options['forceCreate']);
		$sql = 'ALTER TABLE '.$this->PMFormData->getTableName().(($created) ? ' ADD ' : ' MODIFY ').$sql_field;
		if ($created) {
			$this->Logger->write(($created) ? 'INSERT' : 'UPDATE', 'SQL:' . $sql);
			$this->query($sql);
		}
	}
	
	public function beforeDelete($cascade = true) {
		App::uses('PMFormKey', 'Form.Model');
		$this->PMFormKey = new PMFormKey();
		$this->PMFormKey->deleteAll(array('PMFormKey.field_id' => $this->id));

		$this->PMFormData = $this->loadModel('Form.PMFormData');
		$sql = 'ALTER TABLE '.$this->PMFormData->getTableName().' DROP fk_'.$this->id;
		$this->Logger->write('DELETE', 'SQL:'.$sql);
		$this->query($sql);
		return true;
	}

	public function packOptions($data)
	{
		$options = array();
		if ($data['field_type'] == FieldTypes::FORMULA) {
			foreach (array('formula', 'decimals', 'div_float', 'div_int') as $_field) {
				$options[$_field] = Hash::get($data, $_field);
			}
		} else if ($data['field_type'] == FieldTypes::PRICE) {
			foreach (array('formula', 'decimals', 'div_float', 'div_int', 'prefix', 'postfix', 'currency') as $_field) {
				$options['price_' . $_field] = Hash::get($data, 'price_' . $_field);
			}
		}
		return (in_array($data['field_type'], array(FieldTypes::FORMULA, FieldTypes::PRICE))) ? serialize($options) : $data['options'];
	}

	public function unpackOptions($data)
	{
		if (in_array($data['field_type'], array(FieldTypes::FORMULA, FieldTypes::PRICE))) {
			foreach (unserialize($data['options']) as $key => $val) {
				$data[$key] = $val;
			}
		};
		return $data;
	}
	/*
        public function packFormulaOptions($data) {
            extract($data);
            return serialize(compact('formula', 'decimals', 'div_float', 'div_int'));
        }

        public function unpackFormulaOptions($options) {
            return ($options) ? unserialize($options) : array();
        }
    */
	/**
	 * @param $formula - PMFormField record (unpacked)
	 * @param $aData - PMFormData (PMFormField.key) => value
	 * @return string
	 */
	public function calcFormula($row, $aData)
	{
		$formula = (isset($row['price_formula']) && $row['price_formula']) ? $row['price_formula'] : $row['formula'];
		extract($aData); // инициализация ключей для расчета
		$_res = null;
		@eval('$_res = ' . $formula . ';');
		if (isset($row['price_formula']) && $row['price_formula']) {
			return floatval($_res); // приводим 100% к float
		}
		return $this->formatFormula($_res, $row);
	}
    
	public function formatFormula($_res, $formula) {
		if (($formula['decimals'] || $formula['div_float'] || $formula['div_int']) && is_numeric($_res)) {
			return number_format($_res, $formula['decimals'], $formula['div_float'], $formula['div_int']);
		}
		return $_res;
    }
    
    public function getFieldsList($object_type, $object_id) {
    	$aFields = $this->getObjectList($object_type, $object_id, 'PMFormField.sort_order');
    	return Hash::combine($aFields, '{n}.PMFormField.id', '{n}');
    }
}
