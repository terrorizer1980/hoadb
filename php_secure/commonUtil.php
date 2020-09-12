<?php
/*==============================================================================
 * (C) Copyright 2015,2020 John J Kauflin, All rights reserved. 
 *----------------------------------------------------------------------------
 * DESCRIPTION: 
 *----------------------------------------------------------------------------
 * Modification History
 * 2015-03-06 JJK 	Initial version with some common utilities 
 * 2015-09-08 JJK	Added getAdminLevel to return an admin level based on
 *                  username to control updates
 * 2015-10-01 JJK	Added $fromEmailAddress to sendHtmlEMail                
 * 2015-10-20 JJK   Added function wildCardStrFromTokens to build a wild
 * 					card parameter string from the tokens in a string
 * 2016-04-10 JJK	Added calcCompoundInterest to calculate compound 
 * 					interests for the total dues calculation
 * 2016-09-11 JJK   Corrected handling of bad dates for interest calculation
 * 2016-09-11 JJK   Modified the truncDate routine to take the 1st token
 * 					before truncating to 10 characters (to handle bad dates
 * 					like "4/7/2007 0"
 * 2020-08-05 JJK   Removed getAdminLevel and getUsername (in favor of new
 *                  Login/Authentication logic)
 *============================================================================*/

function strToUSD($inStr) {
	// Replace every ascii character except decimal and digits with a null
	$numericStr = preg_replace('/[\x01-\x2D\x2F\x3A-\x7F]+/', '', $inStr);
	// Convert to a float value and round down to 2 digits
	//return round(floatval($numericStr),2,PHP_ROUND_HALF_DOWN);
	return round(floatval($numericStr),2);
}

// Replace comma with null so you can use it as a CSV value
function csvFilter($inVal) {
	return preg_replace('/[\x2C]+/', '', String($inVal));
}


// Set 0 or 1 according to the boolean value of a string
function paramBoolVal($paramName) {
	$retBoolean = 0;
	if (strtolower(getParamVal($paramName)) == 'true') {
		$retBoolean = 1;
	}
	return $retBoolean;
}

function getParamVal($paramName) {
	$paramVal = "";
	if (isset($_REQUEST[$paramName])) {
		$paramVal = trim(urldecode($_REQUEST[$paramName]));
		// more input string cleanup ???  invalid characters?
	}
	return $paramVal;
}

function downloadUrlToFile($url, $outFileName)
{
    
	//if (is_file($url)) {
	//	copy($url, $outFileName); // download xml file
	//} else {
		$options = array(
				CURLOPT_FILE    => fopen($outFileName, 'w'),
				CURLOPT_TIMEOUT =>  100, // set this to 10 minutes so we do not timeout on big files
				CURLOPT_URL     => $url
		);
		//CURLOPT_TIMEOUT =>  28800, // set this to 8 hours so we dont timeout on big files
		
		$ch = curl_init();
		curl_setopt_array($ch, $options);
		curl_exec($ch);
		curl_close($ch);
    //}
    
/*
$options = array(
  CURLOPT_FILE    => '/path/to/download/the/file/to.zip',
  CURLOPT_TIMEOUT =>  28800, // set this to 8 hours so we dont timeout on big files
  CURLOPT_URL     => 'http://remoteserver.com/path/to/big/file.zip',
);
$ch = curl_init();
curl_setopt_array($ch, $options);
curl_exec($ch);
curl_close($ch);

$options = array(
          CURLOPT_FILE    => fopen($outFileName, 'w'),
          CURLOPT_TIMEOUT =>  28800, // set this to 8 hours so we dont timeout on big files
          CURLOPT_URL     => $url
        );
        $ch = curl_init();
        curl_setopt_array($ch, $options);
        curl_exec($ch);
        curl_close($ch);

function download_remote_file_with_curl($file_url, $save_to)
	{
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_POST, 0); 
		curl_setopt($ch,CURLOPT_URL,$file_url); 
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
		$file_content = curl_exec($ch);
		curl_close($ch);
 
		$downloaded_file = fopen($save_to, 'w');
		fwrite($downloaded_file, $file_content);
		fclose($downloaded_file);
 
	}
*/

}

