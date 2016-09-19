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
require "INWX/Domrobot.php";


$addr = "https://api.domrobot.com/xmlrpc/";
//$addr = "https://api.ote.domrobot.com/xmlrpc/";

$usr = "";
$pwd = "";

$domains_per_request = 20;
$sleep_between_requests = false; // sleep for a defined number of seconds after each api-request (default: false)


$domrobot = new INWX\Domrobot($addr);
$domrobot->setDebug(false);
$domrobot->setLanguage('en');
$res = $res_lgn = $domrobot->login($usr,$pwd);

$domains = array();

if($res_lgn['code'] == 1000){
	$res_count = $domrobot->call("domain", "list", array("page" => 1, "pagelimit" => 1));
	$num_domains = (int) $res_count['resData']['count'];
	$pages = round(($num_domains / $domains_per_request)+2, 0);
	for($i=1;$i<$pages;$i++){
		$res = $domrobot->call("domain", "list", array("page" => $i, "pagelimit" => $domains_per_request));
		foreach($res['resData']['domain'] as $domain){
			$domains[] = $domain;
		}
		if($sleep_between_requests !== false && is_numeric($sleep_between_requests)) sleep((int) $sleep_between_requests);
	}
	print_r($domains);
} else{
	print_r($res_lgn);
}

?>
