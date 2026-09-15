-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1:3306
-- Généré le : mar. 01 sep. 2026 à 15:02
-- Version du serveur : 8.4.7
-- Version de PHP : 8.3.28

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `stockage`
--

-- --------------------------------------------------------

--
-- Structure de la table `affectations`
--

DROP TABLE IF EXISTS `affectations`;
CREATE TABLE IF NOT EXISTS `affectations` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` bigint UNSIGNED NOT NULL,
  `poste_id` bigint UNSIGNED NOT NULL,
  `service_id` bigint UNSIGNED NOT NULL,
  `department_id` bigint UNSIGNED DEFAULT NULL,
  `direction_id` bigint UNSIGNED DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `started_at` date NOT NULL,
  `ended_at` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `affectations_poste_id_foreign` (`poste_id`),
  KEY `affectations_service_id_foreign` (`service_id`),
  KEY `affectations_department_id_foreign` (`department_id`),
  KEY `affectations_direction_id_foreign` (`direction_id`),
  KEY `aff_user_active_started_index` (`user_id`,`is_active`,`started_at`)
) ENGINE=MyISAM AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `affectations`
--

INSERT INTO `affectations` (`id`, `user_id`, `poste_id`, `service_id`, `department_id`, `direction_id`, `is_active`, `started_at`, `ended_at`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 1, NULL, NULL, 1, '2026-08-31', NULL, '2026-08-31 21:03:32', '2026-08-31 21:03:32'),
(2, 2, 1, 3, 1, 1, 1, '2026-09-01', NULL, '2026-09-01 12:55:28', '2026-09-01 12:55:28'),
(3, 3, 2, 3, 1, 1, 1, '2026-09-01', NULL, '2026-09-01 12:55:28', '2026-09-01 12:55:28'),
(4, 4, 3, 6, 3, 2, 1, '2026-09-01', NULL, '2026-09-01 12:55:29', '2026-09-01 12:55:29'),
(5, 5, 3, 14, 8, 4, 1, '2026-09-01', NULL, '2026-09-01 12:55:29', '2026-09-01 12:55:29'),
(6, 6, 4, 6, 3, 2, 1, '2026-09-01', NULL, '2026-09-01 12:55:29', '2026-09-01 12:55:29'),
(7, 7, 4, 14, 8, 4, 1, '2026-09-01', NULL, '2026-09-01 12:55:30', '2026-09-01 12:55:30'),
(8, 8, 5, 8, 4, 2, 1, '2026-09-01', NULL, '2026-09-01 12:55:30', '2026-09-01 12:55:30'),
(9, 9, 5, 17, 10, 4, 1, '2026-09-01', NULL, '2026-09-01 12:55:31', '2026-09-01 12:55:31'),
(10, 10, 6, 8, 4, 2, 1, '2026-09-01', NULL, '2026-09-01 12:55:32', '2026-09-01 12:55:32'),
(11, 11, 6, 14, 8, 4, 1, '2026-09-01', NULL, '2026-09-01 12:55:32', '2026-09-01 12:55:32'),
(12, 12, 7, 17, 10, 4, 1, '2026-09-01', NULL, '2026-09-01 12:55:33', '2026-09-01 12:55:33'),
(13, 13, 7, 3, 1, 1, 1, '2026-09-01', NULL, '2026-09-01 12:55:33', '2026-09-01 12:55:33');

-- --------------------------------------------------------

--
-- Structure de la table `cache`
--

DROP TABLE IF EXISTS `cache`;
CREATE TABLE IF NOT EXISTS `cache` (
  `key` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` bigint NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_expiration_index` (`expiration`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `cache_locks`
--

DROP TABLE IF EXISTS `cache_locks`;
CREATE TABLE IF NOT EXISTS `cache_locks` (
  `key` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `owner` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` bigint NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_locks_expiration_index` (`expiration`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `companies`
--

DROP TABLE IF EXISTS `companies`;
CREATE TABLE IF NOT EXISTS `companies` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `size` enum('small','large') COLLATE utf8mb4_unicode_ci NOT NULL,
  `logo_path` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_configured` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `companies`
--

INSERT INTO `companies` (`id`, `name`, `size`, `logo_path`, `is_configured`, `created_at`, `updated_at`) VALUES
(1, 'DGMP', 'large', NULL, 1, '2026-08-31 21:03:32', '2026-08-31 21:03:32');

-- --------------------------------------------------------

--
-- Structure de la table `departments`
--

DROP TABLE IF EXISTS `departments`;
CREATE TABLE IF NOT EXISTS `departments` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `company_id` bigint UNSIGNED NOT NULL,
  `direction_id` bigint UNSIGNED DEFAULT NULL,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `departments_company_id_foreign` (`company_id`),
  KEY `departments_direction_id_foreign` (`direction_id`)
) ENGINE=MyISAM AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `departments`
--

INSERT INTO `departments` (`id`, `company_id`, `direction_id`, `name`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 'Secrétariat Général', '2026-09-01 12:36:45', '2026-09-01 12:36:45'),
(2, 1, 1, 'Communication', '2026-09-01 12:36:45', '2026-09-01 12:36:45'),
(3, 1, 2, 'Recrutement', '2026-09-01 12:36:45', '2026-09-01 12:36:45'),
(4, 1, 2, 'Gestion du Personnel', '2026-09-01 12:36:45', '2026-09-01 12:36:45'),
(5, 1, 2, 'Formation', '2026-09-01 12:36:45', '2026-09-01 12:36:45'),
(6, 1, 3, 'Comptabilité', '2026-09-01 12:36:45', '2026-09-01 12:36:45'),
(7, 1, 3, 'Trésorerie', '2026-09-01 12:36:45', '2026-09-01 12:36:45'),
(8, 1, 4, 'Développement', '2026-09-01 12:36:45', '2026-09-01 12:36:45'),
(9, 1, 4, 'Infrastructure', '2026-09-01 12:36:45', '2026-09-01 12:36:45'),
(10, 1, 4, 'Support Utilisateurs', '2026-09-01 12:36:45', '2026-09-01 12:36:45'),
(11, 1, 5, 'Logistique', '2026-09-01 12:36:45', '2026-09-01 12:36:45'),
(12, 1, 5, 'Production', '2026-09-01 12:36:45', '2026-09-01 12:36:45');

