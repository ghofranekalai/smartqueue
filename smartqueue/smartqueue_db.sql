-- ============================================================
-- SmartQueue – Base de données MySQL
-- Projet Intégré 2BIS – ESEN Manouba 2025/2026
-- ============================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";
SET NAMES utf8mb4;

-- --------------------------------------------------------
-- Création de la base
-- --------------------------------------------------------
CREATE DATABASE IF NOT EXISTS `smartqueue_db`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `smartqueue_db`;

-- --------------------------------------------------------
-- Table : utilisateurs
-- --------------------------------------------------------
CREATE TABLE `utilisateurs` (
  `id`         INT AUTO_INCREMENT PRIMARY KEY,
  `nom`        VARCHAR(100) NOT NULL,
  `prenom`     VARCHAR(100) NOT NULL,
  `email`      VARCHAR(150) NOT NULL UNIQUE,
  `telephone`  VARCHAR(20)  DEFAULT NULL,
  `mot_de_passe` VARCHAR(255) NOT NULL,
  `role`       ENUM('citoyen','agent','admin') NOT NULL DEFAULT 'citoyen',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Table : services
-- --------------------------------------------------------
CREATE TABLE `services` (
  `id`           INT AUTO_INCREMENT PRIMARY KEY,
  `nom`          VARCHAR(150) NOT NULL,
  `description`  TEXT,
  `capacite_max` INT NOT NULL DEFAULT 50,
  `duree_moy`    INT NOT NULL DEFAULT 10  COMMENT 'Durée moyenne en minutes',
  `est_actif`    TINYINT(1) NOT NULL DEFAULT 1,
  `created_at`   DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Table : guichets
-- --------------------------------------------------------
CREATE TABLE `guichets` (
  `id`         INT AUTO_INCREMENT PRIMARY KEY,
  `numero`     VARCHAR(20) NOT NULL,
  `service_id` INT NOT NULL,
  `agent_id`   INT DEFAULT NULL,
  `statut`     ENUM('ouvert','ferme','pause') NOT NULL DEFAULT 'ferme',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`service_id`) REFERENCES `services`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`agent_id`)   REFERENCES `utilisateurs`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Table : tickets
-- --------------------------------------------------------
CREATE TABLE `tickets` (
  `id`           INT AUTO_INCREMENT PRIMARY KEY,
  `numero`       VARCHAR(20) NOT NULL,
  `citoyen_id`   INT NOT NULL,
  `service_id`   INT NOT NULL,
  `guichet_id`   INT DEFAULT NULL,
  `statut`       ENUM('en_attente','appele','en_service','termine','annule','no_show') NOT NULL DEFAULT 'en_attente',
  `priorite`     ENUM('normal','urgent') NOT NULL DEFAULT 'normal',
  `created_at`   DATETIME DEFAULT CURRENT_TIMESTAMP,
  `appele_at`    DATETIME DEFAULT NULL,
  `termine_at`   DATETIME DEFAULT NULL,
  FOREIGN KEY (`citoyen_id`)  REFERENCES `utilisateurs`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`service_id`)  REFERENCES `services`(`id`)     ON DELETE CASCADE,
  FOREIGN KEY (`guichet_id`)  REFERENCES `guichets`(`id`)     ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Table : evaluations  (plusieurs-à-plusieurs porteuse de données)
-- --------------------------------------------------------
CREATE TABLE `evaluations` (
  `id`         INT AUTO_INCREMENT PRIMARY KEY,
  `ticket_id`  INT NOT NULL UNIQUE,
  `note`       TINYINT NOT NULL CHECK (`note` BETWEEN 1 AND 5),
  `commentaire` TEXT,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`ticket_id`) REFERENCES `tickets`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Table : notifications
-- --------------------------------------------------------
CREATE TABLE `notifications` (
  `id`         INT AUTO_INCREMENT PRIMARY KEY,
  `user_id`    INT NOT NULL,
  `message`    TEXT NOT NULL,
  `type`       ENUM('info','success','warning','danger') DEFAULT 'info',
  `lu`         TINYINT(1) DEFAULT 0,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `utilisateurs`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- DONNÉES DE TEST
-- ============================================================

-- Utilisateurs (mot de passe = "password" hashé)
INSERT INTO `utilisateurs` (`nom`,`prenom`,`email`,`telephone`,`mot_de_passe`,`role`) VALUES
('Admin',   'System',  'admin@smartqueue.tn',   '71000000', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin'),
('Ben Ali', 'Khalil',  'agent1@smartqueue.tn',  '55112233', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'agent'),
('Trabelsi','Sonia',   'agent2@smartqueue.tn',  '55998877', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'agent'),
('Chaabane','Mohamed', 'citoyen1@gmail.com',    '22334455', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'citoyen'),
('Jebali',  'Amira',   'citoyen2@gmail.com',    '99001122', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'citoyen');

-- Services
INSERT INTO `services` (`nom`,`description`,`capacite_max`,`duree_moy`,`est_actif`) VALUES
('État Civil',       'Demande de documents d\'état civil (CIN, naissance…)', 30, 8,  1),
('Permis de Conduire','Dépôt et retrait de permis de conduire',              20, 15, 1),
('Service Fiscal',   'Déclarations fiscales et attestations',                25, 12, 1),
('Santé',            'Rendez-vous médicaux et certificats',                  40, 10, 1),
('Urbanisme',        'Permis de construire et documents cadastraux',         15, 20, 1);

-- Guichets
INSERT INTO `guichets` (`numero`,`service_id`,`agent_id`,`statut`) VALUES
('G-01', 1, 2, 'ouvert'),
('G-02', 1, 3, 'ouvert'),
('G-03', 2, 2, 'ferme'),
('G-04', 3, 3, 'ouvert'),
('G-05', 4, 2, 'pause');

-- Tickets
INSERT INTO `tickets` (`numero`,`citoyen_id`,`service_id`,`guichet_id`,`statut`,`priorite`,`appele_at`,`termine_at`) VALUES
('EC-001', 4, 1, 1, 'termine',    'normal', NOW()-INTERVAL 2 HOUR, NOW()-INTERVAL 1 HOUR),
('EC-002', 5, 1, 2, 'en_attente', 'urgent', NULL, NULL),
('PC-001', 4, 2, NULL,'en_attente','normal', NULL, NULL),
('SF-001', 5, 3, 4, 'appele',     'normal', NOW()-INTERVAL 10 MINUTE, NULL),
('SA-001', 4, 4, NULL,'annule',   'normal', NULL, NULL);

-- Evaluations
INSERT INTO `evaluations` (`ticket_id`,`note`,`commentaire`) VALUES
(1, 5, 'Service rapide et efficace, très satisfait !');

-- Notifications
INSERT INTO `notifications` (`user_id`,`message`,`type`,`lu`) VALUES
(4, 'Votre ticket EC-001 a été traité avec succès.', 'success', 0),
(5, 'Votre ticket EC-002 est en attente. Position : 1', 'info', 0);

COMMIT;
