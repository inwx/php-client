<?php
/*
 * XML-RPC support in PHP is not enabled by default. 
 * You will need to use the --with-xmlrpc[=DIR] configuration option when compiling PHP to enable XML-RPC support. 
 * This extension is bundled into PHP as of 4.1.0.
 * 
 * cURL needs to be installed and activated
 * 
 */

header('Content-type: text/plain; charset=utf-8');
error_reporting(E_ALL);
require "domrobot.class.php";

//$addr = "https://api.domrobot.com/xmlrpc/";
//$addr = "https://api.ote.domrobot.com/xmlrpc/";

$usr = "your_username";
$pwd = "your_password";

$domrobot = new domrobot($addr);
$domrobot->setDebug(false);
$domrobot->setLanguage('en');
$res = $domrobot->login($usr,$pwd);

if ($res['code']==1000) {
	$obj = "domain";
	$meth = "check";
	$params = array();
	$params['domain'] = "mydomain.com";
	$res = $domrobot->call($obj,$meth,$params);
	print_r($res);
} else {
	print_r($res);
}

$res = $domrobot->logout();

?>