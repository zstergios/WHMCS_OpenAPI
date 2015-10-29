# WHMCS_OpenAPI

#How to start
$openAPI=dirname(__FILE__)."/modules/addons/openAPI/init.php";
if(!file_exists($openAPI)) exit('This addon requires openAPI addon module');
require($openAPI);