function sendHtmlEMail($toStr,$subject,$messageStr,$fromEmailAddress) {
    //mb_internal_encoding("UTF-8");
	$message = '<html><head><title>' . $subject .'</title></head><body>' . $messageStr . '</body></html>';
	
	// Always set content-type when sending HTML email
	//$headers = "MIME-Version: 1.0" . "\r\n";
	//$headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
	
	// More headers
	//$headers .= 'From: ' . $fromEmailAddress . "\r\n";
	/*
	 $headers = 'From: webmaster@example.com' . "\r\n" .
	 'Reply-To: webmaster@example.com' . "\r\n" .
	 'X-Mailer: PHP/' . phpversion();
	 */
	
    //mail($toStr,$subject,$message,$headers);

    //$mimeType = 'text/plain';
    $mimeType = 'text/html';

    try {
    	// Create the Transport (using default linux sendmail)
    	$transport = new Swift_SendmailTransport();

    	// Create the Mailer using your created Transport
    	$mailer = new Swift_Mailer($transport);

    	// Create a message
    	$message = (new Swift_Message($subject))
    		->setFrom([$fromEmailAddress])
    		->setTo([$toStr])
    		->setBody($messageStr,$mimeType);

    	// swiftmailer PHP read receipt capability
    	// $message -> setReadReceiptTo('your@address.tld');
    	// When the email is opened, if the mail client supports it a notification will be sent to this address.
    	// Read receipts won't work for the majority of recipients since many mail clients auto-disable them. 
    	// Those clients that will send a read receipt will make the user aware that one has been requested.

        // Create the attachment with your data
    	//$attachment = new Swift_Attachment($filedata, $filename, 'application/pdf');
    	// Attach it to the message
    	//$message->attach($attachment);
         
    	// Send the message and check for success
    	if ($mailer->send($message)) {
            error_log(date('[Y-m-d H:i] '). "in " . basename(__FILE__,".php") . ", swiftmail SUCCESS " . PHP_EOL, 3, LOG_FILE);
    	} else {
            error_log(date('[Y-m-d H:i] '). "in " . basename(__FILE__,".php") . ", swiftmail ERROR " . PHP_EOL, 3, LOG_FILE);
    	}

    } catch(Exception $e) {
        error_log(date('[Y-m-d H:i] '). "in " . basename(__FILE__,".php") . ", Exception = " . $e->getMessage() . PHP_EOL, 3, LOG_FILE);
    }

}


function truncDate($inStr) {
	$outStr = "";
	if ($inStr != null) {
		$outStr = strtok($inStr," ");
		if (strlen($outStr) > 10) {
			$outStr = substr($outStr,0,10);
		}
	}
	return $outStr;
}

// Create a wild card parameter string from the tokens in a string
function wildCardStrFromTokens($inStr) {
	$string = $inStr;
	$token = strtok($string, " ");
	$paramStr = '';
	while ($token !== false)
	{
		$paramStr = $paramStr . '%' . $token;
		$token = strtok(" ");
	}
	$paramStr = $paramStr . '%';
	//error_log('$paramStr = ' . $paramStr);
	return $paramStr;
}

// Replace every ascii character except decimal and digits with a null, and round to 2 decimal places
function stringToMoney($inAmountStr) {
	return round(floatval( preg_replace('/[\x01-\x2D\x2F\x3A-\x7F]+/', '', $inAmountStr) ),2);
}

// Calculate compound interest from a principal and a start date
function calcCompoundInterest($principal,$startDate) {
				/*
				 A = the future value of the investment/loan, including interest
				 P = the principal investment amount (the initial deposit or loan amount)
				 r = the annual interest rate (decimal)
				 n = the number of times that interest is compounded per year
				 t = the number of years the money is invested or borrowed for
				 A = P(1+r/n)^nt
				 */
	$interestAmount = 0.0;
	// Annaul percentage rate (i.e. 6%)
	$rate = 0.06;
	// Starting principal value
	// Frequency of compounding (1 = yearly, 12 = monthly)
	$annualFrequency = 12.0;

	//error_log(date('[Y-m-d H:i] '). "StartDate = " . $startDate . PHP_EOL, 3, "jjk-commonUtil.log");
	if ($startDate != null && $startDate != '' && $startDate != '0000-00-00') {
		
		// Convert the 1st start date string token (i.e. till space) into a DateTime object (to check the date)
		if ($startDateTime = date_create( strtok($startDate," ") )) {
			// Difference between passed date and current system date
			$diff = date_diff($startDateTime,date_create(),true);
			
			// Time in fractional years
			$time = floatval($diff->days) / 365.0;
			
			$A = floatval($principal) * pow((1+($rate/$annualFrequency)),($annualFrequency*$time));
			// Subtract the original principal to get just the interest
			$interestAmount = round(($A - $principal),2);
			
		} else {
			// Error in date_create
			error_log(date('[Y-m-d H:i] '). "Problem with StartDate = " . $startDate . PHP_EOL, 3, "jjk-commonUtil.log");
		}
	}
	//error_log("diff days = " . $diff->days . ", time = " . $time . ", A = " . $A . ", interest = " . $interestAmount);

				/*
//Monthly
	for ($time = 1; $time <= 10; $time++) {
		$interestAmount = round($principal * pow((1+($rate/$annualFrequency)),($annualFrequency*$time)),2,PHP_ROUND_HALF_DOWN);
		//echo "<br>Year = $time ($principal * pow((1+($rate/$annualFrequency)),($annualFrequency*$time)) = " . $principalWithInterest;
	}

				$annualFrequency = 1.0;
				echo "<br><br>Compounded Yearly";
				for ($time = 1; $time <= 10; $time++) {
					$principalWithInterest = round($principal * pow((1+($rate/$annualFrequency)),($annualFrequency*$time)),2,PHP_ROUND_HALF_DOWN);
					echo "<br>Year = $time ($principal * pow((1+($rate/$annualFrequency)),($annualFrequency*$time)) = " . $principalWithInterest;
				}
				*/

	return $interestAmount;

} // End of function calcCompoundInterest($principal,$startDate) {

?>
