CREATE TABLE user_following_relation (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    follower_id INT NOT NULL,
    INDEX (user_id, follower_id),
    FOREIGN KEY (user_id)
        REFERENCES user_account_info(user_id)
        ON DELETE CASCADE,
    FOREIGN KEY (follower_id)
        REFERENCES user_account_info(user_id)
        ON DELETE CASCADE
) ENGINE=INNODB;

CREATE TABLE winery_info_English (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    winery_id INT NOT NULL,
    winery_name VARCHAR(100) NOT NULL,
    winery_gallery_id INT,
    winery_content_id INT,
    time_created INT DEFAULT 0,
    time_updated INT DEFAULT 0,
    INDEX (winery_id),
    INDEX (winery_name)
) ENGINE=INNODB CHARSET=utf8;

CREATE TABLE winery_info_Chinese (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    winery_id INT NOT NULL,
    winery_name INT NOT NULL,
    winery_gallery_id INT,
    winery_content_id INT,
    time_created INT DEFAULT 0,
    time_updated INT DEFAULT 0,
    INDEX (winery_id),
    INDEX (winery_name)
) ENGINE=INNODB CHARSET=utf8;


CREATE TABLE gallery_photos (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  gallery_id INT NOT NULL,
  photo_title VARCHAR(100) NOT NULL DEFAULT '',
  photo_url VARCHAR(200) NOT NULL DEFAULT '',
  time_created INT DEFAULT 0,
  time_updated INT DEFAULT 0,
  INDEX (gallery_id)
)

CREATE TABLE content_info (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  content_id INT NOT NULL,
  title VARCHAR(100) NOT NULL DEFAULT '',
  brief_text VARCHAR(200) NOT NULL DEFAULT '',
  expand_url VARCHAR(200) NOT NULL DEFAULT '',
  INDEX (content_id)
)

INSERT INTO winery_info_English (winery_id, winery_name, winery_gallery_id, winery_content_id) VALUES (1, 'Tamburlaine', 1, 1);

INSERT INTO gallery_photos (gallery_id, photo_title, photo_url) VALUES (1, 'Tamburlaine Pic 1', 'http://54.67.111.151/pics/wine_info/wine4_2012_shiraz/food_pair_pics/london_broil_whole.jpg');
INSERT INTO gallery_photos (gallery_id, photo_title, photo_url) VALUES (1, 'Tamburlaine Pic 2', 'http://54.67.111.151/pics/wine_info/wine4_2012_shiraz/food_pair_pics/beef_stew.jpg');


INSERT INTO content_info (content_id, title, brief_text, expand_url) VALUES (1, 'Our Story', 'Tamburlaine is Australia\'s largest producer of organic wines, although not a large ', 'http://54.67.111.151/pics/newsfeed_contents/five_wines_from_the_world_smallest_wine_country/five_wines_from_the_world_smallest_wine_country.html');
INSERT INTO content_info (content_id, title, brief_text, expand_url) VALUES (1, 'Our Organic Approach', 'Our approach, we describe as `contemporary organics`, applies modern best practice in vineyard management. Our organic wines come from organically managed vineyards', 'http://54.67.111.151/pics/newsfeed_contents/seven_ways_to_keep_wine_cool/seven_ways_to_keep_wine_cool_this_summer.html');

ALTER TABLE user_account_info ADD show_profile_to_stranger tinyint(1) DEFAULT 1

alter table bottle_open_history add column `tag_id` varchar(255);
ALTER TABLE bottle_open_history ADD INDEX (tag_id)


CREATE TABLE user_reward_point (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  winery_id INT NOT NULL DEFAULT 1,
  point INT NOT NULL DEFAULT 0,
  time_updated INT NOT NULL DEFAULT 0,
  INDEX (user_id)
) ENGINE=INNODB CHARSET=utf8;

CREATE TABLE user_reward_history ( 
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  action_type TINYINT NOT NULL,
  object_id  INT NOT NULL DEFAULT 0,
  point SMALLINT NOT NULL,
  time_created INT NOT NULL DEFAULT 0,
  INDEX (user_id, action_type, object_id) 
)


enum UserActions {
        ShareWineToWechat = 1,
        OpenedBottle = 2,
        ShareWineryInfoToWechat = 3,
        ShareWineryMemberShipToWechat = 4,
}

enum AddRewardPointsResponse {
        Success = 1,
        AlreadyEarned = 2,
}

struct AddRewardPointsRequest {
        1: i32 userId,
        2: UserActions userAction,
        3: i32 wineId, // only valid for 'OpenedBottle' action, 0 for other actions
}


