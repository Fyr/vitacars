<?php
App::uses('AppController', 'Controller');
App::uses('CakeEmail', 'Network/Email');
App::uses('Settings', 'Model');
App::uses('PMFormConst', 'Form.Model');
class RateController extends AppController {
	public $name = 'Rate';
	public $uses = array('Form.PMFormConst', 'Currency');

	public function refresh($lRedirect = false)
	{
		$aCurrency = $this->Currency->getOptions('', false);
		unset($aCurrency[0]);
		$setKurs = array();
		$_setKurs = array();
		$setCrossKurs = array();
		$errMsg = '';

		try {

			$this->PMFormConst->trxBegin();

			$rates = @simplexml_load_file('http://www.nbrb.by/Services/XmlExRates.aspx');
			if (!(($rates instanceof SimpleXMLElement) && isset($rates->Currency))) {
				throw new Exception(__('Nbrb.by API: Incorrect rates'));
			}
			$aRates = array();
			$aCrossRates = array();
			foreach($rates->Currency as $rate) {
				$curr = (string) $rate->CharCode;
				if (in_array($curr, $aCurrency)) {
					$kurs = round(floatval($rate->Rate) / intval($rate->Scale), 4);
					$aRates[$curr] = $kurs;
					$row = $this->PMFormConst->findByKey($curr.'0');
					if ($row) {

						if ($kurs != $row['PMFormConst']['value']) {
							$setKurs[$curr] = $kurs;
							if (!$this->PMFormConst->save(array('id' => $row['PMFormConst']['id'], 'value' => $kurs . ''), false)) {
								throw new Exception(__('Nbrb.by API: Cannot save currency rates (%s: %s ID = %s)', $curr . '0', $kurs, $row['PMFormConst']['id']));
							}
							$_setKurs[] = $curr . ': ' . $kurs;

							$aOtherCurr = array_diff($aCurrency, array($curr));
							foreach($aOtherCurr as $_curr) {
								$aCrossRates[$curr.'_'.$_curr.'0'] = 0;
								$aCrossRates[$_curr.'_'.$curr.'0'] = 0;
							}
						}
					}
				}
			}

			foreach($aCrossRates as $key => $kurs) {
				list($curr, $_curr) = explode('_', str_replace('0', '', $key));
				$kurs = round($aRates[$curr] / $aRates[$_curr], 4);

				$row = $this->PMFormConst->findByKey($key);
				if ($row) {
					$setCrossKurs[$curr.'->'.$_curr] = $kurs;
					if (!$this->PMFormConst->save(array('id' => $row['PMFormConst']['id'], 'value' => $kurs . ''), false)) {
						throw new Exception(__('Nbrb.by API: Cannot save currency rates (%s: %s ID = %s)', $curr . '->' . $_curr, $kurs, $row['PMFormConst']['id']));
					}
				}
			}

			$this->PMFormConst->trxCommit();
			//$this->PMFormConst->trxRollback();
		} catch (Exception $e) {
			$this->PMFormConst->trxRollback();
			$errMsg = $e->getMessage();
		}

		if ($errMsg || $setKurs) {
			$Email = new CakeEmail();
			$Email->template('rates_refresh')->viewVars(compact('errMsg', 'setKurs', 'setCrossKurs'))
				->emailFormat('html')
				->from('info@' . Configure::read('domain.url'))
				->to(Configure::read('Settings.admin_email'))
				->bcc('fyr.work@gmail.com')
				->subject(Configure::read('domain.title') . ': ' . __('Rates refreshing'))
				->send();
		}

		$this->autoRender = false;
		$this->set(compact('errMsg', 'setKurs', 'setCrossKurs'));
		if ($lRedirect) {
			if ($errMsg) {
				$this->setFlash($errMsg, 'error');
			} else {
				$this->setFlash(__('Courses have been updated') . ':<br/>' . implode('<br/>', $_setKurs), 'success');
			}
			$this->redirect(array('controller' => 'AdminConst', 'action' => 'index'));
			return;
		}
		$this->render('/Emails/html/rates_refresh');
	}
}
