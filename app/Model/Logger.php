<?php
App::uses('AppModel', 'Model');
class Logger extends AppModel {
    public $useTable = false;

    protected function _afterInit() {
        $this->log = ROOT.DS.APP_DIR.DS.'tmp'.DS.'logs'.DS.'app.log';
    }

    public function init($log) {
        $this->log = $log;
    }

    public function write($actionType, $data){
        if (is_array($data)) {
            $data = json_encode($data);
        } elseif (is_object($data)) {
            $data = serialize($data);
        }
        $string = date('Y-m-d H:i:s').' '.$actionType.' '.$data;
        file_put_contents($this->log, $string."\r\n", FILE_APPEND);
    }
}
