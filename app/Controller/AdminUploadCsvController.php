<?php
App::uses('AdminController', 'Controller');
App::uses('Product', 'Model');
App::uses('DetailNum', 'Model');
App::uses('PMFormField', 'Form.Model');
App::uses('PMFormData', 'Form.Model');
class AdminUploadCsvController extends AdminController {
    public $name = 'AdminUploadCsv';
    public $layout = 'admin';
    public $uses = array('Product', 'Form.PMFormData', 'Form.PMFormField', 'Brand', 'Category', 'Subcategory', 'Seo.Seo', 'ProductRemain', 'DetailNum', 'Task');
    
    const CSV_DIV = ';';
    private $errLine = 0, $errLog;
    
	public function beforeFilter() {
		/*
		if (!$this->isAdmin()) {
			$this->redirect(array('controller' => 'Admin', 'action' => 'index'));
			return;
		}
		*/
		parent::beforeFilter();
	}

	/**
	 * Получить данные из CSV файла в виде ассоц.массива 
	 *
	 * @param str $file
	 * @return array
	 */
	private function _parseCsv($file) {
		$file = mb_convert_encoding(trim(file_get_contents($file)), 'utf-8', 'cp1251');
		$file = str_replace("\r\n", "\n", $file);
		$file = str_replace(array('   ', '  '), ' ', $file);
		$file = explode("\n", $file);
		if (!($file && is_array($file) && count($file) > 1)) {
			throw new Exception('Incorrect file content');
		}
		
		$keys = explode(self::CSV_DIV, trim($file[0]));
		unset($file[0]);
		
		$aData = array();
		$this->errLine = 1;
		foreach($file as $row) {
			$this->errLine++;
			$_row = explode(self::CSV_DIV, trim($row));
			if (count($keys) !== count($_row)) {
				throw new Exception('Incorrect file format (Line %s)');
			}
			$aData[] = array_combine($keys, $_row);
		}
		
		return array('keys' => $keys, 'data' => $aData);
	}

	public function checkProducts() {
		$keyField = 'code';
		$aCodes = array();
		try {
			if (isset($_FILES['csv_file']) && is_array($_FILES['csv_file']) && isset($_FILES['csv_file']['tmp_name']) && $_FILES['csv_file']['tmp_name'] ) {
				$aData = $this->_parseCsv($_FILES['csv_file']['tmp_name']);
				
				if (in_array('detail_num', $aData['keys'])) {
					$keyField = 'detail_num';
				}
				
				if (!Hash::get($aData, 'data.0.'.$keyField)) {
					throw new Exception(__('CSV file must contain `%s` field', $keyField));
				}
				$aCodes = Hash::extract($aData, 'data.{n}.'.$keyField);
			} else {
				$keyField = $this->request->data('keyField');
				if ($codes = $this->request->data('codes')) {
					$aCodes = explode(',', $codes);
				}
			}
			
			if ($aCodes) {
				$fields = array('Product.id', 'Product.code', 'Product.detail_num', 'Product.title', 'Product.title_rus');
				$countRecs = 0;
				if ($keyField == 'detail_num') {
					$conditions = array();
					foreach($aCodes as $number) {
						$conditions['OR'][] = array('Product.detail_num LIKE ' => '%'.trim($number).'%');
					}
					$order = 'Product.detail_num';
					$aData = $this->Product->find('all', compact('fields', 'conditions', 'order'));
					$countRecs = count($aData);
					$aProducts = array();
					foreach($aData as $product) {
						foreach(explode(' ', $product['Product']['detail_num']) as $_detail_num) {
							$aProducts[$_detail_num][] = $product; // встречаются разные детали с одинаковыми номерами!!!
						}
					}
				} else {
					$conditions = array('Product.code' => $aCodes);
					$order = 'Product.code';
					$aProducts = $this->Product->find('all', compact('fields', 'conditions', 'order'));
					$countRecs = count($aProducts);
					$aProducts = Hash::combine($aProducts, '{n}.Product.code', '{n}');
				}
				
				$this->set('keyField', $keyField);
				$this->set('aCodes', $aCodes);
				$this->set('aProducts', $aProducts);
				if ($this->request->data('print')) {
					$this->layout = 'print_xls';
					$this->render('check_products_print');
				} else {
					$msg = __('Found %s products / %s codes', $countRecs, count($aCodes));
					$this->setFlash($msg, 'success');
				}
			}
		} catch (Exception $e) {
			$this->setFlash(__($e->getMessage(), $this->errLine), 'error');
			$this->redirect(array('controller' => 'AdminUploadCsv', 'action' => 'checkProducts'));
		}
	}
	
	public function processBigCsv() {
		set_time_limit(60 * 10);
		$this->autoRender = false;
		$chunkSize = 20000;
		
		try {
			
			$aBrands = array_keys($this->Brand->getOptions());
			$aCategories = array_keys($this->Category->getOptions());
			$aSubcategories = array_keys($this->Subcategory->getOptions());
		
			$aData = $this->_parseCsv('big_csv.csv');
			$this->errLine = 1;
			$chunk = '';
			$chunkCount = 0;
			$chunkFile = 0;
			foreach($aData['data'] as $row) {
				$this->errLine++;
				
				// Проверить обязательные поля
				if ( !(isset($row['title']) && trim($row['title'])) ) {
					throw new Exception('Field `title` cannot be blank (Line %s)');
				}
				if ( !(isset($row['title_rus']) && trim($row['title_rus'])) ) {
					throw new Exception('Field `title_rus` cannot be blank (Line %s)');
				}
				if ( !(isset($row['code']) && trim($row['code'])) ) {
					throw new Exception('Field `code` cannot be blank (Line %s)');
				}
				
				// Проверить необязательные поля
				if (isset($row['brand_id']) && !in_array($row['brand_id'], $aBrands)) {
					throw new Exception('Incorrect brand ID (Line %s)');
				}
				if (isset($row['cat_id']) && !in_array($row['cat_id'], $aCategories)) {
					throw new Exception('Incorrect category ID (Line %s)');
				}
				if (isset($row['subcat_id']) && !in_array($row['subcat_id'], $aSubcategories)) {
					throw new Exception('Incorrect subcategory ID (Line %s)');
				}
				$chunkCount++;
				$chunk.= implode(self::CSV_DIV, array_values($row))."\r\n";
				if ($chunkCount >= $chunkSize) {
					$chunkFile++;
					$f = 'big_csv_'.$chunkFile.'.csv';
					@unlink($f);
					fdebug(implode(self::CSV_DIV, $aData['keys'])."\r\n".$chunk, $f);
					$chunk = '';
					$chunkCount = 0;
				}
			}
			// last part of CSV
			$chunkFile++;
			$f = 'big_csv_'.$chunkFile.'.csv';
			@unlink($f);
			fdebug(implode(self::CSV_DIV, $aData['keys'])."\r\n".$chunk, $f);
			
			echo 'No errors in file';
		} catch (Exception $e) {
			echo 'Error!';
			echo __($e->getMessage(), $this->errLine);
		}
	}
	
}

