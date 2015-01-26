<?php
App::uses('AppController', 'Controller');
App::uses('PAjaxController', 'Core.Controller');
App::uses('FieldTypes', 'Form.Vendor');

class AdminExportAjaxController extends PAjaxController {
	public $name = 'AdminExportAjax';
	public $components = array('Core.PCAuth');
	public $uses = array(
    	'Category', 'Subcategory', 'Article.Article', 'Media.Media', 'SiteArticle', 'Form.FormField', 'Form.PMFormData', 'Seo.Seo',
    	'ExportArticle', 'ExportMedia', 'ExportParams', 'ExportParamsObjects', 'ExportParamsValues', 'ExportSeo'
    );
    
    private $aTypes = array(
    	'Category' => 'category', 
    	'Subcategory' => 'subcategory', 
    	'Brand' => 'brands', 
    	'Product' => 'products'
    );
    
    private $aParamTypes = array(
    	FieldTypes::STRING => 4,
		FieldTypes::INT => 2,
		FieldTypes::FLOAT => 3,
		FieldTypes::DATE => 6,
		FieldTypes::DATETIME => 7,
		FieldTypes::TEXTAREA => 5,
		FieldTypes::CHECKBOX => 1,
		FieldTypes::SELECT => 8,
		// FieldTypes::EMAIL => 4,
		// FieldTypes::URL => 4,
		// FieldTypes::UPLOAD_FILE => 4,
		// FieldTypes::EDITOR => 5,
		FieldTypes::MULTISELECT => 8,
		FieldTypes::FORMULA => 4
    );
    
    private $xArticle, $xMedia, $xParam, $xParamObject, $xParamValue, $xSeo;
    private $PHMedia, $aDataSource = array('agromotors_by', 'agromotors_ru');
    
    const LIMIT = 10;
    
    public function beforeFilter() {
		parent::beforeFilter();
		
		$this->xArticle = $this->ExportArticle;
		$this->xMedia = $this->ExportMedia;
		$this->xParam = $this->ExportParams;
		$this->xParamObject = $this->ExportParamsObjects;
		$this->xParamValue = $this->ExportParamsValues;
		$this->xSeo = $this->ExportSeo;
		/*
		$dataSource = $this->request->data('dataSource');
		foreach(array('xArticle', 'xMedia', 'xParam', 'xParamObject', 'xParamValue', 'xSeo') as $model) {
    		$this->{$model}->setDataSource($dataSource);
    		$db = ConnectionManager::getDataSource($dataSource);
    		$this->{$model}->schemaName = $db->getSchemaName();
    		$this->{$model}->clear(); // на всякий случай чистим поля модели
    	}
    	$this->xMedia->setBasePath(($dataSource == 'agromotors_ru') ? PATH_FILES_UPLOAD_RU : PATH_FILES_UPLOAD_BY);
    	*/
	}
	
	private function setDataSource($dataSource, $models) {
		if (is_string($models)) {
			$models = array($models);
		}
		foreach($models as $model) {
    		$this->{$model}->setDataSource($dataSource);
    		$db = ConnectionManager::getDataSource($dataSource);
    		$this->{$model}->schemaName = $db->getSchemaName();
    		$this->{$model}->clear(); // на всякий случай чистим поля модели
    	}
    	if (in_array('xMedia', $models)) {
    		$this->xMedia->setBasePath(($dataSource == 'agromotors_ru') ? PATH_FILES_UPLOAD_RU : PATH_FILES_UPLOAD_BY);
    	}
	}
	
	public function clearMedia() {
		fdebug('', 'export.log', false);
		try {
			fdebug('Удаление предыдущих media-данных...', 'export.log');
			
			foreach($this->aDataSource as $dataSource) {
	    		$this->setDataSource($dataSource, 'xMedia');
	    		
	    		$this->xMedia->deleteAll(array('object_type' => 'Article'), true, true);
	    	}
	    	fdebug('ОК'."\r\n", 'export.log');
	    	
			$this->setResponse(true);
		} catch (Exception $e) {
			$this->setError($e->getMessage());
		}
	}
	
