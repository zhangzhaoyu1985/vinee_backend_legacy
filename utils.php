<?php
namespace wineMateThrift\php;
#require_once './libs/PHPMailer-master/PHPMailerAutoload.php';
require_once '/home/ubuntu/yaoliu/thrift/tutorial/tagtalk_dev/libs/PHPMailer-master/PHPMailerAutoload.php';
#require_once 'libs/PHPMailer-master/PHPMailerAutoload.php';
require_once 'PEAR.php';
require_once 'Mail.php';

// Send email from Amazon SES service. 
// Setup steps: http://docs.aws.amazon.com/ses/latest/DeveloperGuide/send-using-smtp-php.html
// This is prefered over sending email via mail(), since from the receiver's point of view, the emaill is sent by a real email account instead of system.
// Currently we send our emails from contactus@tagtalk.co. We can change it in the future.
// To do that, follow the instruction to verify the email account.
// http://docs.aws.amazon.com/ses/latest/DeveloperGuide/verify-email-addresses.html#verify-email-addresses-procedure
function sendByAmazonSES($to, $subject, $message) {
	$SENDER = 'TagTalk <contactus@tagtalk.co>';
	$USERNAME = 'AKIAJPKQ3TXKK54SOSIA';
	$PASSWORD = 'AltL/sj+ZQIX/IAcvFb5VevRsAB7qACPzm4BS66Kfx/+';
	$HOST = 'email-smtp.us-west-2.amazonaws.com';
	$PORT = '587';
	$headers = array (
  		'From' => $SENDER,
  		'To' => $to,
  		'Subject' => $subject,
		'MIME-Version' => 1,
    		'Content-type' => 'text/html;charset=iso-8859-1');

	$smtpParams = array (
 		'host' => $HOST,
  		'port' => $PORT,
  		'auth' => true,
  		'username' => $USERNAME,
  		'password' => $PASSWORD);

	 // Create an SMTP client.
	$mail = \Mail::factory('smtp', $smtpParams);

	// Send the email.
	$result = $mail->send($to, $headers, $message);

	if (\PEAR::isError($result)) {
  		echo("Email not sent. " .$result->getMessage() ."\n");
		return false;
	} else {
		return true;
	}

}

// Send email by PhpMailer.
// This is also sending email from a real account, so it is prefered over mail().
// Currently we first use sendByAmazonSES, if it failed, we use this function, if this function failed, we fallback to mail().
function sendByPhpMailer($to, $subject, $message) {
	// Use tagtalk Gmail account to send.
        $sender = 'tagtalk.co@gmail.com';
        $ps = "TagTalk78388!";

        $mail = new \PHPMailer;
        //$mail->SMTPDebug = 3;                               // Enable verbose debug output
        $mail->isSMTP();                                      // Set mailer to use SMTP
        $mail->Host = 'smtp.gmail.com;smtp.live.com';  // Specify main and backup SMTP servers
        $mail->SMTPAuth = true;                               // Enable SMTP authentication
        $mail->Username = $sender;                 // SMTP username
        $mail->Password = $ps;                           // SMTP password
        $mail->SMTPSecure = 'tls';                            // Enable TLS encryption, `ssl` also accepted
        $mail->Port = 587;                                    // TCP port to connect to
        //$mail->Port = 465;
        $mail->setFrom($sender, 'TagTalk Support');
        $mail->addAddress($to);     // Add a recipient
        $mail->addReplyTo($sender, 'TagTalk Support');
        $mail->isHTML(true);                                  // Set email format to HTML
        $mail->Subject = $subject;
        $mail->Body    = $message;
        if(!$mail->send()) {
                echo 'PhpMailer Error: ' . $mail->ErrorInfo;
		return false;
        } else {
		return true;
        }
}

function getPriceString($priceInCents, $currencyIndex) {
  // currencyId => currencyName string
  $currencyMap = array(
                  1 => "$",
                  );
  $result = "";
  $price = (float)$priceInCents/100;
  $result = $result.$price;
  if (in_array($currencyIndex, $currencyMap)) {
    $unit = $currencyMap[$currencyIndex];
    $result = "$unit ".$result;
  } else {
    $result = "$ ".$result;
  }
  return $result;
}

