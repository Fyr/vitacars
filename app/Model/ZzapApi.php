<?php
App::uses('AppModel', 'Model');
App::uses('ProxyUse', 'Model');
App::uses('ZzapCache', 'Model');
App::uses('GpzOffer', 'Model');
App::uses('Curl', 'Vendor');

class ZzapApi extends AppModel {
	
	public $useTable = false;
	
	const MAX_ROW_SUGGEST = 20;
	const MAX_ROW_PRICE = 100;
	
	private function writeLog($actionType, $data = ''){
		if (Configure::read('ZzapApi.txtLog')) {
			$string = date('d-m-Y H:i:s') . ' ' . $actionType . ' ' . $data;
			file_put_contents(Configure::read('ZzapApi.log'), $string . "\r\n", FILE_APPEND);
		}
	}
	
	private function sendApiRequest($method, $data){
		$url = Configure::read('ZzapApi.url').$method;
		$data['api_key'] = Configure::read('ZzapApi.key');
		$request = json_encode($data);
		
		// Определяем идет ли это запрос от поискового бота
		$ip = $_SERVER['REMOTE_ADDR'];
		$proxy_type = ($this->isBot($ip)) ? 'Bot' : 'Site';
		if ($proxy_type == 'Bot' || TEST_ENV) {
			// пытаемся достать инфу из кэша без запроса на API - так быстрее и не нужно юзать прокси
			$_cache = $this->loadModel('ZzapCache')->getCache($method, $request);
			if ($_cache) {
				if (Configure::read('ZzapApi.dbLog')) {
					$this->loadModel('ZzapLog')->clear();
					$this->loadModel('ZzapLog')->save(array(
						'ip_type' => $proxy_type,
						'ip' => $ip,
						'host' => gethostbyaddr($ip),
						'ip_details' => json_encode($_SERVER),
						'method' => $method,
						'request' => $request,
						'response_type' => 'CACHE',
						'cache_id' => $_cache['ZzapCache']['id'],
						'cache' => $_cache['ZzapCache']['response']
					));
				}
				return json_decode($_cache['ZzapCache']['response'], true);
			}
		}
		
		$curl = new Curl($url);
		$curl->setParams($data)
			->setMethod(Curl::POST)
			->setFormat(Curl::JSON);
		// этого уже достаточно чтобы отправить запрос
		
		// если бот - перенаправляем на др.прокси-сервера для ботов - снимаем нагрузку с прокси для сайта
		$proxy = $this->loadModel('ProxyUse')->getProxy($proxy_type);
		$this->loadModel('ProxyUse')->useProxy($proxy['ProxyUse']['host']);
		
		$curl->setOption(CURLOPT_PROXY, $proxy['ProxyUse']['host'])
			->setOption(CURLOPT_PROXYUSERPWD, $proxy['ProxyUse']['login'].':'.$proxy['ProxyUse']['password']);
		
		$response = $_response = '';
		$responseType = 'OK';
		try {
			// перед запросом - логируем
			$this->writeLog('REQUEST', "PROXY: {$proxy['ProxyUse']['host']} URL: {$url}; DATA: {$request}");
			
			$response = $_response = $curl->sendRequest();
			
			// логируем сразу после запроса
			$this->writeLog('RESPONSE', "PROXY: {$proxy['ProxyUse']['host']} DATA: {$_response}");
			
		} catch (Exception $e) {
			// отдельно логируем ошибки Curl
			$status = json_encode($curl->getStatus());
			$this->writeLog('ERROR', "PROXY: {$proxy['ProxyUse']['host']} STATUS: {$status}");
			$responseType = 'ERROR';
		}
		
		$cache_id = null;
		$cache = '';
		$e = null;
		try {
			$response = json_decode($response, true);
			if (!$response || !isset($response['d'])) {
				throw new Exception(__('API Server error'));
			}
			
			$content = json_decode($response['d'], true);
			
			if (!isset($content['table']) || $content['error']){
				throw new Exception(__('API Server response error: %s', $content['error'])); 
			}
			
			// если все хорошо - сохраняем ответ в кэше
			$this->loadModel('ZzapCache')->setCache($method, $request, $response['d']);
		} catch (Exception $e) {
			if ($responseType == 'OK') {
				// была ошибка ответа
				$responseType = 'RESPONSE_ERROR';
			}
			// пытаемся достать ответ из кэша
			$_cache = $this->loadModel('ZzapCache')->getCache($method, $request);
			if ($_cache) {
				$cache_id = $_cache['ZzapCache']['id'];
				$cache = $_cache['ZzapCache']['response'];
				$this->writeLog('LOAD CACHE', "PROXY: {$proxy['ProxyUse']['host']} DATA: {$cache}");
				$content = json_decode($cache, true);
				
				$e = null; // сбрасываем ошибку - мы восттановили инфу из кэша
			} else {
				$content = array();
			}
		}
		
		// Логируем всю инфу для статистики
		if (Configure::read('ZzapApi.dbLog')) {
			$this->loadModel('ZzapLog')->clear();
			$this->loadModel('ZzapLog')->save(array(
				'ip_type' => $proxy_type,
				'ip' => $ip,
				'host' => gethostbyaddr($ip),
				'ip_details' => json_encode($_SERVER),
				'proxy_used' => $proxy['ProxyUse']['host'],
				'method' => $method,
				'request' => $request,
				'response_type' => $responseType,
				'response_status' => json_encode($curl->getStatus()),
				'response' => $_response,
				'cache_id' => $cache_id,
				'cache' => $cache
			));
		}
		
		if ($e) {
			throw $e; // повторно кидаем ошибку чтоб ее показать
		}
		
		return $content;
	}
	
