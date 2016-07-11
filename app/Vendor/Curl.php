<?php
class Curl {
	// methods
	const GET = 1;
	const POST = 2;
	
	// format
	const HTTP = 1;
	const JSON = 2;
	
	private $url = '';
	private $aOptions = array();
	private $cookieJar = '';
	private $cookieFile = '';
	private $logFile = '';
	private $method, $format;
	private $params = array();
	private $status = array();
	
	public function __construct($sUrl = '', $aCurlOpts = array()) {
		$this->setUrl($sUrl);
		$this->method = self::GET;
		$this->format = self::HTTP;
		
		// set default options for getting a page
		if (!$aCurlOpts) {
			$aCurlOpts = array(
				CURLOPT_HEADER => false,
				CURLOPT_RETURNTRANSFER => 1,
				CURLOPT_TIMEOUT => 60
			);
		}
		$this->setOptions($aCurlOpts);
	}
	
	public function setUrl($sUrl) {
		$this->url = $sUrl;
		return $this;
	}
	
	public function setOption($curlOption, $value) {
		$this->aOptions[$curlOption] = $value;
		return $this;
	}

	public function setOptions($aOpts = array()) {
		foreach($aOpts as $key => $value) {
			$this->setOption($key, $value);
		}
		return $this;
	}

	/*
	function setCookieJar($sCookieJar) {
		$this->cookieJar = $sCookieJar;
	}
	
	function setCookieFile($sCookiefile) {
		$this->cookieFile = $sCookieFile;
	}
	*/
	
	public function setParam($key, $val) {
		$this->params[$key] = $val;
		return $this;
	}
	
	public function setParams($aParams) {
		$this->params = $aParams;
		return $this;
	}
	
	public function setMethod($method) {
		$this->method = $method;
		return $this;
	}
	
	public function setFormat($format) {
		$this->format = $format;
		return $this;
	}
	
	function sendRequest() {
		$this->error = '';
		
		$curl = curl_init($this->url);
		$this->processParams();
		foreach($this->aOptions as $key => $value) {
			curl_setopt($curl, $key, $value);
		}
		
		$response = curl_exec($curl);
		$this->status['errCode'] = curl_errno($curl);
		$this->status['errMsg'] = curl_error($curl);
		$this->status['info'] = curl_getinfo($curl);
		curl_close($curl);
		if ($this->status['errMsg']) {
	    	throw new Exception($this->status['errMsg'], $this->status['errCode']);
		}
		
		if (strpos($response, 'HTTP/1.1 404 Not Found') !== false) {
			throw new Exception('HTTP/1.1 404 Not Found', 404);
		}
		return $this->processResponse($response);
	}
	
	public function getStatus() {
		return $this->status;
	}
	
	private function processParams() {
		if (!$this->params) {
			return;
		}
		
		if ($this->method == self::GET) {
			$this->setOption(CURLOPT_URL, $this->url.'?'.http_build_query($this->params));
		} elseif ($this->method == self::POST) {
			if ($this->format == self::JSON) {
				$params = json_encode($this->params);
				$this->setOption(CURLOPT_HTTPHEADER, array(                                                                          
					'Content-Type: application/json',                                                                                
					'Content-Length: '.strlen($params))
				);
			} else {
				$params = http_build_query($this->params);
			}
			$this->setOption(CURLOPT_POST, true);
			$this->setOption(CURLOPT_POSTFIELDS, $params);
		}
	}
	
	private function processResponse($response) {
		/*
		if ($this->format == self::JSON) {
			if (!trim($response)) {
				throw new Exception('Incorrect JSON response', 0);
			}
			$response = json_decode($response, true);
		}
		*/
		return $response;
	}

/*
	function loadFile($url = '', $sFName = '', $aOpts = array()) {
		$this->setOptions(array(CURLOPT_HEADER => false, CURLOPT_BINARYTRANSFER => 1));
		if ($this->sendRequest($url) && $sFName) {
			$fh = fopen($sFName, 'w');
			fwrite($fh, $this->content);
			fclose($fh);
			return is_readable($sFName);
		}
	}
	
	function logFile($url, $content) {
		$fh = fopen($this->logFile, 'a');
		$div1 = str_repeat('-', 70);
		$div2= str_repeat('=', 70);
		fwrite($fh, $url."\r\n".$div1."\r\n".$content."\r\n".$div2."\r\n");
		fclose($fh);
	}
	*/
}
?>