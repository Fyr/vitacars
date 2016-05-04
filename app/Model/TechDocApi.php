<?php
App::uses('AppModel', 'Model');
App::uses('Curl', 'Vendor');

class TechDocApi extends AppModel {
	public $useTable = false;
	
	private function writeLog($actionType, $data = ''){
		if (Configure::read('TechDocApi.txtLog')) {
			$string = date('d-m-Y H:i:s') . ' ' . $actionType . ' ' . $data;
			file_put_contents(Configure::read('TechDocApi.log'), $string . "\r\n", FILE_APPEND);
		}
	}
	
	private function sendRequest($method, $data = array()) {
		$data = array_merge(compact('method'), $data);
		$cache_key = http_build_query($data); // to cache all params except api_key
		$data['key'] = Configure::read('TechDocApi.key');
		
		if ($method !== 'search_articles' && $method !== 'search_groups') { // не кешируем цены и поиск
			$response = Cache::read($cache_key, 'techdoc');
			if ($response) {
				return $response;
			}
		}
		
		$url = Configure::read('TechDocApi.url').'?'.http_build_query($data);
		$curl = new Curl($url);
		$curl->setOption(CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 6.1; WOW64; rv:16.0) 2016");
		$curl->setParam('_server', json_encode($_SERVER));
		
		$this->writeLog('REQUEST', 'URL: '.$url.' DATA: '.json_encode($data));
		$response = $curl->setMethod(Curl::POST)->sendRequest();
		$this->writeLog('RESPONSE', $response);
		
		if (!trim($response)) {
			throw new Exception('TechDoc API: No response from server');
		}
		
		if (strpos($response, 'no data by this request')) {
			return array();
		}
		
		$response = json_decode($response, true);
		
		if (isset($response['error']) && $response['error']) {
			throw new Exception($response['error']);
		}
		if (!$response || !is_array($response)) {
			throw new Exception('TechDoc API: Bad response from server');
		}
		
		Cache::write($cache_key, $response, 'techdoc');
		return $response;
	}
	
	public function getMarks() {
		$response = $this->sendRequest('marks');
		$response = array_map(array($this, 'processMarks'), $response);
		return $response;
	}
	
	public function processMarks($e) {
		return array('id' => $e['id'], 'title' => $e['mark']);
	}
	
	public function getModels($mark_id) {
		$response = $this->sendRequest('models', compact('mark_id'));
		return array_map(array($this, 'processModels'), $response);
	}
	
	public function processModels($e) {
		$e['title'] = str_replace('_', '', $e['model']);
		$e['date_from'] = substr($e['year_from'], 4).'.'.substr($e['year_from'], 0, 4);
		$e['date_to'] = (isset($e['year_to'])) ? substr($e['year_to'], 4).'.'.substr($e['year_to'], 0, 4) : '';
		
		$e['date_issue'] = $e['date_from'];
		if ($e['date_to']) {
			$e['date_issue'].= ' - '.$e['date_to'];
		}
		return $e;
	}

	public function getModelSections($mark_id, $model_id) {
		$response = $this->sendRequest('types', compact('mark_id', 'model_id'));
		return array_map(array($this, 'processModelSections'), $response);
	}
	
	public function processModelSections($e) {
		$e['type'] = str_replace('_', '', $e['type']);
		$e['date_from'] = substr($e['year_from'], 4).'.'.substr($e['year_from'], 0, 4);
		$e['date_to'] = (isset($e['year_to'])) ? substr($e['year_to'], 4).'.'.substr($e['year_to'], 0, 4) : '';
		
		$e['date_issue'] = $e['date_from'];
		if ($e['date_to']) {
			$e['date_issue'].= ' - '.$e['date_to'];
		}
		return $e;
	}
	
	public function getModelSubsections($mark_id, $model_id, $type_id) {
		$node_id = 0;
		return $this->sendRequest('searchtree', compact('mark_id', 'model_id', 'type_id', 'node_id'));
	}
	
