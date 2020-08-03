<?php
/*==============================================================================
 * (C) Copyright 2020 John J Kauflin, All rights reserved. 
 *----------------------------------------------------------------------------
 * DESCRIPTION: Authenticate login against database and create JWT token
 *----------------------------------------------------------------------------
 * Modification History
 * 2020-07-25 JJK 	Initial version
 *============================================================================*/
require_once 'vendor/autoload.php'; 

// Common functions
require_once 'php_secure/commonUtil.php';
// Common database functions and table record classes
require_once 'php_secure/hoaDbCommon.php';
// Login Authentication class
require_once 'php_secure/jjklogin.php';
use \jkauflin\jjklogin\LoginAuth;
// Include database connection credentials from an external includes location
require_once getSecretsFilename();
// Define a super global constant for the log file (this will be in scope for all functions)
define("LOG_FILE", "./php.log");


header("Content-Type: application/json; charset=UTF-8");
# Get JSON as a string
$json_str = file_get_contents('php://input');

//error_log(date('[Y-m-d H:i] '). "in login, json_str = $json_str" . PHP_EOL, 3, LOG_FILE);

# Decode the string to get a JSON object
$param = json_decode($json_str);

//$loginAuth = new LoginAuth();

//error_log(date('[Y-m-d H:i] '). "in login, username = " . $param->username . PHP_EOL, 3, LOG_FILE);
if (empty($param->username) || empty($param->password)) {
    $userRec = LoginAuth::initUserRec();
    $userRec->userMessage = 'Username and Password are required';
} else {
    $conn = getConn($host, $dbadmin, $password, $dbname);
    $userRec = LoginAuth::setUserCookie($conn,$cookieName,$cookiePath,$serverKey,$param);
    $conn->close();
}

echo json_encode($userRec);
?>
