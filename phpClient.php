#!/usr/bin/env php
<?php
namespace wineMateThrift\php;
error_reporting(E_ALL);
require_once __DIR__.'/../../lib/php/lib/Thrift/ClassLoader/ThriftClassLoader.php';
use Thrift\ClassLoader\ThriftClassLoader;
$GEN_DIR = realpath(dirname(__FILE__)).'/gen-php';
$loader = new ThriftClassLoader();
$loader->registerNamespace('Thrift', __DIR__ .'/../../lib/php/lib');
$loader->registerDefinition('wineMateThrift', $GEN_DIR);
$loader->register();

use Thrift\Protocol\TBinaryProtocol;
use Thrift\Transport\TSocket;
use Thrift\Transport\THttpClient;
use Thrift\Transport\TBufferedTransport;
use Thrift\Exception\TException;

function testLogin($client, $user) {
  $login_result = $client->login($user);
  if ($login_result->status === \wineMateThrift\LoginStatus::LOGIN_SUCCESS) {
    printf("User %d has successfully logged in\n", $login_result->userId);
  } else if ($login_result->status === \wineMateThrift\LoginStatus::LOGIN_UNACTIVATED) {
	print("User account is unactivated.\n");
  } else {
	print("User log in fail !!!\n");
  }

  $registration_result = $client->registration($user);
  if ($registration_result === \wineMateThrift\RegistrationStatus::REGISTRATION_SUCCESS) {
	   echo "User successfully registered !"."\n";
  } else {
	   printf("User registration failure, error code = %d \n", (int)$registration_result);
  }

  print("test find password \n\n");
  $login_result = $client->findPassword($user);
  var_dump($login_result);
}

function testRegistration($client) {
  $user = new \wineMateThrift\User(array(
      'userName' => 'testRegistration',
      'email' => 'test@gmail.com',
      'password' => 'password',
      'lastName' => 'lastName',
      'firstName' => 'firstName',
      'sex' => 'f',
      'age' => 18,
      'yearOfBirth' => 1986,
      'monthOfBirth' => 11,
      'dayOfBirth' => 24
    ));
  $login_result = $client->registration($user);
}

function testFindPassword($client) {
  $user = new \wineMateThrift\User(array(
      'email' => 'haoliu.ucsd@gmail.com',
    ));
  $result = $client->findPassword($user);
}

function testSendActivateEmail($client, $userId) {
  $resp = $client->sendActivateEmail($userId);
  var_dump($resp);
  print("\n\n\n\n\n");
}

function testActivateAccount($client, $userId, $activate) {
  $resp = $client->activateAccount($userId, $activate);
  var_dump($resp);
  print("\n\n\n\n\n");
}

function testAuthentication($client, $tag, $user) {
  print("test authentication \n\n");
  $wine_info = $client->authentication($tag, $user);
  var_dump($wine_info);
  print("\n\n\n\n\n");
}

function testOpenBottle($client, $bottle) {
  print("test openBottle \n\n");
  $open_ok = $client->openBottle($bottle);
  if ($open_ok == true) {
    print("open successfully\n");
  } else {
    print("open failed \n");
  }
  print("\n\n\n\n\n");
}

function testGetBasicInfo($client) {
  print("test getBasicInfo \n\n");
  $request = new \wineMateThrift\WineBasicInfoRequest(
              array(
                'wineId' => 1,
                'countryId' => 2,
              )
            );
  $response = $client->getBasicInfo($request);
  var_dump($response);
  print("\n\n\n\n\n");
}

function testGetWineReviewAndRating($client) {
  print("test getWineReviewAndRating \n\n");
  $request = new \wineMateThrift\WineReviewAndRatingReadRequest(
              array(
                'wineId' => 2,
              )
            );
  $response = $client->getWineReviewAndRating($request);
  var_dump($response);
  print("\n\n\n\n\n");
}

function testGetMyRateRecord($client) {
  print("test getMyRateRecord \n\n");
  $request = new \wineMateThrift\MyRateRecordRequest(
              array(
                'userId' => 1,
                'wineId' => 1,
              )
            );
  $response = $client->getMyRateRecord($request);
  var_dump($response);
  print("\n\n\n\n\n");
}

function testWriteWineReviewAndRating($client, $wineId, $userId, $score = 0.0, $reviewContent = ''){
  $request = new \wineMateThrift\WineReviewAndRatingWriteRequest(
              array(
                'wineId' => $wineId,
                'userId' => $userId,
                'score' => $score,
                'reviewContent' => $reviewContent,
              )
            );
  $response = $client->writeWineReviewAndRating($request);
  var_dump($response);
  print("\n\n\n\n\n");
}