	public function initExportArticles() {
		try {
			fdebug('Подготовка для экспорта статей...', 'export.log');
			
			foreach($this->aDataSource as $dataSource) {
	    		$this->setDataSource($dataSource, 'xArticle');
	    		
				$this->xArticle->deleteAll(true);
			}
			
			$conditions = array('object_type' => array_keys($this->aTypes));
			$count = $this->SiteArticle->find('count', compact('conditions'));
			fdebug('ОК'."\r\n", 'export.log');
			
			$this->setResponse(array('page_count' => ceil($count / self::LIMIT)));
		} catch (Exception $e) {
			$this->setError($e->getMessage());
		}
	}
	
	public function exportArticles() {
		try {
			$page = $this->request->data('page');
			$total = $this->request->data('total');
			fdebug(sprintf('Экспорт статей %d/%d...', $page, $total), 'export.log');
			$conditions = array('object_type' => array_keys($this->aTypes));
	    	$limit = self::LIMIT;
	    	$order = 'SiteArticle.id'; // !!!  БД не может по другому те же ID записать
	    	$articles = $this->SiteArticle->find('all', compact('conditions', 'page', 'limit', 'order'));
	    	
	    	foreach($this->aDataSource as $dataSource) {
	    		$this->setDataSource($dataSource, array('xArticle', 'xMedia'));
	    		
				foreach($articles as $article) {
					$this->_addArticle($article['SiteArticle'], $article['Media']);
					$object_type = $article['SiteArticle']['object_type'];
				}
	    	}
	    	fdebug('ОК'."\r\n", 'export.log');
	    	$this->setResponse(true);
		} catch (Exception $e) {
			$this->setError($e->getMessage());
		}
	}
	
	private function _addArticle($article, $aMedia) {
		$object_type = $article['object_type'];
		$body = $article['body'];
		$article['object_type'] = $this->aTypes[$object_type];
		$this->xArticle->save($article); // пишем ID статьи 1:1
		
		foreach($aMedia as $media) {
			$xFile = $this->Media->getPHMedia()->getFileName($object_type, $media['id'], 'noresize', $media['file'].$media['ext']);
				
			$media['object_type'] = 'Article';
			$media['object_id'] = $article['id'];
			$media['real_name'] = $xFile;
			$old_media_id = $media['id'];
			unset($media['id']);
			$new_media_id = $this->xMedia->uploadMedia($media);
			
			// update new media in article's body
			$body = str_replace('/media/router/index/'.strtolower($object_type).'/'.$old_media_id, '/media/router/index/article/'.$new_media_id, $body);
		}
		$this->xArticle->save(array('id' => $article['id'], 'body' => $body));
	}
	
	public function exportParams() {
		try {
			fdebug('Экспорт данных о тех.параметрах...', 'export.log');
			
			// переносим параметры
	    	$aSubcategories = $this->Subcategory->find('all');
	    	$aParams = $this->FormField->find('all', array(
	    		'conditions' => array('exported' => 1),
	    		'order' => 'FormField.id'
	    	));
			
			foreach($this->aDataSource as $dataSource) {
	    		$this->setDataSource($dataSource, array('xParam', 'xParamObject'));
				
				$this->xParam->deleteAll(true);
				$this->xParamObject->deleteAll(true);
				
		    	foreach($aParams as $param) {
		    		$data = $param['FormField'];
		    		
		    		$data['object_type'] = 'ProductParam';
		    		$data['title'] = $data['label'];
		    		$data['param_type'] = $this->aParamTypes[$data['field_type']];
		    		$this->xParam->save($data); // сохраняем ID параметра
		    		
		    		// Привязываем параметры ко всем подкатегориям
			    	foreach($aSubcategories as $article) {
			    		$this->xParamObject->clear();
			    		$this->xParamObject->save(array(
			    			'object_type' => 'ProductParam',
			    			'object_id' => $article['Subcategory']['id'],
			    			'param_id' => $data['id']
			    		));
			    	}
		    	}
			}
	    	fdebug('ОК'."\r\n", 'export.log');
	    	$this->setResponse(true);
		} catch (Exception $e) {
			$this->setError($e->getMessage());
		}
	}
	
