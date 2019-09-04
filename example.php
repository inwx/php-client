<?php

error_reporting(E_ALL);
require 'vendor/autoload.php';

// Get your credentials from a safe place when using in production
$username = 'your_username';
$password = 'your_password';

$domrobot = new \INWX\Domrobot();
$result = $domrobot->setLanguage('en')
    // use the OTE endpoint
    ->useOte()
    // or use the LIVE endpoint instead
    //->useLive()
    // use the JSON-RPC API
    ->useJson()
    // or use the XML-RPC API instead
    //->useXml()
    // debug will let you see everything you're sending and receiving
    ->setDebug(true)
    ->login($username, $password);

if ($result['code'] == 1000) {
    $object = 'domain';
    $method = 'check';
    $params = ['domain' => 'mydomain.com'];

    $result = $domrobot->call($object, $method, $params);

    // $res now contains an array with the complete response
    // the basic format of this array is the same whether you use our JSON or XML API
    // there are however some differences with files and dates in the response
    // you should try out your code in our OTE system, to be sure that everything works
}

print_r($result);

$domrobot->logout();