	/* // Старый метод получения всех цен
	public function getSuggests($searchString) {
		$dataArray = array(
			'search_text' => $searchString,
			'row_count'=>  self::MAX_ROW_SUGGEST
		);
		$this->content = $this->sendApiRequest('GetSearchSuggest', $dataArray);

		if($this->content['table']){
			//ограничение в АПИ не работает - поэтому срезаем
			$this->content['table'] = array_slice($this->content['table'], 0, self::MAX_ROW_SUGGEST);
			$this->multiCurlPrice($this->content['table']);
			$this->content['table'] = Hash::sort($this->content['table'], '{n}.price', 'desc'); 
		}
		return $this->content;
	}
	*/
	public function getSuggests($searchString) {
		$data = array(
			'search_text' => $searchString,
			'row_count'=>  self::MAX_ROW_SUGGEST
		);
		$response = $this->sendApiRequest('GetSearchSuggest', $data);
		if ($response['table']){
			//ограничение в АПИ не работает - поэтому срезаем
			// $content['table'] = array_slice($content['table'], 0, self::MAX_ROW_SUGGEST);
			// $content = Hash::combine($this->content['table'], '{n}.code_cat', '{n}');
			return $this->processSuggests($response['table']);
		}
		return array();
	}
	
	private function processSuggests($table) {
		$aData = array();
		foreach($table as $i => $item) {
			$aData[] = array(
				'provider' => 'Zzap',
				'provider_data' => $item,
				'brand' => $item['class_man'],
				'brand_logo' => $item['logopath'],
				'partnumber' => $item['partnumber'],
				'image' => $item['imagepath'],
				'title' => $item['class_cat'],
				'title_descr' => ''
			);
		}
		return $aData;
	}
	
	private function getStatPrices($aCodes) {
		$data = array(
			'codes_cat' => implode(';', $aCodes),
			'code_region' => 1,
			'instock' => 1,
			'wholesale' => '0'
		);
		$response = $this->sendApiRequest('GetStatPrices', $data);
		$prices = Hash::combine($response['table'], '{n}.code_cat', '{n}'); // Берем min.цену
		
		$data = array(
			'codes_cat' => implode(';', $aCodes),
			'code_region' => 1,
			'instock' => 0,
			'wholesale' => '0'
		);
		$response = $this->sendApiRequest('GetStatPrices', $data);
		$prices2 = Hash::combine($response['table'], '{n}.code_cat', '{n}');
		
		return Hash::merge($prices, $prices2);
	}
	/*
	private function getSuggestPriceAndShipping($priceResponse){
		
		if(!$priceResponse){
			return 0; 
		}
		
		$priceContent = json_decode($priceResponse);
		if(!$priceContent or !isset($priceContent->d)){
			return 0;
		}
		
		$priceResult = json_decode($priceContent->d,true);	
		if(!isset($priceResult['table']) or !$priceResult['table']){
			return 0;
		}
		
		return $this->getPriceAndShipping($priceResult['table']);
		
	}
	
	private function getPriceAndShipping($priceResult){
		$prices = array();
		$result['shipping'] = false;
		$result['price'] = 0;
		foreach ($priceResult as $id=>$priceRow){
			$priceRow['price'] = preg_replace('/\D/', '', $priceRow['price']);
			$priceRow['qty'] = preg_replace('/\D/', '', $priceRow['qty']);
			
			if($priceRow['price']>0 and $priceRow['qty']>0){
				$prices[$id] = $priceRow['price'];
			}
		}
		if(!$prices){
			return $result;
		}
		//переводим процент в десятичный коэффициент
		$minPrice = min($prices);
		$result['price'] = $minPrice;
		$rowId = array_search($minPrice, $prices);
		$result['shipping'] = $priceResult[$rowId]['descr_qty']; 
		
		return $result;
	}
	
	
	private function getRequestPriceBody($classman,$partnumber){
		$dataArray = array(
			'login' => '',
			'password'=> '',
			'partnumber' => $partnumber,
			'class_man' => $classman,
			'location' => '',
			'row_count' => self::MAX_ROW_PRICE,
			'api_key' => Configure::read('ZzapApi.key')
		);
		return json_encode($dataArray);
	}

	private function multiCurlPrice($suggestTable){
		$url = Configure::read('ZzapApi.url').'GetSearchResult';
		$multi = curl_multi_init();
		$channels = array();
		
		$aProxy = Configure::read('proxy.list');
		foreach ($suggestTable as $i => $suggest) {
			$data[$i] = $this->getRequestPriceBody($suggest['class_man'], $suggest['partnumber']);
			$curl = curl_init();  
			curl_setopt($curl, CURLOPT_URL, $url);
			curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST");
			curl_setopt($curl, CURLOPT_POSTFIELDS, $data[$i]);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($curl, CURLOPT_HTTPHEADER, array(
				'Content-Type: application/json',
				'Content-Length: ' . strlen($data[$i]))
			);
 
			// add proxy server to get different IP
			$proxy = trim($aProxy[$i]);
			curl_setopt($curl, CURLOPT_PROXY, $proxy);
			
			curl_multi_add_handle($multi, $curl);
			$channels[$i] = $curl;
			
			$i++;
		}
		
		$active = null;

		do {
			$mrc = curl_multi_exec($multi, $active);
		}while ($mrc == CURLM_CALL_MULTI_PERFORM);
 
		while ($active && ($mrc == CURLM_OK)) {
			if (curl_multi_select($multi) != -1) {
				do {
					$mrc = curl_multi_exec($multi, $active);
					$info = curl_multi_info_read($multi);
					if ($info['msg'] == CURLMSG_DONE) {
						$ch = $info['handle'];
						$i = array_search($ch, $channels);
						$proxy = trim($aProxy[$i]);
						$this->writeLog('REQUEST', "URL: {$url}; PROXY: {$proxy} DATA: {$data[$i]}");
						$priceContent = curl_multi_getcontent($ch);
						$this->writeLog('RESPONSE', $priceContent);
						$priceShippingResult = $this->getSuggestPriceAndShipping($priceContent);
						
						if (!$priceShippingResult) {
							$this->writeLog('PROXY FAILED', $proxy);
							
							$proxyLog = Configure::read('proxy.logs').'proxy_failed.log';
							file_put_contents($proxyLog, trim($aProxy[$i])."\r\n", FILE_APPEND);
						}
						
						$this->content['table'][$i]['price'] = $priceShippingResult['price'];
						$this->content['table'][$i]['shipping'] = $priceShippingResult['shipping'];
						curl_multi_remove_handle($multi, $ch);
						curl_close($ch);
					}
				}
				while ($mrc == CURLM_CALL_MULTI_PERFORM);
			}
		}
		curl_multi_close($multi);
	}
	*/
	public function getItemInfo($classman, $partnumber){
		$data = array(
			'login' => '',
			'password'=> '',
			'location' => '',
			'class_man' => $classman,
			'partnumber' => $partnumber,
			'row_count'=>  self::MAX_ROW_PRICE
		);
		$response = $this->sendApiRequest('GetSearchResult', $data);
		return $this->processPriceTable($response['table'], $partnumber);
	}
	
