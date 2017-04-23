#!/usr/bin/env php
<?php

namespace wineMateThrift\php;

error_reporting(E_ALL);

require_once __DIR__.'/../../lib/php/lib/Thrift/ClassLoader/ThriftClassLoader.php';
require_once('db_utils.php');
require_once('utils.php');

use Thrift\ClassLoader\ThriftClassLoader;

$GEN_DIR = realpath(dirname(__FILE__)).'/gen-php';

$loader = new ThriftClassLoader();
$loader->registerNamespace('Thrift', __DIR__ .'/../../lib/php/lib');
$loader->registerDefinition('wineMateThrift', $GEN_DIR);
$loader->register();

/*
 * Licensed to the Apache Software Foundation (ASF) under one
 * or more contributor license agreements. See the NOTICE file
 * distributed with this work for additional information
 * regarding copyright ownership. The ASF licenses this file
 * to you under the Apache License, Version 2.0 (the
 * "License"); you may not use this file except in compliance
 * with the License. You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing,
 * software distributed under the License is distributed on an
 * "AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY
 * KIND, either express or implied. See the License for the
 * specific language governing permissions and limitations
 * under the License.
 */

/*#!/usr/bin/env python

#
# Licensed to the Apache Software Foundation (ASF) under one
# or more contributor license agreements. See the NOTICE file
# distributed with this work for additional information
# regarding copyright ownership. The ASF licenses this file
# to you under the Apache License, Version 2.0 (the
# "License"); you may not use this file except in compliance
# with the License. You may obtain a copy of the License at
#
#   http://www.apache.org/licenses/LICENSE-2.0
#
# Unless required by applicable law or agreed to in writing,
# software distributed under the License is distributed on an
# "AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY
# KIND, either express or implied. See the License for the
# specific language governing permissions and limitations
# under the License.
#

import os
import BaseHTTPServer
import CGIHTTPServer

# chdir(2) into the tutorial directory.
os.chdir(os.path.dirname(os.path.dirname(os.path.realpath(__file__))))


class Handler(CGIHTTPServer.CGIHTTPRequestHandler):
    cgi_directories = ['/php']

BaseHTTPServer.HTTPServer(('', 8080), Handler).serve_forever()

 * This is not a stand-alone server.  It should be run as a normal
 * php web script (like through Apache's mod_php) or as a cgi script
 * (like with the included runserver.py).  You can connect to it with
 * THttpClient in any language that supports it.  The PHP tutorial client
 * will work if you pass it the argument "--http".
 */

if (php_sapi_name() == 'cli') {
  ini_set("display_errors", "stderr");
}

use Thrift\Protocol\TBinaryProtocol;
use Thrift\Transport\TPhpStream;
use Thrift\Transport\TBufferedTransport;
use Thrift\Server\TServerSocket;
use Thrift\Server\TSimpleServer;
use Thrift\Factory\TBinaryProtocolFactory;
use Thrift\Factory\TTransportFactory;

class wineMateThriftHandler implements \wineMateThrift\WineMateServicesIf {

  public function login(\wineMateThrift\User $user) {
    //var_dump($user);
    $response = new \wineMateThrift\LoginResult;

    $conn = db_connect();
    $sql = "SELECT * FROM user_account_info WHERE user_name = '".$user->userName."' and password = '".$user->password."'";
    $result = $conn->query($sql);
    if ($result && $result->num_rows == 1) {
	  $row = $result->fetch_assoc();
	  
	  if ($row['status'] == true) {
	    print("log in success , userName = ".$user->userName."\n");
	    print(intval(\wineMateThrift\LoginStatus::LOGIN_SUCCESS));
	    $response->status = \wineMateThrift\LoginStatus::LOGIN_SUCCESS;
	  } else {
	    print("log in user unactivated , userName = ".$user->userName."\n");
	    print(intval(\wineMateThrift\LoginStatus::LOGIN_UNACTIVATED));
	    $response->status = \wineMateThrift\LoginStatus::LOGIN_UNACTIVATED;
      }
	  
      $response->userId = $row['user_id'];
      return $response;
    }
  		
    print("log in failed , userName = ".$user->userName."\n");
    print(intval(\wineMateThrift\LoginStatus::LOGIN_FAILED));
    $response->status = \wineMateThrift\LoginStatus::LOGIN_FAILED;
    return $response;
  }
		
