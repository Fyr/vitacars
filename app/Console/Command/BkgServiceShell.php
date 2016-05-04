<?
App::uses('AppShell', 'Console/Command');
class BkgServiceShell extends AppShell {
    public $uses = array('Form.PMFormConst', 'Form.PMFormData', 'Form.PMFormField');
    public function main() {
        ignore_user_abort(true);
        set_time_limit(0);
        $i = 0;
        $this->out('Run...');
        while (file_get_contents('cont.log')) { //
            $i++;
            // $this->out($i);
            fdebug("$i\r\n");
            sleep(1);
        }
        $this->out('Done');
    }

    public function recalc_formula() {
        $fields = array('key', 'value');
        $conditions = array('PMFormConst.object_type' => 'SubcategoryParam');
        $aConst = $this->PMFormConst->find('list', compact('fields', 'conditions'));

        $page = 1;
        $limit = 1000;
        $count = 0;
        $fields = $this->PMFormField->getObjectList('SubcategoryParam', '');

        while (file_get_contents('cont.log') && $rowset = $this->PMFormData->find('all', compact('page', 'limit'))) {
            $page++;
            foreach($rowset as $row) {
                $count++;
                $this->PMFormData->_recalcFormula($row, $fields, $aConst);
            }
        }
    }
}

