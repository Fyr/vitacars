<?php
App::uses('Shell', 'Console');
App::uses('AppShell', 'Console/Command');
class RecalcFormulaTask extends AppShell {
    public $uses = array('Form.PMFormConst', 'Form.PMFormData', 'Form.PMFormField', 'FormPrice');

    public function execute() {

        $total = $this->PMFormData->find('count');
        $this->Task->setProgress($this->id, 0, $total); // 389099
        // fdebug($total." total!\r\n");
        $this->Task->setStatus($this->id, Task::RUN);

        $aConst = $this->PMFormConst->find('all');

        $page = 1;
        $limit = 500;
        $fields = $this->PMFormField->getObjectList('SubcategoryParam', '');
        $i = 0;

        $conditions = array();
        while ($rowset = $this->PMFormData->find('all', compact('page', 'limit', 'conditions'))) {
            $this->PMFormData->trxBegin();
            $page++;
            foreach($rowset as $row) {
                $status = $this->Task->getStatus($this->id);
                if ($status == Task::ABORT) {
                    $this->PMFormData->trxCommit(); // по любому сохраняем рез-ты пересчета
                    throw new Exception(__('Processing was aborted by user'));
                }

                $this->PMFormData->recalcFormulaData($row, $fields, $aConst);

                $i++;
                $this->Task->setProgress($this->id, $i);
            }
            $this->PMFormData->trxCommit();
        }

        $this->Task->setData($this->id, 'xdata', $total);
        $this->Task->setStatus($this->id, Task::DONE);
    }

}
