-- MySQL dump 10.13  Distrib 5.7.26, for Linux (x86_64)
--
-- Host: localhost    Database: wordpress
-- ------------------------------------------------------
-- Server version	5.7.26-0ubuntu0.18.04.1

LOCK TABLES `wp_posts` WRITE;
UPDATE `wp_posts` SET `post_content` = '<!-- wp:paragraph -->\r\n<p>Welcome to Damn Vulnerable WordPress. This is your first post. Edit or delete it, then start writing!</p>\r\n<!-- /wp:paragraph -->', `post_title` = 'Hack Me If You Can', `post_name` = 'hack-me-if-you-can' WHERE `wp_posts`.`ID` = 1;
UNLOCK TABLES;

LOCK TABLES `wp_users` WRITE;
INSERT INTO `wordpress`.`wp_users` (`ID`, `user_login`, `user_pass`, `user_nicename`, `user_email`, `user_registered`) VALUES ('2', 'editor', MD5('editor'), 'Editor', 'editor@yourdomain.com', '2020-01-01 00:00:00');
UNLOCK TABLES;

LOCK TABLES `wp_usermeta` WRITE;
INSERT INTO `wordpress`.`wp_usermeta` (`umeta_id`, `user_id`, `meta_key`, `meta_value`) VALUES (NULL, '2', 'wp_capabilities', 'a:1:{s:6:"editor";b:1;}');
UNLOCK TABLES;

LOCK TABLES `wp_usermeta` WRITE;
INSERT INTO `wordpress`.`wp_usermeta` (`umeta_id`, `user_id`, `meta_key`, `meta_value`) VALUES (NULL, '2', 'wp_user_level', '7');
UNLOCK TABLES;