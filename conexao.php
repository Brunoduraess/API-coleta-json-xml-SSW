<?php
$dbHost = 'db url';
$dbUsername = 'db use';
$dbPassword = 'db pass';
$dbName = 'db name';


$conexao = new mysqli($dbHost, $dbUsername, $dbPassword, $dbName);

$createTable = "CREATE TABLE `api_coleta` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `user` varchar(50) NOT NULL,
  `phone` varchar(15) DEFAULT NULL,
  `request` text NOT NULL,
  `response` text NOT NULL,
  `status` varchar(100) NOT NULL,
  `error_message` text DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4;";