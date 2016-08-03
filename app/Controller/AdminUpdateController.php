<?php
App::uses('AdminController', 'Controller');
class AdminUpdateController extends AdminController {
    public $name = 'AdminUpdate';
    public $layout = false;

	// public $components = array('Gearman.Gearman');
    
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
		set_time_limit(60 * 10);
		App::uses('Translit', 'Article.Vendor');
		// $this->PHTranslit = new PHTranslitHelper($this->view);
		
		$this->loadModel('SiteArticle');
		$conditions = array('object_type' => 'Product');
		$fields = array('id', 'title_rus', 'code');
		$page = 1;
		$limit = 1000;
		$order = 'SiteArticle.id'; 
		$count = 0;
		$data = array();
		while ($articles = $this->SiteArticle->find('all', compact('fields', 'conditions', 'page', 'limit', 'order'))) {
			$page++;
			foreach($articles as $article) {
				$count++;
				$data = $article['SiteArticle'];
				$data['page_id'] = Translit::convert($data['title_rus'].'-'.$data['code'], true);
				$this->SiteArticle->save(array('id' => $data['id'], 'page_id' => $data['page_id']));
			}
		}
		exit($count.' products processed');
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

	public function update5() {
		App::uses('Path', 'Core.Vendor');
		$pathInfo = Path::dirContent(Configure::read('import.folder'));
		foreach($pathInfo['files'] as $file) {
			$fullPath = Configure::read('import.folder').$file;
			$path = explode(DS, $this->_getFilePath($file));
			
			$_path = Configure::read('import.folder').DS.$path[0];
			if (!file_exists($_path)) {
				mkdir($_path);
			}
			$_path.= DS.$path[1];
			if (!file_exists($_path)) {
				mkdir($_path);
			}
			$_path.= DS.$path[2];
			if (!file_exists($_path)) {
				mkdir($_path);
			}
			rename($fullPath, $_path.DS.$file);
		}
		
		$this->autoRender = false;
		echo 'Processed '.count($pathInfo['files']).' files';
	}
	
	private function _getFilePath($file) {
		list($fileDate) = explode('_', str_replace('dlt_mgr', '', $file));
		$path = substr($fileDate, 0, 4).DS.substr($fileDate, 4, 2).DS.substr($fileDate, 6, 2);
		return $path;
	}

	public function update6() {
		ignore_user_abort(true);
		set_time_limit(0);
		$this->autoRender = false;
		$this->loadModel('Product');
		$this->loadModel('DetailNum');
		$fields = array('id', 'detail_num');
		$conditions = array('object_type' => 'Product', 'processed' => 0);
		$page = 1;
		$limit = 1000;
		$order = array('Product.id');
		$recursive = -1;
		$count_rows = 0;
		$count_nums = 0;
		while (file_get_contents('cont.log') && $rows = $this->Product->find('all', compact('fields', 'conditions', 'page', 'limit', 'order', 'recursive'))) {
			$page++;
			foreach($rows as $row) {
				$detail_nums = $this->DetailNum->stripList($row['Product']['detail_num']); // explode(',', str_replace(array('   ', '  ', ' '), ' ', trim($row['Product']['detail_num'])));
				fdebug($row, 'products.log');
				$count_rows++;
				foreach($detail_nums as $dn) {
					$dn = $this->DetailNum->strip($dn);
					if (!$this->DetailNum->findByProductIdAndDetailNum($row['Product']['id'], $dn)) {
						fdebug("{$dn}\r\n", 'detail_nums.log');
						$count_nums++;
						$this->DetailNum->clear();
						$this->DetailNum->save(array('detail_num' => mb_strtolower($dn), 'product_id' => $row['Product']['id'], 'num_type' => DetailNum::ORIG));
					}
				}
				$this->Product->clear();
				$this->Product->save(
					array('id' => $row['Product']['id'], 'processed' => 1),
					array('callbacks' => false)
				);
			}
		}
		echo "Processing finished. Rows: {$count_rows}, nums: {$count_nums}";
	}

