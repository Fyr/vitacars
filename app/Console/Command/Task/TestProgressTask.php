<?php
App::uses('Shell', 'Console');
App::uses('AppShell', 'Console/Command');
class TestProgressTask extends AppShell {

    public function execute($params) {

        $this->Task->setProgress($this->id, 0, 3); // 3 subtasks
        $this->Task->setStatus($this->id, Task::RUN);

        $total = $params['total'];
        $this->run(1, $total);
        $this->run(2, $total);
        $this->run(3, $total);

        $this->Task->setData($this->id, 'xdata', $total * 3);
        $this->Task->setStatus($this->id, Task::DONE);
    }

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
}
