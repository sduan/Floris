<?php

// include db connect class
require_once __DIR__ . '/db_connect.php';
 
// connecting to db
$db = new DB_CONNECT();

$id = addslashes($_REQUEST['id']);

$image = mysql_query("SELECT * FROM BabyPictures WHERE ID=$id");
$image = mysql_fetch_assoc($image);
$image = $image['Image'];

header("Content-type: image/jpeg");

echo $image;
?>
