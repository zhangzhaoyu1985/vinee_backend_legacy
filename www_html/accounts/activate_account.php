<?php
function response($server_url, $title, $desc) {
	echo sprintf(
	'<html>
	<head><title>WineMate Account</title>
		<style type="text/css">
			body {font-family: arial,sans-serif;margin: 10px 30px;}
		</style>
	</head>
	<body>
		<div style="margin-top:30px;margin-bottom:20px;font-size:18px;font-weight:bold;color:#8C123C">
			<img src="%s/pics/email/logo.png" style="vertical-align:middle"height="60" width="60" border="0" alt="WineMate"> W I N E M A T E
		</div>
		<div style="font-size:18px;"><b>%s</b></div>
		<div style="font-size:13px;">
			<p style="line-height:17px">%s</p>
		</div>
	</body></html>', $server_url, $title, $desc);
}
function db_connect() {
	$servername = "localhost";
	$username = "root";
	$password = "TagTalk78388!";
	// For local debug
	/*
	   $servername = "127.0.0.1";
	   $username = "arthur";
	   $password = "arthur";
	 */
	$dbname = "wineTage1";

	// Create connection
	//$conn = new \mysqli($servername, $username, $password, $dbname);
	$conn = new \mysqli($servername, $username, $password, $dbname, 3307); // For local debug
	$conn->set_charset("utf8");
	// Check connection
	if ($conn->connect_error) {
		die("Connection failed: " . $conn->connect_error);
	}
	return $conn;
}

if ($_SERVER['REQUEST_METHOD'] == 'GET' && $_GET['user_id']) {
	$conn = db_connect();
	$userId = $_GET['user_id'];
	$sql = sprintf("SELECT * FROM user_account_info WHERE user_id = %d", $userId);
	$result = $conn->query($sql);
	if ($result && $result->num_rows > 0) {
		$sql = sprintf("UPDATE user_account_info SET status = %d WHERE user_id = %d", 1, $userId);
		$conn->query($sql);

		// Check if successfully updated.
		$sql = sprintf("SELECT * FROM user_account_info WHERE user_id = %d AND status = 1", $userId);
		$result = $conn->query($sql);
		if ($result && $result->num_rows > 0) { 
			response("http://50.18.207.106", 
				"Account Activated.",
				"Your WineMate account is activated succesfully.
				</br>You can now login from the WineMate app.");	
		} 
	} else {      
		response("http://50.18.207.106", 
			"Activation Failed",
			'We are very sorry that we could not activate your account.</br>
			Please contact <a href="mailto:support@tagtalk.co" target="_top">support@tagtalk.co</a> for support.');	
	}
} else {
	response("http://50.18.207.106", 
		"Activation Failed",
		'We are very sorry that we could not activate your account.</br>
		Please contact <a href="mailto:support@tagtalk.co" target="_top">support@tagtalk.co</a> for support.');	
}
?>
