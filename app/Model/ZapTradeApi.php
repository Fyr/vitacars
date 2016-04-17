<?php
App::uses('AppModel', 'Model');

class ZapTradeApi extends AppModel {
	public $useTable = false;
	
	private function writeLog($actionType, $data = ''){
		if (Configure::read('ZapTradeApi.txtLog')) {
			$string = date('d-m-Y H:i:s') . ' ' . $actionType . ' ' . $data;
			file_put_contents(Configure::read('ZapTradeApi.log'), $string . "\r\n", FILE_APPEND);
		}
	}
	
	private function sendRequest($method, $data = array()) {
		error_reporting(0);
		ini_set('default_socket_timeout', 30);
		
		$options = array(
			"soap_version" => SOAP_1_2,
			"encoding" => "utf-8" // windows-1251
		);
		$soapClient = new SoapClient(Configure::read('ZapTradeApi.url'), $options);
		$userData = array(
			'email' => Configure::read('ZapTradeApi.username'), 
			'password' => Configure::read('ZapTradeApi.password')
		);
		$response = (array) $soapClient->$method($userData, $data);
		
		$this->writeLog('REQUEST', json_encode($data)."\r\n".$soapClient->__getLastRequest());
		$this->writeLog('RESPONSE', json_encode($response));
		
		if (!$response) {
			throw new Exception('ZapTradeAPI: No response from server');
		}
		
		if (isset($response['error'])) {
			throw new Exception('ZapTradeAPI: '.$response['error']);
		}
		return $response;
	}
	
	/**
	 * Получить цены поставщиков
	 *
	 * @param string $article - номер детали
	 * @param int $brand - производитель
	 * @return array
	 */
	public function getPrices($article, $brand = '') {
		$params = array("article" => $article, "findSubstitutes" => true, 'showPartSource' => true);
		$data = $this->sendRequest('findDetail', $params);
		
		if (!isset($data['parts'])) {
			throw new Exception('ZapTradeAPI: Bad server response');
		}
		$aData = array();
		$data['parts'] = (array) $data['parts'];
		if (!isset($data['parts'][0])) {
			$data['parts'] = array($data['parts']);
		}
		foreach($data['parts'] as $item) {
			$offerType = GpzOffer::ANALOG;
			if ($item['article'] === $article) {
				$offerType = GpzOffer::ORIGINAL;
			}
		
			$aData[] = array(
				'provider' => 'ZapTrade',
				'provider_data' => $item,
				'offer_type' => $offerType,
				'brand' => $item['makerName'],
				'brand_logo' => '',
				'partnumber' => $item['detailNum'],
				'image' => '',
				'title' => $item['detailName'],
				'title_descr' => '',
				'qty' => $item['quantity'],
				'qty_descr' => (is_numeric($item['quantity'])) ? 'Минимальный заказ: '.$item['quantity'] : '',
				'qty_order' => '',
				'price' => $this->getPrice($item), 
				'price2' => $this->getPrice2($item),
				'price_orig' => $item['price'].' RUR',
				'price_descr' => 'Цены поставщиков в RUR. Формирование цены - см. настройки ZapTrade + GiperZap',
				'provider_descr' => 'Поставщик: '.$item['source']
			);
		}
		return $aData;
	}
	
	/**
	 * Оригинальная цена в BYR без наценки
	 */
	private function getPrice($item) {
		$price = floatval($item['price']);
		return round(Configure::read('Settings.xchg_rur') * $price, -2); // переводим в BYR по курсу из настроек
	}
	
	/**
	 * Цена в BYR с наценкой
	 */
	private function getPrice2($item) {
		$priceRatio = 1 + (Configure::read('Settings.zt_price_ratio')/100);
		return round($priceRatio * $this->getPrice($item), -2);
	}
}
