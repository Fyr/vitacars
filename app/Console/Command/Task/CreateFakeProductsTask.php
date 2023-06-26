<?php
App::uses('Shell', 'Console');
App::uses('AppShell', 'Console/Command');
App::uses('AppModel', 'Model');
App::uses('Article', 'Article.Model');
App::uses('Product', 'Model');
App::uses('DetailNum', 'Model');
App::uses('Brand', 'Model');
App::uses('Category', 'Model');
App::uses('Subcategory', 'Model');
App::uses('CsvWriter', 'Vendor');
App::uses('Translit', 'Article.Vendor');
class CreateFakeProductsTask extends AppShell {
    public $uses = array('Product', 'DetailNum', 'Brand', 'Category', 'Subcategory');

    public function execute() {
        $conditions = $this->_getProductConditions();
        $total = $this->Product->find('count', compact('conditions'));

        $this->Task->setProgress($this->id, 0, $total);
        $this->Task->setStatus($this->id, Task::RUN);

        $result = $this->run();

        $this->Task->setData($this->id, 'xdata', $result);
        $this->Task->setStatus($this->id, Task::DONE);
    }

    private function _getProductConditions() {
        $brand_id = $this->params['parse_brand_id'];
        $allow_brands = $this->params['allow_brands'];
        $fk_cross = 'fk_'.Configure::read('Params.crossNumber');

        $opers = array();
		foreach($allow_brands as $brand) {
			$opers[] = "PMFormData.$fk_cross LIKE '%$brand%'";
		}
		$xQuery = implode(' OR ', $opers);
        return array('Product.brand_id' => $brand_id, 'Product.published' => 1, "($xQuery)");
    }

    private function run() {
        $allow_brands = $this->params['allow_brands'];

        $csvFile = PATH_FILES_UPLOAD.'create-fake-products.csv';
        $headers = array('orig_id', 'code', 'brand', 'cat', 'subcat', 'title', 'title_rus');
        $csv = new CsvWriter($csvFile, $headers);
        $csv->writeHeaders();

        $fk_cross = 'fk_'.Configure::read('Params.crossNumber');
        $aUnique = array();
        $aBrands = array();
        $products_count = 0;
        $is_fake = true;

        // remove useless data that slows down processing
        $this->Product->unbindModel(array(
            'belongsTo' => array('Brand', 'Category', 'Subcategory'),
            'hasOne' => array('Media', 'Search')
        ), true);  // permanently
        $this->Brand->unbindModel(array(
            'hasOne' => array('Media', 'Seo')
        ), true);  // permanently

        $page = 1;
        $limit = 1000;
        $conditions = $this->_getProductConditions();

        $i = 0;
        $this->Product->trxBegin();
        while ($rowset = $this->Product->find('all', compact('page', 'limit', 'conditions'))) {
            $page++;
            foreach ($rowset as $product) {
                $id = $product['Product']['id'];
                $orig_id = $id;
                $aDetailRows = $this->_preprocess($product['PMFormData'][$fk_cross]);
                foreach($aDetailRows as $_row) {
                    $status = $this->Task->getStatus($this->id);
                    if ($status == Task::ABORT) {
                        $this->Product->trxRollback(); 
                        throw new Exception(__('Processing was aborted by user'));
                    }
                    
                    list($brands, $detail_nums) = $this->_parseCrossNumber($_row);
                    if ($brands && $detail_nums) {
                        foreach($brands as $brand) {
                            // normalize brand name
                            $brand = trim($brand);
                            $char1st = strtoupper(substr($brand, 0, 1));

                            // if brand more then 2 chars and starts with A-Z (avoid badly parsed brands)
                            if (strlen($brand) > 2 && (ord('A') <= ord($char1st) && ord($char1st) <= ord('Z'))) {
                                if (in_array($brand, $allow_brands)) {
                                    // save fake brand & categories anyway
                                    $title = $brand;
                                    $brand_id = $cat_id = $subcat_id = 0;
                                    $slug = Translit::convert($brand, true);
                                    if (isset($aBrands[$brand])) { // no need to save it again
                                        list($brand_id, $cat_id, $subcat_id) = $aBrands[$brand];
                                    } else {
                                        $object_type = 'Brand'; // should not need object_type - why so ???
                                        if (!$this->Brand->save(compact('object_type', 'is_fake', 'title'))) {
                                            $this->Product->trxRollback(); 
                                            throw new Exception('Cannot save Brand');    
                                        }
                                        $brand_id = $this->Brand->id;
                                        $this->Brand->clear();

                                        $object_type = 'Category';
                                        $is_subdomain = 1;
                                        if (!$this->Category->save(compact('object_type', 'is_fake', 'title', 'slug', 'is_subdomain'))) {
                                            $this->Product->trxRollback(); 
                                            throw new Exception('Cannot save Category');  
                                        }
                                        $cat_id = $this->Category->id;
                                        $this->Category->clear();

                                        $object_type = 'Subcategory';
                                        if (!$this->Subcategory->save(compact('object_type', 'is_fake', 'title', 'slug', 'cat_id'))) {
                                            $this->Product->trxRollback(); 
                                            throw new Exception('Cannot save Subcategory');  
                                        }
                                        $subcat_id = $this->Subcategory->id;
                                        $this->Subcategory->clear();

                                        $aBrands[$brand] = array($brand_id, $cat_id, $subcat_id);
                                    }

                                    foreach($detail_nums as $dn) {
                                        // calculate unique hash to avoid adding the same product
                                        $hash = $this->_calcHash(array(
                                            'brand' => $brand,
                                            'title' => $product['Product']['title'],
                                            'title_rus' => $product['Product']['title_rus'],
                                            'code' => $dn
                                        ));
                                        if (!isset($aUnique[$hash])) {
                                            $aUnique[$hash] = $id; 

                                            // add created product to CSV report
                                            $csvPproduct = array(
                                                'orig_id' => ''.$id,
                                                'code' => ' '.$dn,
                                                'brand' => $brand,
                                                'title' => $product['Product']['title'],
                                                'title_rus' => $product['Product']['title_rus']
                                            );
                                            $csv->writeData($csvPproduct);

                                            unset($product['Product']['id']);
                                            unset($product['PMFormData']['id']);
                                            unset($product['PMFormData']['object_id']);
                                            unset($product['Seo']['id']);
                                            unset($product['Seo']['object_id']);

                                            $code = $dn;
                                            $detail_num = $dn; // dont know why this field is filled ???
                                            $slug = Translit::convert($product['Product']['title_rus'].' '.$dn, true);
                                            $page_id = $slug;
                                            $product['Product'] = array_merge(
                                                $product['Product'], 
                                                compact('code', 'detail_num', 'slug', 'page_id', 'is_fake', 'brand_id', 'cat_id', 'subcat_id', 'orig_id')
                                            );

                                            fdebug($product, 'products.log');
                                            if (!$this->Product->saveAll($product)) {
                                                $this->Product->trxRollback(); 
                                                throw new Exception('Cannot save Product');
                                            }
                                            $this->Product->clear();
                                            $products_count++;
                                            /*
                                            if ($products_count > 10) {
                                                $this->Product->trxCommit();
                                                throw new Exception('Enough for test'); 
                                            }
                                            */
                                        }
                                    }
                                }
                            } else {
                                fdebug(compact('id', 'brand', 'detail_nums'), 'bad_brand.log');
                            }
                        }
                    } else {
                        fdebug(compact('id', 'brands', 'detail_nums'), 'bad_cross_number.log');
                    }
                }

                $i++;
                $this->Task->setProgress($this->id, $i);
            }
        }
        $this->Product->trxCommit();

        return array('products' => $products_count);
    }

