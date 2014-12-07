<?php
App::uses('AdminController', 'Controller');
App::uses('FieldTypes', 'Form.Vendor');

class AdminExportController extends AdminController {
    public $name = 'AdminExport';
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
    private $xArticle, $xMedia, $PHMedia;

	public function beforeFilter() {
		if (!$this->isAdmin()) {
			$this->redirect(array('controller' => 'Admin', 'action' => 'index'));
			return;
		}
		parent::beforeFilter();
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
	
    public function index() {
    	set_time_limit(600);
    	$this->xArticle = $this->ExportArticle;
    	$this->xMedia = $this->ExportMedia;
    	$this->xParam = $this->ExportParams;
    	$this->xParamObject = $this->ExportParamsObjects;
    	$this->xParamValue = $this->ExportParamsValues;
    	$this->xSeo = $this->ExportSeo;
    	
    	try {
	    	fdebug('', 'export.log', false); // чистим лог
	    	foreach(array('agromotors_by', 'agromotors_ru') as $dataSource) { //
	    		fdebug('Переключение на БД: '.$dataSource."\r\n", 'export.log');
		    	foreach(array('xArticle', 'xMedia', 'xParam', 'xParamObject', 'xParamValue', 'xSeo') as $model) {
		    		$this->{$model}->setDataSource($dataSource);
		    		$db = ConnectionManager::getDataSource($dataSource);
		    		$this->{$model}->schemaName = $db->getSchemaName();
		    		$this->{$model}->clear(); // на всякий случай чистим поля модели
		    	}
		    	$this->xMedia->setBasePath(($dataSource == 'agromotors_ru') ? PATH_FILES_UPLOAD_RU : PATH_FILES_UPLOAD_BY);
		    	
		    	$counter = array('Media' => 0, 'Product' => 0, 'Brand' => 0, 'Category' => 0, 'Subcategory' => 0, 'ParamsValues' => 0, 'Seo' => 0);
		    	
		    	$this->xArticle->getDataSource()->begin();
		    	
		    	fdebug('Очистка БД...', 'export.log');
		    	$this->xMedia->deleteAll(array('object_type' => 'Article'), true, true);
		    	$this->xArticle->deleteAll(true);
		    	$this->xParam->deleteAll(true);
		    	$this->xParamObject->deleteAll(true);
		    	$this->xParamValue->deleteAll(true);
		    	$this->xSeo->deleteAll(array('object_type' => 'Article'));
		    	fdebug('ОК'."\r\n", 'export.log');
		    	
		    	fdebug('Экспорт статей...', 'export.log');
		    	$conditions = array('object_type' => array_keys($this->aTypes));
		    	$count = $this->SiteArticle->find('count', compact('conditions'));
		    	$this->set('count', $count);
		    	$page = 1;
		    	$limit = 10;
		    	$order = 'SiteArticle.id'; // !!!  БД не может по другому те же ID записать
		    	
		    	while ($articles = $this->SiteArticle->find('all', compact('conditions', 'page', 'limit', 'order'))) {
		    		$page++;
		    		foreach($articles as $article) {
						$this->_addArticle($article['SiteArticle'], $article['Media']);
						$object_type = $article['SiteArticle']['object_type'];
						$counter[$object_type]++;
						$counter['Media']+= count($article['Media']);
					}
		    	}
		    	fdebug('ОК'."\r\n", 'export.log');
		    	
		    	fdebug('Экспорт параметров...', 'export.log');
		    	// переносим параметры
		    	$aSubcategories = $this->Subcategory->find('all');
		    	$aParams = $this->FormField->find('all', array(
		    		'conditions' => array('exported' => 1),
		    		'order' => 'FormField.id'
		    	));
		    	$fields = array();
		    	foreach($aParams as $param) {
		    		$data = $param['FormField'];
		    		
		    		$fields[] = 'PMFormData.fk_'.$data['id'];
		    		
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
		    	fdebug('ОК'."\r\n", 'export.log');
		    	
		    	$counter['Params'] = count($aParams);
		    	
		    	$fields = array_merge(array('object_id'), $fields);
		    	$conditions = array('object_type' => 'ProductParam');
		    	$page = 1;
		    	$limit = 10;
		    	$order = array('object_type', 'object_id');
		    	fdebug('Экспорт значений параметров...', 'export.log');
		    	while ($rows = $this->PMFormData->find('all', compact('fields', 'conditions', 'page', 'limit', 'order'))) {
		    		$page++;
		    		foreach($rows as $row) {
		    			$data = array('object_type' => 'ProductParam', 'object_id' => $row['PMFormData']['object_id']);
		    			unset($row['PMFormData']['object_id']);
		    			foreach($row['PMFormData'] as $field => $value) {
		    				$counter['ParamsValues']++;
		    				$data['param_id'] = str_replace('fk_', '', $field);
		    				$data['value'] = $value;
		    				$this->xParamValue->clear();
			    			$this->xParamValue->save($data);
		    			}
		    		}
		    	}
		    	fdebug('ОК'."\r\n", 'export.log');
		    	
		    	$page = 1;
		    	$limit = 10;
		    	$order = array('id');
		    	fdebug('Экспорт SEO...', 'export.log');
		    	while ($aRowset = $this->Seo->find('all', compact('page', 'limit', 'order'))) {
		    		$page++;
		    		foreach($aRowset as $row) {
		    			$counter['Seo']++;
		    			$data = $row['Seo'];
		    			unset($data['id']);
		    			$data['object_type'] = 'Article';
		    			
		    			$this->xSeo->clear();
		    			$this->xSeo->save($data);
		    		}
		    	}
		    	fdebug('ОК'."\r\n", 'export.log');
		    	$this->set('counter', $counter);
		    	
		    	$this->xArticle->getDataSource()->commit();
	    	}
    	} catch (Exception $e) {
    		$this->xArticle->getDataSource()->rollback();
    		$this->autoRender = false;
    		exit($e->getMessage());
    	}
    }

}
