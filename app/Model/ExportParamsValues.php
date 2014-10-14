<?php
App::uses('AppModel', 'Model');
class ExportParamsValues extends AppModel {
	public $useDbConfig = 'agromotors_by';
	public $useTable = 'params_values';
	public $alias = 'xParamsValues';
}