-- --------------------------------------------------------

--
-- Structure de la table `directions`
--

DROP TABLE IF EXISTS `directions`;
CREATE TABLE IF NOT EXISTS `directions` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `company_id` bigint UNSIGNED NOT NULL,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `directions_company_id_foreign` (`company_id`)
) ENGINE=MyISAM AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `directions`
--

INSERT INTO `directions` (`id`, `company_id`, `name`, `created_at`, `updated_at`) VALUES
(1, 1, 'Direction Générale', '2026-09-01 12:36:44', '2026-09-01 12:36:44'),
(2, 1, 'Direction des Ressources Humaines', '2026-09-01 12:36:44', '2026-09-01 12:36:44'),
(3, 1, 'Direction Financière et Comptable', '2026-09-01 12:36:44', '2026-09-01 12:36:44'),
(4, 1, 'Direction des Systèmes d\'Information', '2026-09-01 12:36:44', '2026-09-01 12:36:44'),
(5, 1, 'Direction Opérations', '2026-09-01 12:36:44', '2026-09-01 12:36:44');

-- --------------------------------------------------------

--
-- Structure de la table `documents`
--

DROP TABLE IF EXISTS `documents`;
CREATE TABLE IF NOT EXISTS `documents` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `company_id` bigint UNSIGNED NOT NULL,
  `document_type_id` bigint UNSIGNED NOT NULL,
  `uploaded_by` bigint UNSIGNED NOT NULL,
  `service_id` bigint UNSIGNED DEFAULT NULL,
  `department_id` bigint UNSIGNED DEFAULT NULL,
  `direction_id` bigint UNSIGNED DEFAULT NULL,
  `title` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `file_path` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `file_type` enum('pdf','png','jpg','jpeg','gif') COLLATE utf8mb4_unicode_ci NOT NULL,
  `file_size` bigint NOT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `documents_company_id_foreign` (`company_id`),
  KEY `documents_document_type_id_foreign` (`document_type_id`),
  KEY `documents_uploaded_by_foreign` (`uploaded_by`),
  KEY `documents_service_id_index` (`service_id`),
  KEY `documents_department_id_index` (`department_id`),
  KEY `documents_direction_id_index` (`direction_id`)
) ENGINE=MyISAM AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `documents`
--

INSERT INTO `documents` (`id`, `company_id`, `document_type_id`, `uploaded_by`, `service_id`, `department_id`, `direction_id`, `title`, `description`, `file_path`, `file_type`, `file_size`, `deleted_at`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 1, 1, NULL, NULL, 'img', 'kalli', 'documents/1/2026/09/1a0110e7-f25d-44e6-9a3e-bea586737717.jpg', 'jpg', 12973, NULL, '2026-09-01 10:10:47', '2026-09-01 10:10:47'),
(2, 1, 1, 1, 1, NULL, NULL, 'cv', NULL, 'documents/1/2026/09/3f88b200-2fb1-4243-b2af-a07796d8c40d.pdf', 'pdf', 101935, NULL, '2026-09-01 10:38:41', '2026-09-01 10:38:41'),
(3, 1, 2, 1, 1, NULL, NULL, 'cvv', 'cvv', 'documents/1/2026/09/96afcad0-580f-4401-bd1e-42ecba134c48.pdf', 'pdf', 834269, NULL, '2026-09-01 13:16:54', '2026-09-01 13:16:54');

-- --------------------------------------------------------

--
-- Structure de la table `document_permissions`
--

DROP TABLE IF EXISTS `document_permissions`;
CREATE TABLE IF NOT EXISTS `document_permissions` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `document_id` bigint UNSIGNED NOT NULL,
  `target_type` enum('poste','service','user') COLLATE utf8mb4_unicode_ci NOT NULL,
  `target_id` bigint UNSIGNED NOT NULL,
  `expires_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `doc_perm_target_index` (`document_id`,`target_type`,`target_id`),
  KEY `doc_perm_expires_at_index` (`expires_at`)
) ENGINE=MyISAM AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `document_permissions`
--

INSERT INTO `document_permissions` (`id`, `document_id`, `target_type`, `target_id`, `expires_at`, `created_at`, `updated_at`) VALUES
(1, 2, 'poste', 2, '2026-09-02 18:30:00', '2026-09-01 11:27:21', '2026-09-01 11:27:21'),
(3, 3, 'poste', 3, '2026-09-18 17:23:00', '2026-09-01 13:19:20', '2026-09-01 13:19:20');

-- --------------------------------------------------------

--
-- Structure de la table `document_types`
--

DROP TABLE IF EXISTS `document_types`;
CREATE TABLE IF NOT EXISTS `document_types` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `company_id` bigint UNSIGNED NOT NULL,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `document_types_company_id_foreign` (`company_id`)
) ENGINE=MyISAM AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `document_types`
--

INSERT INTO `document_types` (`id`, `company_id`, `name`, `created_at`, `updated_at`) VALUES
(1, 1, 'Contrat', '2026-08-31 21:02:38', '2026-08-31 21:02:38'),
(2, 1, 'Facture', '2026-08-31 21:02:38', '2026-08-31 21:02:38'),
(3, 1, 'Note de service', '2026-08-31 21:02:38', '2026-08-31 21:02:38'),
(4, 1, 'Rapport', '2026-08-31 21:02:38', '2026-08-31 21:02:38'),
(5, 1, 'Courrier Administratif', '2026-08-31 21:02:38', '2026-08-31 21:02:38'),
(6, 1, 'Demande de congé', '2026-09-01 12:36:45', '2026-09-01 12:36:45'),
(7, 1, 'Bulletin de paie', '2026-09-01 12:36:45', '2026-09-01 12:36:45'),
(8, 1, 'Bon de commande', '2026-09-01 12:36:45', '2026-09-01 12:36:45'),
(9, 1, 'Reçu', '2026-09-01 12:36:45', '2026-09-01 12:36:45'),
(10, 1, 'Procédure', '2026-09-01 12:36:45', '2026-09-01 12:36:45'),
(11, 1, 'Manuel', '2026-09-01 12:36:45', '2026-09-01 12:36:45'),
(12, 1, 'CV', '2026-09-01 12:36:45', '2026-09-01 12:36:45'),
(13, 1, 'Lettre de motivation', '2026-09-01 12:36:45', '2026-09-01 12:36:45');

-- --------------------------------------------------------

--
-- Structure de la table `jobs`
--

DROP TABLE IF EXISTS `jobs`;
CREATE TABLE IF NOT EXISTS `jobs` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `queue` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `attempts` smallint UNSIGNED NOT NULL,
  `reserved_at` int UNSIGNED DEFAULT NULL,
  `available_at` int UNSIGNED NOT NULL,
  `created_at` int UNSIGNED NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `journals`
--

DROP TABLE IF EXISTS `journals`;
CREATE TABLE IF NOT EXISTS `journals` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` bigint UNSIGNED DEFAULT NULL,
  `action` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `ip_address` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `journals_user_id_foreign` (`user_id`)
) ENGINE=MyISAM AUTO_INCREMENT=120 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `journals`
--