function testMyBottles($client, $userId) {
  $request = new \wineMateThrift\MyBottlesRequest(
              array(
                'userId' => $userId,
                'countryId' => 2,
              )
            );
  $response = $client->getMyBottles($request);
  var_dump($response);
  print("\n\n\n\n\n");
}

function testNewsFeed($client) {
  $request = new \wineMateThrift\NewsFeedRequest(
              array(
                'userId' => 3,
                'countryId' => \wineMateThrift\CountryId::CHINESE,
              )
            );
  $response = $client->getMyNewsFeed($request);
  var_dump($response);
  print("\n\n\n\n\n");
}

function testGetTagPassword($client, $tagId) {
  $tagId = '048AEA0A6E4D80';
  $response = $client->getTagPassword($tagId);
  var_dump($response);
  print("\n\n\n\n\n");
}

function testGetMyProfile($client, $userId, $observerId) {
  $response = $client->getMyProfile($userId, $observerId);
  var_dump($response);
}

function testGetWineryInfo($client, $wineryName) {
  $request = new \wineMateThrift\WineryInfoRequest(
    array(
      'wineryName' => $wineryName,
      'countryId' => 1,
    )
  );
  $response = $client->getWineryInfo($request);
  var_dump($response);
}

function testGetMyFollowingList($client, $userId) {
  $request = new \wineMateThrift\FriendListRequest(
    array(
      'userId' => $userId,
    )
  );
  $response = $client->getMyFollowingList($request);
  var_dump($response);
}

function testGetMyFollowersList($client, $userId) {
  $request = new \wineMateThrift\FriendListRequest(
    array(
      'userId' => $userId,
    )
  );
  $response = $client->getMyFollowersList($request);
  var_dump($response);
}

function testRewardProgram($client) {
  $request = new \wineMateThrift\AddRewardPointsRequest(
    array(
      'userId' => 2,
      'useAction' => \wineMateThrift\UserActions::ShareWineInfoToWechat,
      'wineId' => 1,
    )
  );
  $response = $client->addRewardPoints($request);
  var_dump($response); 
  $scoreResponse = $client->getMyRewardPoints(3); 
  var_dump($scoreResponse);
  $response = $client->addRewardPoints($request);
  var_dump($response); 
  $scoreResponse = $client->getMyRewardPoints(3); 
  var_dump($scoreResponse);


  /*$request = new \wineMateThrift\AddRewardPointsRequest(
    array(
      'userId' => 2,
      'userAction' => \wineMateThrift\UserActions::OpenedBottle,
    )
  );
  $response = $client->addRewardPoints($request);
  var_dump($response);
  $scoreResponse = $client->getMyRewardPoints(2); 
  var_dump($scoreResponse); */
}

function testGetRewardItem($client, $userId, $wineryName){
  $request = new \wineMateThrift\RewardItemRequest(
               array(
                 'userId' => $userId,
                 'wineryName' => $wineryName,
                 'countryId' => \wineMateThrift\CountryId::CHINESE
               ));
  $response = $client->getRewardItemList($request);
  var_dump($response);
}

function testRewardRedeem($client, $userId){
  $address = new \wineMateThrift\Address(
               array(
                 'province' => 'Hubei',
                 'city' => 'Wuhan',
                 'street' => 'Hongshan Road 2017',
                 'zipCode' => '430074',
                 'phoneNumber' => '8888888888',
                 'email' => 'abc@tagtalk.com',
                 'fullName' => 'test name',
               )
             );

  $redeemItem1 = new \wineMateThrift\RewardRedeemSingleItem(
                   array('wineId' => 1,
                         'quantity' => 2,
                   ));

  $redeemItem2 = new \wineMateThrift\RewardRedeemSingleItem(
                  array('wineId' => 2,
                  'quantity' => 1)
                 );

  $redeemItems = array($redeemItem1, $redeemItem2);
  $request = new \wineMateThrift\RewardRedeemRequest(
               array(
                 'userId' => $userId,
                 'RewardRedeemItems' => $redeemItems,
                 'address' => $address,
                 'trackingNumber' => 'TrackingNumberTest',
               ));
  var_dump($request);
  $response = $client->rewardRedeem($request);
  var_dump($response);
}

function testAddToWishList($client, $userId, $wineId, $enabled) {
  $request = new \wineMateThrift\AddToWishlistRequest(
  				array(
	  			  	'userId' => $userId,
	  			  	'wineId' => $wineId,
	  			  	'enabled' => $enabled,
  			  	)
  		  	);
  
  $response = $client->addToWishlist($request);
  var_dump($response);
  print("\n\n\n\n\n");
}

