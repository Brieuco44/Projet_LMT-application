-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: mysqlcontainer:3306
-- Generation Time: Apr 20, 2025 at 08:26 AM
-- Server version: 9.1.0
-- PHP Version: 8.2.8

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `Catalys_OCR`
--

-- --------------------------------------------------------

--
-- Table structure for table `champs`
--

CREATE TABLE `champs` (
  `id` int NOT NULL,
  `nom` varchar(255) NOT NULL,
  `zoneid` int NOT NULL,
  `question` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `type_champs_id` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `champs`
--

INSERT INTO `champs` (`id`, `nom`, `zoneid`, `question`, `type_champs_id`) VALUES
(2, 'Identifiant N Beneficiaire', 9, 'le numéro d\'identifiant', 6),
(19, 'N Marche Organisme', 11, 'le N° marché', 2),
(20, 'Lettre de commande Organisme', 11, 'la lettre de commande n°', 1),
(21, 'Nom/prenom Consultant', 12, 'le nom et prénom', 2),
(24, 'Date adhesion CSP', 13, 'la date d\'adhésion au CSP', 5),
(25, 'Date Demarrer Accompagnement', 13, 'la date de l\'accompagnement démarre', 5),
(26, 'Date Fin Accompagnement', 13, 'la date de fin de l\'accompagnement', 5),
(27, 'Date Signature Beneficiaire', 14, '', 7),
(28, 'Signature Beneficiaire', 14, '', 4),
(29, 'Date Signature Consultant', 15, '', 7),
(30, 'Signature Consultant', 15, '', 4),
(32, 'Identifiant N Beneficiaire', 1, 'le numéro d\'identifiant', 6),
(51, 'N Marche Organisme', 3, 'le N° marché', 2),
(52, 'Lettre de commande Organisme', 3, 'la lettre de commande n°', 1),
(55, 'Nom/prenom Referent', 4, 'le nom et prénom', 2),
(59, 'Date adhesion CSP', 5, 'la date d\'adhésion au CSP', 5),
(60, 'Date Demarrer Accompagnement', 5, 'la date de l\'accompagnement démarre', 5),
(61, 'Date Fin Accompagnement', 5, 'la date de fin de l\'accompagnement', 5),
(62, 'Date Signature Beneficiaire', 6, '', 7),
(63, 'Signature Beneficiaire', 6, '', 4),
(64, 'Date Signature Consultant', 7, '', 7),
(65, 'Signature Consultant', 7, '', 4);

-- --------------------------------------------------------

--
-- Table structure for table `controle`
--

CREATE TABLE `controle` (
  `id` int NOT NULL,
  `resultat` tinyint(1) NOT NULL,
  `document_id` int DEFAULT NULL,
  `champs_id` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `doctrine_migration_versions`
--

CREATE TABLE `doctrine_migration_versions` (
  `version` varchar(191) NOT NULL,
  `executed_at` datetime DEFAULT NULL,
  `execution_time` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `doctrine_migration_versions`
--

INSERT INTO `doctrine_migration_versions` (`version`, `executed_at`, `execution_time`) VALUES
('DoctrineMigrations\\Version20250315214814', '2025-03-16 10:03:35', 143);

-- --------------------------------------------------------

--
-- Table structure for table `document`
--

CREATE TABLE `document` (
  `id` int NOT NULL,
  `date` datetime NOT NULL,
  `type_livrable_id` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `type_champs`
--

CREATE TABLE `type_champs` (
  `id` int NOT NULL,
  `nom` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `type_champs`
--

INSERT INTO `type_champs` (`id`, `nom`) VALUES
(1, 'Numéromarché'),
(2, 'Text'),
(4, 'Signature'),
(5, 'Date'),
(6, 'Identifiant'),
(7, 'Date manuscrite');

-- --------------------------------------------------------

--
-- Table structure for table `type_livrable`
--

CREATE TABLE `type_livrable` (
  `id` int NOT NULL,
  `nom` varchar(255) NOT NULL,
  `path` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `type_livrable`
--

INSERT INTO `type_livrable` (`id`, `nom`, `path`) VALUES
(6, 'CA Signé CSP LIR25/LIN27', ''),
(7, 'CA Signé CSP France Travail', '');

-- --------------------------------------------------------

--
-- Table structure for table `utilisateur`
--

CREATE TABLE `utilisateur` (
  `id` int NOT NULL,
  `email` varchar(180) NOT NULL,
  `roles` json NOT NULL,
  `password` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `zone`
--

CREATE TABLE `zone` (
  `id` int NOT NULL,
  `libelle` varchar(255) NOT NULL,
  `coordonnees` json NOT NULL,
  `page` int NOT NULL,
  `type_livrable_id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `zone`
--

INSERT INTO `zone` (`id`, `libelle`, `coordonnees`, `page`, `type_livrable_id`) VALUES
(1, 'Beneficiare', '{\"x1\": 77, \"x2\": 2416, \"y1\": 257, \"y2\": 728}', 1, 7),
(2, 'Conseiller référent', '{\"x1\": 70, \"x2\": 2424, \"y1\": 736, \"y2\": 1067}', 1, 7),
(3, 'Organisme Prestataire', '{\"x1\": 77, \"x2\": 2420, \"y1\": 1064, \"y2\": 1617}', 1, 7),
(4, 'Referent', '{\"x1\": 74, \"x2\": 2428, \"y1\": 1625, \"y2\": 1913}', 1, 7),
(5, 'Information', '{\"x1\": 66, \"x2\": 2420, \"y1\": 1882, \"y2\": 2946}', 1, 7),
(6, 'Signature bénéficiare', '{\"x1\": 42, \"x2\": 1208, \"y1\": 2942, \"y2\": 3223}', 1, 7),
(7, 'Signature consultant', '{\"x1\": 1243, \"x2\": 2424, \"y1\": 2927, \"y2\": 3223}', 1, 7),
(9, 'Beneficiare', '{\"x1\": 97, \"x2\": 1351, \"y1\": 677, \"y2\": 1168}', 1, 6),
(10, 'Correspondant Pole emploi', '{\"x1\": 85, \"x2\": 1347, \"y1\": 1145, \"y2\": 1495}', 1, 6),
(11, 'Organisme Prestataire', '{\"x1\": 1340, \"x2\": 2454, \"y1\": 662, \"y2\": 1262}', 1, 6),
(12, 'Consultant', '{\"x1\": 1332, \"x2\": 2446, \"y1\": 1242, \"y2\": 1562}', 1, 6),
(13, 'Information', '{\"x1\": 38, \"x2\": 2446, \"y1\": 1464, \"y2\": 2684}', 1, 6),
(14, 'Signature bénéficiare', '{\"x1\": 112, \"x2\": 1297, \"y1\": 2660, \"y2\": 3077}', 1, 6),
(15, 'Signature consultant', '{\"x1\": 1262, \"x2\": 2442, \"y1\": 2656, \"y2\": 3085}', 1, 6);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `champs`
--
ALTER TABLE `champs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `IDX_B34671BEB4DCFB9B` (`type_champs_id`),
  ADD KEY `zoneid` (`zoneid`);

--
-- Indexes for table `controle`
--
ALTER TABLE `controle`
  ADD PRIMARY KEY (`id`),
  ADD KEY `IDX_E39396EC33F7837` (`document_id`),
  ADD KEY `IDX_E39396E1ABA8B` (`champs_id`);

--
-- Indexes for table `doctrine_migration_versions`
--
ALTER TABLE `doctrine_migration_versions`
  ADD PRIMARY KEY (`version`);

--
-- Indexes for table `document`
--
ALTER TABLE `document`
  ADD PRIMARY KEY (`id`),
  ADD KEY `IDX_D8698A769BA909D5` (`type_livrable_id`);

--
-- Indexes for table `type_champs`
--
ALTER TABLE `type_champs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `type_livrable`
--
ALTER TABLE `type_livrable`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `utilisateur`
--
ALTER TABLE `utilisateur`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `UNIQ_IDENTIFIER_EMAIL` (`email`);

--
-- Indexes for table `zone`
--
ALTER TABLE `zone`
  ADD PRIMARY KEY (`id`),
  ADD KEY `type_livrable_id` (`type_livrable_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `champs`
--
ALTER TABLE `champs`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=66;

--
-- AUTO_INCREMENT for table `controle`
--
ALTER TABLE `controle`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `document`
--
ALTER TABLE `document`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `type_champs`
--
ALTER TABLE `type_champs`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `type_livrable`
--
ALTER TABLE `type_livrable`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `utilisateur`
--
ALTER TABLE `utilisateur`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `zone`
--
ALTER TABLE `zone`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `champs`
--
ALTER TABLE `champs`
  ADD CONSTRAINT `champs_ibfk_1` FOREIGN KEY (`zoneid`) REFERENCES `zone` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  ADD CONSTRAINT `FK_B34671BEB4DCFB9B` FOREIGN KEY (`type_champs_id`) REFERENCES `type_champs` (`id`);

--
-- Constraints for table `controle`
--
ALTER TABLE `controle`
  ADD CONSTRAINT `FK_E39396E1ABA8B` FOREIGN KEY (`champs_id`) REFERENCES `champs` (`id`),
  ADD CONSTRAINT `FK_E39396EC33F7837` FOREIGN KEY (`document_id`) REFERENCES `document` (`id`);

--
-- Constraints for table `document`
--
ALTER TABLE `document`
  ADD CONSTRAINT `FK_D8698A769BA909D5` FOREIGN KEY (`type_livrable_id`) REFERENCES `type_livrable` (`id`);

--
-- Constraints for table `zone`
--
ALTER TABLE `zone`
  ADD CONSTRAINT `zone_ibfk_1` FOREIGN KEY (`type_livrable_id`) REFERENCES `type_livrable` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
