<?php
/*==============================================================================
 * (C) Copyright 2016 John J Kauflin, All rights reserved. 
 *----------------------------------------------------------------------------
 * DESCRIPTION: 
 *----------------------------------------------------------------------------
 * Modification History
 * 2016-10-25 JJK 	Initial version to get Communications list
 * 2018-11-12 JJK	Modified to put rows straight in the output array
 *============================================================================*/
// Common functions
require_once 'php_secure/commonUtil.php';
// Common database functions and table record classes
require_once 'php_secure/hoaDbCommon.php';

// Include database connection credentials from an external includes location
require_once getCredentialsFilename();
// This include will have the following variables set
//$host = 'localhost';
//$dbadmin = "username";
//$password = "password";
//$dbname = "<name of the mysql database>";

// Define a super global constant for the log file (this will be in scope for all functions)
define("LOG_FILE", "./php.log");


 include 'commonUtil.php';
	// Include table record classes and db connection parameters
	include 'hoaDbCommon.php';

		// If they are set, get input parameters from the REQUEST
	$parcelId = getParamVal("parcelId");
	$ownerId = getParamVal("ownerId");
	$commId = getParamVal("commId");

	$conn = getConn();
	
	if (!empty($parcelId)) {
		if (!empty($commId)) {
			$sql = "SELECT * FROM hoa_communications WHERE Parcel_ID = ? AND CommID = ? ";
			$stmt = $conn->prepare($sql);
			$stmt->bind_param("ss", $parcelId,$commId);
		} else {
			$sql = "SELECT * FROM hoa_communications WHERE Parcel_ID = ? ORDER BY CommID DESC ";
			$stmt = $conn->prepare($sql);
			$stmt->bind_param("s", $parcelId);
		}
	} else {
		$sql = "SELECT * FROM hoa_communications ORDER BY CommID DESC ";
		$stmt = $conn->prepare($sql);
	}
	
	//error_log('$sql = ' . $sql);
	
	$stmt->execute();
	$result = $stmt->get_result();
	$outputArray = array();
	if ($result->num_rows > 0) {
		while($row = $result->fetch_assoc()) {
			array_push($outputArray,$row);
		}
	}
	$stmt->close();
	$conn->close();
	
	echo json_encode($outputArray);
?>