function testGetMyWishlist($client, $userId, $countryId) {
  $request = new \wineMateThrift\MyBottlesRequest(
  				array(
	  			  	'userId' => $userId,
	  			  	'countryId' => $countryId,
  			  	)
  		  	);
  
  $response = $client->getMyWishlist($request);
  var_dump($response);
  print("\n\n\n\n\n");
}

function testIsInWishlist($client, $userId, $wineId) {
  $response = $client->isInWishlist($userId, $wineId);
  var_dump($response);
  print("\n\n\n\n\n");
}

function testGetMyFriendList($client, $userId) {
  $request = new \wineMateThrift\FriendListRequest;
  $request->userId = $userId;
  $response = $client->getFriendList($request);
  var_dump($response);
}

function testSearchFriend($client, $friendPrefix) {
  $response = $client->searchFriend($friendPrefix);
  var_dump($response);
  print("\n\n\n\n\n");
} 

try {
  if (array_search('--http', $argv)) {
    //$socket = new THttpClient('localhost', 8080, '/tagtalk_dev/PhpServer.php');
   //$socket = new THttpClient('ec2-54-67-111-151.us-west-1.compute.amazonaws.com', 8080, '/php/PhpServer.php');
    $socket = new THttpClient('ec2-50-18-207-106.us-west-1.compute.amazonaws.com', 7890, '/tagtalk_dev/PhpServer_good_with_py.php');
  } else {
    //$socket = new TSocket('localhost', 8080, '/tagtalk_dev/PhpServer.php');
	 // $socket = new THttpClient('localhost', 8080, '/tagtalk_dev/PhpServer.php')
        //$socket = new TSocket('54.223.152.54', 7890); // China server elastic IP
        //$socket = new TSocket('54.223.152.54', 7890);
         $socket = new TSocket('127.0.0.1', 7890);
	//$socket = new TSocket('localhost', 7890); // For local debug
	//$socket = new TSocket('ec2-54-67-111-151.us-west-1.compute.amazonaws.com', 7890);
  }
  //$transport = new TBufferedTransport($socket, 1024, 1024);
  $transport = $socket;
  $transport->open();
  $protocol = new TBinaryProtocol($transport);
  $client = new \wineMateThrift\WineMateServicesClient($protocol);
  $user = new \wineMateThrift\User(array('userName' => 'zhaoyuzhang',
    'email' => 'zhangzhaoyu1985@gmail.com', 'password' => 'password'));

  $tag = new \wineMateThrift\TagInfo(
              array(
                'tagID' => '041EEB0A6E4D81',
                'secretNumber' => 12345688,
                'countryId' => 2,
                'city' => 'San Jose',
              )
           );

  $bottle = new \wineMateThrift\BottleOpenInfo(
              array(
                //'tagID' => '0424220AF04981',
                //'userName' => 'yaoliu',
                'tagId' => '04285BF2304C81',
                'userId' => 2,
                'wineId' => 2,
                'bottleOpenIdentifier' => '1cc500329b8e3fed5e386917da0af231ad8b65a108754ddc56bbf3f6c2f51ec1',
                'date' => "Aug.7",
                'time' => "4:26 PM",
                'city' => "Beijing",
                'detailedLocation' => "37.56396639, -122.270695824",
                'country' => 'US',
              )
            );

  $userId = 2;

  //testLogin($client, $user);
  testAuthentication($client, $tag, $userId);
  //testRegistration($client);
  //itestFindPassword($client); not good
  //testSendActivateEmail($client, 3); not good
  //testActivateAccount($client, 2, true);
  
  //testOpenBottle($client, $bottle);
  //testGetBasicInfo($client);
  //testGetWineReviewAndRating($client);
  //testGetMyRateRecord($client);
  //testWriteWineReviewAndRating($client, 1, 1, 0.0, 'haha');
  //testWriteWineReviewAndRating($client, 1, 1, 3.0, '');
  //testWriteWineReviewAndRating($client, 1, 1, 1.5, 'kkkk');
  //testMyBottles($client, 2);
  //testNewsFeed($client);
  //testGetMyFriendList($client, $userId);
  //testSearchFriend($client, "first");
  
  //testGetMyProfile($client, 2, 3);
  //testGetWineryInfo($client, 'Tamburlaine');
  //testGetTagPassword($client, $tag->tagID);

  //testGetMyFollowingList($client, 2);
  //testGetMyFollowersList($client, 2);
  //testRewardProgram($client);
  //testRewardRedeem($client, 14); 
  //testGetRewardItem($client, 2, 'Tamburlaine');

  //testAddToWishList($client, 1000, 3, false);
  //testGetMyWishlist($client, 1000, 2);
  //testIsInWishlist($client, 1000, 3);
    
  $transport->close();
} catch (TException $tx) {
  print 'TException: '.$tx->getMessage()."\n";
}
?>
