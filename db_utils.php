<?php

function db_connect($is_write=false) {
  var_dump("***************************");
  print("is_write: ");
  var_dump($is_write);
  if ($is_write){
    $servername = "54.223.152.54";
  } else {
    $servername = "localhost";
  }
  $username = "root";
  $password = "TagTalk78388!";
  
  /*
  // For local debug
  $servername = "127.0.0.1";
  $username = "arthur";
  $password = "arthur";
  */
  
  $dbname = "wineTage1";

  // Create connection
  $conn = new \mysqli($servername, $username, $password, $dbname);
  //$conn = new \mysqli($servername, $username, $password, $dbname, 3307); // For local debug
  $conn->set_charset("utf8");
  // Check connection
  if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
  }
  return $conn;
}

function get_user_info_from_id($conn, $user_id) {
  $sql = "SELECT user_name, first_name, head_image_url, third_party FROM user_account_info WHERE user_id = ".$user_id;
  $sql_result = $conn->query($sql);
  if ($sql_result->num_rows != 1) {
    return;
  } else {
    $row = $sql_result->fetch_assoc();
    return array($row['user_name'], $row['head_image_url'], $row['first_name'], $row['third_party']);
  }
}

function get_wine_pic_url_from_id($conn, $wine_id) {
  $sql = "SELECT wine_pic_url FROM wine_basic_info_english WHERE wine_id = ".$wine_id;
  $sql_result = $conn->query($sql);
  if ($sql_result->num_rows != 1) {
    return "";
  } else {
    $row = $sql_result->fetch_assoc();
    return $row['wine_pic_url'];
  }
}

function create_follow_relation($user, $user_to_follow) {
  $conn = db_connect();
  $sql = sprintf("SELECT * FROM user_following_relation WHERE user_id = %d AND follower_id = %d", $user_to_follow, $user);
  $sql_result = $conn->query($sql);
  if ($sql_result->num_rows == 1) {
    $sql = sprintf("UPDATE user_following_relation SET enabled = 1 WHERE user_id = %d AND follower_id = %d", $user_to_follow, $user);
    $conn->query($sql);
    return;
  } else {
    $sql = sprintf("INSERT INTO user_following_relation (user_id, follower_id, enabled) VALUES (%d, %d, 1)", $user_to_follow, $user);
    print($sql);
    $sql_result = $conn->query($sql);
    return;
  }
}

function destory_follow_relation($user, $user_to_follow) {
  $conn = db_connect();
  $sql = sprintf("SELECT * FROM user_following_relation WHERE user_id = %d AND follower_id = %d", $user_to_follow, $user);
  $sql_result = $conn->query($sql);
  if ($sql_result->num_rows == 1) {
    $sql = sprintf("UPDATE user_following_relation SET enabled = 0 WHERE user_id = %d AND follower_id = %d", $user_to_follow, $user);
    $conn->query($sql);
    return;
  }
}

// has user1 already followed user2 ??
function has_followed($user1, $user2) {
  $conn = db_connect();
  $sql = sprintf("SELECT * FROM user_following_relation WHERE user_id = %d AND follower_id = %d", $user2, $user1);
  $sql_result = $conn->query($sql);
  if ($sql_result->num_rows == 1) {
    $row = $sql_result->fetch_assoc();
    if ($row['enabled'] == 1) {
      return true;
    }
  }
  return false;
}

function prepareFriendListResponse($userIdArray) {
  $str = '';
  $result = array();
  foreach ($userIdArray as $userId) {
    $str = $str.',';
    $str = $str.((string)$userId);
  }
  $str = $str.')';
  $str[0] = '(';

  $sql = sprintf("SELECT * FROM user_account_info WHERE user_id IN %s", $str);
  $conn = db_connect();
  $sql_result = $conn->query($sql);
  for ($i = 0; $i < $sql_result->num_rows; $i++) {
    $row = $sql_result->fetch_assoc();
    $data = array();
    $data['userId'] = $row['user_id'];
    $data['userName'] = $row['user_name'];
    $data['sex'] = ($row['sex'] == 'm' ? "Male" : "Female");
    $data['firstName'] = $row['first_name'];
    $data['lastName'] = $row['last_name'];
    $data['photoUrl'] = $row['head_image_url'];
	$data['thirdParty'] = $row['third_party'];
    array_push($result, $data);
  }
  return $result;
}