INSERT INTO `journals` (`id`, `user_id`, `action`, `description`, `ip_address`, `created_at`, `updated_at`) VALUES
(1, 1, 'APP_SETUP_COMPLETED', 'Configuration initiale de l\'application et création de l\'administrateur.', '127.0.0.1', '2026-08-31 21:03:32', '2026-08-31 21:03:32'),
(2, 1, 'LOGIN_SUCCESS', 'Connexion réussie pour l\'utilisateur Regis Kouame', '127.0.0.1', '2026-08-31 21:03:43', '2026-08-31 21:03:43'),
(3, 1, 'LOGOUT', 'Déconnexion de l\'utilisateur Regis Kouame', '127.0.0.1', '2026-08-31 21:29:30', '2026-08-31 21:29:30'),
(4, 1, 'LOGIN_SUCCESS', 'Connexion réussie pour l\'utilisateur Regis Kouame', '127.0.0.1', '2026-08-31 21:29:46', '2026-08-31 21:29:46'),
(5, 1, 'DOCUMENT_CREATE', 'Création du document #1 : img', '127.0.0.1', '2026-09-01 10:10:47', '2026-09-01 10:10:47'),
(6, 1, 'DOCUMENT_DOWNLOAD', 'Téléchargement du document #1', '127.0.0.1', '2026-09-01 10:14:30', '2026-09-01 10:14:30'),
(7, 1, 'DOCUMENT_DOWNLOAD', 'Téléchargement du document #1', '127.0.0.1', '2026-09-01 10:38:10', '2026-09-01 10:38:10'),
(8, 1, 'DOCUMENT_CREATE', 'Création du document #2 : cv', '127.0.0.1', '2026-09-01 10:38:41', '2026-09-01 10:38:41'),
(9, 1, 'DOCUMENT_DOWNLOAD', 'Téléchargement du document #2', '127.0.0.1', '2026-09-01 10:38:44', '2026-09-01 10:38:44'),
(10, 1, 'DOCUMENT_DOWNLOAD', 'Téléchargement du document #2', '127.0.0.1', '2026-09-01 10:38:52', '2026-09-01 10:38:52'),
(11, 1, 'DOCUMENT_DOWNLOAD', 'Téléchargement du document #2', '127.0.0.1', '2026-09-01 10:38:52', '2026-09-01 10:38:52'),
(12, 1, 'DOCUMENT_DOWNLOAD', 'Téléchargement du document #2', '127.0.0.1', '2026-09-01 10:38:52', '2026-09-01 10:38:52'),
(13, 1, 'LOGOUT', 'Déconnexion de l\'utilisateur Regis Kouame', '127.0.0.1', '2026-09-01 10:41:43', '2026-09-01 10:41:43'),
(14, 1, 'LOGIN_SUCCESS', 'Connexion réussie pour l\'utilisateur Regis Kouame', '127.0.0.1', '2026-09-01 10:41:52', '2026-09-01 10:41:52'),
(15, 1, 'LOGOUT', 'Déconnexion de l\'utilisateur Regis Kouame', '127.0.0.1', '2026-09-01 10:42:31', '2026-09-01 10:42:31'),
(16, 1, 'LOGIN_SUCCESS', 'Connexion réussie pour l\'utilisateur Regis Kouame', '127.0.0.1', '2026-09-01 10:42:39', '2026-09-01 10:42:39'),
(17, 1, 'LOGOUT', 'Déconnexion de l\'utilisateur Regis Kouame', '127.0.0.1', '2026-09-01 10:45:02', '2026-09-01 10:45:02'),
(18, 1, 'LOGIN_SUCCESS', 'Connexion réussie pour l\'utilisateur Regis Kouame', '127.0.0.1', '2026-09-01 10:45:11', '2026-09-01 10:45:11'),
(19, 1, 'DOCUMENT_DOWNLOAD', 'Téléchargement du document #2', '127.0.0.1', '2026-09-01 10:45:45', '2026-09-01 10:45:45'),
(20, 1, 'DOCUMENT_DOWNLOAD', 'Téléchargement du document #2', '127.0.0.1', '2026-09-01 10:45:46', '2026-09-01 10:45:46'),
(21, 1, 'DOCUMENT_DOWNLOAD', 'Téléchargement du document #2', '127.0.0.1', '2026-09-01 10:45:46', '2026-09-01 10:45:46'),
(22, 1, 'DOCUMENT_DOWNLOAD', 'Téléchargement du document #2', '127.0.0.1', '2026-09-01 10:45:46', '2026-09-01 10:45:46'),
(23, 1, 'DOCUMENT_DOWNLOAD', 'Téléchargement du document #2', '127.0.0.1', '2026-09-01 10:46:40', '2026-09-01 10:46:40'),
(24, 1, 'DOCUMENT_DOWNLOAD', 'Téléchargement du document #2', '127.0.0.1', '2026-09-01 10:46:41', '2026-09-01 10:46:41'),
(25, 1, 'DOCUMENT_DOWNLOAD', 'Téléchargement du document #2', '127.0.0.1', '2026-09-01 10:46:41', '2026-09-01 10:46:41'),
(26, 1, 'DOCUMENT_DOWNLOAD', 'Téléchargement du document #2', '127.0.0.1', '2026-09-01 10:46:41', '2026-09-01 10:46:41'),
(27, 1, 'DOCUMENT_DOWNLOAD', 'Téléchargement du document #2', '127.0.0.1', '2026-09-01 10:47:37', '2026-09-01 10:47:37'),
(28, 1, 'DOCUMENT_DOWNLOAD', 'Téléchargement du document #2', '127.0.0.1', '2026-09-01 10:47:38', '2026-09-01 10:47:38'),
(29, 1, 'DOCUMENT_DOWNLOAD', 'Téléchargement du document #2', '127.0.0.1', '2026-09-01 10:47:39', '2026-09-01 10:47:39'),
(30, 1, 'DOCUMENT_DOWNLOAD', 'Téléchargement du document #2', '127.0.0.1', '2026-09-01 10:47:39', '2026-09-01 10:47:39'),
(31, 1, 'DOCUMENT_DOWNLOAD', 'Téléchargement du document #2', '127.0.0.1', '2026-09-01 10:49:31', '2026-09-01 10:49:31'),
(32, 1, 'DOCUMENT_DOWNLOAD', 'Téléchargement du document #2', '127.0.0.1', '2026-09-01 10:49:32', '2026-09-01 10:49:32'),
(33, 1, 'DOCUMENT_DOWNLOAD', 'Téléchargement du document #2', '127.0.0.1', '2026-09-01 10:49:32', '2026-09-01 10:49:32'),
(34, 1, 'DOCUMENT_DOWNLOAD', 'Téléchargement du document #2', '127.0.0.1', '2026-09-01 10:49:32', '2026-09-01 10:49:32'),
(35, 1, 'DOCUMENT_DOWNLOAD', 'Téléchargement du document #2', '127.0.0.1', '2026-09-01 10:49:37', '2026-09-01 10:49:37'),
(36, 1, 'DOCUMENT_DOWNLOAD', 'Téléchargement du document #2', '127.0.0.1', '2026-09-01 10:49:37', '2026-09-01 10:49:37'),
(37, 1, 'DOCUMENT_DOWNLOAD', 'Téléchargement du document #2', '127.0.0.1', '2026-09-01 10:49:37', '2026-09-01 10:49:37'),
(38, 1, 'DOCUMENT_DOWNLOAD', 'Téléchargement du document #2', '127.0.0.1', '2026-09-01 10:49:38', '2026-09-01 10:49:38'),
(39, 1, 'SERVICE_CREATE', 'Création du service : Directions', '127.0.0.1', '2026-09-01 10:55:34', '2026-09-01 10:55:34'),
(40, 1, 'SERVICE_DELETE', 'Suppression du service : Directions', '127.0.0.1', '2026-09-01 10:56:12', '2026-09-01 10:56:12'),
(41, 1, 'DOCUMENT_DOWNLOAD', 'Téléchargement du document #2', '127.0.0.1', '2026-09-01 10:56:41', '2026-09-01 10:56:41'),
(42, 1, 'DOCUMENT_DOWNLOAD', 'Téléchargement du document #2', '127.0.0.1', '2026-09-01 10:56:42', '2026-09-01 10:56:42'),
(43, 1, 'DOCUMENT_DOWNLOAD', 'Téléchargement du document #2', '127.0.0.1', '2026-09-01 10:56:42', '2026-09-01 10:56:42'),
(44, 1, 'DOCUMENT_DOWNLOAD', 'Téléchargement du document #2', '127.0.0.1', '2026-09-01 10:56:42', '2026-09-01 10:56:42'),
(45, 1, 'DOCUMENT_DOWNLOAD', 'Téléchargement du document #2', '127.0.0.1', '2026-09-01 11:00:26', '2026-09-01 11:00:26'),
(46, 1, 'DOCUMENT_DOWNLOAD', 'Téléchargement du document #2', '127.0.0.1', '2026-09-01 11:00:27', '2026-09-01 11:00:27'),
(47, 1, 'DOCUMENT_DOWNLOAD', 'Téléchargement du document #2', '127.0.0.1', '2026-09-01 11:00:27', '2026-09-01 11:00:27'),
(48, 1, 'DOCUMENT_DOWNLOAD', 'Téléchargement du document #2', '127.0.0.1', '2026-09-01 11:00:27', '2026-09-01 11:00:27'),
(49, 1, 'DOCUMENT_PERMISSION_CREATE', 'Création de la permission #1 (cible poste:2) sur le document #2', '127.0.0.1', '2026-09-01 11:27:21', '2026-09-01 11:27:21'),
(50, 1, 'DOCUMENT_DOWNLOAD', 'Téléchargement du document #2', '127.0.0.1', '2026-09-01 11:27:51', '2026-09-01 11:27:51'),
(51, 1, 'DOCUMENT_DOWNLOAD', 'Téléchargement du document #2', '127.0.0.1', '2026-09-01 11:27:51', '2026-09-01 11:27:51'),
(52, 1, 'DOCUMENT_DOWNLOAD', 'Téléchargement du document #2', '127.0.0.1', '2026-09-01 11:27:51', '2026-09-01 11:27:51'),
(53, 1, 'DOCUMENT_DOWNLOAD', 'Téléchargement du document #2', '127.0.0.1', '2026-09-01 11:27:51', '2026-09-01 11:27:51'),
(54, 1, 'DOCUMENT_DOWNLOAD', 'Téléchargement du document #2', '127.0.0.1', '2026-09-01 11:29:14', '2026-09-01 11:29:14'),
(55, 1, 'DOCUMENT_DOWNLOAD', 'Téléchargement du document #2', '127.0.0.1', '2026-09-01 11:29:14', '2026-09-01 11:29:14'),
(56, 1, 'DOCUMENT_DOWNLOAD', 'Téléchargement du document #2', '127.0.0.1', '2026-09-01 11:29:15', '2026-09-01 11:29:15'),
(57, 1, 'DOCUMENT_DOWNLOAD', 'Téléchargement du document #2', '127.0.0.1', '2026-09-01 11:29:15', '2026-09-01 11:29:15'),
(58, 1, 'DOCUMENT_DOWNLOAD', 'Téléchargement du document #2', '127.0.0.1', '2026-09-01 11:29:31', '2026-09-01 11:29:31'),
(59, 1, 'DOCUMENT_DOWNLOAD', 'Téléchargement du document #2', '127.0.0.1', '2026-09-01 11:29:31', '2026-09-01 11:29:31'),
(60, 1, 'DOCUMENT_DOWNLOAD', 'Téléchargement du document #2', '127.0.0.1', '2026-09-01 11:29:32', '2026-09-01 11:29:32'),
(61, 1, 'DOCUMENT_DOWNLOAD', 'Téléchargement du document #2', '127.0.0.1', '2026-09-01 11:29:32', '2026-09-01 11:29:32'),
(62, 1, 'DOCUMENT_DOWNLOAD', 'Téléchargement du document #2', '127.0.0.1', '2026-09-01 11:37:48', '2026-09-01 11:37:48'),
(63, 1, 'DOCUMENT_DOWNLOAD', 'Téléchargement du document #2', '127.0.0.1', '2026-09-01 11:37:48', '2026-09-01 11:37:48'),
(64, 1, 'DOCUMENT_DOWNLOAD', 'Téléchargement du document #2', '127.0.0.1', '2026-09-01 11:37:49', '2026-09-01 11:37:49'),
(65, 1, 'DOCUMENT_DOWNLOAD', 'Téléchargement du document #2', '127.0.0.1', '2026-09-01 11:37:49', '2026-09-01 11:37:49'),
(66, 1, 'DOCUMENT_DOWNLOAD', 'Téléchargement du document #2', '127.0.0.1', '2026-09-01 11:38:13', '2026-09-01 11:38:13'),
(67, 1, 'DOCUMENT_DOWNLOAD', 'Téléchargement du document #2', '127.0.0.1', '2026-09-01 11:38:13', '2026-09-01 11:38:13'),
(68, 1, 'DOCUMENT_DOWNLOAD', 'Téléchargement du document #2', '127.0.0.1', '2026-09-01 11:38:14', '2026-09-01 11:38:14'),
(69, 1, 'DOCUMENT_DOWNLOAD', 'Téléchargement du document #2', '127.0.0.1', '2026-09-01 11:38:14', '2026-09-01 11:38:14'),
(70, 1, 'LOGIN_SUCCESS', 'Connexion réussie pour l\'utilisateur Regis Kouame', '127.0.0.1', '2026-09-01 11:39:39', '2026-09-01 11:39:39'),
(71, 1, 'DOCUMENT_DOWNLOAD', 'Téléchargement du document #2', '127.0.0.1', '2026-09-01 11:39:42', '2026-09-01 11:39:42'),
(72, 1, 'DOCUMENT_DOWNLOAD', 'Téléchargement du document #2', '127.0.0.1', '2026-09-01 11:39:43', '2026-09-01 11:39:43'),
(73, 1, 'DOCUMENT_DOWNLOAD', 'Téléchargement du document #2', '127.0.0.1', '2026-09-01 11:39:43', '2026-09-01 11:39:43'),
(74, 1, 'DOCUMENT_DOWNLOAD', 'Téléchargement du document #2', '127.0.0.1', '2026-09-01 11:39:43', '2026-09-01 11:39:43'),
(75, 1, 'DOCUMENT_DOWNLOAD', 'Téléchargement du document #2', '127.0.0.1', '2026-09-01 11:40:08', '2026-09-01 11:40:08'),
(76, 1, 'LOGIN_SUCCESS', 'Connexion réussie pour l\'utilisateur Regis Kouame', '127.0.0.1', '2026-09-01 11:40:28', '2026-09-01 11:40:28'),
(77, 1, 'DOCUMENT_DOWNLOAD', 'Téléchargement du document #2', '127.0.0.1', '2026-09-01 11:40:33', '2026-09-01 11:40:33'),
(78, 1, 'LOGOUT', 'Déconnexion de l\'utilisateur Regis Kouame', '127.0.0.1', '2026-09-01 11:50:11', '2026-09-01 11:50:11'),
(79, 1, 'LOGIN_SUCCESS', 'Connexion réussie pour l\'utilisateur Regis Kouame', '127.0.0.1', '2026-09-01 11:50:21', '2026-09-01 11:50:21'),
(80, 1, 'DOCUMENT_DOWNLOAD', 'Téléchargement du document #2', '127.0.0.1', '2026-09-01 11:54:54', '2026-09-01 11:54:54'),
(81, 1, 'LOGOUT', 'Déconnexion de l\'utilisateur Regis Kouame', '127.0.0.1', '2026-09-01 12:56:50', '2026-09-01 12:56:50'),
(82, NULL, 'LOGIN_FAILED', 'Tentative de connexion échouée pour l\'email : admin.test@archive2.test', '127.0.0.1', '2026-09-01 12:56:58', '2026-09-01 12:56:58'),
(83, 1, 'LOGIN_SUCCESS', 'Connexion réussie pour l\'utilisateur Regis Kouame', '127.0.0.1', '2026-09-01 12:57:24', '2026-09-01 12:57:24'),
(84, 1, 'LOGOUT', 'Déconnexion de l\'utilisateur Regis Kouame', '127.0.0.1', '2026-09-01 12:57:53', '2026-09-01 12:57:53'),
(85, 8, 'LOGIN_SUCCESS', 'Connexion réussie pour l\'utilisateur Awa Traoré', '127.0.0.1', '2026-09-01 12:58:04', '2026-09-01 12:58:04'),
(86, 8, 'LOGOUT', 'Déconnexion de l\'utilisateur Awa Traoré', '127.0.0.1', '2026-09-01 12:58:29', '2026-09-01 12:58:29'),
(87, 1, 'LOGIN_SUCCESS', 'Connexion réussie pour l\'utilisateur Regis Kouame', '127.0.0.1', '2026-09-01 12:58:36', '2026-09-01 12:58:36'),
(88, 2, 'LOGIN_SUCCESS', 'Connexion réussie pour l\'utilisateur  Admin Test', '127.0.0.1', '2026-09-01 12:59:27', '2026-09-01 12:59:27'),
(89, 2, 'LOGOUT', 'Déconnexion de l\'utilisateur  Admin Test', '127.0.0.1', '2026-09-01 12:59:44', '2026-09-01 12:59:44'),
(90, 3, 'LOGIN_SUCCESS', 'Connexion réussie pour l\'utilisateur Jean-Marc DirecteurGénéral', '127.0.0.1', '2026-09-01 12:59:59', '2026-09-01 12:59:59'),
(91, 3, 'LOGOUT', 'Déconnexion de l\'utilisateur Jean-Marc DirecteurGénéral', '127.0.0.1', '2026-09-01 13:00:03', '2026-09-01 13:00:03'),
(92, 5, 'LOGIN_SUCCESS', 'Connexion réussie pour l\'utilisateur Karim Benali', '127.0.0.1', '2026-09-01 13:00:15', '2026-09-01 13:00:15'),
(93, 5, 'LOGOUT', 'Déconnexion de l\'utilisateur Karim Benali', '127.0.0.1', '2026-09-01 13:00:19', '2026-09-01 13:00:19'),
(94, 11, 'LOGIN_SUCCESS', 'Connexion réussie pour l\'utilisateur Lucas Bernard', '127.0.0.1', '2026-09-01 13:00:39', '2026-09-01 13:00:39'),
(95, 11, 'LOGOUT', 'Déconnexion de l\'utilisateur Lucas Bernard', '127.0.0.1', '2026-09-01 13:00:43', '2026-09-01 13:00:43'),
(96, 3, 'LOGIN_SUCCESS', 'Connexion réussie pour l\'utilisateur Jean-Marc DirecteurGénéral', '127.0.0.1', '2026-09-01 13:15:31', '2026-09-01 13:15:31'),
(97, 3, 'LOGOUT', 'Déconnexion de l\'utilisateur Jean-Marc DirecteurGénéral', '127.0.0.1', '2026-09-01 13:16:09', '2026-09-01 13:16:09'),
(98, NULL, 'LOGIN_FAILED', 'Tentative de connexion échouée pour l\'email : directeur.si@archive2.test', '127.0.0.1', '2026-09-01 13:16:17', '2026-09-01 13:16:17'),
(99, 5, 'LOGIN_SUCCESS', 'Connexion réussie pour l\'utilisateur Karim Benali', '127.0.0.1', '2026-09-01 13:16:22', '2026-09-01 13:16:22'),
(100, 1, 'DOCUMENT_CREATE', 'Création du document #3 : cvv', '127.0.0.1', '2026-09-01 13:16:54', '2026-09-01 13:16:54'),
(101, 1, 'DOCUMENT_PERMISSION_CREATE', 'Création de la permission #2 (cible poste:2) sur le document #3', '127.0.0.1', '2026-09-01 13:17:22', '2026-09-01 13:17:22'),
(102, 5, 'LOGOUT', 'Déconnexion de l\'utilisateur Karim Benali', '127.0.0.1', '2026-09-01 13:17:39', '2026-09-01 13:17:39'),
(103, 3, 'LOGIN_SUCCESS', 'Connexion réussie pour l\'utilisateur Jean-Marc DirecteurGénéral', '127.0.0.1', '2026-09-01 13:18:13', '2026-09-01 13:18:13'),
(104, 3, 'LOGOUT', 'Déconnexion de l\'utilisateur Jean-Marc DirecteurGénéral', '127.0.0.1', '2026-09-01 13:18:29', '2026-09-01 13:18:29'),
(105, NULL, 'LOGIN_FAILED', 'Tentative de connexion échouée pour l\'email : directeur.si@archive2.test', '127.0.0.1', '2026-09-01 13:18:39', '2026-09-01 13:18:39'),
(106, 5, 'LOGIN_SUCCESS', 'Connexion réussie pour l\'utilisateur Karim Benali', '127.0.0.1', '2026-09-01 13:18:46', '2026-09-01 13:18:46'),
(107, 1, 'DOCUMENT_PERMISSION_DELETE', 'Suppression de la permission #2', '127.0.0.1', '2026-09-01 13:19:06', '2026-09-01 13:19:06'),
(108, 1, 'DOCUMENT_PERMISSION_CREATE', 'Création de la permission #3 (cible poste:3) sur le document #3', '127.0.0.1', '2026-09-01 13:19:20', '2026-09-01 13:19:20'),
(109, 1, 'DOCUMENT_DOWNLOAD', 'Téléchargement du document #3', '127.0.0.1', '2026-09-01 14:25:12', '2026-09-01 14:25:12'),
(110, 1, 'DOCUMENT_DOWNLOAD', 'Téléchargement du document #3', '127.0.0.1', '2026-09-01 14:25:13', '2026-09-01 14:25:13'),
(111, 1, 'DOCUMENT_DOWNLOAD', 'Téléchargement du document #3', '127.0.0.1', '2026-09-01 14:25:13', '2026-09-01 14:25:13'),
(112, 1, 'DOCUMENT_DOWNLOAD', 'Téléchargement du document #3', '127.0.0.1', '2026-09-01 14:25:14', '2026-09-01 14:25:14'),
(113, 1, 'DOCUMENT_DOWNLOAD', 'Téléchargement du document #3', '127.0.0.1', '2026-09-01 14:25:14', '2026-09-01 14:25:14'),
(114, 1, 'DOCUMENT_DOWNLOAD', 'Téléchargement du document #3', '127.0.0.1', '2026-09-01 14:25:14', '2026-09-01 14:25:14'),
(115, 1, 'DOCUMENT_DOWNLOAD', 'Téléchargement du document #3', '127.0.0.1', '2026-09-01 14:25:14', '2026-09-01 14:25:14'),
(116, 1, 'DOCUMENT_DOWNLOAD', 'Téléchargement du document #3', '127.0.0.1', '2026-09-01 14:25:25', '2026-09-01 14:25:25'),
(117, 1, 'DOCUMENT_DOWNLOAD', 'Téléchargement du document #2', '127.0.0.1', '2026-09-01 14:25:31', '2026-09-01 14:25:31'),
(118, 1, 'DOCUMENT_DOWNLOAD', 'Téléchargement du document #1', '127.0.0.1', '2026-09-01 14:25:39', '2026-09-01 14:25:39'),
(119, 1, 'DOCUMENT_DOWNLOAD', 'Téléchargement du document #3', '127.0.0.1', '2026-09-01 14:36:30', '2026-09-01 14:36:30');

