<?php
$url = 'http://ec2-54-173-226-104.compute-1.amazonaws.com/floris/transaction_log.php';
$data = array('device_id' => 'device_id_3', 'user_id' => 'user_3', 'sync_id' => '3', 'op_code' => '3', 'log' => 'log33333333333333');

//// use key 'http' even if you send the request to https://...
//$options = array(
//    'http' => array(
//        'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
//        'method'  => 'POST',
//        'content' => http_build_query($data),
//    ),
//);
//$context  = stream_context_create($options);
//$result = file_get_contents($url, false, $context);

//var_dump($result);


//url-ify the data for the POST
foreach($data as $key=>$value) { $fields_string .= $key.'='.$value.'&'; }
rtrim($fields_string, '&');

//open connection
$ch = curl_init();

//set the url, number of POST vars, POST data
curl_setopt($ch,CURLOPT_URL, $url);
curl_setopt($ch,CURLOPT_POST, count($data));
curl_setopt($ch,CURLOPT_POSTFIELDS, $fields_string);

//execute post
$result = curl_exec($ch);

//close connection
curl_close($ch);

?>
