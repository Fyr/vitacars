<?php
App::uses('Shell', 'Console');
App::uses('AppShell', 'Console/Command');
class RecalcFormulaTask extends AppShell {
    public $uses = array('Form.PMFormConst', 'Form.PMFormData', 'Form.PMFormField', 'FormPrice');

    public function execute() {

        $total = $this->PMFormData->find('count');
        $this->Task->setProgress($this->id, 0, $total);
        $this->Task->setStatus($this->id, Task::RUN);

        $aConst = $this->PMFormConst->getData();

        $page = 1;
        $limit = 1000;
        $fields = $this->PMFormField->getObjectList('SubcategoryParam', '');
        $i = 0;

        $conditions = array();
        $this->PMFormData->trxBegin();
        while ($rowset = $this->PMFormData->find('all', compact('page', 'limit', 'conditions'))) {
            $page++;
            foreach($rowset as $row) {
                $status = $this->Task->getStatus($this->id);
                if ($status == Task::ABORT) {
                    $this->PMFormData->trxCommit(); // по любому сохраняем рез-ты пересчета
                    throw new Exception(__('Processing was aborted by user'));
                }

                $this->PMFormData->recalcFormula($row, $fields, $aConst);

                $i++;
                $this->Task->setProgress($this->id, $i);
            }
        }
        $this->PMFormData->trxCommit();

        $this->Task->setData($this->id, 'xdata', $total);
        $this->Task->setStatus($this->id, Task::DONE);
    }

}
