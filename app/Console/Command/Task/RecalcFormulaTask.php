<?php
App::uses('Shell', 'Console');
App::uses('AppShell', 'Console/Command');
class RecalcFormulaTask extends AppShell {
    public $uses = array('Form.PMFormConst', 'Form.PMFormData', 'Form.PMFormField', 'FormPrice');

    public function execute() {

        $total = $this->PMFormData->find('count');
        $this->Task->setProgress($this->id, 0, $total);
        $this->Task->setStatus($this->id, Task::RUN);

        $fields = array('key', 'value');
        $conditions = array('PMFormConst.object_type' => 'SubcategoryParam');
        $aConst = $this->PMFormConst->find('list', compact('fields', 'conditions'));

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

                $xPrices = $this->FormPrice->findAllByProductId($row['PMFormData']['object_id']);
                $aExclude = ($xPrices) ? Hash::extract($xPrices, '{n}.FormPrice.fk_id') : array();
                $this->PMFormData->_recalcFormula($row, $fields, $aConst, $aExclude);

                $i++;
                $this->Task->setProgress($this->id, $i);
            }
        }
        $this->PMFormData->trxCommit();

        $this->Task->setData($this->id, 'xdata', $total);
        $this->Task->setStatus($this->id, Task::DONE);
    }
/*
    private function run($taskI, $total) {
        $subtask_id = $this->Task->add(0, 'TestProgress_task'.$taskI, null, $this->id);
        $this->Task->setData($this->id, 'subtask_id', $subtask_id);
        $this->Task->setProgress($subtask_id, 0, $total);
        $this->Task->setStatus($subtask_id, Task::RUN);
        for($i = 0; $i < $total; $i++) {
            $status = $this->Task->getStatus($this->id);
            if ($status == Task::ABORT) {
                $this->Task->setStatus($subtask_id, Task::ABORTED);
                throw new Exception(__('Processing was aborted by user'));
            }

            sleep(1);
            $this->Task->setProgress($subtask_id, $i + 1);

            $_progress = $this->Task->getProgressInfo($subtask_id);
            $progress = $this->Task->getProgressInfo($this->id);
            $this->Task->setProgress($this->id, $progress['progress'] + $_progress['percent'] * 0.01);

            if ($i > 60) {
                throw new Exception(__('Too much iterations'));
            }
        }

        $this->Task->setStatus($subtask_id, Task::DONE);

        $this->Task->setProgress($this->id, $taskI);
        $this->Task->saveStatus($this->id);
    }
*/
}
