-- --------------------------------------------------------
-- Host:                         127.0.0.1
-- Server version:               8.4.3 - MySQL Community Server - GPL
-- Server OS:                    Win64
-- HeidiSQL Version:             12.8.0.6908
-- --------------------------------------------------------

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8 */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;


-- Dumping database structure for polygon_example
DROP DATABASE IF EXISTS `polygon_example`;
CREATE DATABASE IF NOT EXISTS `polygon_example` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci */ /*!80016 DEFAULT ENCRYPTION='N' */;
USE `polygon_example`;

-- Dumping structure for table polygon_example.areas
DROP TABLE IF EXISTS `areas`;
CREATE TABLE IF NOT EXISTS `areas` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `geom` polygon NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=59 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table polygon_example.areas: ~1 rows (approximately)
INSERT INTO `areas` (`id`, `name`, `geom`) VALUES
	(1, 'RumahPolygon', _binary 0x000000000103000000010000000700000075e4486760795c40b70a62a06bdf01c0ab251de560795c402ec6c03a8edf01c01d01dc2c5e795c407216f6b4c3df01c0b37e33315d795c40fb20cb8289df01c0058bc3995f795c4051c0763062df01c0702711e15f795c40f629c76471df01c075e4486760795c40b70a62a06bdf01c0),
	(2, 'HalamanPolygon', _binary 0x000000000103000000010000000b0000001d01dc2c5e795c407216f6b4c3df01c0889d29745e795c40fbce2f4ad0df01c009fa0b3d62795c40accabe2b82df01c0f2eb87d860795c406283859334df01c089d1730b5d795c407825c9737ddf01c0b37e33315d795c40fb20cb8289df01c0058bc3995f795c4051c0763062df01c0702711e15f795c40f629c76471df01c075e4486760795c40b70a62a06bdf01c0ab251de560795c402ec6c03a8edf01c01d01dc2c5e795c407216f6b4c3df01c0);

/*!40103 SET TIME_ZONE=IFNULL(@OLD_TIME_ZONE, 'system') */;
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IFNULL(@OLD_FOREIGN_KEY_CHECKS, 1) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40111 SET SQL_NOTES=IFNULL(@OLD_SQL_NOTES, 1) */;
