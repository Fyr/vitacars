<?php
App::uses('Shell', 'Console');
App::uses('AppShell', 'Console/Command');
App::uses('Curl', 'Vendor');
App::uses('Curl', 'Vendor');
App::import('Vendor', 'simple_html_dom');
class DeutzParserTask extends AppShell {
    public $uses = array('Product', 'Form.PMFormData', 'DetailNum');

    const FILE = 'parser.xls';
    private $class = '';

    public function execute() {
        $curl = new Curl();

        $baseURL = 'http://www.technikexpert.net/motorenteile/deutz-khd?dir=asc&limit=26&order=position&p=';
        $total = 5; // $total = 58060; // смотрю кол-во позиций и вручную делю на 26 (позиций на страницу)
        $pages = ceil($total / 26);

        $this->Product->unbindModel(array(
            'hasOne' => array('Media', 'Seo')
        ));

        fdebug($this->_reportHeader(), self::FILE, false);

        $item = 0;
        $this->Task->setProgress($this->id, 0, $total);
        $this->Task->setStatus($this->id, Task::RUN);
        for($page = 1; $page<= $pages; $page++) {
            $curl->setUrl($baseURL . $page)
                ->setOption(CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.3; WOW64; rv:46.0) Gecko/20100101 Firefox/46.0');
            $html = $curl->sendRequest();
            fdebug($html, 'page.log');
            $html = str_get_html($html);
            $ol = $html->find('#products-list', 0);
            if ($ol) {
                $list = $ol->find('li.item h2.product-name a');
                if ($list) {
                    foreach ($ol->find('li.item h2.product-name a') as $a) {
                        $this->_parsePage($a->href);
                        $item++;
                        $this->Task->setProgress($this->id, $item);
                        if ($item >= $total) {
                            break;
                        }
                    }
                }
            }
        }

        fdebug($this->_reportFooter(), self::FILE);

        $this->Task->setData($this->id, 'xdata', $total);
        $this->Task->setStatus($this->id, Task::DONE);
    }

    private function _parsePage($url) {
        $curl = new Curl($url);
        $curl->setOption(CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.3; WOW64; rv:46.0) Gecko/20100101 Firefox/46.0');
        $html = $curl->sendRequest();

        $html = str_get_html($html);
        $div = $html->find('.product-view .padder .std', 0);
        $aInfo = explode('<br/>', $div->innertext);
        fdebug('5!');
        if ($aInfo) {
            $title = trim($aInfo[0]);

            list($code1, $code2) = explode(' ', str_replace('DEUTZ / KHD ', '', $title));
            $code = $code1.$code2;
            fdebug('6!');
            fdebug($code);
            fdebug($aInfo);
            if (count($aInfo) > 4) {
                fdebug('7!');
                // запомнить что получили на входе для контроля (в отчет)
                $raw_nums = array();
                for($i = 4; $i < count($aInfo); $i++) {
                    if ($raw_num = trim($aInfo[$i])) {
                        $raw_nums[] = $raw_num;
                    }
                }
                $raw_nums = implode("\n", $raw_nums);

                /*
                $detail_nums = str_replace(array('DEUTZ / KHD ', ', '), array('', ','), $aInfo[4]);

                // очистить номера от пробелов и дублей
                $detail_nums = array_unique(explode(',', str_replace(' ', '', $detail_nums)));
                */
                fdebug('1!');
                $detail_nums = $this->stripSpaces($aInfo[4]);
                fdebug('2!');
                $cross_nums = array();
                for($i = 5; $i < count($aInfo); $i++) {
                    if (isset($aInfo[$i]) && trim($aInfo[$i])) {
                        $cross_nums[] = trim($aInfo[$i]);
                    }
                }

                // очистить кросс-номера от пробелов и дублей
                foreach($cross_nums as &$str) {
                    $str = $this->stripSpaces($str);
                }
                unset($str);

                fdebug('3!'.$code.'!');
                $product = $this->Product->findByCode($code);
                if ($product) {
                    fdebug('4!');
                    $key = 'fk_'.Configure::read('Params.crossNumber');
                    $val = implode(', ', $detail_nums);
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
                    // $this->Product->saveAll($data);
                    $html = $this->_reportDetail(array('product' => $product, 'rawNumber' => $raw_nums, 'crossNumber' => $val, 'url' => $url));
                    fdebug($html, self::FILE);
                }
            }
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
