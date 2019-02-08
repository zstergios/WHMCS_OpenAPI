# WHMCS Open API

This addon module includes some most frequent functions and database custom API, more efficient from WHMCS functions.

Also includes "forms" class that you can create HTML pages very quickly just calling the functions

#How to start


Place this code to your project file:

```
$openAPI=dirname(__FILE__)."/modules/addons/openAPI/init.php";

if(!file_exists($openAPI)) exit('This addon requires openAPI addon module');
require($openAPI);
```
