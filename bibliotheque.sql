-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 25, 2025 at 08:01 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `bibliotheque`
--

-- --------------------------------------------------------

--
-- Table structure for table `adherents`
--

CREATE TABLE `adherents` (
  `id` int(11) NOT NULL,
  `nom` varchar(100) NOT NULL,
  `prenom` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `telephone` varchar(20) DEFAULT NULL,
  `date_inscription` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `adherents`
--

INSERT INTO `adherents` (`id`, `nom`, `prenom`, `email`, `password`, `telephone`, `date_inscription`) VALUES
(20, 'ghilly', 'Med', 'medghilly@gmail.com', '$2y$10$Nh6WerYy8cr4TH5CP29clenDRRxj8Xo/CaHaNzVjzysRgyqaZguxm', '46071882', '2025-05-08 09:57:51'),
(21, 'Abdlhamid', 'Boutta', 'hamid33@gmail.com', '$2y$10$vMxlO/pUj0g/pr9JtJIpY.ki2.6..g26g626/gZQ.kohnRV2sM2.2', '42643414', '2025-05-25 05:58:48');

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `id` int(11) NOT NULL,
  `nom` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `mot_de_passe` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`id`, `nom`, `email`, `mot_de_passe`) VALUES
(1, 'Mohamed ghilly', 'mghilly@gmail.com', '$2y$10$ni/vjCZNSO5ykCvIWFg5WeuU75u/1ix/Ny/K8Nejy9qFj4nEb/O.m');

-- --------------------------------------------------------

--
-- Table structure for table `annonces`
--

