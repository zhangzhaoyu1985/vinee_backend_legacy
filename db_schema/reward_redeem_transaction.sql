CREATE TABLE reward_redeem_transaction (
    transaction_id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    content TEXT NOT NULL,
    point INT NOT NULL,
    status char(1) NOT NULL,
    address_country varchar(20) DEFAULT '',
    address_province varchar(20) DEFAULT '',
    address_city varchar(20) DEFAULT '',
    address_street varchar(80) DEFAULT '',
    address_zip varchar(20) DEFAULT '',
    phone_number varchar(20) DEFAULT '',
    email varchar(30) DEFAULT '',
    contact_name varchar(30) DEFAULT '',
    time_created INT DEFAULT 0,
    time_updated INT DEFAULT 0,
    INDEX `user_id` (`user_id`),
    INDEX `status` (`status`)
) ENGINE=INNODB CHARSET=utf8;
