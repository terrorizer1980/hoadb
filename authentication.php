<?php
/*==============================================================================
 * (C) Copyright 2020 John J Kauflin, All rights reserved. 
 *----------------------------------------------------------------------------
 * DESCRIPTION: Check for JWT token to authenticate user
 *----------------------------------------------------------------------------
 * Modification History
 * 2020-07-25 JJK 	Initial version
 * 2020-07-31 JJK   Re-factor to use new class
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

$userRec = LoginAuth::getUserRec($cookieName,$cookiePath,$serverKey);

//error_log(date('[Y-m-d H:i] '). "in authentication, after getUserRec " . PHP_EOL, 3, LOG_FILE);
//error_log(date('[Y-m-d H:i] '). "in authentication, userName = $userRec->userName" . PHP_EOL, 3, LOG_FILE);
//    error_log(date('[Y-m-d H:i] '). "in " .   basename(__FILE__ , ".php")   . ", User NOT authenticated, DIE" . PHP_EOL, 3, LOG_FILE);

echo json_encode($userRec);
?>