-- --------------------------------------------------------

--
-- Structure de la table `migrations`
--

DROP TABLE IF EXISTS `migrations`;
CREATE TABLE IF NOT EXISTS `migrations` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `migration` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `migrations`
--

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES
(1, '0001_01_01_000000_create_users_table', 1),
(2, '0001_01_01_000001_create_cache_table', 1),
(3, '0001_01_01_000002_create_jobs_table', 1),
(4, '2026_08_31_011128_create_companies_table', 1),
(5, '2026_08_31_011129_create_directions_table', 1),
(6, '2026_08_31_011130_create_departments_table', 1),
(7, '2026_08_31_011131_create_services_table', 1),
(8, '2026_08_31_011132_create_postes_table', 1),
(9, '2026_08_31_011133_create_affectations_table', 1),
(10, '2026_08_31_011134_create_document_types_table', 1),
(11, '2026_08_31_011135_create_documents_table', 1),
(12, '2026_08_31_011136_create_document_permissions_table', 1),
(13, '2026_08_31_011137_create_journals_table', 1),
(14, '2026_08_31_015432_create_personal_access_tokens_table', 1),
(15, '2026_09_01_000001_add_organizational_scope_to_documents_table', 2),
(16, '2026_09_01_000002_optimize_document_permissions_and_affectations_indexes', 2);

