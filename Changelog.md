#XML-RPC Inwx-Domrobot 
 
XML-RPC support in PHP is not enabled by default. 
You will need to use the --with-xmlrpc[=DIR] configuration option when compiling PHP to enable XML-RPC support.
 
#Changelog:

##2015/01/XX - Master
 - Add Composer
 - Move Domrobot Class to Namespace "INWX"
 - Set Cookiefile default to the System tmp folder/file
 - Add Funktion setCookiefile and getCookiefile

##2014/10/29
 - Use Namespace and add Composer

##2013/01/22 - v2.3
 - added google 2-step-verification methods
 - added parameter 'sharedSecret' to login method
 
##2012/04/08 - v2.2
 - use "nested" methods  (e.g. domain.check)
 - removed nonce and secure-login
 - added setter and getter
 - added credentials params to login function 
 - response utf-8 decoded 
 - removed newlines and white spaces in xml request (verbosity=no_white_space)
 - added optional clTRID set/get functions

##2011/07/19 - v2.1 
 - using cookiefile instead of session
 - added login and logout function
 - added client version transmission
    
   
by InterNetworX Ltd. & Co. KG
