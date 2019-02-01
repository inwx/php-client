inwx.com XML-RPC PHP Client
=========
You can access all functions of our frontend via an application programming interface (API). Our API is based on the XML-RPC protocol and thus can be easily addressed by almost all programming languages. The documentation and programming examples in PHP, Java, Ruby and Python can be downloaded here.

There is also an OT&E test system, which you can access via ote.inwx.com. Here you will find the known web interface which is using a test database. On the OT&E system no actions will be charged. So you can test how to register domains etc.

Documentation
------
You can view a detailed description of the API functions in our documentation. The documentation as PDF ist part of the Projekt. You also can read the documentation online http://www.inwx.de/en/help/apidoc

Installation
-------
We provide composer support for an easy installation. If u wish to setup our php-client manually
you can also require the domrobot class without composer.

```
composer require inwx/domrobot
```

API Endpoints
-------
Production endpoint: `https://api.domrobot.com/xmlrpc/`

Development endpoint: `https://api.ote.domrobot.com/xmlrpc/`

Example
-------

```php
require "domrobot.class.php";

// Config
$endpoint = "https://api.ote.domrobot.com/xmlrpc/";
$username = "your_username";
$password = "your_password";

// Create Domrobot instance and authorize
$domrobot = new INWX\Domrobot($endpoint);
$domrobot->setDebug(false);
$domrobot->setLanguage('en');
$result = $domrobot->login($username, $password);

// Build an API Call
if ($result['code'] == 1000) {
	$result = $domrobot->call('domain', 'check', [
	    'domain' => 'mydomain.com'
	]);
	
	echo '<pre>';
	print_r($result);
	
} else {
    echo '<pre>';
	print_r($res);
}

// Logout
$result = $domrobot->logout();
```

You can also take a look at the example.php in this project.

License
----

MIT
