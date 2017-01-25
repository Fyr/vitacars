<?php
App::uses('AppModel', 'Model');
class Logger extends AppModel {
    public $useTable = false;

    // const BASE_DIR = ROOT.DS.APP_DIR.DS.'tmp'.DS.'logs'.DS;

    private $log;

    protected function _afterInit() {
        $this->init('app.log');
    }

    public function init($log) {
        $this->log = (strpos($log, DS) === false) ? ROOT.DS.APP_DIR.DS.'tmp'.DS.'logs'.DS.$log : $log;
        $this->log = (strpos($log, '.') === false) ? $this->log.'.log' : $this->log;
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
