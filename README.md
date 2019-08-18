<p align="center">
  <a href="https://www.inwx.com/en/" target="_blank">
    <img src="https://images.inwx.com/logos/inwx.png">
  </a>
</p>

INWX Domrobot PHP Client
=========
You can access all functions of our frontend via our API, which is available via the XML-RPC or JSON-RPC protocol and thus can be easily consumed with all programming languages.

There is also an OT&E test system, which you can access via [ote.inwx.com](https://ote.inwx.com/en/). Here you will find the known web interface which is using a test database. On the OT&E system no actions will be charged. So you can test as much as you like there.

Documentation
------
You can view a detailed description of the API functions in our documentation. You can find the online documentation [by clicking here](https://www.inwx.de/en/help/apidoc).

If you still experience any kind of problems don't hesitate to contact our [support via email](mailto:support@inwx.de).

Installation
-------
the recommended way is via composer:
```bash
composer require inwx/domrobot
```
now you can use the client by importing the Domrobot class:
```php
use INWX\Domrobot;
```

Example
-------

```php
<?php

error_reporting(E_ALL);
require 'vendor/autoload.php';

$username = 'your_username';
$password = 'your_password';

$domrobot = new \INWX\Domrobot();
$result = $domrobot->setLanguage('en')
    ->useOte()
    ->useJson()
    ->setDebug(true)
    ->login($username, $password);

if ($result['code'] == 1000) {
    $object = 'domain';
    $method = 'check';
    $params = ['domain' => 'mydomain.com'];

    $result = $domrobot->call($object, $method, $params);
}

print_r($result);

$domrobot->logout();
```

You can also have a look at the example.php file in the project for even more info.

License
----

MIT
