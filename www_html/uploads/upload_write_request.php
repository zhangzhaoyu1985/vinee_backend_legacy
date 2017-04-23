<?php

if($_SERVER['REQUEST_METHOD']=='POST'){
  /*$image = $_POST['image'];
  $path = "/home/ubuntu/zhaoyu/uploads/123.png";
  $actualpath = "http://50.18.207.106/home/ubuntu/zhaoyu/".$path;
  print("test2\n");
  print("actualPath = %s", $actualpath);
  file_put_contents($path,base64_decode($image));*/

  $image = $_POST['image'];
  $path = $_POST['url'];
  $prefix = 'http://50.18.207.106/';
  $replace_with = '/var/www/html/';
  $internal_url = str_replace($prefix, $replace_with, $path);

  file_put_contents($internal_url, base64_decode($image));
  echo('Photo upload succeed');
}else{
  echo "Error during photo uploading";
}


?>