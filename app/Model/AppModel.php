<?php
App::uses('Model', 'Model');
class AppModel extends Model {
	
	protected $objectType = '';
	
	public function __construct($id = false, $table = null, $ds = null) {
		$this->_beforeInit();
	    parent::__construct($id, $table, $ds);
	    $this->_afterInit();
	}
	
	protected function _beforeInit() {
	    // Add here behaviours, models etc that will be also loaded while extending child class
	}

	protected function _afterInit() {
	    // after construct actions here
	}
	
	/**
	 * Auto-add object type in find conditions
	 *
	 * @param array $query
	 * @return array
	 */
	public function beforeFind($query) {
		if ($this->objectType) {
			$query['conditions'][$this->objectType.'.object_type'] = $this->objectType;
		}
		return $query;
	}
	
	public function loadModel($models) {
		if (!is_array($models)) {
			$models = array($models);
		}
		foreach($models as $model) {
			App::import('Model', $model);
			if (strpos($model, '.') !== false) {
				list($plugin, $model) = explode('.', $model);
			}
			$this->$model = new $model();
		}
	}

	
	private function _getObjectConditions($objectType = '', $objectID = '') {
		$conditions = array();
		if ($objectType) {
			$conditions[$this->alias.'.object_type'] = $objectType;
		}
		if ($objectID) {
			$conditions[$this->alias.'.object_id'] = $objectID;
		}
		return compact('conditions');
	}
	
	public function getOptions($objectType = '', $objectID = '') {
		return $this->find('list', $this->_getObjectConditions($objectType, $objectID));
	}
	
	public function getObject($objectType = '', $objectID = '') {
		return $this->find('first', $this->_getObjectConditions($objectType, $objectID));
	}
	
	public function getObjectList($objectType = '', $objectID = '', $order = array()) {
		$conditions = array_values($this->_getObjectConditions($objectType, $objectID));
		return $this->find('all', compact('conditions', 'order'));
	}
	
	public function getTableName() {
		return $this->getDataSource()->fullTableName($this);
	}
	
	public function dateRange($field, $date1, $date2 = '') {
		// TODO: implement for free date2
		$date1 = date('Y-m-d 00:00:00', strtotime($date1));
		$date2 = date('Y-m-d 23:59:59', strtotime($date2));
		return array($field.' >= ' => $date1, $field.' <= ' => $date2);
	}
	
	public function dateTimeRange($field, $date1, $date2 = '') {
		// TODO: implement for free date2
		$date1 = date('Y-m-d H:i:s', strtotime($date1));
		$date2 = date('Y-m-d H:i:s', strtotime($date2));
		return array($field.' >= ' => $date1, $field.' <= ' => $date2);
	}
}
