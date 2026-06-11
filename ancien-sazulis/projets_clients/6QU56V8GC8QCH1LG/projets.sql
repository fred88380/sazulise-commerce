-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Hôte : localhost
-- Généré le : mer. 06 mai 2026 à 10:10
-- Version du serveur : 10.11.13-MariaDB-deb11
-- Version de PHP : 8.2.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `irjqws_sazulisf_db`
--

-- --------------------------------------------------------

--
-- Structure de la table `projets`
--

CREATE TABLE `projets` (
  `id` int(11) NOT NULL,
  `id_utilisateur` int(11) NOT NULL,
  `titre` varchar(255) NOT NULL,
  `nom` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `statut` varchar(50) NOT NULL DEFAULT 'en_attente',
  `total` decimal(10,2) NOT NULL DEFAULT 0.00,
  `acompte` decimal(10,2) NOT NULL DEFAULT 0.00,
  `solde` decimal(10,2) NOT NULL DEFAULT 0.00,
  `acompte_recu` tinyint(1) NOT NULL DEFAULT 0,
  `contrat_signe` tinyint(1) NOT NULL DEFAULT 0,
  `avancement` int(11) NOT NULL DEFAULT 0,
  `solde_regle` tinyint(1) NOT NULL DEFAULT 0,
  `code_livraison` varchar(255) DEFAULT NULL,
  `livraison_validee` tinyint(1) NOT NULL DEFAULT 0,
  `date_creation` datetime DEFAULT current_timestamp(),
  `commande_id` int(11) DEFAULT NULL,
  `facture_id` int(11) DEFAULT NULL,
  `remise` decimal(10,2) NOT NULL DEFAULT 0.00,
  `id_panier` int(11) DEFAULT NULL,
  `id_produit` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `projets`
--
ALTER TABLE `projets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_projets_user_statut` (`id_utilisateur`,`statut`);

--
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `projets`
--
ALTER TABLE `projets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `projets`
--
ALTER TABLE `projets`
  ADD CONSTRAINT `projets_ibfk_1` FOREIGN KEY (`id_utilisateur`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
