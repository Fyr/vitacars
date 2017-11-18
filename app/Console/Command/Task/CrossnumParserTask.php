<?php
App::uses('Shell', 'Console');
App::uses('AppShell', 'Console/Command');
App::uses('Product', 'Model');
App::uses('CsvWriter', 'Vendor');
class CrossnumParserTask extends AppShell {
    public $uses = array('Product', 'Form.PMFormData', 'Settings', 'Brand', 'Category', 'Subcategory', 'DetailNum');

    public function execute() {
        $this->Settings->initData();

        $fk_cross = 'fk_'.Configure::read('Params.crossNumber');

        $fields = array(
            'Product.id', 'Product.brand_id', 'Product.cat_id', 'Product.subcat_id', 'Product.title', 'Product.title_rus', 'Product.detail_num', 'Product.code',
            'Brand.id', 'Brand.title', 'Category.id', 'Category.title', 'Subcategory.id', 'Subcategory.title', 'PMFormData.'.$fk_cross
        );

        $conditions = array();
        // $this->params['category_id'] = 3894; // OE Germany
        if ($this->params['category_id']) {
            $conditions['Product.cat_id'] = $this->params['category_id'];
        }


        $conditions[] = 'TRIM(PMFormData.'.$fk_cross.') != ""';

        $total = $this->Product->find('count', compact('conditions'));
        $this->Task->setProgress($this->id, 0, $total);

        $page = 1;
        $limit = 1000;
        $i = 0;
        $aID = array();

        $this->Task->setStatus($this->id, Task::RUN);
        // $this->Product->unbind(array('belongsTo' => array('Category', 'Subcategory', '')));
        $this->Product->unbindModel(array('hasOne' => array('Media', 'Seo', 'Search')));

        $headers = array('brand_id', 'cat_id', 'subcat_id', 'title', 'title_rus', 'code', 'detail_num', 'orig_id');
        fdebug(WEBROOT_DIR.'/files/crossnumparser.csv');
        $csv = new CsvWriter(WEBROOT_DIR.'/files/crossnumparser.csv', $headers);
        $csv->writeHeaders();

        while ($rowset = $this->Product->find('all', compact('fields', 'conditions', 'page', 'limit'))) {
            $page++;
            // $this->Product->trxBegin();
            foreach($rowset as $row) {
                $status = $this->Task->getStatus($this->id);
                if ($status == Task::ABORT) {
                    // $this->Product->trxRollback(); // по любому сохраняем рез-ты пересчета
                    throw new Exception(__('Processing was aborted by user'));
                }

                $id = $row['Product']['id'];
                $aID[] = $id;

                $aRows = $this->_preprocess($row['PMFormData'][$fk_cross]);
                // fdebug(array($aRows, $row));
                foreach($aRows as $_row) {
                    list($subcat, $detail_nums) = $this->_parseCrossNumber($_row);
                    if ($subcat && $detail_nums) {
                        foreach($detail_nums as $dn) {
                            $csv->writeData(array(
                                'brand_id' => $row['Brand']['title'],
                                'cat_id' => $row['Category']['title'],
                                'subcat_id' => $subcat,
                                'title' => $row['Product']['title'],
                                'title_rus' => $row['Product']['title_rus'],
                                'code' => ' '.$dn,
                                'detail_num' => ' '.$dn,
                                'orig_id' => ' '.$id
                            ));
                        }
                    }
                }

                $i++;
                $this->Task->setProgress($this->id, $i);
            }
            // $this->Product->trxCommit();
        }
        $this->Task->setData($this->id, 'xdata', $aID);
        $this->Task->setStatus($this->id, Task::DONE);

    }

    private function _preprocess($crossNumbers) {
        $crossNumbers = explode("\n", str_replace(array("\r\n", "\r"), "\n", trim($crossNumbers)));
        $aRows = array();
        foreach($crossNumbers as $row) {
            $row = trim($row);
            if (in_array(substr($row, -1), array('.', ','))) {
                $row = substr($row, 0, -1);
            }
            if (strpos(strtoupper($row), 'DEUTZ / KHD') === false) {
                $aRows[] = $row;
            }
        }
        return $aRows;
    }

    private function _parseCrossNumber($row) {
        $parts = explode(',', str_replace(', ', ',', strtoupper($row)));
        $detail_nums = array();
        $cat = '';
        foreach($parts as $dn) {
            if ($this->DetailNum->isDigitWord($dn)) {
                $detail_nums[] = $this->DetailNum->strip($dn);
            } else {
                // убиваем все то, что в скобках
                $dn = $this->_stripParenthesis($dn);
                $a_dn = explode(' ', str_replace(array('   ', '  '), ' ', $dn));
                $dn = array_pop($a_dn);
                if ($this->DetailNum->isDigitWord($dn)) {
                    $detail_nums[] = $this->DetailNum->strip($dn);
                }
                $cat = implode(' ', $a_dn);

                // если название со слэшами - берем первое
                $cat = str_replace(array(' \ ', '\\', ' / '), '/', $cat);
                if (strpos($cat, '/') !== false) {
                    list($cat) = explode('/', $cat);
                }
            }
        }
        return array(trim($cat), array_unique($detail_nums));
    }

    private function _stripParenthesis($s) {
        $pos = strpos($s, '(');
        $pos2 = strpos($s, ')');
        if ($pos && $pos2) {
            $s = substr($s, 0, $pos - 1).substr($s, $pos2 + 1);
        }
        return $s;
    }
}
