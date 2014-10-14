<?php
App::uses('AppModel', 'Model');
class ExportParamsObjects extends AppModel {
	public $useDbConfig = 'agromotors_by';
	public $useTable = 'params_objects';
	public $alias = 'xParamsObjects';
}
