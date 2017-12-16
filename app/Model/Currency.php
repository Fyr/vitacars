<?php
App::uses('AppModel', 'Model');

class Currency extends AppModel
{
    public $useTable = false;

    public function getOptions($type = '', $lLong = true)
    {
        $aOptions = array(
            'BYR' => 'BYR (бел.руб)',
            'USD' => 'USD ($, доллар США)',
            'EUR' => 'EUR (&euro;, евро)',
            'RUB' => 'RUB (<span class="rubl">₽</span>, росс.рубль)',
            'UAH' => 'UAH (укр.гривна)',
        );
        if ($lLong) {
            return ($type) ? $aOptions[$type] : $aOptions;
        }
        return ($type) ? $type : array_keys($aOptions);
    }
}
