<?
App::uses('AppShell', 'Console/Command');
class BkgServiceShell extends AppShell {

    public function execTask() {
        ignore_user_abort(true);
        set_time_limit(0);

        $id = $this->args[0];
        $taskData = $this->Task->findById($id);
        $taskName = $taskData['Task']['task_name'];
        $task = $this->Tasks->load($taskName);
        $task->id = $id;
        $task->user_id = $taskData['Task']['user_id'];
        $task->params = unserialize($taskData['Task']['params']);
        try {
            $task->execute();
        } catch (Exception $e) {
            $status = $this->Task->getStatus($id);
            $status = ($status == Task::ABORT) ? Task::ABORTED : Task::ERROR;
            $this->Task->setData($id, 'xdata', $e->getMessage());
            $this->Task->setStatus($id, $status);
            $this->out(mb_convert_encoding($e->getMessage(), 'cp1251', 'utf8'));
        }
        $task->cleanup();
    }

}

