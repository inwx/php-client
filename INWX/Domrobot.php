<?php

namespace INWX;

/*
 * XML-RPC Inwx-Domrobot 
 * 
 * XML-RPC support in PHP is not enabled by default. 
 * You will need to use the --with-xmlrpc[=DIR] configuration option when compiling PHP to enable XML-RPC support.
 * 
 * Changelog:
 * ----------
 *
 * 2014/10/29
 *	  - Use Namespace and add Composer
 *
 * 2013/01/22 - v2.3
 * 	  - added google 2-step-verification methods
 * 	  - added parameter 'sharedSecret' to login method
 * 
 * 2012/04/08 - v2.2
 *    - use "nested" methods  (e.g. domain.check)
 *    - removed nonce and secure-login
 *    - added setter and getter
 *    - added credentials params to login function 
 *    - response utf-8 decoded 
 * 	  - removed newlines and white spaces in xml request (verbosity=no_white_space)
 *    - added optional clTRID set/get functions
 * 
 * 2011/07/19 - v2.1 
 * 	  - using cookiefile instead of session
 * 	  - added login and logout function
 * 	  - added client version transmission
 *    
 *   
 * by InterNetworX Ltd. & Co. KG
 */

class Domrobot 
{
	private $debug=false;
	private $address;
	private $language;
	private $customer=false;
	private $clTRID = null;

	private $_ver = "2.4";
	private $_cookiefile = "domrobot.tmp";

	function __construct($address) {
		$this->address = (substr($address,-1)!="/")?$address."/":$address;

		$seperator = (DIRECTORY_SEPARATOR=="/" || DIRECTORY_SEPARATOR=="\\")?DIRECTORY_SEPARATOR:"/";
		$this->_cookiefile = dirname(__FILE__).$seperator.$this->_cookiefile;

		if (file_exists($this->_cookiefile) && !is_writable($this->_cookiefile) ||
			!file_exists($this->_cookiefile) && !is_writeable(dirname(__FILE__))) {
			throw new \Exception("Cannot write cookiefile: '{$this->_cookiefile}'. Please check file/folder permissions.",2400);			
		}
	}

	public function setLanguage($language) {
		$this->language = $language;
	}
	public function getLanguage() {
		return $this->language;
	}
	
	public function setDebug($debug=false) {
		$this->debug = (bool)$debug;
	}
	public function getDebug() {
		return $debug;
	}
	
	public function setCustomer($customer) {
		$this->customer = (string)$customer;
	}
	public function getCustomer() {
		return $this->customer;
	}
	
	public function setClTrId($clTrId) {
		$this->clTRID = (string)$clTrId;
	}
	public function getClTrId() {
		return $this->clTRID;
	}

	public function login($username,$password,$sharedSecret=null) {
        $fp = fopen($this->_cookiefile, "w");
        fclose($fp);
        
        if (!empty($this->language)) {
			$params['lang'] = $this->language;
        }
		$params['user'] = $username;
		$params['pass'] = $password;
		
		$loginRes = $this->call('account','login',$params); 
		if (!empty($sharedSecret) && $loginRes['code']==1000 && !empty($loginRes['resData']['tfa'])) {
			$_tan = $this->_getSecretCode($sharedSecret);
			$unlockRes = $this->call('account','unlock',array('tan'=>$_tan));
			if ($unlockRes['code']==1000) {
				return $loginRes;
			} else {
				return $unlockRes;
			}
		} else {
			return $loginRes;
		}
	}

	public function logout() {
		$ret = $this->call('account','logout');
		if (file_exists($this->_cookiefile)) {
			unlink($this->_cookiefile);
		}
		return $ret;
	}

