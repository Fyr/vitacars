<?php
App::uses('AppController', 'Controller');
App::uses('CakeEmail', 'Network/Email');
App::uses('Settings', 'Model');
App::uses('PMFormConst', 'Form.Model');
class RateController extends AppController {
	public $name = 'Rate';
	public $uses = array('Form.PMFormConst');

	public function refresh() {
		$this->autoRender = false;

		$aCurrency = array('USD', 'EUR', 'RUB');

		$setKurs = array();
		$errMsg = '';
		try {

			$this->PMFormConst->trxBegin();

			$rates = @simplexml_load_file('http://www.nbrb.by/Services/XmlExRates.aspx');
			if (!(($rates instanceof SimpleXMLElement) && isset($rates->Currency))) {
				throw new Exception('Nbrb.by API: Incorrect rates');
			}
			foreach($rates->Currency as $rate) {
				$curr = (string) $rate->CharCode;
				if (in_array($curr, $aCurrency)) {
					$kurs = floatval($rate->Rate);

					$row = $this->PMFormConst->findByKey($curr.'0');
					if (!$row) {
						throw new Exception("No constant key `{$curr}0`");
					}
					if ($kurs != $row['PMFormConst']['value']) {
						$setKurs[$curr] = $kurs;
						$this->PMFormConst->save(array('id' => $row['PMFormConst']['id'], 'value' => $kurs));
					}
				}
			}

			$this->PMFormConst->trxCommit();
		} catch (Exception $e) {
			$this->PMFormConst->trxRollback();
			$errMsg = $e->getMessage();
			echo "Error! ".$errMsg;
		}

		if ($errMsg || $setKurs) {
			$Email = new CakeEmail();
			$Email->template('rates_refresh')->viewVars(compact('setKurs', 'errMsg'))
				->emailFormat('html')
				->from('info@' . Configure::read('domain.url'))
				->to(Configure::read('Settings.admin_email'))
				->bcc('fyr.work@gmail.com')
				->subject(Configure::read('domain.title') . ': ' . __('Rates refreshing'))
				->send();
		}
	}
	
}