-- --------------------------------------------------------

--
-- Structure de la table `password_reset_tokens`
--

DROP TABLE IF EXISTS `password_reset_tokens`;
CREATE TABLE IF NOT EXISTS `password_reset_tokens` (
  `email` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `personal_access_tokens`
--

DROP TABLE IF EXISTS `personal_access_tokens`;
CREATE TABLE IF NOT EXISTS `personal_access_tokens` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `tokenable_type` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tokenable_id` bigint UNSIGNED NOT NULL,
  `name` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `abilities` text COLLATE utf8mb4_unicode_ci,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`),
  KEY `personal_access_tokens_expires_at_index` (`expires_at`)
) ENGINE=MyISAM AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `personal_access_tokens`
--

INSERT INTO `personal_access_tokens` (`id`, `tokenable_type`, `tokenable_id`, `name`, `token`, `abilities`, `last_used_at`, `expires_at`, `created_at`, `updated_at`) VALUES
(11, 'App\\Models\\User', 1, 'auth_token', 'e663f3f98fcc75fc6b341305477da35ca1c08a044fad7fe80823bc4b938afd42', '[\"*\"]', '2026-09-01 15:01:56', NULL, '2026-09-01 12:58:36', '2026-09-01 15:01:56'),
(19, 'App\\Models\\User', 5, 'auth_token', '9231b06f068cf349166d4bd501c0d6d4114bf569fee8c36bd180724f20bc4bef', '[\"*\"]', '2026-09-01 15:02:38', NULL, '2026-09-01 13:18:46', '2026-09-01 15:02:38');

