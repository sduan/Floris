<?php
 
// array for JSON response
$response = array();
 
// include db connect class
require_once __DIR__ . '/db_connect.php';
 
// connecting to db
$db = new DB_CONNECT();

$TR_ID 		= "id";
$TR_DEVICE_ID	= "device_id";
$TR_USER_ID	= "user_id";
$TR_SYNC_ID	= "sync_id";
$TR_OP_CODE	= "op_code";
$TR_LOG		= "log";

if (isset($_POST[$TR_DEVICE_ID]) &&
    isset($_POST[$TR_USER_ID]) &&
    isset($_POST[$TR_SYNC_ID]) &&
    isset($_POST[$TR_OP_CODE]) &&
    isset($_POST[$TR_LOG])) {

	$device_id	= $_POST[$TR_DEVICE_ID];
	$user_id	= $_POST[$TR_USER_ID];
	$sync_id	= $_POST[$TR_SYNC_ID];
	$op_code	= $_POST[$TR_OP_CODE];
	$log		= $_POST[$TR_LOG];

	// mysql inserting a new row
	$result = mysql_query("INSERT INTO TransactionLog VALUES('', '$device_id', '$user_id', '$sync_id', '$op_code', '$log')");

        // no product found
        $response["success"] = 0;
        $response["message"] = "Transaction Log Inserted";

	echo json_encode($response);	
	return;
}

if (isset($_GET[$TR_ID])) {
	$tr_id = $_GET[$TR_ID];
	$result = mysql_query("SELECT * FROM TransactionLog WHERE id = '$tr_id'");
} else {
	$result = mysql_query("SELECT * FROM TransactionLog");
}
 
if (!empty($result)) {
	// check for empty result
	if (mysql_num_rows($result) > 0) {
		$result = mysql_fetch_array($result);
		$product = array();
		$product[$TR_ID] = $result[$TR_ID];
		$product[$TR_DEVICE_ID] = $result[$TR_DEVICE_ID];
		$product[$TR_USER_ID] = $result[$TR_USER_ID];
		$product[$TR_SYNC_ID] = $result[$TR_SYNC_ID];
		$product[$TR_OP_CODE] = $result[$TR_OP_CODE];
		$product[$TR_LOG] = $result[$TR_LOG];

		// success
		$response["success"] = 1;
 
		// user node
		$response["product"] = array();
 
		array_push($response["product"], $product);
 
		// echoing JSON response
		echo json_encode($response);
	} else {
		// no product found
		$response["success"] = 0;
		$response["message"] = "No product found";
 
		// echo no users JSON
		echo json_encode($response);
	}
} else {
	// no product found
	$response["success"] = 0;
	$response["message"] = "No product found";

	// echo no users JSON
	echo json_encode($response);
}
?>
