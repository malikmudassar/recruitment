<?php
require_once __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$client_id = $_ENV['AZURE_CLIENT_ID'];
$tenant = $_ENV['AZURE_TENANT_ID'];
 
$redirect_uri = urlencode(
    'https://api.cinergiedigital.com/recruitment/admin/microsoft-callback.php'
);
 
$scope = urlencode('openid profile email');
 
$authorize_url =
    "https://login.microsoftonline.com/$tenant/oauth2/v2.0/authorize?" .
    "client_id=$client_id" .
    "&response_type=code" .
    "&redirect_uri=$redirect_uri" .
    "&response_mode=query" .
    "&scope=$scope";
 
header("Location: $authorize_url");
exit;
 
 