	public function update7() {
		ignore_user_abort(true);
		set_time_limit(0);
		$this->loadModel('DetailNum');
		$this->loadModel('Form.FormData');
		$fields = array('id', 'object_id', 'fk_60');
		$conditions = array('fk_60 IS NOT NULL'); //
		$page = 1;
		$limit = 1000;
		$order = array('object_id');
		$recursive = -1;
		$count_rows = 0;
		$count_nums = 0;
		while ($rows = $this->FormData->find('all', compact('fields', 'conditions', 'page', 'limit', 'order', 'recursive'))) {
			$page++;
			foreach($rows as $row) {
				$detail_nums = trim($row['FormData']['fk_60']);
				$detail_nums = str_replace(array("\r\n", "\r", "\n"), ',', $detail_nums); // разделяем строки номеров
				$detail_nums = str_replace(array('   ', '  ', ' '), ',', $detail_nums);
				$detail_nums = explode(',', $detail_nums);
				$count_rows++;

				foreach($detail_nums as $dn) {
					$dn = trim($dn);
					if ($dn && $this->DetailNum->isDigitWord($dn)) {
						$dn = $this->DetailNum->strip($dn);
						if ($r = $this->DetailNum->findByProductIdAndDetailNum($row['FormData']['object_id'], $dn)) {
						} else {
							fdebug("{$dn}\r\n", 'detail_nums_cross.log');
							$count_nums++;
							$this->DetailNum->clear();
							$this->DetailNum->save(array('detail_num' => mb_strtolower($dn), 'product_id' => $row['FormData']['object_id'], 'num_type' => DetailNum::CROSS));
						}
					}
				}
			}
		}
		echo "Processing finished. Rows: {$count_rows}, nums: {$count_nums}";
	}



/*
	public function statusUpdate6() {
		$this->autoRender = false;
		$this->loadModel('Product');
		$conditions = array();
		$recursive = -1;
		$total = $this->Product->find('count', compact('conditions', 'recursive'));

		$conditions['processed'] = 1;
		$proc = $this->Product->find('count', compact('conditions', 'recursive'));
		echo "{$proc} / {$total} (".round($proc / $total * 100, 1)."%)";
	}
*/

    public function update8() {
		$this->autoRender = true;
		$this->layout = 'print_xls';
		ignore_user_abort(true);
		set_time_limit(0);

		$this->loadModel('Form.PMFormField');
		$aParams = $this->PMFormField->getFieldsList('SubcategoryParam', '');
		$this->set('aParams', $aParams);
		$aLabels = array();
		foreach($aParams as $id => $_field) {
			$alias = 'PMFormData.fk_'.$id;
			$aLabels[$alias] = $_field['PMFormField']['label'];
		}
		$this->set('aLabels', $aLabels);

		$this->loadModel('Product');
		$this->loadModel('Form.PMFormData');
		$sql = "SELECT code, COUNT(*) AS count FROM articles WHERE object_type = 'Product' AND code != '' GROUP BY code HAVING count > 1";
		$res = $this->Product->query($sql);
		$codes = Hash::extract($res, '{n}.articles.code');
		$aProducts = array();
		$aDeleted = array();
		$deleted = 0;
		foreach($codes as $i => $code) {
			$products = $this->Product->findAllByObjectTypeAndCode('Product', $code, null, 'Product.id');
			$product = array_shift($products);
			$aProducts[] = $product;
			$aDeleted[$code] = $products;
			$products = array_reverse($products);

			$lFlag = false;
			$aFK = array();
			foreach($products as $_product) {

				foreach($_product['PMFormData'] as $fk_id => $_val) {
					if (strpos($fk_id, 'fk_') !== false) {
						$val = (is_numeric($product['PMFormData'][$fk_id])) ? floatval($product['PMFormData'][$fk_id]) : trim($product['PMFormData'][$fk_id]);
						$_val = (is_numeric($_val)) ? floatval($_val) : trim($_val);
						if (!$val && $_val) {
							$product['PMFormData'][$fk_id] = $_val;
							$lFlag = true;
							$aFK[] = 'PMFormData.'.$fk_id;
						}
					}
				}
				$this->Product->delete($_product['Product']['id']);
			}
			if ($lFlag) {
				$aData[$code] = array('fks' => $aFK, 'data' => $product['PMFormData']);
				$this->PMFormData->save($product['PMFormData']);
			}
		}
		$this->set(compact('aProducts', 'aDeleted', 'aData'));

		$this->loadModel('Category');
		$this->loadModel('Subcategory');
		$this->loadModel('Brand');
		$ids = array_unique(Hash::extract($aProducts, '{n}.Product.cat_id'));
		$conditions = array('Category.object_type' => 'Category', 'Category.id' => $ids);
		$aCategories = $this->Category->find('list', compact('conditions'));

		$ids = array_unique(Hash::extract($aProducts, '{n}.Product.subcat_id'));
		$conditions = array('Subcategory.object_type' => 'Subcategory', 'Subcategory.id' => $ids);
		$aSubcategories = $this->Subcategory->find('list', compact('conditions'));

		$ids = array_unique(Hash::extract($aProducts, '{n}.Product.brand_id'));
		$conditions = array('Brand.object_type' => 'Brand', 'Brand.id' => $ids);
		$aBrands = $this->Brand->find('list', compact('conditions'));

		$this->set(compact('aCategories', 'aSubcategories', 'aBrands'));
	}

