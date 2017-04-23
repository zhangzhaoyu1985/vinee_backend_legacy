<?php

print "yaoliu\n";

$servername = "localhost";
$username = "root";
$password = "TagTalk78388!";
$dbname = "wineTage1";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);
// Check connection
if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}

$sql = "SELECT * FROM user_account_info ";
$result = $conn->query($sql);
if ($result->num_rows > 0) {
  print("log in success , userName \n");
} else {
  print("log in failed , userName \n");
}
?>
