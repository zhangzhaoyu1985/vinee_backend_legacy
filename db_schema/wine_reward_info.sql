CREATE TABLE wine_reward_info (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    winery_name CHAR(100) NOT NULL,
    winery_id TINYINT NOT NULL,
    wine_id INT NOT NULL,
    reward_point INT NOT NULL,
    stock_num INT NOT NULL,
    time_created INT DEFAULT 0,
    time_updated INT DEFAULT 0,
    KEY `winery_name` (`winery_name`),
    KEY `wine_id` (`wine_id`)
) ENGINE=INNODB CHARSET=utf8;


INSERT INTO wine_reward_info (winery_name, winery_id, wine_id, reward_point, stock_num) VALUES ('Tamburlaine', 1, 1, 20, 20);
INSERT INTO wine_reward_info (winery_name, winery_id, wine_id, reward_point, stock_num) VALUES ('Tamburlaine', 1, 2, 30, 50);
INSERT INTO wine_reward_info (winery_name, winery_id, wine_id, reward_point, stock_num) VALUES ('Tamburlaine', 1, 3, 35, 50);
INSERT INTO wine_reward_info (winery_name, winery_id, wine_id, reward_point, stock_num) VALUES ('Tamburlaine', 1, 4, 45, 20);
