<?php
/*==============================================================================
 * (C) Copyright 2015 John J Kauflin, All rights reserved. 
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
 *============================================================================*/

// common method to return admin level based on authenticated user name from the server
function getAdminLevel() {
	//$adminLevel = 0;
	// JJK set to 4 for testing
	$adminLevel = 4;
	
	if (isset($_SERVER['PHP_AUTH_USER'])) {
		$username = strtolower(trim($_SERVER['PHP_AUTH_USER']));
		// Just hard-code this check for now and put in the DB later
		if ($username == 'president') {
			$adminLevel = 4;
		} else if ($username == 'treasurer') {
			$adminLevel = 2;
		}
	}
	
	return $adminLevel;
}

function getUsername() {
	$username = 'unknown';
	if (isset($_SERVER['PHP_AUTH_USER'])) {
		$username = strtolower(trim($_SERVER['PHP_AUTH_USER']));
	}
	return $username;
}

function strToUSD($inStr) {
	// Replace every ascii character except decimal and digits with a null
	$numericStr = preg_replace('/[\x01-\x2D\x2F\x3A-\x7F]+/', '', $inStr);
	// Convert to a float value and round down to 2 digits
	//return round(floatval($numericStr),2,PHP_ROUND_HALF_DOWN);
	return round(floatval($numericStr),2);
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
	//file_put_contents($xmlFileName, fopen($link, 'r'));
	//copy($link, $xmlFileName); // download xml file

	if (is_file($url)) {
		copy($url, $outFileName); // download xml file
	} else {
		$options = array(
				CURLOPT_FILE    => fopen($outFileName, 'w'),
				CURLOPT_TIMEOUT =>  30, // set this to 30 minutes so we do not timeout on big files
				CURLOPT_URL     => $url
		);
		//CURLOPT_TIMEOUT =>  28800, // set this to 8 hours so we dont timeout on big files
		
		$ch = curl_init();
		curl_setopt_array($ch, $options);
		curl_exec($ch);
		curl_close($ch);
	}
	
	/* loop through elements in a value array
	 foreach($valArray as $x => $x_value) {
	 echo "Key=" . $x . ", Value=" . $x_value;
	 echo "<br>";
	 }
	 */
}

function sendHtmlEMail($toStr,$subject,$messageStr,$fromEmailAddress) {
	$message = '<html><head><title>' . $subject .'</title></head><body>' . $messageStr . '</body></html>';
	
	// Always set content-type when sending HTML email
	$headers = "MIME-Version: 1.0" . "\r\n";
	$headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
	
	// More headers
	$headers .= 'From: ' . $fromEmailAddress . "\r\n";
	/*
	 $headers = 'From: webmaster@example.com' . "\r\n" .
	 'Reply-To: webmaster@example.com' . "\r\n" .
	 'X-Mailer: PHP/' . phpversion();
	 */
	
	mail($toStr,$subject,$message,$headers);
}

function truncDate($inStr) {
	if (strlen($inStr) > 10) {
		return substr($inStr,0,10);
	} else {
		return $inStr;
	}
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

				/*
				 A = the future value of the investment/loan, including interest
				 P = the principal investment amount (the initial deposit or loan amount)
				 r = the annual interest rate (decimal)
				 n = the number of times that interest is compounded per year
				 t = the number of years the money is invested or borrowed for
				 A = P(1+r/n)^nt
				 */
function calcCompoundInterest($principal,$startDate) {
	$interestAmount = 0.0;
	// Annaul percentage rate (i.e. 6%)
	$rate = 0.06;
	// Starting principal value
	// Frequency of compounding (1 = yearly, 12 = monthly)
	$annualFrequency = 12.0;

	// Current System datetime
	$currSysDate = date_create();

	// Difference between passed date and current system date
	$diff = date_diff(date_create($startDate),date_create(),true);
	//error_log('date1=' . date_format($date1,"Y-m-d") . ', date2=' . date_format($date2,"Y-m-d") . ", diff days = " . $diff->days);

	// Time in fractional years
	$time = floatval($diff->days) / 365.0;

	$A = floatval($principal) * pow((1+($rate/$annualFrequency)),($annualFrequency*$time));
	// Subtract the original principal to get just the interest
	$interestAmount = round(($A - $principal),2);

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

				/*
				$date1=date_create("2015-12-25");
				$date2=date_create("2016-01-05");
				$diff=date_diff($date1,$date2);
				*/
				//echo "<br><br>diff days = " . $diff->days;
				
				/*
				 $date1 = new DateTime("2013-08-07");
				 $date2 = new DateTime("2013-08-09");
				 echo "<br><br> diff days = " . $date1->diff($date2)->days;
				*/
				//(string)$diff->format('%R%a');
				
				/*
				 $d1=strtotime("July 04");
				 $d2=ceil(($d1-time())/60/60/24);
				 echo "There are " . $d2 ." days until 4th of July.";
				 */




?>
