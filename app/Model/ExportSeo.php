<?php
App::uses('AppModel', 'Model');
class ExportSeo extends AppModel {
	public $useDbConfig = 'agromotors_by';
	public $useTable = 'seo';
	public $alias = 'xSeo';
}
