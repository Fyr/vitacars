<?php
App::uses('AdminController', 'Controller');
App::uses('FieldTypes', 'Form.Vendor');

class AdminExportController extends AdminController {
    public $name = 'AdminExport';
    public $uses = array(
    	'Category', 'Subcategory', 'Article.Article', 'Media.Media', 'SiteArticle', 'Form.FormField', 'Form.FormValues',
    	'ExportArticle', 'ExportMedia', 'ExportParams', 'ExportParamsObjects', 'ExportParamsValues'
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
		FieldTypes::EMAIL => 4,
		FieldTypes::URL => 4,
		FieldTypes::UPLOAD_FILE => 4,
		FieldTypes::EDITOR => 5,
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
    	
    	fdebug('', 'export.log', false); // чистим лог
    	foreach(array('agromotors_by') as $dataSource) { // , 'agromotors_ru'
    		fdebug('Переключение на БД: '.$dataSource."\r\n", 'export.log');
	    	foreach(array('xArticle', 'xMedia', 'xParam', 'xParamObject', 'xParamValue') as $model) {
	    		$this->{$model}->setDataSource($dataSource);
	    		$db = ConnectionManager::getDataSource($dataSource);
	    		$this->{$model}->schemaName = $db->getSchemaName();
	    		$this->{$model}->clear(); // на всякий случай чистим поля модели
	    	}
	    	$this->xMedia->setBasePath(($dataSource == 'agromotors_ru') ? PATH_FILES_UPLOAD_RU : PATH_FILES_UPLOAD_BY);
	    	
	    	fdebug('Очистка БД...', 'export.log');
	    	$this->xMedia->deleteAll(array('object_type' => 'Article'), true, true);
	    	$this->xArticle->deleteAll(true);
	    	$this->xParam->deleteAll(true);
	    	$this->xParamObject->deleteAll(true);
	    	$this->xParamValue->deleteAll(true);
	    	fdebug('ОК'."\r\n", 'export.log');
	    	
	    	fdebug('Экспорт статей...', 'export.log');
	    	$conditions = array('object_type' => array_keys($this->aTypes));
	    	$count = $this->SiteArticle->find('count', compact('conditions'));
	    	$this->set('count', $count);
	    	$page = 1;
	    	$limit = 10;
	    	$order = 'SiteArticle.id'; // !!!  БД не может по другому те же ID записать
	    	$counter = array('Media' => 0, 'Product' => 0);
	    	while ($articles = $this->SiteArticle->find('all', compact('conditions', 'page', 'limit', 'order'))) {
	    		$page++;
	    		foreach($articles as $article) {
					$this->_addArticle($article['SiteArticle'], $article['Media']);
					$object_type = $article['SiteArticle']['object_type'];
					if (!isset($counter[$object_type])) {
						$counter[$object_type] = 0;
					}
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
	    	$aParamID = array();
	    	foreach($aParams as $param) {
	    		$data = $param['FormField'];
	    		
	    		$aParamID[] = $data['id'];
	    		
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
	    	$counter['ParamsValues'] = 0;
	    	
	    	$conditions = array('object_type' => 'ProductParam', 'field_id' => $aParamID);
	    	$page = 1;
	    	$limit = 10;
	    	$order = array('object_type', 'object_id', 'field_id', 'value');
	    	fdebug('Экспорт значений параметров...', 'export.log');
	    	while ($aParamValues = $this->FormValues->find('all', compact('conditions', 'page', 'limit', 'order'))) {
	    		$page++;
	    		foreach($aParamValues as $param) {
	    			$counter['ParamsValues']++;
	    			$data = $param['FormValues'];
	    			$data['param_id'] = $data['field_id'];
	    			
	    			$this->xParamValue->clear();
	    			$this->xParamValue->save($data);
	    		}
	    	}
	    	fdebug('ОК'."\r\n", 'export.log');
	    	$this->set('counter', $counter);
    	}
    }

	public function update2() {
		$this->autoRender = false;
		$this->_importCategories();
		$this->_importBrands();
	}

	private function _importCategories() {
		// импортируем категории с Agromotors.BY
		$this->xArticle = $this->ExportArticle;
		
		// Delete prev.articles
		$conditions = array('object_type' => array('Category', 'Subcategory'));
		$this->Article->deleteAll($conditions);
		$this->Media->deleteAll($conditions, true, true);
		
		$aCategories = array();
		$aSubcategories = array();
		$conditions = array('object_type' => array('category', 'subcategory'));
		$count = 0;
		$count2 = 0;
		foreach($this->xArticle->find('all', compact('conditions')) as $article) {
			$article['xArticle']['object_type'] = ucfirst($article['xArticle']['object_type']);
			if ($article['xArticle']['object_type'] == 'Category') {
				$cat_id = $article['xArticle']['id'];
				unset($article['xArticle']['id']);
				$this->Category->clear();
				$this->Category->save($article['xArticle']);
				$count++;
				$article['xArticle']['id'] = $this->Category->id;
				$aCategories[$cat_id] = $article['xArticle'];
			} else {
				unset($article['xArticle']['id']);
				$cat_id = $article['xArticle']['object_id'];
				$aSubcategories[$cat_id][] = $article['xArticle'];
			}
		}
		foreach($aSubcategories as $cat_id => $subcategories) {
			foreach($subcategories as $subcategory) {
				$subcategory['object_id'] = $aCategories[$cat_id]['id'];
				$this->Category->clear();
				$this->Category->save($subcategory);
				$count2++;
			}
		}
		echo 'Перенесено '.$count.' категорий, '.$count2.' подкатегорий<br>';
	}
	
	private function _importBrands() {
		$this->xArticle = $this->ExportArticle;
		$this->xMedia = $this->ExportMedia;
		$this->xMedia->setBasePath(PATH_FILES_UPLOAD_BY);
		
		// Delete prev.articles
		$conditions = array('object_type' => array('Brand'));
		$this->Article->deleteAll($conditions);
		$this->Media->deleteAll($conditions, true, true);
		
		$conditions = array('object_type' => array('brands'));
		$count = 0;
		$count2 = 0;
		foreach($this->xArticle->find('all', compact('conditions')) as $article) {
			$article['xArticle']['object_type'] = 'Brand';
			unset($article['xArticle']['id']);
			$this->Article->clear();
			$this->Article->save($article['xArticle']);
			$count++;
			$body = $article['xArticle']['body'];
			$article_id = $this->Article->id;
			
			$aMediaID = array();
			foreach($article['xMedia'] as $media) {
				// save media
				$xFile = $this->xMedia->getPHMedia()->getFileName('Article', $media['id'], 'noresize', $media['file'].$media['ext']);
				
				$media['object_type'] = 'Brand';
				$media['object_id'] = $article_id;
				$media['real_name'] = $xFile;
				$old_media_id = $media['id'];
				unset($media['id']);
				$new_media_id = $this->Media->uploadMedia($media);
				$count2++;
				
				// update new media in article's body
				$body = str_replace('/media/router/index/article/'.$old_media_id, '/media/router/index/brand/'.$new_media_id, $body);
			}
			$this->Article->save(array('id' => $article_id, 'body' => $body));
		}
		echo 'Перенесено '.$count.' брэндов, '.$count2.' media файлов';
	}

}
