<?php
App::uses('AppModel', 'Model');
class SiteOrderCompany extends AppModel {
    public $useTable = 'clients_companies';
    public $primaryKey = 'user_id';
}