  public function loginWechat(\wineMateThrift\WechatLoginInfo $wechatLoginInfo) {
        printf("enter loginWechat");
        var_dump($wechatLoginInfo);
        $response = new \wineMateThrift\LoginResult;
        $conn = db_connect(true); // connect to master for writing
        $sql = "SELECT * FROM third_platform_logins WHERE third_platform_name = 'wechat' and third_platform_user_id = '".$wechatLoginInfo->openId."'";
        $result = $conn->query($sql);
        $now_ts = time();
        $response->status = \wineMateThrift\LoginStatus::LOGIN_SUCCESS;
        if ($result && $result->num_rows == 1) {
            $row = $result->fetch_assoc();
            $response->userId = $row['user_id'];
            print(intval(\wineMateThrift\LoginStatus::LOGIN_SUCCESS));
            print("loginWechat log in success , userName = ".$row['user_name']."\n");
			
			// Update userinfo in case they are changed.
			// TODO: Move this codes to another thread.
			$wechat_user_info = getWechatUserInfo($wechatLoginInfo);
            $sex = "";
            if ($wechat_user_info->sex == 1) {
                $sex = "m";
            } else if ($wechat_user_info->sex == 2) {
                $sex = "f";
            }
	        $sql = sprintf("UPDATE user_account_info SET first_name = '%s', head_image_url = '%s', sex = '%s', third_party = %d".
	                       " WHERE user_id = %d",
	                       $wechat_user_info->nickname,
	                       $wechat_user_info->headimgurl,
						   $sex,
                           \wineMateThrift\ThirdParty::WECHAT,
	                       $row['user_id']);
	        print($sql."\n");
	        $conn->query($sql);
        } else {
	    	$wechat_user_info = getWechatUserInfo($wechatLoginInfo);
            $sex = "";
            if ($wechat_user_info->sex == 1) {
                $sex = "m";
            } else if ($wechat_user_info->sex == 2) {
                $sex = "f";
            }
            $sql = sprintf("INSERT INTO user_account_info (user_name, email, password, last_login_time,
       registration_time, last_name, first_name, sex, age, year_of_birth,
       month_of_birth, day_of_birth, head_image_url, status, third_party) VALUES ('%s', '%s', '%s', %d, %d, '%s', '%s', '%s', %d, %d, %d, %d, '%s', %d, %d)",
                            $wechat_user_info->openid,
                            "",
                            "",
                            $now_ts,
                            $now_ts,
                            "",
                            $wechat_user_info->nickname,
                            $sex,
                            0,
                            0,
                            0,
                            0,
                            $wechat_user_info->headimgurl,
                            1,
                            \wineMateThrift\ThirdParty::WECHAT);
            print ($sql);
            $result = $conn->query($sql);
            printf ("New Record has id %d.\n", $conn->insert_id);
            $response->userId = $conn->insert_id;
            $sql = sprintf("insert into third_platform_logins (third_platform_name , third_platform_user_id , user_id ,
                            last_login_time , registration_time ) value ('%s', '%s', '%s', %d, %d)",
                            "wechat",
                            $wechatLoginInfo->openId,
                            $conn->insert_id,
                            $now_ts,
                            $now_ts);
            $result = $conn->query($sql);
        }
        mysqli_close($conn);
        return $response;
  }
  
  public function registration(\wineMateThrift\User $user) {
    if(empty($user->userName) || empty($user->email) || empty($user->password)) {
      return \wineMateThrift\RegistrationStatus::REGISTRATION_INVALID_INPUT;
    }
    $conn = db_connect(true);
    $sql_find_name = "SELECT * FROM user_account_info WHERE user_name ='".$user->userName."'";
    $res_find_name = $conn->query($sql_find_name);
    if ($res_find_name && $res_find_name->num_rows > 0) {
      print "registration failed, user name already exist\n";
      return \wineMateThrift\RegistrationStatus::REGISTRATION_DUPLICATE_USERNAME;
    }

    $sql_find_email = "SELECT * FROM user_account_info WHERE email ='".$user->email."'";
    $res_find_email = $conn->query($sql_find_email);
    if ($res_find_email && $res_find_email->num_rows > 0) {
      print "registration failed, email address already exist\n";
      return \wineMateThrift\RegistrationStatus::REGISTRATION_DUPLICATE_EMAIL;
    }

    $now_ts = time();
    $sql = sprintf("INSERT INTO user_account_info (user_name, email, password, last_login_time,
                   registration_time, last_name, first_name, sex, age, year_of_birth,
                   month_of_birth, day_of_birth) VALUES ('%s', '%s', '%s', %d, %d, '%s', '%s', '%s', %d, %d, %d, %d)",
                   $user->userName,
                   $user->email,
                   $user->password,
                   $now_ts,
                   $now_ts,
                   $user->lastName,
                   $user->firstName,
                   $user->sex,
                   $user->age,
                   $user->yearOfBirth,
                   $user->monthOfBirth,
                   $user->dayOfBirth);
                   // status: default to 0 (unactivated)
                   // third_party: default to 0 (NONE)
    $result = $conn->query($sql);
	
    // Send activation email to the newly created account.
	$userId = $conn->insert_id;
	$activate_email_stauts = send_activate_email($conn, $userId);
    if ($activate_email_stauts) {
	  print "Sent activate email to user: ".$userId."\n";
	} else {
	  print "ERROR: failed to send activate email to user: ".$userId."\n";
	}
	
	print "registration request successful\n";
    return \wineMateThrift\RegistrationStatus::REGISTRATION_SUCCESS;
  }

  public function sendActivateEmail($userId) {
    $conn = db_connect(true);
	return send_activate_email($conn, $userId);
  }
  
  public function activateAccount($userId, $activate) {
    $conn = db_connect(true);
	$sql = sprintf("SELECT * FROM user_account_info WHERE user_id = %d", $userId);
    $result = $conn->query($sql);
	if ($result && $result->num_rows > 0) {
	  $sql = sprintf("UPDATE user_account_info SET status = %d WHERE user_id = %d", $activate, $userId);
	  $conn->query($sql);
	  
	  // Check if successfully updated.=
	  $sql = sprintf("SELECT * FROM user_account_info WHERE user_id = %d AND status = %d", $userId, $activate);
	  $result = $conn->query($sql);
	  if ($result && $result->num_rows > 0) {
	    return true;
	  }
	}
	return false;
  }

  public function findPassword(\wineMateThrift\User $user) {
    print "find password request received";
    if (empty($user->userName) && empty($user->email)) {
      return \wineMateThrift\FindPasswordStatus::PW_FAILED;
    }

    $response = new \wineMateThrift\LoginResult;
    $conn = db_connect();
    $sql = "";
    if (!empty($user->userName)) {
      $sql .= "SELECT * FROM user_account_info WHERE user_name = '".$user->userName."'";
    } else {
      $sql .= "SELECT * FROM user_account_info WHERE email = '".$user->email."'";
    }

    $result = $conn->query($sql);
    if ($result && $result->num_rows == 1) {
      $row = $result->fetch_assoc();
      $email_address = $row['email'];
      $user_name = $row['user_name'];
      $pw = $row['password'];
      $user_id = $row['user_id'];
      sendEmailAboutPassword($email_address, $user_name, $pw, $user_id);
      return \wineMateThrift\FindPasswordStatus::PW_SUCCESS;
    }

    return \wineMateThrift\FindPasswordStatus::PW_FAILED;
  }

  public function authentication(\wineMateThrift\TagInfo $tag, $userId) {
    $wineInfo = new \wineMateThrift\WineInfo;
    $conn = db_connect(true);
    var_dump($tag);
    $sql_find_info = "SELECT * FROM tag_info WHERE tag_id = '".$tag->tagID."'";
    $sql_result = $conn->query($sql_find_info);
    if ($sql_result && $sql_result->num_rows > 0) {
      $row = $sql_result->fetch_assoc();

      // Check authentication key
      if ($tag->secretNumber != $row['authentication_key']) {
        $wineInfo->isGenuine = false;
        var_dump($wineInfo);
        return $wineInfo;
      }

      $wineInfo->isGenuine = true;
      $wineInfo->isSealed = ($row['is_open'] == 0 ? true : false);
      if ($wineInfo->isSealed == false) {
        $open_sql = "SELECT * from bottle_open_history WHERE tag_id = '".$tag->tagID."'";
        $open_result = $conn->query($open_sql);
        if ($open_result && $open_result->num_rows == 1) {
          $open_row = $open_result->fetch_assoc();
          var_dump($open_row['date']);
          var_dump($open_row['time']);
          $wineInfo->openedTime = $open_row['date'].", ".$open_row['time'];
          $wineInfo->openedCity = $open_row['city_name'];
          $wineInfo->openedCountry = $open_row['country_name'];
        }
      }
      $wineInfo->wineId = $row['wine_id'];

      $sql_wine_info = "";
      if ($tag->countryId == \wineMateThrift\CountryId::ENGLISH) {
        $sql_wine_info = "SELECT * FROM wine_basic_info_english WHERE wine_id = ".$wineInfo->wineId;
      } else {
        $sql_wine_info = "SELECT * FROM wine_basic_info_chinese WHERE wine_id = ".$wineInfo->wineId;
      }

      $wine_info_result = $conn->query($sql_wine_info);
      if ($wine_info_result && $wine_info_result->num_rows == 1) {
        $row = $wine_info_result->fetch_assoc();
        $wineInfo->wineName = $row['wine_name'];
        $wineInfo->winePicURL = $row['wine_pic_url'];
        $wineInfo->wineryName = $row['winery_name'];
        $wineInfo->wineryNationalFlagUrl = $row['national_flag_url'];
        $wineInfo->regionName = $row['region_name'];
	$wineInfo->wineryLogoPicUrl = $row['winery_logo_pic_url'];
        $wineInfo->year = strval($row['year']);
      }

      $ratingRequest = new \wineMateThrift\WineReviewAndRatingReadRequest;
      $ratingRequest->wineId = $wineInfo->wineId;
      $ratingReponse = $this->getWineReviewAndRating($ratingRequest);
      $wineInfo->wineRate = $ratingReponse->averageRate;
      $wineInfo->wechatShareUrl = getWineWechatShareUrl($wineInfo->wineId);
      var_dump($wineInfo->wechatShareUrl);
      $wineInfo->rewardPoint = getRewardPointsByActionType(\wineMateThrift\UserActions::OpenedBottle, $wineInfo->wineId);       


      $sql_scan_history = sprintf("REPLACE INTO wine_scan_history (wine_id, user_id, time_stamp, detailed_address, tag, city_name, date, time) VALUES (%d, %d, %d, '%s', '%s', '%s', '%s', '%s')",
                            $wineInfo->wineId, $userId, time(), $tag->detailedLocation, $tag->tagID, $tag->city, $tag->date, $tag->time);
      var_dump($sql_scan_history);                      
      $conn->query($sql_scan_history);
    } else {
      $wineInfo->isGenuine = false;
    }
    var_dump($wineInfo);
    return $wineInfo;
  }

  public function openBottle(\wineMateThrift\BottleOpenInfo $openInfo) {
    print("received open bottle request\n");
    $conn = db_connect(true);

    $sql_find_info = "SELECT * FROM tag_info WHERE tag_id = '".$openInfo->tagId."'";
    $sql_result = $conn->query($sql_find_info);

    if ($sql_result && $sql_result->num_rows == 1) {
      $row = $sql_result->fetch_assoc();
      if ($row['is_open'] == 1) {
        return false;
      }
      $db_open_num = $row['open_num'];
      $sql_find_info2 = "SELECT is_used FROM qr_code WHERE code = '".$openInfo->bottleOpenIdentifier."'";
      $sql_result2 = $conn->query($sql_find_info2);
      if ($sql_result2->num_rows < 1) {
        return false;
      } else {
        $row2 = $sql_result2->fetch_assoc();
        if ($row2['is_used'] == 1) {
          return false;
        } 
      }

      if ($row['is_open'] == 1) {
        return false;
      }
      $now = time();

      $open_time = $openInfo->date.":".$openInfo->time;
      $sql_update_open = "UPDATE tag_info SET is_open = 1, open_time = ".$now." , open_user_id = ".$openInfo->userId." WHERE tag_id = '".$openInfo->tagId."'";
      $conn->query($sql_update_open);

      $sql_update_open_history = sprintf("INSERT INTO bottle_open_history VALUES (%d, %d, %d, '%s', '%s', '%s', '%s', '%s')",
                            $row['wine_id'], $openInfo->userId, $now, $openInfo->city,
                            $openInfo->date, $openInfo->time, $openInfo->tagId, $openInfo->country);
      $conn->query($sql_update_open_history);
      $sql_update_qr_code = sprintf("UPDATE qr_code SET is_used = 1, time_used = %d WHERE code = '%s'",
                                     $now, $openInfo->bottleOpenIdentifier);
      $conn->query($sql_update_qr_code);
      return true;
    } else {
      return false;
    }
  }

  public function getBasicInfo(\wineMateThrift\WineBasicInfoRequest $request) {
    $response = new \wineMateThrift\WineBasicInfoResponse;
    //var_dump($request);
    $countryId = $request->countryId;
    $conn = db_connect();

    $table_name = ($countryId == 1 ? 'wine_basic_info_english' : 'wine_basic_info_chinese');
    $sql_find_info = "SELECT * FROM ".$table_name." WHERE wine_id = ".$request->wineId;
    $sql_result = $conn->query($sql_find_info);
    if ($sql_result && $sql_result->num_rows == 1) {
      print "successfully find wine info in database\n";
      $row = $sql_result->fetch_assoc();
      $response->wineName = $row['wine_name'];
      $response->wineryName = $row['winery_name'];
      $response->location = $row['winery_location'];
      $response->nationalFlagUrl = $row['national_flag_url'];
      $response->theWineInfo = $row['wine_info'];
      $response->foodPairingInfo = $row['food_pairing_info'];
      $response->cellaringInfo = $row['cellaring_info'];

      $response->foodParingPics = array();

      if (!empty($row['food_paring_pic_text']) && !empty($row['food_paring_pic_url'])) {
        $textVec = explode("#", $row['food_paring_pic_text']);
        $urlVec = explode("#", $row['food_paring_pic_url']);
        $size = sizeof($textVec);
        if(sizeof($urlVec) == $size && $size > 0) {
          for ($x = 0; $x < $size; $x++) {
            $foodParingInfo = new \wineMateThrift\FoodParingPics;
            $foodParingInfo->picName = $textVec[$x];
            $foodParingInfo->picUrl = $urlVec[$x];
            array_push($response->foodParingPics, $foodParingInfo);
          }
        }
      }

      $response->regionName = $row['region_name'];
      $response->regionInfo = $row['region_info'];
      $response->wineryWebsiteUrl = $row['winery_web_url'];
      $response->wineryLogoPicUrl = $row['winery_logo_pic_url'];
      $response->grapeInfo = $row['grape_info'];
      $response->wechatShareUrl = getWineWechatShareUrl($request->wineId);
	  $response->year = strval($row['year']);

      $sql = "SELECT * FROM wine_price WHERE wine_id = ".$request->wineId;
      $sql_result = $conn->query($sql);
      $sum_price = 0; // cents
      for ($i = 0; $i < $sql_result->num_rows; $i++){
        $row = $sql_result->fetch_assoc();
        $response->averagePrice = getPriceString($row['price'], $row['currency_id']);;
      }
    }
    //var_dump($response);
    return $response;
  }

  public function getWineReviewAndRating(\wineMateThrift\WineReviewAndRatingReadRequest $request) {
    //var_dump($request);
    $response = new \wineMateThrift\WineReviewAndRatingReadResponse;
    $response->data = array();
    $conn = db_connect();

    $score_array = array();
    $sql_score = "SELECT * FROM wine_rating_score WHERE wine_id = ".$request->wineId." ORDER BY time_stamp DESC LIMIT 10";
    $score_result = $conn->query($sql_score);
    $sum_score = 0.0;
    $response->numOfRating = $score_result->num_rows;
    for ($i = 0; $i < $score_result->num_rows; $i++) {
      $row = $score_result->fetch_assoc();
      $d = array();
      $d['reviewerId'] = $row['user_id'];
      $d['score'] = $row['score'];
      $d['timeStamp'] = $row['time_stamp'];
      $score_array[] = $d;
      $sum_score += $row['score'];
    }
    if ($response->numOfRating > 0) {
      $response->averageRate = (float)$sum_score/$response->numOfRating;
      $response->averageRate = ((int)($response->averageRate/0.5)) * 0.5;
    } else {
      $response->averageRate = 0.0;
    }

    $review_array = array();
    $sql_review = "SELECT * FROM wine_rating_review WHERE wine_id = ".$request->wineId." ORDER BY time_stamp DESC LIMIT 10";
    $review_result = $conn->query($sql_review);
    $response->numOfReview = $review_result->num_rows;
    for ($i = 0; $i < $review_result->num_rows; $i++) {
      $row = $review_result->fetch_assoc();
      $d = array();
      $d['reviewerId'] = $row['user_id'];
      $d['reviewContent'] = $row['review_content'];
      $d['timeStamp'] = $row['time_stamp'];
      $review_array[] = $d;
    }

    $combined_array = mergeScoreAndReview($conn, $score_array, $review_array);
    foreach ($combined_array as $data) {
      $data->isFollowed = has_followed($request->userId, $data->userId);
      array_push($response->data, $data);
    }
    return $response;
/*
    if ($sql_result && $sql_result->num_rows > 0) {
      print "successfully find wine rating in database\n";
      for ($i = 0; $i < $sql_result->num_rows; $i++){
        $row = $sql_result->fetch_assoc();
        $data = new \wineMateThrift\WineReviewAndRatingData;
        $sql_reviewer = "SELECT * FROM user_account_info WHERE user_id = ".$row['user_id'];
        $result_reviewer = $conn->query($sql_reviewer);
        if ($result_reviewer && $result_reviewer->num_rows > 0) {
          $row_reviewer = $result_reviewer->fetch_assoc();
          $data->reviewerUserName = $row_reviewer['user_name'];
          $data->reviewerFirstName = $row_reviewer['first_name'];
          $data->sex = ($row_reviewer['sex'] == "m" ? (\wineMateThrift\ReviewerSex::MALE) : (\wineMateThrift\ReviewerSex::FEMALE));
        }

        $data->rate = $row['score'];
        $data->reviewContent = $row['review_content'];
        if ($data->rate > 0) {
          $numRating++;
          $totalRating += $data->rate;
        }
        if (!empty($data->reviewContent)) {
          $numReview++;
        }
        $reviewTimeStamp = $row['time_stamp'];
        $elapsedSeconds = time() - $reviewTimeStamp;
        if ($elapsedSeconds > 0) {
          $data->timeElapsed = secs_to_h($elapsedSeconds);
        }
        array_push($response->data, $data);
      }
      $response->numOfRating = $numRating;
      $response->numOfReview = $numReview;
      if ($numRating > 0) {
        $response->averageRate = (double)$totalRating/$numRating;
        $response->averageRate = ((int)($response->averageRate / 0.5)) * 0.5;
      }
    }
    var_dump($response);
    return $response; */
  }

  public function writeWineReviewAndRating(\wineMateThrift\WineReviewAndRatingWriteRequest $request) {
    //var_dump($request);
    $response = new \wineMateThrift\WineReviewAndRatingWriteResponse;
    if($request->score < 0.5 && empty($request->reviewContent)) {
      $response->isSuccess = false;
      return $response;
    }

    $conn = db_connect(true);
    $now = time();
    if($request->score >= 0.5) {
      // check if this user has already give score to this wine,
      // if yes, replace the old score with new score
      $sql = "SELECT score FROM wine_rating_score WHERE wine_id = ".$request->wineId." AND user_id = ".$request->userId;
      $sql_result = $conn->query($sql);
      if ($sql_result && $sql_result->num_rows > 0) {
        print("overwrite score\n");
        $sql = sprintf("UPDATE wine_rating_score SET time_stamp = %d, score = %f".
                       " WHERE user_id = %d AND wine_id = %d",
                       $now,
                       $request->score,
                       $request->userId,
                       $request->wineId);
        print($sql."\n");
        $conn->query($sql);
      } else {
        print("insert new score\n");
        $sql = sprintf("INSERT INTO wine_rating_score VALUES (%d, %d, %d, %f)",
                $request->wineId,
                $request->userId,
                $now,
                $request->score);
        $conn->query($sql);
      }
    }

    if(!empty($request->reviewContent)) {
      print("insert new review\n");
      //$sql = "INSERT INTO wine_rating_score VALUES (".$request->wineId.", ".$request->userId.$request->reviewContent.", ".$request->score.", '".$request->reviewContent."', ".time().")";
      $sql = sprintf("INSERT INTO wine_rating_review VALUES (%d, %d, %d, '%s')",
              $request->wineId,
              $request->userId,
              $now,
              $request->reviewContent);
      $sql = $conn->query($sql);
    }
    $response->isSuccess = true;
    return $response;
  }

  public function getMyRateRecord(\wineMateThrift\MyRateRecordRequest $request) {
    //var_dump($request);

    $response = new \wineMateThrift\MyRateRecordResponse;
    $conn = db_connect();

    $sql_find_info = "SELECT score FROM wine_rating_score WHERE wine_id = ".$request->wineId." AND user_id = ".$request->userId;
    $sql_result = $conn->query($sql_find_info);

    if ($sql_result && $sql_result->num_rows == 1) {
      $row = $sql_result->fetch_assoc();
      $response->alreadyRated = true;
      $response->myRate = ((int)($row['score'] / 0.5)) * 0.5;
    }

    //var_dump($response);
    return $response;
  }

  public function getMyOpenedBottles(\wineMateThrift\MyBottlesRequest $request) {
    $response = new \wineMateThrift\OpenedBottlesResponse;
    $response->openedBottleHistory = array();

    $conn = db_connect();
    $sql_find_info = "SELECT sex, head_image_url FROM user_account_info WHERE user_id = ".$request->userId;
    $sql_result = $conn->query($sql_find_info);
    if($sql_result->num_rows > 0) {
      $row = $sql_result->fetch_assoc();
      $response->sex = ($row['sex'] == 'm' ? \wineMateThrift\ReviewerSex::MALE : \wineMateThrift\ReviewerSex::FEMALE);
	  $response->photoUrl = $row['head_image_url'];
    } else {
      $response->sex = \wineMateThrift\ReviewerSex::MALE;
    }

    $sql_find_info = "SELECT distinct wine_id FROM wine_rating_score WHERE user_id = ".$request->userId;
    $sql_result = $conn->query($sql_find_info);
    $response->ratedNumber = $sql_result->num_rows;

    $sql_find_info = "SELECT wine_id, city_name, date, time FROM bottle_open_history WHERE user_id = ".$request->userId." ORDER BY time_stamp DESC";
    $sql_result = $conn->query($sql_find_info);
    $response->openedNumber = $sql_result->num_rows;
    for($i = 0; $i < $sql_result->num_rows; $i++) {
      $row = $sql_result->fetch_assoc();
      $wineId = $row['wine_id'];
      $data = new \wineMateThrift\BottleInfo;
      $data->wineId = $wineId;
      $data->openDate = $row['date'];
      $data->openTime = $row['time'];
      $data->openCity = $row['city_name'];
      $table_name = ($request->countryId == 1 ? "wine_basic_info_english" : "wine_basic_info_chinese");
      $sql2 = "SELECT * FROM ".$table_name." WHERE wine_id = ".$wineId;
      $sql2 = $conn->query($sql2);
      if ($sql2 && $sql2->num_rows == 1) {
        $row2 = $sql2->fetch_assoc();
        $data->wineName = $row2['wine_name'];
        $data->wineryName = $row2['winery_name']; 
        $data->regionName = $row2['region_name'];
        $data->winePicUrl = $row2['wine_pic_url'];
        $data->nationalFlagUrl = $row2['national_flag_url'];
	$data->year = strval($row2['year']);
      }
      $response->openedBottleHistory[] = $data;
    }
    $response->totalWinesNumber = $response->openedNumber;

    // get scanned number
    $sql_find_info = "SELECT * FROM wine_scan_history WHERE user_id = ".$request->userId;
    $sql_result = $conn->query($sql_find_info);
    $response->scannedNumber = $sql_result->num_rows;

    //var_dump($response);
    return $response;
  }

  public function getMyScannedBottles(\wineMateThrift\MyBottlesRequest $request) {
    var_dump($request);
    $response = new \wineMateThrift\ScannedBottlesResponse;
    $response->scannedBottleHistory = array();
    $conn = db_connect();
    $scan_map = array();
    $sql_find_info = "SELECT wine_id, time_stamp FROM wine_scan_history WHERE user_id = ".$request->userId." ORDER BY time_stamp DESC";
    $sql_result = $conn->query($sql_find_info);

    for($i = 0; $i < $sql_result->num_rows; $i++) {
      $row = $sql_result->fetch_assoc();
      $wineId = $row['wine_id'];
      if (in_array($wineId, $scan_map)) {
        //$response->scannedBottleHistory[] = $scan_map[$wineId];
        continue;
      }
      $data = new \wineMateThrift\BottleInfo;
      $data->wineId = $wineId;
      $table_name = ($request->countryId == 1 ? "wine_basic_info_english" : "wine_basic_info_chinese");
      $sql2 = "SELECT * FROM ".$table_name." WHERE wine_id = ".$wineId;
      $sql2 = $conn->query($sql2);
      if ($sql2 && $sql2->num_rows == 1) {
        $row2 = $sql2->fetch_assoc();
        $data->wineName = $row2['wine_name'];
        $data->wineryName = $row2['winery_name']; 
        $data->regionName = $row2['region_name'];
        $data->winePicUrl = $row2['wine_pic_url'];
        $data->nationalFlagUrl = $row2['national_flag_url'];
	$data->year = strval($row2['year']);
      }
      $response->scannedBottleHistory[] = $data;
      $scan_map[$wineId] = $data;
    }
    return $response;
  }

  public function getMyRatedBottles(\wineMateThrift\MyBottlesRequest $request) {
    $response = new \wineMateThrift\ScannedBottlesResponse;
    $response->ratedBottleHistory = array();
    $conn = db_connect();
    $rated_map = array();
    $sql_find_info = "SELECT * FROM wine_rating_score WHERE user_id = ".$request->userId." ORDER BY time_stamp DESC";
    $sql_result = $conn->query($sql_find_info);
    for($i = 0; $i < $sql_result->num_rows; $i++) {
      $row = $sql_result->fetch_assoc();
      $wineId = $row['wine_id'];
      if (in_array($wineId, $rated_map)) {
        continue;
        //$response->openedBottleHistory[] = $rated_map[$wineId];
      }
      $data = new \wineMateThrift\BottleInfo;
      $data->wineId = $wineId;
      $table_name = ($request->countryId == 1 ? "wine_basic_info_english" : "wine_basic_info_chinese");
      $sql2 = "SELECT * FROM ".$table_name." WHERE wine_id = ".$wineId;
      $sql2 = $conn->query($sql2);
      if ($sql2 && $sql2->num_rows == 1) {
        $row2 = $sql2->fetch_assoc();
        $data->wineName = $row2['wine_name'];
        $data->wineryName = $row2['winery_name']; 
        $data->regionName = $row2['region_name'];
        $data->winePicUrl = $row2['wine_pic_url'];
        $data->nationalFlagUrl = $row2['national_flag_url'];
        $data->myRate = $row['score'];
        $data->year = strval($row2['year']);
      }
      $response->scannedBottleHistory[] = $data;
      $rated_map[$wineId] = $data;
    }
    return $response;
  }

  public function getMyNewsFeed(\wineMateThrift\NewsFeedRequest $request) {
    //var_dump($request);
    $response = new \wineMateThrift\NewsFeedResponse;
    $response->response = array();

    // add the posts added by system administrator (not by individuals)
    insert_system_posts($request, $response);

    // get a list of people this user is following, and put their open bottle
    // history into newsfeed
    $conn = db_connect();
    $sql = "SELECT user_id FROM user_following_relation WHERE follower_id = ".$request->userId;
    $sql_result = $conn->query($sql);
    $users_str = ""; // example: "1, 3, 4, 5"
    for ($i = 0; $i < $sql_result->num_rows; $i++) {
      $row = $sql_result->fetch_assoc();
      if ($i > 0) {
        $users_str = $users_str.",";
      }
      $users_str = $users_str.$row['user_id'];
    }
    $sql = "SELECT wine_id, user_id, time_stamp, date FROM bottle_open_history WHERE user_id IN (".$users_str.") ORDER BY time_stamp DESC";
    $sql_result = $conn->query($sql);
    for ($i = 0; $i < $sql_result->num_rows; $i++) {
      $row = $sql_result->fetch_assoc();
      $data = new \wineMateThrift\NewsFeedData;
      $data->feedType = \wineMateThrift\FeedType::USERFEED;
      $data->userid = $row['user_id'];
      $user_info = get_user_info_from_id($conn, $row['user_id']);
      if (is_array($user_info)) {
        if ($user_info[3] == 0) {
		  // Not third party user. Use user_name.
		  $data->authorName = $user_info[0];
		} else {
		  // Third party user. Use first_name.
		  $data->authorName = $user_info[2];
		}
        $data->authorUrl = $user_info[1];
        if ($request->countryId == \wineMateThrift\CountryId::ENGLISH) {
          $data->feedTitle = $data->authorName." opened a bottle in Vinee!";
        } else {
          $data->feedTitle = $data->authorName." 在葡萄藤里开了一瓶酒！";
        }
      }
      $data->date = $row['date'];
      $data->picUrl = get_wine_pic_url_from_id($conn, $row['wine_id']);

      $bottle_info = new \wineMateThrift\BottleInfo;
      $bottle_info->wineId = $row['wine_id'];
      $bottle_info->winePicUrl = $data->picUrl;
      $data->bottleInfo = $bottle_info;

      array_push($response->response, $data);
    }

    //var_dump($response);
    return $response;
  }

  public function getWineryInfo(\wineMateThrift\WineryInfoRequest $request) {
    //var_dump($request);
    $response = new \wineMateThrift\WineryInfoResponse;
    $response->wineryWineList = array();
    var_dump($request);

    $conn = db_connect();

    $sql_wine_info = "";
    if ($request->countryId == 1) {
      $sql_wine_info = "SELECT * FROM wine_basic_info_english WHERE winery_name = '".$request->wineryName."'";
    } else {
      $sql_wine_info = "SELECT * FROM wine_basic_info_chinese WHERE winery_name = '".$request->wineryName."'";
    }

    $wine_info_result = $conn->query($sql_wine_info);
    for($i = 0; $i < $wine_info_result->num_rows; $i++) {
      $row = $wine_info_result->fetch_assoc();
      $item = new \wineMateThrift\WineryInfoResponseSingleItem;
      $item->wineId = $row['wine_id'];
      $item->wineName = $row['wine_name'];
      $item->winePicUrl = $row['wine_pic_url'];
      $item-> year = strval($row['year']);
      array_push($response->wineryWineList, $item);
    }

    if ($request->countryId == 1) {
      $sql_winery_info = "SELECT * FROM winery_info_English WHERE winery_name = '".$request->wineryName."'";
    } else {
      $sql_winery_info = "SELECT * FROM winery_info_Chinese WHERE winery_name = '".$request->wineryName."'";
    }

    print($sql_winery_info);
    $sql_winery_result = $conn->query($sql_winery_info);
    if($sql_winery_result->num_rows != 1) {
      return $response;
    }
    $row = $sql_winery_result->fetch_assoc();
    $gallery_id = $row['winery_gallery_id'];
    $content_id = $row['winery_content_id'];
	$website_url = $row['winery_website_url'];
	
	$response->wineryWebsiteUrl = $website_url;

    $sql = "SELECT * FROM content_info WHERE content_id = ".$content_id;
    $sql_result = $conn->query($sql);

    $info_content_list = array();
    for($i = 0; $i < $sql_result->num_rows; $i++) {
      $row = $sql_result->fetch_assoc();
      $single_content = new \wineMateThrift\WineryInfoSingleContent;
      $single_content->title = $row['title'];
      $single_content->briefText = $row['brief_text'];
      $single_content->url = $row['expand_url'];
      array_push($info_content_list, $single_content);
    }
    $response->wineryInfoContents = $info_content_list;

    $photo_list = array();
    $sql = "SELECT * FROM gallery_photos WHERE gallery_id = ".$gallery_id;
    $sql_result = $conn->query($sql);
    for($i = 0; $i < $sql_result->num_rows; $i++) {
      $row = $sql_result->fetch_assoc();
      array_push($photo_list, $row['photo_url']);
    }
    $response->wineryPhotoUrls = $photo_list;
    var_dump($response);
    return $response;
  }

  public function followUser($user, $userToFollow) {
    create_follow_relation($user, $userToFollow);
  }

  public function unfollowUser($user, $userToUnfollow) {
    destory_follow_relation($user, $userToUnfollow);
  }

  public function addFriend($user1, $user2) {
    create_follow_relation($user1, $user2);
    create_follow_relation($user2, $user1);
  }

  public function getFriendList(\wineMateThrift\FriendListRequest $friendListRequest) {

    $userId = $friendListRequest->userId;
    $response = new \wineMateThrift\FriendListResponse;
    $conn = db_connect();
    $response->friendList = array();

    $sql = sprintf("SELECT DISTINCT follower_id FROM user_following_relation WHERE user_id = %d AND enabled = 1", $userId);
    $userFollowMe = array();
    $result = $conn->query($sql);
    for($i = 0; $i < $result->num_rows; $i++) {
      $row = $result->fetch_assoc();
      array_push($userFollowMe, $row['follower_id']);
    }

    $sql = sprintf("SELECT DISTINCT user_id FROM user_following_relation WHERE follower_id = %d AND enabled = 1", $userId);
    $userIFollow = array();
    $result = $conn->query($sql);
    for($i = 0; $i < $result->num_rows; $i++) {
      $row = $result->fetch_assoc();
      array_push($userIFollow, $row['user_id']);
    }
    
    var_dump($userFollowMe, $userIFollow);

    $friendList = array_intersect($userFollowMe, $userIFollow);
    $friendInfoArray = prepareFriendListResponse($friendList);

    foreach($friendInfoArray as $friendInfo) {
      $data = new \wineMateThrift\FriendInfo;
      $data->userId = $friendInfo['userId'];
      $data->userName = $friendInfo['userName'];
      $data->sex = $friendInfo['sex'];
      $data->firstName = $friendInfo['firstName'];
      $data->lastName = $friendInfo['lastName'];
      $data->isFollowing = True;
      $data->isFollowed = True;
      $data->photoUrl = $friendInfo['photoUrl'];
      $data->thirdParty = $friendInfo['thirdParty'];
      array_push($response->friendList, $data);
    }
    return $response;
  }

  public function searchFriend($friendPrefix) {
      $response = new \wineMateThrift\FriendListResponse;
      $conn = db_connect();
      $response->friendList = array();
      
      print ($friendPrefix."\n");
      // Strip special char to prevent XSS hacking.
      $cleanPrefix = filter_var($friendPrefix, FILTER_SANITIZE_STRING);
      // Match user_name prefix
      $sql = "SELECT DISTINCT user_id FROM user_account_info WHERE user_name LIKE '".$cleanPrefix."%'";
      // Match first_name prefix, since wechat nickname is stored in first_name.
      $sql .= " OR first_name LIKE '".$cleanPrefix."%'";
      $result = $conn->query($sql);
      $userIdList = array();
      for($i = 0; $i < $result->num_rows; $i++) {
        $row = $result->fetch_assoc();
        array_push($userIdList, $row['user_id']);
      }
      var_dump($result);
      $friendInfoArray = prepareFriendListResponse($userIdList);
      foreach($friendInfoArray as $friendInfo) {
        $data = new \wineMateThrift\FriendInfo;
        $data->userId = $friendInfo['userId'];
        $data->userName = $friendInfo['userName'];
        $data->sex = $friendInfo['sex'];
        $data->firstName = $friendInfo['firstName'];
        $data->lastName = $friendInfo['lastName'];
        $data->isFollowing = True;
        $data->isFollowed = True;
        $data->photoUrl = $friendInfo['photoUrl'];
        $data->thirdParty = $friendInfo['thirdParty'];
        array_push($response->friendList, $data);
      }
      return $response;
  }
  
  public function getMyFollowingList(\wineMateThrift\FriendListRequest $request) {
    $userId = $request->userId;
    $response = new \wineMateThrift\MyFollowingListResponse;
    $response->myFollowingList = array();
    $conn = db_connect();

    $sql = sprintf("SELECT DISTINCT user_id FROM user_following_relation WHERE follower_id = %d AND enabled = 1", $userId);
    print($sql);
    $userIFollow = array();
    $result = $conn->query($sql);
    for($i = 0; $i < $result->num_rows; $i++) {
      $row = $result->fetch_assoc();
      array_push($userIFollow, $row['user_id']);
    }

    $userFollowMe = array();
    $sql = sprintf("SELECT DISTINCT follower_id FROM user_following_relation WHERE user_id = %d AND enabled = 1", $userId);
    $result = $conn->query($sql);
    for($i = 0; $i < $result->num_rows; $i++) {
      $row = $result->fetch_assoc();
      array_push($userFollowMe, $row['follower_id']);
    }

    $friendInfoArray = prepareFriendListResponse($userIFollow);
    var_dump($userIFollow);
    var_dump($friendInfoArray);
    foreach($friendInfoArray as $friendInfo) {
      $data = new \wineMateThrift\FriendInfo;
      $data->userId = $friendInfo['userId'];
      $data->userName = $friendInfo['userName'];
      $data->sex = $friendInfo['sex'];
      $data->firstName = $friendInfo['firstName'];
      $data->lastName = $friendInfo['lastName'];
      $data->isFollowing = True;
      $data->isFollowed = in_array($userId, $userFollowMe);
      $data->photoUrl = $friendInfo['photoUrl'];
      $data->thirdParty = $friendInfo['thirdParty'];
      array_push($response->myFollowingList, $data);
    }
    var_dump($response);
    return $response;
  }

  public function getMyFollowersList(\wineMateThrift\FriendListRequest $request) {
    $userId = $request->userId;
    $response = new \wineMateThrift\MyFollowersListResponse;
    $response->myFollowersList = array();
    $conn = db_connect();
    $sql = sprintf("SELECT DISTINCT follower_id FROM user_following_relation WHERE user_id = %d AND enabled = 1", $userId);
    var_dump($sql);
    $userFollowMe = array();
    $result = $conn->query($sql);
    for($i = 0; $i < $result->num_rows; $i++) {
      $row = $result->fetch_assoc();
      array_push($userFollowMe, $row['follower_id']);
    }

    $userIFollow = array();
    $sql = sprintf("SELECT DISTINCT user_id FROM user_following_relation WHERE follower_id = %d AND enabled = 1", $userId);
    $result = $conn->query($sql);
    for($i = 0; $i < $result->num_rows; $i++) {
      $row = $result->fetch_assoc();
      array_push($userIFollow, $row['user_id']);
    }

    $friendInfoArray = prepareFriendListResponse($userFollowMe);

    foreach($friendInfoArray as $friendInfo) {
      $data = new \wineMateThrift\FriendInfo;
      $data->userId = $friendInfo['userId'];
      $data->userName = $friendInfo['userName'];
      $data->sex = $friendInfo['sex'];
      $data->firstName = $friendInfo['firstName'];
      $data->lastName = $friendInfo['lastName'];
      $data->isFollowed = True;
      $data->isFollowing = in_array($userId, $userIFollow);
      $data->photoUrl = $friendInfo['photoUrl'];
      $data->thirdParty = $friendInfo['thirdParty'];
      array_push($response->myFollowersList, $data);
    }
    return $response;
  }

  public function getTagPassword($tagId) {
    $conn = db_connect();
    $sql = "SELECT tag_password FROM tag_info WHERE tag_id = '".$tagId."'";
    $result = $conn->query($sql);
    if ($result->num_rows == 1) {
      $row = $result->fetch_assoc();
      if ($row['tag_password']) {
        return $row['tag_password'];
      } else {
        return '';
      }
    }
    return '';
  }

  public function getMyProfile($observerId, $userId) {
    $response = new \wineMateThrift\MyProfile;
    $userStruct = new \wineMateThrift\User;

    $conn = db_connect();
    $sql = "SELECT * FROM user_account_info WHERE user_id=".$userId;
    $result = $conn->query($sql);
    $show_profile = 1;
    if ($result && $result->num_rows == 1) {
      $row = $result->fetch_assoc();
      $userStruct->userName = $row['user_name'];
      $userStruct->email = $row['email'];
      $userStruct->password = $row['password'];
      $userStruct->lastName = $row['last_name'];
      $userStruct->firstName = $row['first_name'];
      $userStruct->sex = ($row['sex'] == 'm' ? "Male" : "Female");
      $userStruct->age = $row['age'];
      $userStruct->yearOfBirth = $row['year_of_birth'];
      $userStruct->monthOfBirth = $row['month_of_birth'];
      $userStruct->dayOfBirth = $row['day_of_birth'];
      $userStruct->photoUrl = $row['head_image_url'];
      $userStruct->thirdParty = $row['third_party'];
      $show_profile = $row['show_profile_to_stranger'];
    }
    
    $sql = "SELECT point FROM user_reward_point WHERE user_id=".$userId;
    $result = $conn->query($sql);
    if ($result && $result->num_rows == 1) {
      $row = $result->fetch_assoc();
      $userStruct->rewardPoints = $row['point'];
    }
    
    $response->user = $userStruct;

    $sql = sprintf("SELECT COUNT(*) as number FROM `wine_wishlist` where user_id = %d AND enabled = 1", $userId);
    $result = $conn->query($sql);
    if ($result->num_rows == 1) {
      $row = $result->fetch_assoc();
      $response->wishlistNumber = $row['number'];
    } 

    $sql = sprintf("SELECT DISTINCT user_id FROM user_following_relation WHERE follower_id = %d AND enabled = 1", $userId);
    $result = $conn->query($sql);
    $response->followingNumber = $result->num_rows;

    $sql = sprintf("SELECT DISTINCT follower_id FROM user_following_relation WHERE user_id = %d AND enabled = 1", $userId);
    $result = $conn->query($sql);
    $response->followerNumber = $result->num_rows;

    $sql = sprintf("SELECT * FROM user_following_relation WHERE user_id = %d AND follower_id = %d AND enabled = 1", $userId, $observerId);
    $result = $conn->query($sql);
    $response->isFollowing = ($result->num_rows == 1 ? True : False);

    $sql = sprintf("SELECT * FROM user_following_relation WHERE follower_id = %d AND user_id = %d AND enabled = 1", $userId, $observerId);
    $result = $conn->query($sql);
    $response->isFollowed = ($result->num_rows == 1 ? True : False);

    $response->hideProfileToStranger = ($show_profile == 1 ? False : True);

    $sql = "SELECT wine_id FROM wine_rating_score WHERE user_id = ".$userId;
    $result = $conn->query($sql);
    $response->ratedNumber = $result->num_rows;
    return $response;
  }

  public function updateMyProfile(\wineMateThrift\User $userStruct) {
    return true;
  }

  public function setPrivacy($userId, $hideProfileToStranger) {
    $conn = db_connect(true);

    $sql = "SELECT user_id FROM user_account_info WHERE user_id=".$userId;
    $result = $conn->query($sql);
    if (!$result || $result->num_rows != 1) {
      return false;
    }
    $show_profile = ($hideProfileToStranger ? 0 : 1);
    $sql = "UPDATE user_account_info SET show_profile_to_stranger = ".$show_profile." WHERE user_id = ".$userId;
    $result = $conn->query($sql);
    return true;
  }

  public function addRewardPoints(\wineMateThrift\AddRewardPointsRequest $addRewardPointsRequest) {
    $conn = db_connect(true);
     var_dump($addRewardPointsRequest);
    $response = \wineMateThrift\AddRewardPointsResponse::AlreadyEarned;
    $userId = $addRewardPointsRequest->userId;
    $actionType = $addRewardPointsRequest->useAction;
    $wineId = $addRewardPointsRequest->wineId;
    $objectId = ($actionType == \wineMateThrift\UserActions::ShareWineInfoToWechat ? $wineId : 0);
    $score = getRewardPointsByActionType($actionType, $wineId);
    if ($score <= 0) {
       print("Invalid actionType !");
       return $response;
    }

    $sql = sprintf("SELECT * FROM user_reward_history WHERE user_id = %d AND action_type = %d", $userId, $actionType);
    if ($objectId > 0) {
      $sql = $sql.sprintf(" AND object_id = %d", $objectId);
    }
    print($sql);    
    $result = $conn->query($sql);
    
    if ($result->num_rows > 0) {
      return $response;
    }

    if ($objectId == 0) {
      $sql = sprintf("INSERT INTO user_reward_history (user_id, action_type, point, time_created) VALUES (%d, %d, %d, %d)",
                     $userId, $actionType, $score, time());
    } else {
      $sql = sprintf("INSERT INTO user_reward_history (user_id, action_type, object_id, point, time_created) VALUES (%d, %d, %d, %d, %d)",
                     $userId, $actionType, $objectId, $score, time());
    }
    print(44444444444444);
    $result = $conn->query($sql); 

    $sql = sprintf("SELECT * FROM user_reward_point WHERE user_id = %d", $userId);
    $result = $conn->query($sql);
    if (!$result || $result->num_rows == 0) {
      $sql = sprintf("INSERT INTO user_reward_point (user_id, point, time_updated) VALUES (%d, %d, %d)", $userId, $score, time());
    } else {
      $sql = sprintf("UPDATE user_reward_point SET point = point + %d, time_updated = %d",
                   $score, time());
    }
    $response = \wineMateThrift\AddRewardPointsResponse::Success;
    $conn->query($sql);
    return $response;
  }

  public function getMyRewardPoints($userId) {
    $conn = db_connect();
    $sql = "SELECT point FROM user_reward_point WHERE user_id = ".$userId;
    $result = $conn->query($sql);
    if ($result->num_rows == 1) {
      $row = $result->fetch_assoc();
      return $row['point'];
    } 
    return -1;
  }
  
  public function getMyWishlist(\wineMateThrift\MyBottlesRequest $request) {
    $response = new \wineMateThrift\MyWishListResponse;
    $response->wishList = array();
    $conn = db_connect();
    $wishlist_map = array();
    $sql_find_info = sprintf("SELECT DISTINCT wine_id FROM wine_wishlist WHERE user_id = %d AND enabled = 1", $request->userId);
    $sql_result = $conn->query($sql_find_info);
    for ($i = 0; $i < $sql_result->num_rows; $i++) {
      $row = $sql_result->fetch_assoc();
      $wineId = $row['wine_id'];
      if (in_array($wineId, $wishlist_map)) {
        continue;
      }
      $data = new \wineMateThrift\BottleInfo;
      $data->wineId = $wineId;
      $table_name = ($request->countryId == 1 ? "wine_basic_info_english" : "wine_basic_info_chinese");
      $sql2 = "SELECT * FROM ".$table_name." WHERE wine_id = ".$wineId;
      $sql2 = $conn->query($sql2);
      if ($sql2 && $sql2->num_rows == 1) {
        $row2 = $sql2->fetch_assoc();
        $data->wineName = $row2['wine_name'];
        $data->wineryName = $row2['winery_name']; 
        $data->regionName = $row2['region_name'];
        $data->winePicUrl = $row2['wine_pic_url'];
        $data->nationalFlagUrl = $row2['national_flag_url'];
        $data->year = strval($row2['year']);
      }
      $response->wishList[] = $data;
      $wishlist_map[$wineId] = $data;
    }
	$response->success = true;
    return $response;
  }
  
  public function addToWishlist(\wineMateThrift\AddToWishlistRequest $request) {
	$user_id = $request->userId;
	$wine_id = $request->wineId;
	$enabled = $request->enabled;
	$conn = db_connect(true);
    $sql = sprintf("SELECT * FROM wine_wishlist WHERE user_id = %d AND wine_id = %d", $user_id, $wine_id);
	$sql_result = $conn->query($sql);
	if ($sql_result->num_rows > 0) {
	  $sql = sprintf("UPDATE wine_wishlist SET enabled = %d WHERE user_id = %d AND wine_id = %d", $enabled, $user_id, $wine_id);
	  $conn->query($sql);
	  return true;
	} else {
	  $sql = sprintf("INSERT INTO wine_wishlist (user_id, wine_id, enabled) VALUES (%d, %d, %d)", $user_id, $wine_id, $enabled);
	  $sql_result = $conn->query($sql);
	  return true;
    }
  }
  
  public function isInWishlist($userId, $wineId) {
    $response = new \wineMateThrift\IsInWishlistResponse;
	$conn = db_connect();
    $sql = sprintf("SELECT wine_id FROM wine_wishlist WHERE user_id = %d AND wine_id = %d AND enabled = 1", $userId, $wineId);
	$sql_result = $conn->query($sql);
	$response->success = true;
	$response->isInList = ($sql_result->num_rows > 0) ? true : false;
	return $response;
  }

  public function getUserPhoto($userId){
    $response = new \wineMateThrift\UserPhotoResponse;
    $response->receiverScriptUrl = "http://50.18.207.106/uploads/upload_write_request.php";
    $conn = db_connect(true);
    $sql = sprintf("SELECT head_image_url FROM user_account_info WHERE user_id = %d", $userId);
    $sql_result = $conn->query($sql);
    if ($sql_result && $sql_result->num_rows == 1) {
      $row = $sql_result->fetch_assoc();
      if (strlen($row['head_image_url']) > 0) {
        $response->userPhotoUrl = $row['head_image_url'];
        $response->alreadyUploaded = url_file_exist($row['head_image_url']);
        return $response;
      }
    }
    
    $response->userPhotoUrl = generate_photo_url_by_user_id($userId);
    $response->alreadyUploaded = false;
    $sql = sprintf("UPDATE user_account_info SET head_image_url = '%s' WHERE user_id = %d", $response->userPhotoUrl, $userId);
    $sql_result = $conn->query($sql);
    var_dump($response);
    return $response;
  }
  
  public function getRewardItemList(\wineMateThrift\RewardItemRequest $request) {
    $response = new \wineMateThrift\RewardItemResponse;
    $response->rewardItemList = array();

    $conn = db_connect();
    // TODO don't use hardcoded winery_name
    $winery_name = 'Tamburlaine';
    $winery_id = 0; // TODO change request from wineryName to wineryId
    $wine_info_table_name = ($request->countryId == \wineMateThrift\CountryId::ENGLISH ? 'wine_basic_info_english' : 'wine_basic_info_chinese');
    $sql = sprintf("SELECT r.winery_id AS winery_id, r.wine_id AS wine_id, w.wine_name, w.wine_pic_url, w.year, w.region_name, r.reward_point, r.stock_num FROM wine_reward_info r INNER JOIN %s w ON r.wine_id = w.wine_id WHERE r.winery_name = '%s'", $wine_info_table_name, $winery_name);
    $sql_result = $conn->query($sql);
    for($i = 0; $i < $sql_result->num_rows; $i++) {
      $row = $sql_result->fetch_assoc();
      $winery_id = $row['winery_id'];
      $rewardItem = new \wineMateThrift\RewardSingleItem;
      $rewardItem->wineId = $row['wine_id'];
      $rewardItem->wineName = $row['wine_name'];
      $rewardItem->winePicUrl = $row['wine_pic_url'];
      $rewardItem->year = $row['year'];
      $rewardItem->region = $row['region_name'];
      $rewardItem->points = $row['reward_point'];
      $rewardItem->outOfStock = ($row['stock_num'] <= 0 ? True : False);
      array_push($response->rewardItemList, $rewardItem);
    }
    
    $sql = sprintf("SELECT point FROM user_reward_point WHERE user_id = %d AND winery_id = %d", $request->userId, $winery_id);
    $sql_result = $conn->query($sql);
    for($i = 0; $i < $sql_result->num_rows; $i++) {
      $row = $sql_result->fetch_assoc();
      $response->currentPoints = $row['point'];
    }
    return $response;
  }
  
  public function rewardRedeem(\wineMateThrift\RewardRedeemRequest $request) {
    $response = new \wineMateThrift\RewardRedeemResponse;
        
    $user_id = $request->userId;
    $conn = db_connect(true);
    
    // Check if user is activated.
    $sql = "SELECT status FROM user_account_info WHERE user_id = '".$user_id."'";
    $sql_result = $conn->query($sql);
    if ($sql_result && $sql_result->num_rows > 0) {
      $row = $sql_result->fetch_assoc();
      if ($row['status'] != true) {
        $response->resp_code = \wineMateThrift\RewardRedeemResponseCode::ACCOUNT_UNACTIVATED;
        return $response;
      }
    } else {
      $response->resp_code = \wineMateThrift\RewardRedeemResponseCode::FAILED;
      return $response;
    }
    
    $sql = sprintf("SELECT point FROM user_reward_point WHERE user_id = %d", $user_id);
    $sql_result = $conn->query($sql);
     
    $remaining_score = 0;
    for ($i = 0; $i < $sql_result->num_rows; $i++){
       $row = $sql_result->fetch_assoc();
       $remaining_score = $row['point'];
    }
    
    $consume_score = 0;
    $wine_id_to_score = array();
    $wine_id_to_number = array();
    $wine_id_list = array();
    foreach ($request->RewardRedeemItems as $item) {
      array_push($wine_id_list, $item->wineId);
    }
   
    $sql= sprintf("SELECT wine_id, reward_point, stock_num FROM wine_reward_info WHERE wine_id IN (%s) AND stock_num > 0", implode(",", $wine_id_list));
    $sql_result = $conn->query($sql);
    for ($i = 0; $i < $sql_result->num_rows; $i++){
      $row = $sql_result->fetch_assoc();
      $wine_id_to_score[$row['wine_id']] = $row['reward_point'];
      $wine_id_to_number[$row['wine_id']] = $row['stock_num'];
    }
    
    if (sizeof($wine_id_to_score) != sizeof($request->RewardRedeemItems)) {
      // some of the wine in request is out of stock, return false
      $response->resp_code = \wineMateThrift\RewardRedeemResponseCode::FAILED;
      $response->remainingPoints = $remaining_score;
      return $response;
    }
     
    $content_str = "";
    $is_first = True;
    foreach ($request->RewardRedeemItems as $item) {
      if ($wine_id_to_number[$item->wineId] < $item->quantity) {
        $response->resp_code = \wineMateThrift\RewardRedeemResponseCode::FAILED;
        $response->remainingPoints = $remaining_score;
        return $response;
      }
      $consume_score = $consume_score + $item->quantity * $wine_id_to_score[$item->wineId];
      if (!$is_first) {
        $content_str = $content_str.", ";
      }
      $is_first = False;
      $content_str = $content_str.sprintf("{wineID %d: %d bottles}", $item->wineId, $item->quantity);
    }

    if ($consume_score > $remaining_score){
      $response->resp_code = \wineMateThrift\RewardRedeemResponseCode::FAILED;
      $response->remainingPoints = $remaining_score;
      return $response;
    }

    $sql= sprintf("INSERT INTO reward_redeem_transaction (user_id, content, point, status, address_country, address_province, address_city, address_street, address_zip, phone_number, email, contact_name, time_created, time_updated, tracking_number) VALUES (%d, '%s', %d, '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', %d, %d, '%s');", 
       $user_id,
       $content_str,
       $consume_score,
       'I', /*initiatiate this transaction*/
       'China', /* Country */
       $request->address->province,
       $request->address->city,
       $request->address->street,
       $request->address->zipCode,
       $request->address->phoneNumber,
       $request->address->email,
       $request->address->fullName,
       time(),
       time(),
       $request->trackingNumber);
    $conn->query($sql);   

    $response->resp_code = \wineMateThrift\RewardRedeemResponseCode::SUCCESS;
    $response->remainingPoints = $remaining_score - $consume_score;
    $sql = sprintf("UPDATE user_reward_point SET point = %d, time_updated = %d WHERE user_id = %d AND winery_id = 1", $response->remainingPoints, time(), $user_id);    
    $conn->query($sql);
    return $response;
  }
  
  public function getAllBottles(\wineMateThrift\AllBottlesRequest $request) {
    var_dump($request);
    $response = new \wineMateThrift\AllBottlesResponse;
    $response->allBottles = array();
    $conn = db_connect();
    $table_name = ($request->countryId == 1 ? "wine_basic_info_english" : "wine_basic_info_chinese");
    $sql_query = "SELECT * FROM ".$table_name;
    $sql_result = $conn->query($sql_query);

    for($i = 0; $i < $sql_result->num_rows; $i++) {
      $row = $sql_result->fetch_assoc();
      $data = new \wineMateThrift\BottleInfo;
      $data->wineId = $row['wine_id'];
      $data->wineName = $row['wine_name'];
      $data->wineryName = $row['winery_name']; 
      $data->regionName = $row['region_name'];
      $data->winePicUrl = $row['wine_pic_url'];
      $data->nationalFlagUrl = $row['national_flag_url'];
      $data->year = $row['year'];
      $data->wineryName = $row['winery_name'];
      $ratingRequest = new \wineMateThrift\WineReviewAndRatingReadRequest;
      $ratingRequest->wineId = $row['wine_id'];
      $ratingReponse = $this->getWineReviewAndRating($ratingRequest);
      $data->averageRate = $ratingReponse->averageRate;
      $response->allBottles[] = $data;
    }
    var_dump($response);
    return $response;
  }
};

header('Content-Type', 'application/x-thrift');
if (php_sapi_name() == 'cli') {
  echo "\r\n";
}

$handler = new wineMateThriftHandler();
$processor = new \wineMateThrift\WineMateServicesProcessor($handler);

$serverTransport = new TServerSocket('0.0.0.0',7892);
//$serverTransport = new TServerSocket('localhost',7892); // For local debug

//$serverTransport->listen();
$tfactory = new TTransportFactory();
$pfactory = new TBinaryProtocolFactory();
$server = new TSimpleServer($processor, $serverTransport, $tfactory, $tfactory, $pfactory, $pfactory);
$server->serve();

/*$transport = new TBufferedTransport(new TPhpStream(TPhpStream::MODE_R | TPhpStream::MODE_W));;
$protocol = new TBinaryProtocol($transport, true, true);
$transport->open();
$processor->process($protocol, $protocol);
transport->close();*/