    private function _preprocess($crossNumbers) {
        $crossNumbers = explode("\n", str_replace(array("\r\n", "\r"), "\n", trim($crossNumbers)));
        $aRows = array();
        foreach($crossNumbers as $row) {
            $row = trim($row);
            if ($row) {
                if (in_array(substr($row, -1), array('.', ','))) {
                    $row = substr($row, 0, -1);
                }
                $aRows[] = $row;
                /* filter for brand/category
                if (strpos(strtoupper($row), 'DEUTZ / KHD') === false) {
                    
                }
                */
            }
        }
        return $aRows;
    }

    private function _parseCrossNumber($row) {
        $parts = array_filter(explode(',', str_replace(', ', ',', strtoupper($row))));
        $detail_nums = array();
        $cat = '';
        foreach($parts as $dn) {
            if ($this->DetailNum->isDigitWord($dn)) {
                $detail_nums[] = $dn;// $this->DetailNum->strip($dn); не вырезать лидирующие нули
            } else {
                // убиваем все то, что в скобках
                $dn = $this->_stripParenthesis($dn);
                $a_dn = explode(' ', str_replace(array('   ', '  '), ' ', $dn));
                $dn = array_pop($a_dn);
                if ($this->DetailNum->isDigitWord($dn)) {
                    $detail_nums[] = $dn; // $this->DetailNum->strip($dn); не вырезать лидирующие нули
                }
                $cat = implode(' ', $a_dn);

                // если название со слэшами - берем все
                $cat = str_replace(array(' \ ', '\\', ' / '), '/', $cat);
                if (strpos($cat, '/') !== false) {
                    $cat = explode('/', trim($cat));
                } else {
                    $cat = array(trim($cat));
                }
            }
        }
        return array($cat, array_unique($detail_nums));
    }

    private function _stripParenthesis($s) {
        $pos = strpos($s, '(');
        $pos2 = strpos($s, ')');
        if ($pos && $pos2) {
            $s = substr($s, 0, $pos - 1).substr($s, $pos2 + 1);
        }
        return $s;
    }

    private function _calcHash($row) {
        return md5(implode('|', array_values($row)));
    }
}