<?php

namespace INWX;

use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;

class Domrobot implements LoggerAwareInterface
{
    protected const VERSION = '3.2.0';
    protected const LIVE_URL = 'https://api.domrobot.com/';
    protected const OTE_URL = 'https://api.ote.domrobot.com/';
    protected const XMLRPC = 'xmlrpc';
    protected const JSONRPC = 'jsonrpc';

    protected $debug = false;
    protected $language = 'en';
    protected $customer = '';
    protected $clTrid;
    protected $cookieFile;

    protected $url = self::OTE_URL;
    protected $api = self::JSONRPC;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * Domrobot constructor.
     *
     * @param string|null $cookieFile You can overwrite the standard cookieFile path by setting a full path here
     */
    public function __construct(?string $cookieFile = null)
    {
        $this->logger = new Logger('domrobot_default_logger');
        $this->logger->pushHandler(new StreamHandler('php://stdout', Logger::DEBUG));
        $this->cookieFile = $cookieFile ?? tempnam(sys_get_temp_dir(), 'INWX');
    }

    /**
     * Configures the Domrobot to use the live endpoint. All actions will be executed live and can cause costs if you buy something.
     * It is recommended to try your code with our OTE system before to check if everything works as expected.
     *
     * @return self
     */
    public function useLive(): self
    {
        $this->url = self::LIVE_URL;

        return $this;
    }

    /**
     * Configures the Domrobot to use the OTE endpoint. All actions will be executed in our test environment which has extra credentials.
     * Here you can test for free as much as you like.
     *
     * @return self
     */
    public function useOte(): self
    {
        $this->url = self::OTE_URL;

        return $this;
    }

    /**
     * Configures the Domrobot to use a specified URL as endpoint.
     *
     * @param string $url
     *
     * @return self
     */
    public function useUrl(string $url): self
    {
        $this->url = $url;

        return $this;
    }

    /**
     * @return bool Is the Domrobot configured to use the live endpoint?
     */
    public function isLive(): bool
    {
        return self::LIVE_URL === $this->url;
    }

    /**
     * @return bool Is the Domrobot configured to use the OTE endpoint?
     */
    public function isOte(): bool
    {
        return self::OTE_URL === $this->url;
    }

    /**
     * Configures the Domrobot to use the JSON-RPC API. This needs the ext-json PHP extension installed to work.
     * This should be installed by default in PHP.
     *
     * @return self
     */
    public function useJson(): self
    {
        $this->api = self::JSONRPC;

        return $this;
    }

    /**
     * Configures the Domrobot to use the XML-RPC API. This needs the ext-xmlrpc PHP extension installed to work.
     * This may not be installed by default in PHP.
     *
     * @return self
     */
    public function useXml(): self
    {
        $this->api = self::XMLRPC;

        return $this;
    }

    /**
     * @return bool Is the Domrobot configured to use XML-RPC?
     */
    public function isXml(): bool
    {
        return self::XMLRPC === $this->api;
    }

    /**
     * @return string Either 'en', 'de' or 'es'
     */
    public function getLanguage(): string
    {
        return $this->language;
    }

    /**
     * @param string $language Either 'en', 'de' or 'es'
     *
     * @return self
     */
    public function setLanguage(string $language): self
    {
        $this->language = $language;

        return $this;
    }

    /**
     * @return bool Is debug mode activated?
     */
    public function getDebug(): bool
    {
        return $this->debug;
    }

    /**
     * @param bool $debug Activate/deactivate debug mode
     *
     * @return self
     */
    public function setDebug(bool $debug = false): self
    {
        $this->debug = $debug;

        return $this;
    }

    /**
     * @return string
     */
    public function getCookieFile(): string
    {
        return $this->cookieFile;
    }

    /**
     * @param string $file
     *
     * @return self
     *
     * @throws RuntimeException If the cookieFile is not writable or does not exist
     */
    public function setCookieFile(string $file): self
    {
        if ((file_exists($file) && !is_writable($file)) || (!file_exists($file) && !is_writable(dirname($file)))) {
            throw new RuntimeException("Cannot write cookiefile: '" . $file . "'. Please check file/folder permissions.",
                2400);
        }
        $this->cookieFile = $file;

        return $this;
    }

    /**
     * @return string
     */
    public function getCustomer(): string
    {
        return $this->customer;
    }

    /**
     * @param string $customer
     *
     * @return self
     */
    public function setCustomer(string $customer): self
    {
        $this->customer = $customer;

        return $this;
    }

