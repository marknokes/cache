SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";

CREATE DATABASE php_ucache;
USE php_ucache;

CREATE TABLE `cache_data` (
  `id` varchar(50) NOT NULL,
  `last_run` bigint(20) NOT NULL,
  `cache_content` text NOT NULL,
  `num_hits` int(11) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE `cache_data`
  ADD PRIMARY KEY (`id`);
COMMIT;