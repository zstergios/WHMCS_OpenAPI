# WHMCS_OpenAPI

#How to start
Place this code to your project file:

```
$openAPI=dirname(__FILE__)."/modules/addons/openAPI/init.php";

if(!file_exists($openAPI)) exit('This addon requires openAPI addon module');
require($openAPI);
```