	public function call($object, $method, array $params=array()) {
		if (isset($this->customer) && $this->customer!="") {
			$params['subuser'] = $this->customer;
		}
		if (!empty($this->clTRID)) {
			$params['clTRID'] = $this->clTRID;
		}
		
		$request = xmlrpc_encode_request(strtolower($object.".".$method), $params, array("encoding"=>"UTF-8","escaping"=>"markup","verbosity"=>"no_white_space"));
	
		$header[] = "Content-Type: text/xml";   
		$header[] = "Connection: keep-alive";
		$header[] = "Keep-Alive: 300";
		$header[] = "X-FORWARDED-FOR: ".@$_SERVER['HTTP_X_FORWARDED_FOR'];
		$ch = curl_init();
		curl_setopt($ch,CURLOPT_URL,$this->address);
		curl_setopt($ch,CURLOPT_SSL_VERIFYPEER, false); 
		curl_setopt($ch,CURLOPT_TIMEOUT,65);
        curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
		curl_setopt($ch,CURLOPT_HTTPHEADER,$header);
		curl_setopt($ch,CURLOPT_COOKIEFILE,$this->_cookiefile);
		curl_setopt($ch,CURLOPT_COOKIEJAR,$this->_cookiefile);
		curl_setopt($ch,CURLOPT_POST,true);
        curl_setopt($ch,CURLOPT_POSTFIELDS,$request);
		curl_setopt($ch,CURLOPT_USERAGENT,"DomRobot/{$this->_ver} (PHP ".phpversion().")");

		$response = curl_exec($ch);
		curl_close($ch); 
		if ($this->debug) {
			echo "Request:\n".$request."\n";
			echo "Response:\n".$response."\n";
		}

		return xmlrpc_decode($response,'UTF-8');
	}
	
	private function _getSecretCode($secret) {
		$_timeSlice = floor(time() / 30);
		$_codeLength = 6;
	
		$secretKey = $this->_base32Decode($secret);
		// Pack time into binary string
		$time = chr(0).chr(0).chr(0).chr(0).pack('N*', $_timeSlice);
		// Hash it with users secret key
		$hm = hash_hmac('SHA1', $time, $secretKey, true);
		// Use last nipple of result as index/offset
		$offset = ord(substr($hm, -1)) & 0x0F;
		// grab 4 bytes of the result
		$hashPart = substr($hm, $offset, 4);
	
		// Unpak binary value
		$value = unpack('N', $hashPart);
		$value = $value[1];
		// Only 32 bits
		$value = $value & 0x7FFFFFFF;
	
		$modulo = pow(10, $_codeLength);
		return str_pad($value % $modulo, $_codeLength, '0', STR_PAD_LEFT);
	}
	
	private function _base32Decode($secret) {
		if (empty($secret)) return '';
	
		$base32chars = $this->_getBase32LookupTable();
		$base32charsFlipped = array_flip($base32chars);
	
		$paddingCharCount = substr_count($secret, $base32chars[32]);
		$allowedValues = array(6, 4, 3, 1, 0);
		if (!in_array($paddingCharCount, $allowedValues)) return false;
		for ($i = 0; $i < 4; $i++){
			if ($paddingCharCount == $allowedValues[$i] &&
					substr($secret, -($allowedValues[$i])) != str_repeat($base32chars[32], $allowedValues[$i])) return false;
		}
		$secret = str_replace('=','', $secret);
		$secret = str_split($secret);
		$binaryString = "";
		for ($i = 0; $i < count($secret); $i = $i+8) {
			$x = "";
			if (!in_array($secret[$i], $base32chars)) return false;
			for ($j = 0; $j < 8; $j++) {
				$x .= str_pad(base_convert(@$base32charsFlipped[@$secret[$i + $j]], 10, 2), 5, '0', STR_PAD_LEFT);
			}
			$eightBits = str_split($x, 8);
			for ($z = 0; $z < count($eightBits); $z++) {
				$binaryString .= ( ($y = chr(base_convert($eightBits[$z], 2, 10))) || ord($y) == 48 ) ? $y:"";
			}
		}
		return $binaryString;
	}
	
	private function _getBase32LookupTable() {
		return array(
				'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', // 7
				'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', // 15
				'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', // 23
				'Y', 'Z', '2', '3', '4', '5', '6', '7', // 31
				'=' // padding char
		);
	}
}
?>