-- --------------------------------------------------------

--
-- Structure de la table `postes`
--

DROP TABLE IF EXISTS `postes`;
CREATE TABLE IF NOT EXISTS `postes` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `level` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `postes`
--

INSERT INTO `postes` (`id`, `name`, `level`, `created_at`, `updated_at`) VALUES
(1, 'Administrateur Système', 'admin', '2026-08-31 21:02:38', '2026-08-31 21:02:38'),
(2, 'Directeur Général', 'dg', '2026-08-31 21:02:38', '2026-08-31 21:02:38'),
(3, 'Directeur', 'directeur', '2026-08-31 21:02:38', '2026-08-31 21:02:38'),
(4, 'Responsable Département', 'responsable_departement', '2026-08-31 21:02:38', '2026-08-31 21:02:38'),
(5, 'Chef de Service', 'chef_service', '2026-08-31 21:02:38', '2026-08-31 21:02:38'),
(6, 'Employé Ordinaire', 'employe', '2026-08-31 21:02:38', '2026-08-31 21:02:38'),
(7, 'Agent Temporaire', 'agent_temporaire', '2026-08-31 21:02:38', '2026-08-31 21:02:38');

-- --------------------------------------------------------

--
-- Structure de la table `services`
--

DROP TABLE IF EXISTS `services`;
CREATE TABLE IF NOT EXISTS `services` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `company_id` bigint UNSIGNED NOT NULL,
  `department_id` bigint UNSIGNED DEFAULT NULL,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `services_company_id_foreign` (`company_id`),
  KEY `services_department_id_foreign` (`department_id`)
) ENGINE=MyISAM AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `services`
--