	public function initExportParamValues() {
		try {
			fdebug('Подготовка для экспорта значений тех.параметров...', 'export.log');
			foreach($this->aDataSource as $dataSource) {
	    		$this->setDataSource($dataSource, 'xParamValue');
	    		
				$this->xParamValue->deleteAll(true);
			}
			
	    	$conditions = array('object_type' => 'ProductParam');
	    	$count = $this->PMFormData->find('count', compact('conditions'));
	    	
	    	$aParams = $this->FormField->find('all', array(
	    		'conditions' => array('exported' => 1),
	    		'order' => 'FormField.id'
	    	));
	    	$fields = array();
	    	foreach($aParams as $param) {
	    		$data = $param['FormField'];
	    		$fields[] = 'PMFormData.fk_'.$data['id'];
	    	}
	    	
			$fields = array_merge(array('object_id'), $fields);
	    	
			fdebug('ОК'."\r\n", 'export.log');
			
			$this->setResponse(array('fields' => $fields, 'page_count' => ceil($count / self::LIMIT)));
		} catch (Exception $e) {
			$this->setError($e->getMessage());
		}
	}
	
	public function exportParamValues() {
		try {
			$page = $this->request->data('page');
			$total = $this->request->data('total');
			$fields = $this->request->data('fields');
			
			fdebug(sprintf('Экспорт значений тех.параметров %d/%d...', $page, $total), 'export.log');
			
	    	$conditions = array('object_type' => 'ProductParam');
	    	$limit = self::LIMIT;
	    	$order = array('object_type', 'object_id');
	    	$rows = $this->PMFormData->find('all', compact('fields', 'conditions', 'page', 'limit', 'order'));
	    	
	    	foreach($this->aDataSource as $dataSource) {
	    		$this->setDataSource($dataSource, 'xParamValue');
	    		
	    		foreach($rows as $row) {
	    			$data = array('object_type' => 'ProductParam', 'object_id' => $row['PMFormData']['object_id']);
	    			unset($row['PMFormData']['object_id']);
	    			foreach($row['PMFormData'] as $field => $value) {
	    				$data['param_id'] = str_replace('fk_', '', $field);
	    				$data['value'] = $value;
	    				$this->xParamValue->clear();
		    			$this->xParamValue->save($data);
	    			}
	    		}
	    	}
	    	fdebug('ОК'."\r\n", 'export.log');
	    	$this->setResponse(true);
		} catch (Exception $e) {
			$this->setError($e->getMessage());
		}
	}
	
	public function initExportSeo() {
		try {
			fdebug('Подготовка для экспорта SEO-данных...', 'export.log');
			foreach($this->aDataSource as $dataSource) {
	    		$this->setDataSource($dataSource, 'xSeo');
	    		
				$this->xSeo->deleteAll(array('object_type' => 'Article'));
			}
			
	    	$count = $this->Seo->find('count');
			fdebug('ОК'."\r\n", 'export.log');
			
			$this->setResponse(array('page_count' => ceil($count / self::LIMIT)));
		} catch (Exception $e) {
			$this->setError($e->getMessage());
		}
	}
	
	public function exportSeo() {
		try {
			$page = $this->request->data('page');
			$total = $this->request->data('total');
			fdebug(sprintf('Экспорт SEO %d/%d...', $page, $total), 'export.log');
			
			$limit = self::LIMIT;
			$order = array('id');
			$aRowset = $this->Seo->find('all', compact('page', 'limit', 'order'));
			
			foreach($this->aDataSource as $dataSource) {
	    		$this->setDataSource($dataSource, 'xSeo');
	    		
	    		foreach($aRowset as $row) {
	    			$data = $row['Seo'];
	    			unset($data['id']);
	    			$data['object_type'] = 'Article';
	    			
	    			$this->xSeo->clear();
	    			$this->xSeo->save($data);
	    		}
			}
	    	fdebug('ОК'."\r\n", 'export.log');
	    	$this->setResponse(true);
		} catch (Exception $e) {
			$this->setError($e->getMessage());
		}
	}
	
}