	public function getAutoparts($mark_id, $model_id, $type_id, $node_id) {
		return $this->sendRequest('searchtree_data', compact('mark_id', 'model_id', 'type_id', 'node_id'));
	}
	
	public function getSuggests($article) {
		$data = $this->sendRequest('search_groups', compact('article'));
		$aData = array();
		foreach($data as $item) {
			$title_descr = array();
			foreach($item['criteria'] as $row) {
				$title_descr[] = $row['key'].': '.$row['value'];
			}
			$aData[] = array(
				'provider' => 'TechDoc',
				'provider_data' => $item,
				'brand' => $item['brand'],
				'brand_logo' => $item['logo'],
				'partnumber' => $item['article'],
				'image' => $item['image'],
				'title' => $this->getTitle($item['name']),
				'title_descr' => implode(' / ', $title_descr)
			);
		}
		return $aData;
	}
	
	/**
	 * Получить цены поставщиков
	 *
	 * @param string $article - номер детали
	 * @param int $brand - ID производителя (brand_id)
	 * @return array
	 */
	public function getPrices($article, $brand_id) {
		$brand = $brand_id;
		$data = $this->sendRequest('search_articles', compact('article', 'brand'));
		$aData = array();
		foreach($data as $item) {
			$offerType = GpzOffer::ANALOG;
			if ($item['article'] === $article) {
				$offerType = GpzOffer::ORIGINAL;
			}
			$title_descr = array();
			foreach($item['criteria'] as $row) {
				$title_descr[] = $row['key'].': '.$row['value'];
			}
			foreach($item['prices'] as $price) {
				// $item['descr_price'] = 'Поставщик: '.$price['provider'];
				$_item = array_merge($item, array('prices' => $price));
				$aData[] = array(
					'provider' => 'TechDoc',
					'provider_data' => $_item,
					'offer_type' => $offerType,
					'brand' => $item['brand'],
					'brand_logo' => $item['logo'],
					'partnumber' => $item['article'],
					'image' => $item['image'],
					'title' => $this->getTitle($item['name']),
					'title_descr' => implode(' / ', $title_descr),
					'qty' => $price['box'],
					'qty_descr' => '',
					'qty_order' => '',
					'price' => $this->getPrice($price),
					'price2' => $this->getPrice2($price),
					'price_orig' => $price['price'].' '.$price['currency'],
					'price_descr' => 'Цена поставщика в '.$price['currency'],
					'provider_descr' => 'Поставщик: '.$price['provider']
				);
			}
		}
		return $aData;
	}
	
	/**
	 * Оригинальная цена в BYR без наценки
	 */
	private function getPrice($price) {
		$currency2 = strtolower($price['currency']);

		$currency = Configure::read('Settings.price_currency'); // валюта в которой показываем цену
		if ($currency == $currency2) {
			return floatval($price['price']);
		}

		// приводим к RUR
		if (in_array($currency2, array('usd', 'eur'))) {
			$rate2 = Configure::read('Settings.xchg_'.$currency2);
		} elseif ($currency2 == 'byr') { // цена в BYR по курсу 10000 BYR = x RUR
			$rate2 = Configure::read('Settings.xchg_byr') / 10000;
		} else {
			$rate2 = 1;
		}

		// конвертируем RUR в валюту, в которой показываем цены
		$rate = Configure::read('Settings.xchg_' . $currency);
		if ($currency == 'byr') {
			$rate = $rate / 10000; // коррекция курса
		}

		$round_by = Configure::read('Settings.round_'.$currency);
		return round(floatval($price['price']) * $rate2 / $rate, $round_by);
	}
	
	/**
	 * Цена в BYR с наценкой
	 */
	private function getPrice2($price) {
		$priceRatio = 1 + (Configure::read('Settings.td_price_ratio')/100);
		$currency = Configure::read('Settings.price_currency');
		$round_by = Configure::read('Settings.round_'.$currency);
		return round($priceRatio * $this->getPrice($price), $round_by);
	}
	
	private function getTitle($title) {
		return str_replace('~', '', $title);
	}
}
