<?php
App::uses('Shell', 'Console');
App::uses('AppShell', 'Console/Command');
App::uses('CsvReader', 'Vendor');
App::import('Vendor', 'simple_html_dom');
class DeutzParserTask extends AppShell {
    public $uses = array('Product', 'Form.PMFormData', 'DetailNum');

    const FILE = 'parser.csv';
    const REPORT = 'parser.xls';
    private $class = '';

    public function execute() {
        $aData = CsvReader::parse(self::FILE);
        $total = count($aData['data']);

        $this->Product->unbindModel(array(
            'hasOne' => array('Media', 'Seo')
        ));

        fdebug($this->_reportHeader(), self::REPORT, false);

        $item = 0;
        $this->Task->setProgress($this->id, 0, $total);
        $this->Task->setStatus($this->id, Task::RUN);

        foreach ($aData['data'] as $_item) {
            $status = $this->Task->getStatus($this->id);
            if ($status == Task::ABORT) {
                throw new Exception(__('Processing was aborted by user'));
            }

            $this->_parseItem($_item);
            $item++;
            $this->Task->setProgress($this->id, $item);
            if ($item >= $total) {
                break;
            }
        }

        fdebug($this->_reportFooter(), self::REPORT);

        $this->Task->setData($this->id, 'xdata', $total);
        $this->Task->setStatus($this->id, Task::DONE);
    }

    private function _parseItem($item) {
        $title = $item['title'];
        list($code1, $code2) = explode(' ', str_replace('DEUTZ / KHD ', '', $title));
        $code = $code1.$code2;

        // запомнить что получили на входе для контроля (в отчет)
        $raw_nums = 'DEUTZ / KHD '.$item['detail_nums'];
        if (trim($item['cross_nums'])) {
            $raw_nums.= "\n" . str_replace('|', "\n", $item['cross_nums']);
        }

        $detail_nums = $this->stripSpaces('DEUTZ / KHD '.$item['detail_nums']);
        $cross_nums = array();
        if (trim($item['cross_nums'])) {
            $cross_nums = explode('|', $item['cross_nums']);

            // очистить кросс-номера от пробелов и дублей
            foreach ($cross_nums as &$str) {
                $str = $this->stripSpaces($str);
            }
            unset($str);
        }

        $product = $this->Product->findByCode($code);
        if ($product) {
            $key = 'fk_'.Configure::read('Params.crossNumber');
            $val = $detail_nums;
            if ($cross_nums) {
                $val = $val.", \n".implode(", \n", $cross_nums).'.';
            }

            // Пересохраняем продукт, чтобы переформировать поисковую инфу
            // иначе снова придется запускать тяжелые скрипты
            $product['PMFormData'][$key] = $val;
            $data = array(
                'Product' => $product['Product'],
                'PMFormData' => $product['PMFormData']
            );
            $this->Product->saveAll($data);
            $html = $this->_reportDetail(array('product' => $product, 'rawNumber' => $raw_nums, 'crossNumber' => $val, 'url' => $item['url']));
            fdebug($html, self::REPORT);
        }
        return false;
    }

    private function stripSpaces($str) {
        $nums = explode(',', $str);
        $_nums = array();

        $_nums1 = array_shift($nums);
        $_nums1 = explode(' ', $_nums1);
        $__num2 = '';
        $lFlag = true;
        $__num = '';
        for($i = count($_nums1) - 1; $i >= 0; $i--) {
            if ($lFlag && $this->DetailNum->isDigitWord($_nums1[$i])) {
                $__num2 = $_nums1[$i].$__num2;
            } else {
                $Flag = false;
                $__num = $_nums1[$i].' '.$__num;
            }
        }
        if ($__num2) {
            $_nums[] = $__num2;
        }
        foreach($nums as $i => $num) {
            $_nums[] = str_replace(' ', '', $num);
        }
        $nums = implode(', ', array_unique($_nums));
        if ($__num) {
            $nums = $__num.$nums;
        }
        return $nums;
    }

    private function _reportHeader() {
        return '
<!DOCTYPE html>
<html>
<head>
<meta http-equiv="content-type" content="text/html; charset=windows-1251">
<style type="text/css">
td {
    vertical-align: top;
    white-space: nowrap;
}
.align-right {
	text-align: right;
}
.even {
	background-color: #eee;
}
.odd {
}
img {
    display: block;
}
.even-changed {
	background-color: #eee;
	border: 1px solid #00f;
}
</style>
</head>
<body>
        <table cellspacing="0" cellpadding="0">
        <thead>
            <tr>
                <th>Код</th>
                <th>Название</th>
                <th>Старый кросс-номер</th>
                <th>Полученный кросс-номер</th>
                <th>Новый кросс-номер</th>
                <th>URL страницы</th>
            </tr>
        </thead>
        <tbody>
';
    }

    private function _reportDetail($data) {
        $key = 'fk_'.Configure::read('Params.crossNumber');
        $id = $data['product']['Product']['id'];
        $code = $data['product']['Product']['code'];
        $title = trim($data['product']['Product']['title_rus']);
        $oldCross = $data['product']['PMFormData'][$key];
        $newCross = $data['crossNumber'];
        list($oldCross, $newCross) = Assert::cmpAsStr($oldCross, $newCross);
        $this->class = ($this->class == 'odd') ? 'even' : 'odd';
        $url = 'http://'.Configure::read('domain.url').Router::url(array('controller' => 'AdminProducts', 'action' => 'edit', $id));
        return '
            <tr class="'.$this->class.'">
                <td>&nbsp;'.$code.'</td>
                <td>
                    '.$title.'
                </td>
                <td>&nbsp;'.nl2br($oldCross).'</td>
                <td>&nbsp;'.nl2br($data['rawNumber']).'</td>
                <td>&nbsp;'.nl2br($newCross).'</td>
                <td>
                    <a href="'.$data['url'].'">'.$data['url'].'</a><br />
                    <a href="'.$url.'">'.$url.'</a>
                </td>
            </tr>';
    }

    private function _reportFooter() {
        return '
        </tbody>
        </table>
</body>
</html>
';
    }

}
