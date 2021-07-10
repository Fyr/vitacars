<?php
App::uses('Model', 'Model');
class AppModel extends Model {
	
	protected $objectType = '', $altDbConfig = false;
	
	public function __construct($id = false, $table = null, $ds = null) {
		$this->_beforeInit();
	    parent::__construct($id, $table, $ds);
	    $this->_afterInit();
	}
	
	protected function _beforeInit() {
	    // Add here behaviours, models etc that will be also loaded while extending child class
		if ($this->altDbConfig) {
			if ($this->getDomain() !== $this->altDbConfig) {
				$this->useDbConfig = $this->altDbConfig;
			}
		}
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

	/**
	 * Fixes bug with HAVING clause: cakePHP BUG - adding "HAVING" class does not work :((((
	 * @param string $type
	 * @param array $params
	 */
	public function find($type = 'first', $params = array()) {
		if (isset($params['having']) && $params['having']) {
			$group = (isset($params['group'])) ? $params['group'] : array();
			$having = array();
			foreach($params['having'] as $key => $val) {
				if (intval($key)) {
					$having[] = $val;
				} else {
					$having[] = $key.$val;
				}
			}
			$params['group'] = implode(',', $group).' HAVING '.implode(' AND ', $having);
		}
		return parent::find($type, $params);
	}

	/*
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
	*/
	public function loadModel($modelClass = null, $id = null) {
		list($plugin, $modelClass) = pluginSplit($modelClass, true);

		$this->{$modelClass} = ClassRegistry::init(array(
			'class' => $plugin . $modelClass, 'alias' => $modelClass, 'id' => $id
		));
		if (!$this->{$modelClass}) {
			throw new MissingModelException($modelClass);
		}

		return $this->{$modelClass};
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
	
	public function dateRange($field, $date1, $date2 = '') {
		// TODO: implement for free date2
		$conditions = array();
		if ($date1) {
			$date1 = date('Y-m-d 00:00:00', strtotime($date1));
			$conditions[$field.' >= '] = $date1;
		}
		if ($date2) {
			$date2 = date('Y-m-d 23:59:59', strtotime($date2));
			$conditions[$field.' <= '] = $date2;
		}
		return $conditions;
	}
	
	public function dateTimeRange($field, $date1, $date2 = '') {
		// TODO: implement for free date2
		$date1 = date('Y-m-d H:i:s', strtotime($date1));
		$date2 = date('Y-m-d H:i:s', strtotime($date2));
		return array($field.' >= ' => $date1, $field.' <= ' => $date2);
	}

	public function getTableName() {
		return $this->getDataSource()->fullTableName($this);
	}

	public function setTableName($table) {
		$this->setSource($table);
	}

	public function trxBegin() {
		$this->getDataSource()->begin();
	}
	
	public function trxCommit() {
		$this->getDataSource()->commit();
	}
	
	public function trxRollback() {
		$this->getDataSource()->rollback();
	}
	
	public function isBot($ip = '') {
		if (!$ip) {
			$ip = $_SERVER['REMOTE_ADDR'];
		}
		$hostname = gethostbyaddr($ip);
		return ($hostname === 'spider-'.str_replace('.', '-', $ip).'.yandex.com') 
			|| ($hostname === 'crawl-'.str_replace('.', '-', $ip).'.googlebot.com');
	}

	public function getDomain() {
		list($domain) = explode('.', Configure::read('domain.url'));
		return $domain;
	}

}
