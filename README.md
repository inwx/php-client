inwx.com XML-RPC PHP Client
=========
You can access all functions of our frontend via an application programming interface (API). Our API is based on the XML-RPC protocol and thus can be easily addressed by almost all programming languages. The documentation and programming examples in PHP, Java, Ruby and Python can be downloaded here.

There is also an OT&E test system, which you can access via ote.inwx.com. Here you will find the known web interface which is using a test database. On the OT&E system no actions will be charged. So you can test how to register domains etc.

Documentation
------
You can view a detailed description of the API functions in our documentation. The documentation as PDF ist part of the Projekt. You also can read the documentation online http://www.inwx.de/en/help/apidoc

Example
-------

```php
require "domrobot.class.php";
// Config
$addr = "https://api.ote.domrobot.com/xmlrpc/";
$usr = "your_username";
$pwd = "your_password";

// Create Domrobot and Login
$domrobot = new domrobot($addr);
$domrobot->setDebug(false);
$domrobot->setLanguage('en');
$res = $domrobot->login($usr,$pwd);

//Make an API Call
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

//Logout
$res = $domrobot->logout();
```

You can also look at the example.php in the Project.

License
----

MIT
