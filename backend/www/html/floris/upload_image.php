<html>
<head>
	<title>Upload an image</title>
</head>
<body>
	<form action="upload_image.php" method="POST" enctype="multipart/form-data">
		File:
		<input type="file" name="image"> <input type="submit" value="Upload">
	</form>

<?php
	// include db connect class
	require_once __DIR__ . '/db_connect.php';
 
	// connecting to db
	$db = new DB_CONNECT();

	// file properties
	if (!(isset($_FILES['image']) && $_FILES['image']['size'] > 0)) {
		echo "Please select an image...";
		return;
	}

	// get image file info
	echo "Image Name: ", $image_name = $_FILES['image']['name'], "<p />";
	echo "Image Size: ", $image_size = $_FILES['image']['size'], "<p />";
	echo "Image Temp Name: ", $image_tmp_name = $_FILES['image']['tmp_name'], "<p />";
	$image = addslashes(file_get_contents($_FILES['image']['tmp_name']));

	// insert image into DB
	$result = mysql_query("INSERT INTO BabyPictures VALUES('', '$image_name', '$image')");
	if(!$result) {
		echo "Error uploading image.";
		echo mysql_error();
		return;
	}
	
	// show the update image
	echo $last_id = mysql_insert_id();
	echo "<p />Image uploaded.<p />Your image:<p /><img src=get_baby_picture.php?id=$last_id>";
?>

</body>
</html>