	public function testMedia() {
		$this->loadModel('Media.Media');
		$this->_resetDB();

		// Тест на установку флага main, если устанавливается 1 флаг для показа
		$this->Media->update(1865, array('show_ru' => 1));
		$fields = array('id', 'main_by', 'main_ru', 'main_bg', 'show_by', 'show_ru', 'show_bg');
		$aRows = $this->Media->find('all', compact('fields'));
		$aExpect = array(
			array('Media' => array('id' => 1864, 'main_by' => false, 'main_ru' => false, 'main_bg' => false, 'show_by' => false, 'show_ru' => false, 'show_bg' => false)),
			array('Media' => array('id' => 1865, 'main_by' => false, 'main_ru' => true, 'main_bg' => false, 'show_by' => false, 'show_ru' => true, 'show_bg' => false)),
			array('Media' => array('id' => 1866, 'main_by' => false, 'main_ru' => false, 'main_bg' => false, 'show_by' => false, 'show_ru' => false, 'show_bg' => false))
		);
		Assert::equal('Test 1.1. Set main if 1 media is shown', $aRows, $aExpect);

		$this->Media->update(1864, array('show_by' => 1));
		$fields = array('id', 'main_by', 'main_ru', 'main_bg', 'show_by', 'show_ru', 'show_bg');
		$aRows = $this->Media->find('all', compact('fields'));
		$aExpect = array(
			array('Media' => array('id' => 1864, 'main_by' => true, 'main_ru' => false, 'main_bg' => false, 'show_by' => true, 'show_ru' => false, 'show_bg' => false)),
			array('Media' => array('id' => 1865, 'main_by' => false, 'main_ru' => true, 'main_bg' => false, 'show_by' => false, 'show_ru' => true, 'show_bg' => false)), // ru флаги остались
			array('Media' => array('id' => 1866, 'main_by' => false, 'main_ru' => false, 'main_bg' => false, 'show_by' => false, 'show_ru' => false, 'show_bg' => false))
		);
		Assert::equal('Test 1.2. Set main if 1 media is shown', $aRows, $aExpect);

		$this->Media->update(1865, array('show_by' => 1));
		$fields = array('id', 'main_by', 'main_ru', 'main_bg', 'show_by', 'show_ru', 'show_bg');
		$aRows = $this->Media->find('all', compact('fields'));
		$aExpect = array(
			array('Media' => array('id' => 1864, 'main_by' => true, 'main_ru' => false, 'main_bg' => false, 'show_by' => true, 'show_ru' => false, 'show_bg' => false)),
			array('Media' => array('id' => 1865, 'main_by' => false, 'main_ru' => true, 'main_bg' => false, 'show_by' => true, 'show_ru' => true, 'show_bg' => false)), // ru флаги остались
			array('Media' => array('id' => 1866, 'main_by' => false, 'main_ru' => false, 'main_bg' => false, 'show_by' => false, 'show_ru' => false, 'show_bg' => false))
		);
		Assert::equal('Test 2.1 Set shown media normally', $aRows, $aExpect);

		$this->Media->update(1866, array('show_ru' => 1));
		$fields = array('id', 'main_by', 'main_ru', 'main_bg', 'show_by', 'show_ru', 'show_bg');
		$aRows = $this->Media->find('all', compact('fields'));
		$aExpect = array(
			array('Media' => array('id' => 1864, 'main_by' => true, 'main_ru' => false, 'main_bg' => false, 'show_by' => true, 'show_ru' => false, 'show_bg' => false)),
			array('Media' => array('id' => 1865, 'main_by' => false, 'main_ru' => true, 'main_bg' => false, 'show_by' => true, 'show_ru' => true, 'show_bg' => false)), // ru флаги остались
			array('Media' => array('id' => 1866, 'main_by' => false, 'main_ru' => false, 'main_bg' => false, 'show_by' => false, 'show_ru' => true, 'show_bg' => false))
		);
		Assert::equal('Test 2.2 Set shown media normally', $aRows, $aExpect);

		$this->Media->update(1865, array('show_ru' => 0));
		$fields = array('id', 'main_by', 'main_ru', 'main_bg', 'show_by', 'show_ru', 'show_bg');
		$aRows = $this->Media->find('all', compact('fields'));
		$aExpect = array(
			array('Media' => array('id' => 1864, 'main_by' => true, 'main_ru' => false, 'main_bg' => false, 'show_by' => true, 'show_ru' => false, 'show_bg' => false)),
			array('Media' => array('id' => 1865, 'main_by' => false, 'main_ru' => false, 'main_bg' => false, 'show_by' => true, 'show_ru' => false, 'show_bg' => false)), // ru флаги остались
			array('Media' => array('id' => 1866, 'main_by' => false, 'main_ru' => true, 'main_bg' => false, 'show_by' => false, 'show_ru' => true, 'show_bg' => false))
		);
		Assert::equal('Test 3.1 Reset main flag if shown flag is reset (set main for shown)', $aRows, $aExpect);

		$this->Media->update(1865, array('show_by' => 0));
		$fields = array('id', 'main_by', 'main_ru', 'main_bg', 'show_by', 'show_ru', 'show_bg');
		$aRows = $this->Media->find('all', compact('fields'));
		$aExpect = array(
			array('Media' => array('id' => 1864, 'main_by' => true, 'main_ru' => false, 'main_bg' => false, 'show_by' => true, 'show_ru' => false, 'show_bg' => false)),
			array('Media' => array('id' => 1865, 'main_by' => false, 'main_ru' => false, 'main_bg' => false, 'show_by' => false, 'show_ru' => false, 'show_bg' => false)), // ru флаги остались
			array('Media' => array('id' => 1866, 'main_by' => false, 'main_ru' => true, 'main_bg' => false, 'show_by' => false, 'show_ru' => true, 'show_bg' => false))
		);
		Assert::equal('Test 3.2 Reset main flag if shown flag is reset (main flag remains for shown)', $aRows, $aExpect);

		$this->Media->update(1864, array('show_by' => 0));
		$fields = array('id', 'main_by', 'main_ru', 'main_bg', 'show_by', 'show_ru', 'show_bg');
		$aRows = $this->Media->find('all', compact('fields'));
		$aExpect = array(
			array('Media' => array('id' => 1864, 'main_by' => false, 'main_ru' => false, 'main_bg' => false, 'show_by' => false, 'show_ru' => false, 'show_bg' => false)),
			array('Media' => array('id' => 1865, 'main_by' => false, 'main_ru' => false, 'main_bg' => false, 'show_by' => false, 'show_ru' => false, 'show_bg' => false)), // ru флаги остались
			array('Media' => array('id' => 1866, 'main_by' => false, 'main_ru' => true, 'main_bg' => false, 'show_by' => false, 'show_ru' => true, 'show_bg' => false))
		);
		Assert::equal('Test 4.1 Reset all main flags if no shown media', $aRows, $aExpect);

		$this->Media->update(1866, array('show_ru' => 0));
		$fields = array('id', 'main_by', 'main_ru', 'main_bg', 'show_by', 'show_ru', 'show_bg');
		$aRows = $this->Media->find('all', compact('fields'));
		$aExpect = array(
			array('Media' => array('id' => 1864, 'main_by' => false, 'main_ru' => false, 'main_bg' => false, 'show_by' => false, 'show_ru' => false, 'show_bg' => false)),
			array('Media' => array('id' => 1865, 'main_by' => false, 'main_ru' => false, 'main_bg' => false, 'show_by' => false, 'show_ru' => false, 'show_bg' => false)), // ru флаги остались
			array('Media' => array('id' => 1866, 'main_by' => false, 'main_ru' => false, 'main_bg' => false, 'show_by' => false, 'show_ru' => false, 'show_bg' => false))
		);
		Assert::equal('Test 4.2 Reset all main flags if no shown media', $aRows, $aExpect);

		//$this->_resetDB();

	}

	private function _resetDB() {
		foreach(Configure::read('domains') as $lang) {
			$this->Media->updateAll(array('show_'.$lang => 0, 'main_'.$lang => 0));
		}
	}

	public function update9() {
		$this->layout = 'admin';
		$this->autoRender = true;
		$this->loadModel('Task');

		$task = $this->Task->getActiveTask('DeutzParser', 0);
		if ($task) {
			$id = Hash::get($task, 'Task.id');
			$task = $this->Task->getFullData($id);
			$this->set(compact('task'));
		} else {
			$id = $this->Task->add(0, 'DeutzParser');
			$this->Task->runBkg($id);
			$this->redirect(array('action' => 'update9'));
		}
	}
}