function secs_to_h($secs) {
        $units = array(
                "day"    =>   24*3600,
                "hour"   =>      3600,
                "minute" =>        60,
        );
        $s = "";
        foreach ( $units as $name => $divisor ) {
                if ( $quot = intval($secs / $divisor) ) {
                        $s .= "$quot $name";
                        $s .= (abs($quot) > 1 ? "s" : "");
                        break;
                }
        }
        if(empty($s)) {
          $s = "just now";
          return;
        }

        return $s." ago";
}

function sendEmailAboutPassword($email_address, $user_name, $pw, $user_id) {
  $to  = $email_address;
  print("1");
  // subject
  $subject = 'Your account information for WineMate';
  // message

  $message = sprintf('
  <html>
    <head>
      <title>Your account information for WineMate</title>
    </head>
    <body>
      <p>Here is your account information for WineMate!</p>
      <table>
        <tr>
          <th>User Name </th><th> Email </th><th> Password </th><th> User Id </th>
        </tr>
        <tr>
          <td>%s</td><td>%s</td><td>%s</td><td>%d</td>
        </tr>
      </table>
    </body>
  </html>', $user_name, $email_address, $pw, $user_id);

  // Send from contactus@tagtalk.co by using Amazon SES. 
  if (!sendByAmazonSES($to, $subject, $message)) {
    // If send by Amazon SES failed, send by using PhpMailer.
    if (!sendByPhpMailer($to, $subject, $message)) {
      // If send by TagTalk email account failed, fallback to send by system.
      // This is not prefered because from the receiver's point of view, 
      // the email is sent from system instead of a real account, and 
      // is more likedly to be put into spam box.
      $headers  = 'MIME-Version: 1.0' . "\r\n";
      $headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
      $headers .= 'From: support@tagtalk.co' . "\r\n" .
      $headers .= 'Reply-To: support@tagtalk.co' . "\r\n" .
      $headers .= "Return-Path: support@tagtalk.co\r\n";
      mail($to, $subject, $message, $headers);
    }
  }
}

function mergeScoreAndReview($conn, $score_array, $review_array) {
  // merge sort, ordered by time
  $p1 = 0;
  $p2 = 0;
  $result = array();
  while($p1 < count($score_array) && $p2 < count($review_array)) {
    $score = $score_array[$p1];
    $review = $review_array[$p2];
    $data = new \wineMateThrift\WineReviewAndRatingData;
    if ($score['timeStamp'] > $review['timeStamp']) {
      $sql = "SELECT user_name, first_name, sex, head_image_url, third_party FROM user_account_info WHERE user_id = ".$score['reviewerId'];
      $data->userId = $score['reviewerId'];
      $sql = $conn->query($sql);
      if($sql->num_rows > 0) {
        $row = $sql->fetch_assoc();
        $data->reviewerUserName = $row['user_name'];
		$data->reviewerFirstName = $row['first_name'];
        $data->sex = ($row['sex'] == 'm' ? \wineMateThrift\ReviewerSex::MALE : \wineMateThrift\ReviewerSex::FEMALE);
		$data->photoUrl = $row['head_image_url'];
        $data->thirdParty = $row['third_party'];
        $data->reviewContent = '';
        $data->rate = $score['score'];
        $elapsedSeconds = time() - $score['timeStamp'];
        if ($elapsedSeconds > 0) {
          $data->timeElapsed = secs_to_h($elapsedSeconds);
        }
      }
      $result[] = $data;
      $p1 ++;
    } else if ($score['timeStamp'] < $review['timeStamp']) {
      $sql = "SELECT user_name, first_name, sex, head_image_url, third_party FROM user_account_info WHERE user_id = ".$review['reviewerId'];
      $data->userId = $review['reviewerId'];
      $sql = $conn->query($sql);
      if($sql->num_rows > 0) {
        $row = $sql->fetch_assoc();
        $data->reviewerUserName = $row['user_name'];
		$data->reviewerFirstName = $row['first_name'];
        $data->sex = ($row['sex'] == 'm' ? \wineMateThrift\ReviewerSex::MALE : \wineMateThrift\ReviewerSex::FEMALE);
		$data->photoUrl = $row['head_image_url'];
        $data->thirdParty = $row['third_party'];
        $data->reviewContent = $review['reviewContent'];
        $data->rate = 0.0;
        $elapsedSeconds = time() - $review['timeStamp'];
        if ($elapsedSeconds > 0) {
          $data->timeElapsed = secs_to_h($elapsedSeconds);
        }
      }
      $result[] = $data;
      $p2 ++;
    } else {
      if ($score['reviewerId'] == $review['reviewerId']) {
        $sql = "SELECT user_name, first_name, sex, head_image_url, third_party FROM user_account_info WHERE user_id = ".$review['reviewerId'];
        $data->userId = $review['reviewerId'];
        $sql = $conn->query($sql);
        if($sql->num_rows > 0) {
          $row = $sql->fetch_assoc();
          $data->reviewerUserName = $row['user_name'];
		  $data->reviewerFirstName = $row['first_name'];
          $data->sex = ($row['sex'] == 'm' ? \wineMateThrift\ReviewerSex::MALE : \wineMateThrift\ReviewerSex::FEMALE);
		  $data->photoUrl = $row['head_image_url'];
		  $data->thirdParty = $row['third_party'];
          $data->reviewContent = $review['reviewContent'];
          $data->rate = $score['score'];
          $elapsedSeconds = time() - $review['timeStamp'];
          if ($elapsedSeconds > 0) {
            $data->timeElapsed = secs_to_h($elapsedSeconds);
          }
        }
        $result[] = $data;
        $p1 ++;
        $p2 ++;
      } else {
        $sql = "SELECT user_name, first_name, sex, head_image_url, third_party FROM user_account_info WHERE user_id = ".$review['reviewerId'];
        $data->userId = $review['reviewerId'];
        $sql = $conn->query($sql);
        if($sql->num_rows > 0) {
          $row = $sql->fetch_assoc();
          $data->reviewerUserName = $row['user_name'];
		  $data->reviewerFirstName = $row['first_name'];
          $data->sex = ($row['sex'] == 'm' ? \wineMateThrift\ReviewerSex::MALE : \wineMateThrift\ReviewerSex::FEMALE);
		  $data->photoUrl = $row['head_image_url'];
		  $data->thirdParty = $row['third_party'];
          $data->reviewContent = $review['reviewContent'];
          $data->rate = 0.0;
          $elapsedSeconds = time() - $review['timeStamp'];
          if ($elapsedSeconds > 0) {
            $data->timeElapsed = secs_to_h($elapsedSeconds);
          }
        }
        $result[] = $data;
        $p2 ++;
      }
    }
  }

  if($p1 == count($score_array)) {
    while ($p2 < count($review_array)) {
      $data = new \wineMateThrift\WineReviewAndRatingData;
      $review = $review_array[$p2];
      $sql = "SELECT user_name, first_name, sex, head_image_url, third_party FROM user_account_info WHERE user_id = ".$review['reviewerId'];
      $data->userId = $review['reviewerId'];
      $sql = $conn->query($sql);
      if($sql->num_rows > 0) {
        $row = $sql->fetch_assoc();
        $data->reviewerUserName = $row['user_name'];
		$data->reviewerFirstName = $row['first_name'];
        $data->sex = ($row['sex'] == 'm' ? \wineMateThrift\ReviewerSex::MALE : \wineMateThrift\ReviewerSex::FEMALE);
		$data->photoUrl = $row['head_image_url'];
		$data->thirdParty = $row['third_party'];
        $data->reviewContent = $review['reviewContent'];
        $data->rate = 0.0;
        $elapsedSeconds = time() - $review['timeStamp'];
        if ($elapsedSeconds > 0) {
          $data->timeElapsed = secs_to_h($elapsedSeconds);
        }
      }
      $result[] = $data;
      $p2 ++;
    }
  } else {
    while ($p1 < count($score_array)) {
      $score = $score_array[$p1];
      $data = new \wineMateThrift\WineReviewAndRatingData;
      $sql = "SELECT user_name, first_name, sex, head_image_url, third_party FROM user_account_info WHERE user_id = ".$score['reviewerId'];
      $data->userId = $score['reviewerId'];
      $sql = $conn->query($sql);
      if($sql->num_rows > 0) {
        $row = $sql->fetch_assoc();
        $data->reviewerUserName = $row['user_name'];
		$data->reviewerFirstName = $row['first_name'];
        $data->sex = ($row['sex'] == 'm' ? \wineMateThrift\ReviewerSex::MALE : \wineMateThrift\ReviewerSex::FEMALE);
		$data->photoUrl = $row['head_image_url'];
		$data->thirdParty = $row['third_party'];
        $data->reviewContent = '';
        $data->rate = $score['score'];
        $elapsedSeconds = time() - $score['timeStamp'];
        if ($elapsedSeconds > 0) {
          $data->timeElapsed = secs_to_h($elapsedSeconds);
        }
      }
      $result[] = $data;
      $p1 ++;
    }
  }
  return $result;
}

function insert_system_posts($request, $response) {
  if ($request->countryId == \wineMateThrift\CountryId::ENGLISH) {
    $data = new \wineMateThrift\NewsFeedData;
    $data->feedType = \wineMateThrift\FeedType::SYSTEMFEED;
    $data->authorName = "WineMate";
    $data->feedTitle = "Vinee Recommendation";
    $data->contentTitle = "5 Wines from the World's Smallest Wine Country";
    $data->contentAbstract = "Belgium is known throughout the world for its chocolate and beer, but did you know wine grapes have grown on Belgian soil since the early Middle Ages? ";
    $data->date = "2016/08/09";
    $data->picUrl = "http://50.18.207.106/pics/newsfeed_contents/five_wines_from_the_world_smallest_wine_country/5_wines_from_the_world_smallest_wine_country.png";
    $data->contentUrl = "http://50.18.207.106/pics/newsfeed_contents/five_wines_from_the_world_smallest_wine_country/five_wines_from_the_world_smallest_wine_country.html";
    array_push($response->response, $data);

    $data1 = new \wineMateThrift\NewsFeedData;
    $data1->feedType = \wineMateThrift\FeedType::SYSTEMFEED;
    $data1->authorName = "WineMate";
    $data1->feedTitle = "Vinee Recommendation";
    $data1->contentTitle = "7 Ways to Keep Wine Cool This Summer";
    $data1->contentAbstract = "For wine lovers, there's one major challenge every summer. Hot weather make it hard to achieve the most enjoyable serving temperature for wine.";
    $data1->date = "2016/08/09";
    $data1->picUrl = "http://50.18.207.106/pics/newsfeed_contents/seven_ways_to_keep_wine_cool/7_ways_to_keep_wine_cool_this_summer.png";
    $data1->contentUrl = "http://50.18.207.106/pics/newsfeed_contents/seven_ways_to_keep_wine_cool/seven_ways_to_keep_wine_cool_this_summer.html";
    array_push($response->response, $data1);

    $data2 = new \wineMateThrift\NewsFeedData;
    $data2->feedType = \wineMateThrift\FeedType::SYSTEMFEED;
    $data2->authorName = "WineMate";
    $data2->feedTitle = "Vinee Recommendation";
    $data2->contentTitle = "The Best Wine For Hot Dogs and Sausages";
    $data2->contentAbstract = "When summer BBQs and baseball games start filling up the calendar, it's safe to say hot dogs will be on the menu, and wine can be there too!";
    $data2->date = "2016/08/08";
    $data2->picUrl = "http://50.18.207.106/pics/newsfeed_contents/the_best_wine_this_summer/the_best_wine_this_summer.png";
    $data2->contentUrl = "http://50.18.207.106/pics/newsfeed_contents/the_best_wine_this_summer/the_best_wine_this_summer.html";
    array_push($response->response, $data2);
  } else {
    $authorName = "葡萄藤";
    $title = "葡萄藤推荐";
    $data = new \wineMateThrift\NewsFeedData;
    $data->feedType = \wineMateThrift\FeedType::SYSTEMFEED;
    $data->authorName = $authorName;
    $data->feedTitle = $title;
    $data->contentTitle = "三分钟学会如何保存红酒";
    $data->contentAbstract = "对嗜酒之人来说，一次喝光一整瓶红酒，似乎不是件难事。但总是有些时候会剩下那么一点儿，倒掉又太浪费，到底该怎么办呢？";
    $data->date = "2017/02/14";
    $data->picUrl = "http://50.18.207.106/pics/newsfeed_contents/NewsFeedChinese_2.png";
    $data->contentUrl = "http://50.18.207.106/pics/newsfeed_contents/NewsFeedChinese_2.html";
    array_push($response->response, $data);

    $data1 = new \wineMateThrift\NewsFeedData;
    $data1->feedType = \wineMateThrift\FeedType::SYSTEMFEED;
    $data1->authorName = $authorName;
    $data1->feedTitle = $title;
    $data1->contentTitle = "天生一对，葡萄酒的辛香料拍档";
    $data1->contentAbstract = "每种料理，都会有最适合他的辛香料，它们分量往往不多，也并非主角，却是不可或缺的得力助手。";
    $data1->date = "2017/02/14";
    $data1->picUrl = "http://50.18.207.106/pics/newsfeed_contents/NewsFeedChinese_3.png";
    $data1->contentUrl = "http://50.18.207.106/pics/newsfeed_contents/NewsFeedChinese_3.html";
    array_push($response->response, $data1);

    $data2 = new \wineMateThrift\NewsFeedData;
    $data2->feedType = \wineMateThrift\FeedType::SYSTEMFEED;
    $data2->authorName = $authorName;
    $data2->feedTitle = $title;
    $data2->contentTitle = "一分钟认识橡木桶：增添葡萄酒香气的幕后功臣";
    $data2->contentAbstract = "我们常会看到标榜着用橡木桶酿制的葡萄酒，但究竟橡木桶在酿酒的过程中扮演了什么样的角色？又产生了什么样的影响？让我们一同揭开橡木桶的神秘面纱！";
    $data2->date = "2017/02/14";
    $data2->picUrl = "http://50.18.207.106/pics/newsfeed_contents/NewsFeedChinese_4.png";
    $data2->contentUrl = "http://50.18.207.106/pics/newsfeed_contents/NewsFeedChinese_4.html";
    array_push($response->response, $data2);

    $data3 = new \wineMateThrift\NewsFeedData;
    $data3->feedType = \wineMateThrift\FeedType::SYSTEMFEED;
    $data3->authorName = $authorName;
    $data3->feedTitle = $title;
    $data3->contentTitle = "老派欧洲酒的古怪美味";
    $data3->contentAbstract = "多数饮者刚开始接触葡萄酒时，偏好口感不算不涩、果香充沛浓郁的新鲜酒款。这些酒懂得讨好消费者，也知道如何在一开瓶时就满足饮者的五感味蕾。";
    $data3->date = "2017/02/14";
    $data3->picUrl = "http://50.18.207.106/pics/newsfeed_contents/NewsFeedChinese_5.png";
    $data3->contentUrl = "http://50.18.207.106/pics/newsfeed_contents/NewsFeedChinese_5.html";
    array_push($response->response, $data3);

    $data4 = new \wineMateThrift\NewsFeedData;
    $data4->feedType = \wineMateThrift\FeedType::SYSTEMFEED;
    $data4->authorName = $authorName;
    $data4->feedTitle = $title;
    $data4->contentTitle = "认识美国酒产区（上)";
    $data4->contentAbstract = "讲到美国葡萄酒大家都知道纳帕谷（Napa Valley）。纳帕谷有着众多的指标性意义；象征着美国酒的标杆、象征着新世界的崛起、象征着人定胜天、不安于既定习俗…";
    $data4->date = "2017/02/14";
    $data4->picUrl = "http://50.18.207.106/pics/newsfeed_contents/NewsFeedChinese_6.png";
    $data4->contentUrl = "http://50.18.207.106/pics/newsfeed_contents/NewsFeedChinese_6.html";
    array_push($response->response, $data4);

    $data5 = new \wineMateThrift\NewsFeedData;
    $data5->feedType = \wineMateThrift\FeedType::SYSTEMFEED;
    $data5->authorName = $authorName;
    $data5->feedTitle = $title;
    $data5->contentTitle = "认识美国酒产区（下)";
    $data5->contentAbstract = "美国葡萄酒由于近年来的蓬勃发展，加州以外的区域也逐渐发展出了葡萄酒产业，而很巧合的这些地方都位于比较寒冷的地方，对于爱好葡萄酒或是想要找新奇美国酒友来说，的确增添了不少值得探索的产区。";
    $data5->date = "2017/02/14";
    $data5->picUrl = "http://50.18.207.106/pics/newsfeed_contents/NewsFeedChinese_7.html";
    $data5->contentUrl = "http://50.18.207.106/pics/newsfeed_contents/NewsFeedChinese_7.html";
    array_push($response->response, $data5);
  }
}

function send_activate_email($conn, $userId) {
  $sql = sprintf("SELECT * FROM user_account_info WHERE user_id = %d", $userId);
  $result = $conn->query($sql);
  if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $to = $row['email'];
    $subject = "Welcome to WineMate";
    $message = sprintf('
    <html>
      <head>
        <title>Activate your WineMate account</title>
      </head>
      <body>
        <p>Dear WineMate user,<br><br></p><p>Welcome to WineMate! To complete your registration, please click the following link:</p>
<a href="http://ec2-50-18-207-106.us-west-1.compute.amazonaws.com/accounts/activate_account.php?user_id=%s" target="_blank">http://ec2-50-18-207-106.us-west-1.compute.amazonaws.com/accounts/activate_account.php?user_id=%s</a>
<br><br><p>Sincerely,</p><p>TagTalk Team</p>
      </body>
    </html>', $userId, $userId);

    // Send from contactus@tagtalk.co by using Amazon SES. 
    if (!sendByAmazonSES($to, $subject, $message)) {
      // If send by Amazon SES failed, send by using PhpMailer.
      if (!sendByPhpMailer($to, $subject, $message)) {
        // If send by TagTalk email account failed, fallback to send by system.
	// This is not prefered because from the receiver's point of view, 
	// the email is sent from system instead of a real account, and 
	// is more likedly to be put into spam box.
        $headers  = 'MIME-Version: 1.0' . "\r\n";
        $headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
        $headers .= 'From: support@tagtalk.co' . "\r\n" .
        $headers .= 'Reply-To: support@tagtalk.co' . "\r\n" .
        $headers .= "Return-Path: support@tagtalk.co\r\n";
        mail($to, $subject, $message, $headers);
      }
    }
    return true;
  }
  return false;
}

function getWineWechatShareUrl($wineId) {
  return sprintf("http://50.18.207.106/pics/urls_for_wechat_sharing/ShareToWechat_%d.htm", $wineId);
}

function getRewardPointsByActionType($action, $wineId) {
  switch($action) {
    case \wineMateThrift\UserActions::ShareWineInfoToWechat:
      return 1;
    case \wineMateThrift\UserActions::OpenedBottle:
      return 5;
    case \wineMateThrift\UserActions::ShareWineryInfoToWechat:
      return 10;
    case \wineMateThrift\UserActions::ShareWineryMemberShipToWechat:
      return 20;
    default: 
      return 0;
  }
}

function getWechatUserInfo($wechatLoginInfo) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $request = "https://api.weixin.qq.com/sns/userinfo?access_token=".$wechatLoginInfo->accessToken."&openid=".$wechatLoginInfo->openId;
    curl_setopt($ch, CURLOPT_URL, $request);
    // this is a blocking call to send GET request.
    $wechat_response = curl_exec($ch);
    printf("Wechat userinfo response: \n\n");
    var_dump($wechat_response);
	return json_decode($wechat_response);
}

function generate_photo_url_by_user_id($userId) {
  $url = "http://50.18.207.106/uploads/uploads/".$userId.".png";
  return $url;
}

function url_file_exist($photoUrl) {
  $prefix = 'http://50.18.207.106/';
  $replace_with = '/var/www/html/';
  $internal_url = str_replace($prefix, $replace_with, $photoUrl);
  return file_exists($internal_url);
}

