<?php
App::uses('AdminController', 'Controller');
class AdminUpdateController extends AdminController {
    public $name = 'AdminUtils';
    public $layout = false;
    
    public function beforeFilter() {
		if (!$this->isAdmin()) {
			$this->redirect(array('controller' => 'Admin', 'action' => 'index'));
			return;
		}
		parent::beforeFilter();
		$this->autoRender = false;
	}
	
	public function update2() {
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
	
	public function update3() {
		App::uses('Translit', 'Article.Vendor');
		// $this->PHTranslit = new PHTranslitHelper($this->view);
		
		$conditions = array('object_type' => 'Product');
    	$page = 1;
    	$limit = 10;
    	$order = 'SiteArticle.id'; 
    	while ($articles = $this->SiteArticle->find('all', compact('conditions', 'page', 'limit', 'order'))) {
    		$page++;
    		foreach($articles as $article) {
    			$data = $article['SiteArticle'];
    			$data['page_id'] = Translit::convert($data['title_rus'].'-'.$data['detail_num'], true);
    			$this->SiteArticle->save(array('id' => $data['id'], 'page_id' => $data['page_id']));
			}
    	}
	}
	
	public function update4() {
		$this->loadModel('Form.FormField');
		$this->loadModel('Form.PMFormValue');
		$this->loadModel('Form.PMFormField');
		$this->loadModel('Form.PMFormData');
		$this->loadModel('SiteArticle');
		
		// пересохраняем поля, чтобы создать form_data
		$aRowset = Hash::combine($this->FormField->find('all'), '{n}.FormField.id', '{n}.FormField');
		foreach($aRowset as $formField) {
			if (!FieldTypes::getSqlTypes($formField['field_type'])) {
				echo 'Error! Unexisted field type: '.$formField['id'].' - '.$formField['field_type'].'<br>';
				$formField['field_type'] = FieldTypes::STRING;
			}
			$this->PMFormField->save($formField, array('forceCreate' => true));
		}
		echo 'Processed '.count($aRowset).' form fields<br>';
		
		$conditions = array('object_type' => 'Product');
    	$page = 1;
    	$limit = 10;
    	$order = 'SiteArticle.id'; 
    	$count = array('Product' => 0, 'Param' => 0);
    	while ($articles = $this->SiteArticle->find('all', compact('conditions', 'page', 'limit', 'order'))) {
    		$page++;
    		foreach($articles as $article) {
    			$count['Product']++;
    			$id = $article['SiteArticle']['id'];
    			$formValues = $this->PMFormValue->getValues('ProductParam', $id);
    			$formData = Hash::combine($formValues, '{n}.PMFormValue.field_id', '{n}.PMFormValue.value');
    			$data = array('object_type' => 'ProductParam', 'object_id' => $id);
    			foreach($aRowset as $id => $field) {
    				$count['Param']++;
    				if (isset($formData[$id])) {
    					$data['fk_'.$id] = $formData[$id];
    					
    					$data['fk_'.$id] = str_replace('&nbsp;', '', $data['fk_'.$id]);
    					if ($field['field_type'] == FieldTypes::INT) {
    						$data['fk_'.$id] = intval($data['fk_'.$id]);
    					} elseif ($field['field_type'] == FieldTypes::FLOAT) {
    						if ($id == 10) {
    							if (trim($data['fk_'.$id]) === '') {
    								$data['fk_'.$id] = 0.01;
    							} 
    						}
    						$data['fk_'.$id] = floatval(str_replace(',', '.', $data['fk_'.$id]));
    					}
    				}
    			}
    			$this->PMFormData->clear();
    			$this->PMFormData->save(array('PMFormData' => $data));
			}
    	}
		echo 'Processed '.$count['Product'].' products, '.$count['Param'].' params <br>';
	}

}
