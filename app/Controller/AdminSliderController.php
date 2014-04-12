<?php
App::uses('AdminController', 'Controller');
class AdminSliderController extends AdminController {
    public $name = 'AdminSlider';
    
    public function index() {
        $this->set('object_type', 'Slider');
        $this->set('object_id', 1);
    }
}
