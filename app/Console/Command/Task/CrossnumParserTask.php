<?php
App::uses('Shell', 'Console');
App::uses('AppShell', 'Console/Command');
App::uses('Product', 'Model');
App::uses('PMFormData', 'Form.Model');
App::uses('TplHelper', 'View/Helper');
class CrossnumParserTask extends AppShell {
    public $uses = array('Product', 'Form.PMFormData', 'Settings', 'Brand', 'Category', 'Subcategory', 'DetailNum');

    public function execute() {

        // $this->loadModel('Settings');
        $this->Settings->initData();

        $conditions = array();
        $this->params['category_id'] = 3894; // OE Germany
        if ($this->params['category_id']) {
            $conditions['Product.cat_id'] = $this->params['category_id'];
        }

        $conditions[] = 'TRIM(PMFormData.fk_60) != ""';

        $total = $this->Product->find('count', compact('conditions'));
        $this->Task->setProgress($this->id, 0, $total);

        $page = 1;
        $limit = 1000;
        $i = 0;
        $aID = array();
        $aCategoryOptions = $this->Category->find('list');

        $this->Task->setStatus($this->id, Task::RUN);
        $aCategories = array();
        $aSubcategories = array();
        // $this->Product->unbind(array('belongsTo' => array('Category', 'Subcategory', '')));
        $this->Product->unbindModel(array('hasOne' => array('Media', 'Seo', 'Search')));

        while ($rowset = $this->Product->find('all', compact('conditions', 'page', 'limit'))) {
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

                $aRows = $this->_preprocess($row['PMFormData']['fk_'.Configure::read('Params.crossNumber')]);
                fdebug(array($row['PMFormData']['fk_'.Configure::read('Params.crossNumber')], $aRows));
                foreach($aRows as $row) {
                    list($cat, $detail_nums) = $this->_parseCrossNumber($row);
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
        foreach($crossNumbers as &$row) {
            $row = trim($row);
            if (in_array(substr($row, -1), array('.', ','))) {
                $row = substr($row, 0, -1);
            }
        }
        return $crossNumbers;
    }

    private function _parseCrossNumber($row) {
        $parts = explode(',', str_replace(', ', ',', $row));
        $detail_nums = array();
        foreach($parts as $dn) {
            if ($this->DetailNum->isDigitWord($dn)) {
                $detail_nums[] = $dn;
            } else {

            }
        }
    }
}