    /**
     * @return string
     */
    public function getClTrId(): string
    {
        return $this->clTrid;
    }

    /**
     * @param string $clTrId
     *
     * @return Domrobot
     */
    public function setClTrId(string $clTrId): self
    {
        $this->clTrid = $clTrId;

        return $this;
    }

    /**
     * Execute a login command with the API. This is needed before you can do anything else.
     *
     * @param string      $username
     * @param string      $password
     * @param string|null $sharedSecret
     *
     * @return array
     */
    public function login(string $username, string $password, ?string $sharedSecret = null): array
    {
        $params['lang'] = $this->language;
        $params['user'] = $username;
        $params['pass'] = $password;

        $loginRes = $this->call('account', 'login', $params);
        if (!empty($sharedSecret) && $loginRes['code'] == 1000 && !empty($loginRes['resData']['tfa'])) {
            $tan = $this->getSecretCode($sharedSecret);
            $unlockRes = $this->call('account', 'unlock', ['tan' => $tan]);
            if ($unlockRes['code'] != 1000) {
                return $unlockRes;
            }
        }

        return $loginRes;
    }

    /**
     * Execute a API Request and decode the Response to an array for easy usage.
     *
     * @param string $object
     * @param string $method
     * @param array  $params
     *
     * @return array
     */
    public function call(string $object, string $method, array $params = []): array
    {
        if ('' !== $this->customer) {
            $params['subuser'] = $this->customer;
        }
        if (!empty($this->clTrid)) {
            $params['clTRID'] = $this->clTrid;
        }

        $methodParam = strtolower($object . '.' . $method);

        if ($this->isJson()) {
            $request = json_encode(['method' => $methodParam, 'params' => $params]);
        } else {
            $request = xmlrpc_encode_request($methodParam, $params,
                ['encoding' => 'UTF-8', 'escaping' => 'markup', 'verbosity' => 'no_white_space']);
        }

        $header[] = 'Content-Type: ' . ($this->isJson() ? 'application/json' : 'text/xml');
        $header[] = 'Connection: keep-alive';
        $header[] = 'Keep-Alive: 300';
        $forwardedFor = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? '';
        $header[] = 'X-FORWARDED-FOR: ' . $forwardedFor;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->url . $this->api . '/');
        curl_setopt($ch, CURLOPT_TIMEOUT, 65);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $this->cookieFile);
        curl_setopt($ch, CURLOPT_COOKIEJAR, $this->cookieFile);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $request);
        curl_setopt($ch, CURLOPT_USERAGENT, 'DomRobot/' . self::VERSION . ' (PHP ' . PHP_VERSION . ')');

        $response = curl_exec($ch);
        curl_close($ch);
        if ($this->debug) {
            $this->logger->debug("Request:\n" . $request . "\n");
            $this->logger->debug("Response:\n" . $response . "\n");
        }

        if ($this->isJson()) {
            return json_decode($response, true);
        }

        return xmlrpc_decode($response, 'UTF-8');
    }

    /**
     * @return bool Is the Domrobot configured to use JSON-RPC?
     */
    public function isJson(): bool
    {
        return self::JSONRPC === $this->api;
    }

    /**
     * Returns a secret code needed for 2 factor auth.
     *
     * @param string $secret
     *
     * @return string
     */
    protected function getSecretCode(string $secret): string
    {
        $timeSlice = floor(time() / 30);
        $codeLength = 6;

        $base32 = new Base32();

        $secretKey = $base32->decode($secret);
        // Pack time into binary string
        $time = chr(0) . chr(0) . chr(0) . chr(0) . pack('N*', $timeSlice);
        // Hash it with users secret key
        $hmac = hash_hmac('SHA1', $time, $secretKey, true);
        // Use last nipple of result as index/offset
        $offset = ord(substr($hmac, -1)) & 0x0F;
        // grab 4 bytes of the result
        $hashPart = substr($hmac, $offset, 4);

        // Unpak binary value
        $value = unpack('N', $hashPart);
        $value = $value[1];
        // Only 32 bits
        $value &= 0x7FFFFFFF;

        $modulo = 10 ** $codeLength;

        return str_pad($value % $modulo, $codeLength, '0', STR_PAD_LEFT);
    }

    /**
     * Execute a logout command with the API.
     *
     * @return array
     */
    public function logout(): array
    {
        $ret = $this->call('account', 'logout');
        if (file_exists($this->cookieFile)) {
            unlink($this->cookieFile);
        }

        return $ret;
    }

    /**
     * @inheritDoc
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }
}
