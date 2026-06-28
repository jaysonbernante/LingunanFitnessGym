-- Lingunan Fitness Gym — Database Backup
-- Generated: 2026-06-28 23:22:14
-- Filter: ALL records

SET FOREIGN_KEY_CHECKS=0;

-- Table: members
DROP TABLE IF EXISTS `members`;
CREATE TABLE `members` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned DEFAULT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `type` varchar(50) DEFAULT NULL,
  `membership_start` date DEFAULT NULL,
  `membership_end` date DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `username` varchar(50) NOT NULL,
  `gmail` varchar(100) NOT NULL,
  `RFID` varchar(50) DEFAULT NULL,
  `Joined_Date` date DEFAULT curdate(),
  `credit` decimal(10,2) DEFAULT 0.00,
  `plan_months` int(11) DEFAULT NULL,
  `membership_expiry` date DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `members_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `members` (`id`, `user_id`, `first_name`, `last_name`, `phone`, `address`, `type`, `membership_start`, `membership_end`, `password`, `username`, `gmail`, `RFID`, `Joined_Date`, `credit`, `plan_months`, `membership_expiry`) VALUES
('5', NULL, 'asd', '', '', '', 'member', NULL, NULL, '$2y$10$upP3tN6xk9lmZXpcLTalbuBVSX4qcbF.La74LudLcegUGd8as.5Qa', 'asd', 'asd@example.com', '111', '2026-04-13', '0.00', NULL, NULL),
('6', NULL, 'jayson', 'bernante', '', '', 'session', NULL, NULL, '$2y$10$MNXZPTcntSM6U8Go8ebXXO/Rsum1Rdehwuy0tSdxiAby04aG/M6/m', 'jaysonbernante', '', '11', '2026-04-13', '440.00', NULL, NULL),
('18', NULL, 'ben', 'onde', '098564645', 'caloocan', 'session', NULL, NULL, '$2y$10$n7Vj0WpCibfHTaXsDK4gHOnRvQPyG9MCUwYsjhSXy5LhzT/IGmh/y', 'benonde', 'benonde@gmail.com', NULL, '2026-04-15', '0.00', NULL, NULL),
('19', NULL, 'alegria', 'hosting', '123123123123', 'asdasdd', 'member', NULL, NULL, '$2y$10$UpyVMci82F8J44PeQPJVYeFlwO7ZskQGGd4D3BacUJHp14tDZHpRW', 'alegriahosting', 'alegriasystemmngr@gmail.com', '1244219170', '2026-04-17', '1222.00', '1', '2026-05-17'),
('21', NULL, 'jerome', 'bernante', '', '', 'session', NULL, NULL, '$2y$10$bBZ27KQTtOkqGCEBmeSvL.d4PLWAYb32sN0oNyhdFQOadMMSYs5rS', 'jeromebernante', '', '1261553234', '2026-04-17', '0.00', NULL, NULL),
('22', NULL, 'sean1258282482', 'roscoe', '', '', 'session', NULL, NULL, '$2y$10$OkRZTBHCMHgD6ENslKXuVepmF0Mw7z9Ly5mcD4bq35O2gMuHZQKwi', 'sean1258282482roscoe', '', NULL, '2026-06-28', '0.00', NULL, NULL),
('23', NULL, 'sean', 'roscoe1258282482', '', '', 'session', NULL, NULL, '$2y$10$.HEwYRweOZ4gWEAEGwcVWO5dO.Dh9Mr0t7VR3rJx9Tz/i9OBKjeNS', 'seanroscoe1258282482', '', NULL, '2026-06-28', '0.00', NULL, NULL),
('24', NULL, 'sean', 'roscoe', '', '', 'member', NULL, NULL, '$2y$10$AxyScLgRzXHazr0NETjkKuAHOGiwmwAZDEOfs7Z4UQp7teYLhwK.y', 'seanroscoe', '', '1258282482', '2026-06-28', '0.00', '1', '2026-07-28');

SET FOREIGN_KEY_CHECKS=1;
