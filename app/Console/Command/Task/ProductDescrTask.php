<?php
App::uses('Shell', 'Console');
App::uses('AppShell', 'Console/Command');
App::uses('Product', 'Model');
App::uses('PMFormData', 'Form.Model');
App::uses('TplHelper', 'View/Helper');
class ProductDescrTask extends AppShell {
    public $uses = array('Product', 'Form.PMFormData', 'Settings', 'Brand', 'Category', 'Subcategory');

    public function execute() {
        // $this->loadModel('Settings');
        $this->Settings->initData();

        $conditions = array();
        $field = ($this->params['zone'] == 'ru') ? 'body_ru' : 'body';
        if ($this->params['update'] == 1) {
            $conditions[] = "(Product.{$field} IS NULL OR Product.{$field} = '')";
        } elseif ($this->params['update'] == 2) {
            $conditions[] = "(Product.{$field} IS NOT NULL AND Product.{$field} != '')";
        }
        if ($this->params['brand_id']) {
            $conditions['Product.brand_id'] = $this->params['brand_id'];
        }
        if ($this->params['category_id']) {
            $conditions['Product.cat_id'] = $this->params['category_id'];
        }
        // fdebug($conditions);
        $total = $this->Product->find('count', compact('conditions'));
        $this->Task->setProgress($this->id, 0, $total);

        $page = 1;
        $limit = 1000;
        $i = 0;
        $this->Tpl = new TplHelper(new View());
        $aID = array();
        $aBrandOptions = $this->Brand->find('list');
        $aCategoryOptions = $this->Category->find('list');
        $aSubcategoryOptions = $this->Subcategory->find('list', array('order' => 'sorting ASC'));

        $this->Task->setStatus($this->id, Task::RUN);
        while ($rowset = $this->Product->find('all', compact('conditions', 'page', 'limit'))) {
            $page++;
            $this->Product->trxBegin();
            foreach($rowset as $row) {
                $status = $this->Task->getStatus($this->id);
                if ($status == Task::ABORT) {
                    // $this->Product->trxRollback(); // по любому сохраняем рез-ты пересчета
                    throw new Exception(__('Processing was aborted by user'));
                }

                $id = $row['Product']['id'];
                $aID[] = $id;

                $row['Product']['brand'] = $aBrandOptions[$row['Product']['brand_id']];
                $row['Product']['category'] = $aCategoryOptions[$row['Product']['cat_id']];
                $row['Product']['subcategory'] = $aSubcategoryOptions[$row['Product']['subcat_id']];

                $row['Product'][$field] = $this->Tpl->format(Configure::read('Settings.tpl_product_descr'), $row);
                $this->Product->clear();
                $this->Product->save($row);

                $i++;
                $this->Task->setProgress($this->id, $i);
            }
            $this->Product->trxCommit();
        }
        $this->Task->setData($this->id, 'xdata', $aID);
        $this->Task->setStatus($this->id, Task::DONE);
    }
}