CREATE TABLE `annonces` (
  `id` int(11) NOT NULL,
  `titre` varchar(255) NOT NULL,
  `contenu` text NOT NULL,
  `date_publication` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `annonces`
--

INSERT INTO `annonces` (`id`, `titre`, `contenu`, `date_publication`) VALUES
(1, 'Recrutement de bénévoles', 'Nous recherchons des étudiants motivés pour participer à l\'organisation des séminaires.', '2025-05-20 21:14:59'),
(2, 'Offre de stage en bibliothèque', 'Stage de 3 mois ouvert aux étudiants en documentation. Candidature avant fin juin.', '2025-05-20 21:14:59'),
(3, 'Nouveaux livres disponibles', 'Découvrez plus de 100 nouveaux ouvrages disponibles dès cette semaine !', '2025-05-20 21:14:59');

-- --------------------------------------------------------

--
-- Table structure for table `emprunts`
--

CREATE TABLE `emprunts` (
  `id` int(11) NOT NULL,
  `id_livre` int(11) NOT NULL,
  `id_adherent` int(11) NOT NULL,
  `date_emprunt` date NOT NULL,
  `date_retour` date DEFAULT NULL,
  `date_retour_reel` date DEFAULT NULL,
  `caution` decimal(10,2) DEFAULT 20.00,
  `penalite_jour` decimal(10,2) DEFAULT 0.50,
  `telephone` varchar(20) DEFAULT NULL,
  `numero_transaction` varchar(50) DEFAULT NULL,
  `banque` varchar(50) DEFAULT NULL,
  `statut` varchar(50) DEFAULT 'en cours',
  `numero_facture` varchar(50) DEFAULT NULL,
  `mode_reception` varchar(50) DEFAULT NULL,
  `adresse_livraison` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `emprunts`
--

INSERT INTO `emprunts` (`id`, `id_livre`, `id_adherent`, `date_emprunt`, `date_retour`, `date_retour_reel`, `caution`, `penalite_jour`, `telephone`, `numero_transaction`, `banque`, `statut`, `numero_facture`, `mode_reception`, `adresse_livraison`) VALUES
(38, 31, 20, '2025-05-24', '2025-05-31', NULL, 200.00, 10.00, '46071882', '1234567890', 'Bankily', 'en cours', 'FACT-20250524-68321BE205E42', 'pickup', ''),
(39, 17, 20, '2025-05-25', '2025-06-01', NULL, 200.00, 10.00, '46071882', '1234567890', 'Sedad', 'en cours', 'FACT-20250525-6832442D6B491', 'pickup', ''),
(40, 31, 21, '2025-05-25', '2025-06-01', NULL, 200.00, 10.00, '42643414', '4232343433', 'Bankily', 'en cours', 'FACT-20250525-6832B1DE71A36', 'pickup', '');

-- --------------------------------------------------------

--
-- Table structure for table `galerie`
--

CREATE TABLE `galerie` (
  `id` int(11) NOT NULL,
  `image` varchar(255) NOT NULL,
  `titre` varchar(255) NOT NULL,
  `categorie` varchar(100) DEFAULT NULL,
  `date_upload` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `galerie`
--

INSERT INTO `galerie` (`id`, `image`, `titre`, `categorie`, `date_upload`) VALUES
(1, 'event1.jpeg', 'Remise des diplômes 2024', 'Cérémonie', '2025-05-20 21:14:59'),
(2, 'conf1.jpeg', 'Séminaire Cybersécurité', 'Séminaire', '2025-05-20 21:14:59'),
(3, 'expo1.jpeg', 'Exposition de livres', 'Culture', '2025-05-20 21:14:59'),
(4, 'visite1.jpeg', 'Visite d\'une école', 'Institution', '2025-05-20 21:14:59'),
(5, 'atelier1.jpeg', 'Atelier HTML/CSS', 'Formation', '2025-05-20 21:14:59'),
(6, 'evenement1.jpeg', 'Journée Portes Ouvertes', 'Événement', '2025-05-20 21:14:59');

-- --------------------------------------------------------

--
-- Table structure for table `livres`
--

CREATE TABLE `livres` (
  `id` int(11) NOT NULL,
  `titre` varchar(255) NOT NULL,
  `auteur` varchar(150) NOT NULL,
  `annee` int(11) DEFAULT NULL,
  `categorie` varchar(100) DEFAULT NULL,
  `nb_exemplaires` int(11) NOT NULL,
  `prix_vente` decimal(10,2) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `nb_exemplaires_vente` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `livres`
--

INSERT INTO `livres` (`id`, `titre`, `auteur`, `annee`, `categorie`, `nb_exemplaires`, `prix_vente`, `image`, `nb_exemplaires_vente`) VALUES
(1, 'Le Petit Prince', 'Antoine de Saint-Exupéry', 1943, 'Roman', 5, 300.00, 'petit.jpeg', 3),
(2, 'Les Misérables', 'Victor Hugo', 1862, 'Classique', 5, 400.00, 'victor.jpeg', 1),
(4, 'L\'Étranger', 'Albert Camus', 1942, 'Philosophie', 3, 300.00, 'etranger.jpeg', 1),
(5, 'Harry Potter à l\'école des sorciers', 'J.K. Rowling', 1997, 'Fantastique', 8, 200.00, 'harry.jpeg', 0),
(6, 'موسم الهجرة إلى الشمال', 'الطيب صالح', 1966, 'roman', 40, 15.99, 'migration_north.jpg', 30),
(7, 'عمارة يعقوبيان', 'علاء الأسواني', 2002, 'roman', 60, 22.50, 'yacoubian.jpg', 45),
(8, 'ذاكرة الجسد', 'أحلام مستغانمي', 1993, 'roman', 35, 18.99, 'body_memory.jpg', 24),
(9, 'Fourth Wing', 'Rebecca Yarros', 2023, 'fantasy', 180, 24.99, 'fourth_wing.jpg', 150),
(10, 'The Covenant of Water', 'Abraham Verghese', 2023, 'fiction', 90, 28.00, 'covenant_water.jpg', 70),
(11, 'Iron Flame', 'Rebecca Yarros', 2023, 'fantasy', 150, 26.99, 'iron_flame.jpg', 119),
(12, 'The Woman in Me', 'Britney Spears', 2023, 'biographie', 200, 29.99, 'woman_in_me.jpg', 180),
(13, 'Programmation Web', 'Mouhamedou Babe', 2025, 'Science', 10, 300.00, 'web.jpg', 6),
(14, 'Apprendre à programmer en Java', 'Claude Delannoy', 2022, 'Java', 15, 4200.00, 'java_claude.jpg', 10),
(15, 'Python pour les nuls', 'John Paul Mueller', 2021, 'Python', 20, 3550.00, 'python_nuls.jpg', 14),
(16, 'Le langage C', 'Brian W. Kernighan & Dennis M. Ritchie', 2019, 'C', 12, 3800.00, 'langage_c.jpg', 9),
(17, 'JavaScript moderne et efficace', 'Nicolas Beuglet', 2020, 'JavaScript', 9, 3200.00, 'js_moderne.jpg', 7),
(18, 'PHP 8 et MySQL', 'Jean Engels', 2021, 'PHP', 18, 3400.00, 'php_mysql.jpg', 12),
(19, 'Débuter en programmation avec Python', 'Vincent Le Goff', 2023, 'Python', 15, 3100.00, 'python_debut.jpg', 10),
(20, 'Algorithmique – Techniques fondamentales', 'Christophe Dony', 2020, 'Algorithmique', 8, 3600.00, 'algo_dony.jpg', 5),
(21, 'Programmation orientée objet avec C++', 'Benoît Hiver', 2018, 'C++', 10, 3900.00, 'cpp_objet.jpg', 6),
(22, 'Développement web avec HTML5 et CSS3', 'Mathieu Nebra', 2022, 'Web', 14, 2800.00, 'html_css3.jpg', 11),
(23, 'Bases de données avec SQL', 'Michel Besson', 2019, 'Base de données', 11, 3300.00, 'sql_besson.jpg', 8),
(24, 'Apprendre à programmer en Java', 'Claude Delannoy', 2022, 'Java', 15, 4200.00, 'java_claude.jpg', 10),
(25, 'Python pour les nuls', 'John Paul Mueller', 2021, 'Python', 20, 3550.00, 'python_nuls.jpg', 14),
(26, 'Le langage C', 'Brian W. Kernighan & Dennis M. Ritchie', 2019, 'C', 12, 3800.00, 'langage_c.jpg', 9),
(27, 'JavaScript moderne et efficace', 'Nicolas Beuglet', 2020, 'JavaScript', 10, 3200.00, 'js_moderne.jpg', 7),
(28, 'PHP 8 et MySQL', 'Jean Engels', 2021, 'PHP', 18, 3400.00, 'php_mysql.jpg', 12),
(29, 'Débuter en programmation avec Python', 'Vincent Le Goff', 2023, 'Python', 15, 3100.00, 'python_debut.jpg', 10),
(30, 'Algorithmique – Techniques fondamentales', 'Christophe Dony', 2020, 'Algorithmique', 8, 3600.00, 'algo_dony.jpg', 5),
(31, 'Programmation orientée objet avec C++', 'Benoît Hiver', 2018, 'C++', 8, 3900.00, 'cpp_objet.jpg', 6),
(32, 'Développement web avec HTML5 et CSS3', 'Mathieu Nebra', 2022, 'Web', 14, 2800.00, 'html_css3.jpg', 11),
(33, 'Bases de données avec SQL', 'Michel Besson', 2019, 'Base de données', 11, 3300.00, 'sql_besson.jpg', 8);

-- --------------------------------------------------------

--
-- Table structure for table `paiements`
--

CREATE TABLE `paiements` (
  `id` int(11) NOT NULL,
  `id_adherent` int(11) NOT NULL,
  `id_livre` int(11) NOT NULL,
  `type_action` enum('achat','emprunt') NOT NULL,
  `montant` decimal(10,2) NOT NULL,
  `numero_transaction` varchar(50) NOT NULL,
  `banque` varchar(50) NOT NULL,
  `date_paiement` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `paiements`
--

INSERT INTO `paiements` (`id`, `id_adherent`, `id_livre`, `type_action`, `montant`, `numero_transaction`, `banque`, `date_paiement`) VALUES
(15, 20, 4, 'achat', 300.00, '1234567890', 'Masrivi', '2025-05-21 13:14:22'),
(16, 20, 13, 'achat', 300.00, '1213256363577', 'masrivi', '2025-05-24 16:53:16'),
(17, 20, 31, 'emprunt', 20.00, '1234567890', 'Bankily', '2025-05-24 22:20:02'),
(18, 20, 17, 'emprunt', 20.00, '1234567890', 'Sedad', '2025-05-25 01:11:57'),
(19, 20, 13, 'achat', 350.00, '1234567890', 'Bankily', '2025-05-25 08:48:48'),
(20, 21, 31, 'emprunt', 20.00, '4232343433', 'Bankily', '2025-05-25 08:59:58');

-- --------------------------------------------------------

--
-- Table structure for table `seminaires`
--

CREATE TABLE `seminaires` (
  `id` int(11) NOT NULL,
  `titre` varchar(255) NOT NULL,
  `intervenant` varchar(255) NOT NULL,
  `date_event` date NOT NULL,
  `description` text DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `seminaires`
--

INSERT INTO `seminaires` (`id`, `titre`, `intervenant`, `date_event`, `description`, `image`) VALUES
(1, 'Séminaire sur la Cybersécurité', 'Dr. Ould Ely', '2025-06-10', 'Introduction aux enjeux de la cybersécurité moderne.', 'cyber.jpg'),
(2, 'Conférence Big Data', 'Pr. Mbaye Diop', '2025-07-05', 'Exploration et traitement des données massives.', 'bigdata.jpeg'),
(3, 'Atelier Développement Web', 'Mme Bintou Bah', '2025-08-12', 'Création d\'applications web modernes avec PHP.', 'webdev.jpeg');

-- --------------------------------------------------------

--
-- Table structure for table `travaux`
--

CREATE TABLE `travaux` (
  `id` int(11) NOT NULL,
  `titre` varchar(255) NOT NULL,
  `auteur` varchar(255) NOT NULL,
  `fichier` varchar(255) NOT NULL,
  `date_publication` date NOT NULL DEFAULT curdate()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `travaux`
--

INSERT INTO `travaux` (`id`, `titre`, `auteur`, `fichier`, `date_publication`) VALUES
(1, 'Mémoire sur les Réseaux Informatiques', 'Mohamed Ghelli Elbou ', 'CommandesRéseaux.pdf', '2024-12-20'),
(2, 'Base des donnees', 'Mohamedou Baba', 'Chap2.pdf', '2025-01-15'),
(3, 'Analyse des bases de données', 'Cheikhani Ali Bety', 'Chap3SQL.pdf', '2025-03-05'),
(4, 'Introduction sur java', 'Abdelhamid Aly Boutta', 'Séance.pdf', '2025-05-04');

-- --------------------------------------------------------

--
-- Table structure for table `ventes`
--

CREATE TABLE `ventes` (
  `id` int(11) NOT NULL,
  `id_livre` int(11) NOT NULL,
  `id_adherent` int(11) NOT NULL,
  `date_vente` datetime NOT NULL,
  `prix` decimal(10,2) NOT NULL,
  `telephone` varchar(20) DEFAULT NULL,
  `numero_transaction` varchar(50) DEFAULT NULL,
  `banque` varchar(50) DEFAULT NULL,
  `numero_facture` varchar(50) DEFAULT NULL,
  `frais_livraison` decimal(10,2) DEFAULT NULL,
  `total` decimal(10,2) DEFAULT NULL,
  `mode_reception` varchar(50) DEFAULT NULL,
  `adresse_livraison` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `ventes`
--

INSERT INTO `ventes` (`id`, `id_livre`, `id_adherent`, `date_vente`, `prix`, `telephone`, `numero_transaction`, `banque`, `numero_facture`, `frais_livraison`, `total`, `mode_reception`, `adresse_livraison`) VALUES
(35, 4, 20, '2025-05-21 13:14:22', 300.00, '46071882', '1234567890', 'Masrivi', 'FACT-20250521-682DA77E0B1B6', 0.00, 300.00, 'pickup', ''),
(36, 13, 20, '2025-05-25 08:48:48', 300.00, '46071882', '1234567890', 'Bankily', 'FACT-20250525-6832AF40CD7A3', 50.00, 350.00, 'delivery', '112,lazou');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `adherents`
--
ALTER TABLE `adherents`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `annonces`
--
ALTER TABLE `annonces`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `emprunts`
--
ALTER TABLE `emprunts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_livre` (`id_livre`),
  ADD KEY `id_adherent` (`id_adherent`);

--
-- Indexes for table `galerie`
--
ALTER TABLE `galerie`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `livres`
--
ALTER TABLE `livres`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `paiements`
--
ALTER TABLE `paiements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_adherent` (`id_adherent`),
  ADD KEY `id_livre` (`id_livre`);

--
-- Indexes for table `seminaires`
--
ALTER TABLE `seminaires`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `travaux`
--
ALTER TABLE `travaux`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `ventes`
--
ALTER TABLE `ventes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_livre` (`id_livre`),
  ADD KEY `id_adherent` (`id_adherent`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `adherents`
--
ALTER TABLE `adherents`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `annonces`
--
ALTER TABLE `annonces`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `emprunts`
--
ALTER TABLE `emprunts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

--
-- AUTO_INCREMENT for table `galerie`
--
ALTER TABLE `galerie`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `livres`
--
ALTER TABLE `livres`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=34;

--
-- AUTO_INCREMENT for table `paiements`
--
ALTER TABLE `paiements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `seminaires`
--
ALTER TABLE `seminaires`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `travaux`
--
ALTER TABLE `travaux`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `ventes`
--
ALTER TABLE `ventes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `emprunts`
--
ALTER TABLE `emprunts`
  ADD CONSTRAINT `emprunts_ibfk_1` FOREIGN KEY (`id_livre`) REFERENCES `livres` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `emprunts_ibfk_2` FOREIGN KEY (`id_adherent`) REFERENCES `adherents` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `paiements`
--
ALTER TABLE `paiements`
  ADD CONSTRAINT `paiements_ibfk_1` FOREIGN KEY (`id_adherent`) REFERENCES `adherents` (`id`),
  ADD CONSTRAINT `paiements_ibfk_2` FOREIGN KEY (`id_livre`) REFERENCES `livres` (`id`);

--
-- Constraints for table `ventes`
--
ALTER TABLE `ventes`
  ADD CONSTRAINT `ventes_ibfk_1` FOREIGN KEY (`id_livre`) REFERENCES `livres` (`id`),
  ADD CONSTRAINT `ventes_ibfk_2` FOREIGN KEY (`id_adherent`) REFERENCES `adherents` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
