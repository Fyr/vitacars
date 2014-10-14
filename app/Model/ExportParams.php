<?php
App::uses('AppModel', 'Model');
class ExportParams extends AppModel {
	public $useDbConfig = 'agromotors_by';
	public $useTable = 'params';
	public $alias = 'xParams';
}