INSERT INTO `services` (`id`, `company_id`, `department_id`, `name`, `created_at`, `updated_at`) VALUES
(1, 1, NULL, 'Administration Générale', '2026-08-31 21:03:32', '2026-08-31 21:03:32'),
(3, 1, 1, 'Accueil', '2026-09-01 12:36:45', '2026-09-01 12:36:45'),
(4, 1, 2, 'Relations Presse', '2026-09-01 12:36:45', '2026-09-01 12:36:45'),
(5, 1, 2, 'Événementiel', '2026-09-01 12:36:45', '2026-09-01 12:36:45'),
(6, 1, 3, 'Sourcing', '2026-09-01 12:36:45', '2026-09-01 12:36:45'),
(7, 1, 3, 'Entretiens', '2026-09-01 12:36:45', '2026-09-01 12:36:45'),
(8, 1, 4, 'Paie', '2026-09-01 12:36:45', '2026-09-01 12:36:45'),
(9, 1, 4, 'Contrats', '2026-09-01 12:36:45', '2026-09-01 12:36:45'),
(10, 1, 5, 'Plan de Formation', '2026-09-01 12:36:45', '2026-09-01 12:36:45'),
(11, 1, 6, 'Fournisseurs', '2026-09-01 12:36:45', '2026-09-01 12:36:45'),
(12, 1, 6, 'Clients', '2026-09-01 12:36:45', '2026-09-01 12:36:45'),
(13, 1, 7, 'Banques', '2026-09-01 12:36:45', '2026-09-01 12:36:45'),
(14, 1, 8, 'Applications Web', '2026-09-01 12:36:45', '2026-09-01 12:36:45'),
(15, 1, 8, 'Applications Mobiles', '2026-09-01 12:36:45', '2026-09-01 12:36:45'),
(16, 1, 9, 'Réseau', '2026-09-01 12:36:45', '2026-09-01 12:36:45'),
(17, 1, 10, 'Helpdesk', '2026-09-01 12:36:45', '2026-09-01 12:36:45'),
(18, 1, 11, 'Achats', '2026-09-01 12:36:45', '2026-09-01 12:36:45'),
(19, 1, 12, 'Atelier', '2026-09-01 12:36:45', '2026-09-01 12:36:45');

