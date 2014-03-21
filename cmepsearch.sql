
-- mysql -uroot -p < /path/to/sql
-- sets up db and user and fills table

-- MySQL dump 10.13  Distrib 5.1.72, for apple-darwin13.0.0 (i386)
--
-- Host: localhost    Database: cmepsearch
-- ------------------------------------------------------
-- Server version	5.1.72-log

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

create database cmepsearch;
CREATE USER 'cmep'@'localhost' IDENTIFIED BY '88hwefce';
GRANT ALL PRIVILEGES ON cmepsearch.* to 'cmep'@'localhost';
FLUSH PRIVILEGES;


USE cmepsearch;


--
-- Table structure for table `cmepsearch`
--

DROP TABLE IF EXISTS `cmepsearch`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cmepsearch` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `page` text,
  `title` varchar(200) DEFAULT NULL,
  `content` text,
  `site` text,
  `meta` text,
  PRIMARY KEY (`id`),
  FULLTEXT KEY `title` (`title`,`content`)
) ENGINE=MyISAM AUTO_INCREMENT=1357 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;