	private function processPriceTable($table, $partnumber) {
		$aData = array();
		foreach($table as $i => $item) {
			$offerType = GpzOffer::ANALOG;
			if ($item['partnumber'] === $partnumber) {
				$offerType = ($item['descr_type_search'] == 'Запрошенный номер (cпец. предложения)') ? GpzOffer::FEATURED_ORIGINAL : GpzOffer::ORIGINAL;
			}
			$price = intval(preg_replace('/\D/', '', $item['price']));
			$aData[] = array(
				'provider' => 'Zzap',
				'provider_data' => $item,
				'offer_type' => $offerType,
				'brand' => $item['class_man'],
				'brand_logo' => $item['logopath'],
				'partnumber' => $item['partnumber'],
				'image' => $item['imagepath'],
				'title' => $item['class_cat'],
				'title_descr' => '',
				'qty' => $item['qty'],
				'qty_descr' => $item['descr_qty'],
				'qty_order' => $this->getQtyDescr($item),
				'price' => $this->getPrice($item),
				'price2' => $this->getPrice2($item),
				'price_orig' => $item['price'],
				'price_descr' => $item['descr_price'].'<br/>Цена поставщика в RUR',
				'provider_descr' => implode('<br/>', array($item['class_user'], $item['descr_address'], $item['phone1']))
			);
		}
		return $aData;
	}
	
	/**
	 * Оригинальная цена без наценки
	 */
	private function getPrice($item) {
		$price = floatval(str_replace(array('р.', ' '), '', $item['price']));
		$currency = Configure::read('Settings.price_currency'); // валюта в которой показываем цену
		$rate = Configure::read('Settings.xchg_'.$currency);
		if ($currency == 'byr') {
			$rate = $rate / 10000; // коррекция курса
		}
		$round_by = Configure::read('Settings.round_'.$currency);
		return round($price / $rate, $round_by); // переводим по курсу из настроек
	}
	
	/**
	 * Цена в BYR с наценкой
	 */
	private function getPrice2($item) {
		$priceRatio = 1 + (Configure::read('Settings.zz_price_ratio')/100);
		$currency = Configure::read('Settings.price_currency');
		$round_by = Configure::read('Settings.round_'.$currency);
		return round($priceRatio * $this->getPrice($item), $round_by);
	}
	
	private function getQtyDescr($item) {
		preg_match('/[0-9\-]+/', $item['descr_qty'], $match);
		if (isset($match[0]) && $match[0]) {
			return $match[0];
		}
		return '';
	}
}