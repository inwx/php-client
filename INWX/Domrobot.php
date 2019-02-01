<?php

namespace INWX;

class Domrobot
{
    private $debug = false;
    private $address;
    private $language;
    private $customer = false;
    private $clTRID = null;

    private $_ver = "2.4.1";
    private $_cookiefile = NULL;

    function __construct($address)
    {
        $this->address = (substr($address, -1) != "/") ? $address . "/" : $address;
        $this->_cookiefile = tempnam(sys_get_temp_dir(), 'INWX');
    }

    public function setLanguage($language)
    {
        $this->language = $language;
    }

    public function getLanguage()
    {
        return $this->language;
    }

    public function setDebug($debug = false)
    {
        $this->debug = (bool) $debug;
    }

    public function getDebug()
    {
        return $this->debug;
    }

    public function setCookiefile($file)
    {
        if ((file_exists($file) && !is_writable($file)) || (!file_exists($file) && !is_writeable(dirname($file)))) {
            throw new \Exception("Cannot write cookie file: '" . $file . "'. Please check file/folder permissions.", 2400);
        }

        $this->_cookiefile = $file;
    }

    public function getCookiefile()
    {
        return $this->_cookiefile;
    }

    public function setCustomer($customer)
    {
        $this->customer = (string)$customer;
    }

    public function getCustomer()
    {
        return $this->customer;
    }

    public function setClTrId($clTrId)
    {
        $this->clTRID = (string)$clTrId;
    }

    public function getClTrId()
    {
        return $this->clTRID;
    }

    public function login($username, $password, $sharedSecret = null)
    {
        $fp = fopen($this->_cookiefile, "w");
        fclose($fp);

        if (!empty($this->language)) {
            $params['lang'] = $this->language;
        }
        $params['user'] = $username;
        $params['pass'] = $password;

        $loginRes = $this->call('account', 'login', $params);
        if (!empty($sharedSecret) && $loginRes['code'] == 1000 && !empty($loginRes['resData']['tfa'])) {
            $_tan = $this->_getSecretCode($sharedSecret);
            $unlockRes = $this->call('account', 'unlock', ['tan' => $_tan]);
            if ($unlockRes['code'] == 1000) {
                return $loginRes;
            } else {
                return $unlockRes;
            }
        } else {
            return $loginRes;
        }
    }

    public function logout()
    {
        $ret = $this->call('account', 'logout');
        if (file_exists($this->_cookiefile)) {
            unlink($this->_cookiefile);
        }
        return $ret;
    }

    public function call($object, $method, array $params = [])
    {
        if (isset($this->customer) && $this->customer != "") {
            $params['subuser'] = $this->customer;
        }
        if (!empty($this->clTRID)) {
            $params['clTRID'] = $this->clTRID;
        }

        $useJSON = preg_match('!/jsonrpc!', $this->address);

        if ($useJSON) {
            $request = json_encode(['method' => $object . "." . $method, 'params' => $params]);
        } else {
            $request = xmlrpc_encode_request(strtolower($object . "." . $method), $params, ["encoding" => "UTF-8", "escaping" => "markup", "verbosity" => "no_white_space"]);
        }


        $header[] = "Content-Type: text/xml";
        $header[] = "Connection: keep-alive";
        $header[] = "Keep-Alive: 300";
        $header[] = "X-FORWARDED-FOR: " . @$_SERVER['HTTP_X_FORWARDED_FOR'];
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->address);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 65);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $this->_cookiefile);
        curl_setopt($ch, CURLOPT_COOKIEJAR, $this->_cookiefile);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $request);
        curl_setopt($ch, CURLOPT_USERAGENT, "DomRobot/{$this->_ver} (PHP " . phpversion() . ")");

        $response = curl_exec($ch);
        curl_close($ch);
        if ($this->debug) {
            echo "Request:\n" . $request . "\n";
            echo "Response:\n" . $response . "\n";
        }

        if ($useJSON) {
            return json_decode($response);
        } else {
            return xmlrpc_decode($response, 'UTF-8');
        }
    }

    private function _getSecretCode($secret)
    {
        $_timeSlice = floor(time() / 30);
        $_codeLength = 6;

        $secretKey = $this->_base32Decode($secret);
        // Pack time into binary string
        $time = chr(0) . chr(0) . chr(0) . chr(0) . pack('N*', $_timeSlice);
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

    private function _base32Decode($secret)
    {
        if (empty($secret)) return '';

        $base32chars = $this->_getBase32LookupTable();
        $base32charsFlipped = array_flip($base32chars);

        $paddingCharCount = substr_count($secret, $base32chars[32]);
        $allowedValues = [6, 4, 3, 1, 0];
        if (!in_array($paddingCharCount, $allowedValues)) return false;
        for ($i = 0; $i < 4; $i++) {
            if ($paddingCharCount == $allowedValues[$i] &&
                substr($secret, -($allowedValues[$i])) != str_repeat($base32chars[32], $allowedValues[$i])) return false;
        }
        $secret = str_replace('=', '', $secret);
        $secret = str_split($secret);
        $binaryString = "";
        for ($i = 0; $i < count($secret); $i = $i + 8) {
            $x = "";
            if (!in_array($secret[$i], $base32chars)) return false;
            for ($j = 0; $j < 8; $j++) {
                $x .= str_pad(base_convert(@$base32charsFlipped[@$secret[$i + $j]], 10, 2), 5, '0', STR_PAD_LEFT);
            }
            $eightBits = str_split($x, 8);
            for ($z = 0; $z < count($eightBits); $z++) {
                $binaryString .= (($y = chr(base_convert($eightBits[$z], 2, 10))) || ord($y) == 48) ? $y : "";
            }
        }
        return $binaryString;
    }

    private function _getBase32LookupTable()
    {
        return [
            'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', // 7
            'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', // 15
            'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', // 23
            'Y', 'Z', '2', '3', '4', '5', '6', '7', // 31
            '=' // padding char
        ];
    }
}