-- --------------------------------------------------------

--
-- Structure de la table `sessions`
--

DROP TABLE IF EXISTS `sessions`;
CREATE TABLE IF NOT EXISTS `sessions` (
  `id` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint UNSIGNED DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_activity` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE IF NOT EXISTS `users` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `company_id` bigint UNSIGNED NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT '1',
  `lang` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'fr',
  `theme_color` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '#2563eb',
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`),
  KEY `users_company_id_foreign` (`company_id`)
) ENGINE=MyISAM AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `users`
--

INSERT INTO `users` (`id`, `company_id`, `name`, `email`, `email_verified_at`, `password`, `status`, `lang`, `theme_color`, `remember_token`, `created_at`, `updated_at`) VALUES
(1, 1, 'Regis Kouame', 'regiskouame09@gmail.com', NULL, '$2y$12$Rkv0YBiWHSf/iSZtCvs0muDOoAstUYOL6enk9LSLlmh1snOqifESm', 1, 'fr', '#2563eb', NULL, '2026-08-31 21:03:32', '2026-08-31 21:03:32'),
(2, 1, ' Admin Test', 'admin.test@archive2.test', NULL, '$2y$12$dZtm2mCn8VorzOjkFrlB8u8BawnQoMfEmq8DGqk2QFp6u3CdUanny', 1, 'fr', '#dc3545', NULL, '2026-09-01 12:55:27', '2026-09-01 12:55:27'),
(3, 1, 'Jean-Marc DirecteurGénéral', 'dg@archive2.test', NULL, '$2y$12$rHuPl5o3B7VwTsWozV7VqevV0G0iiTaNunkpVSbMdMIVU6USE0pIa', 1, 'fr', '#6f42c1', NULL, '2026-09-01 12:55:28', '2026-09-01 12:55:28'),
(4, 1, 'Sophie Martin', 'directeur.rh@archive2.test', NULL, '$2y$12$q7RNbqTECiHb0Wh.VV8fKOBAe8n1H3m9m9BE99LKQ/MeMw9iHDN3O', 1, 'fr', '#0d6efd', NULL, '2026-09-01 12:55:29', '2026-09-01 12:55:29'),
(5, 1, 'Karim Benali', 'directeur.si@archive2.test', NULL, '$2y$12$bGvWKqhKOsHT0pQrF/Sys.xtSXu1eVx5nnjkCx354N.G72Z1E7BWK', 1, 'fr', '#0d6efd', NULL, '2026-09-01 12:55:29', '2026-09-01 12:55:29'),
(6, 1, 'Nadia El Fassi', 'resp.recrutement@archive2.test', NULL, '$2y$12$VHuJy4hncDlS2vyPQBxXDeLIq3OnUQCFeBJWTWiNqdNZZmJgHhWgK', 1, 'fr', '#198754', NULL, '2026-09-01 12:55:29', '2026-09-01 12:55:29'),
(7, 1, 'Thomas Dubois', 'resp.developpement@archive2.test', NULL, '$2y$12$OJfiLC0asM8i8vg5Zh6S5uwA0CENA186Dr.te6Kvc.UjDoytodsuW', 1, 'fr', '#198754', NULL, '2026-09-01 12:55:30', '2026-09-01 12:55:30'),
(8, 1, 'Awa Traoré', 'chef.paie@archive2.test', NULL, '$2y$12$q0bPeOCdf2M9iQxhCwbhcu1wviWa2Vu1pYn/MxV09LBwTn/IXH9JG', 1, 'fr', '#fd7e14', NULL, '2026-09-01 12:55:30', '2026-09-01 12:55:30'),
(9, 1, 'Marc Leclerc', 'chef.helpdesk@archive2.test', NULL, '$2y$12$GC64lKXuCNJzYWgr4B4g9unysm2NgDGpuvftaiwM.oWPAnFqWbgM6', 1, 'fr', '#fd7e14', NULL, '2026-09-01 12:55:31', '2026-09-01 12:55:31'),
(10, 1, 'Fatou Diop', 'employe.paie@archive2.test', NULL, '$2y$12$kIGHR6Dk6wAH5hwU6oTD..c3yQkqYoVfHf1u7tOKW63UkW95QcboW', 1, 'fr', '#20c997', NULL, '2026-09-01 12:55:32', '2026-09-01 12:55:32'),
(11, 1, 'Lucas Bernard', 'employe.devweb@archive2.test', NULL, '$2y$12$oGyiPXcwLaaRLYevudi18OhSYp/fAIYoW3ya64aSmCwYKgUpTRB5C', 1, 'fr', '#20c997', NULL, '2026-09-01 12:55:32', '2026-09-01 12:55:32'),
(12, 1, 'Ibrahim Sow', 'agent.helpdesk@archive2.test', NULL, '$2y$12$KPaWxK8AVMshB0u7xFTdc.tPpDLU6vQUH.c.tQLrdRzEeKDVNxBeW', 1, 'fr', '#6610f2', NULL, '2026-09-01 12:55:33', '2026-09-01 12:55:33'),
(13, 1, 'Céline Moreau', 'agent.accueil@archive2.test', NULL, '$2y$12$fwQfK6FyXnr7Bdc8HHuAX.2onaPVVIP2njxkcguG.PM1MESSKaZV6', 1, 'fr', '#6610f2', NULL, '2026-09-01 12:55:33', '2026-09-01 12:55:33');
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
