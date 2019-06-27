<?php
	try {
		$con = new PDO("mysql:dbname=doodle; host=localhost", "root", "");
		$con->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING);
	} catch(PDOException $ex) {
		echo "Connection failed: ".$ex->getMessage();
	}
?>