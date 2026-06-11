CREATE TABLE commandes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_utilisateur INT NOT NULL,
    date_commande DATETIME DEFAULT CURRENT_TIMESTAMP,
    total DECIMAL(10,2) NOT NULL,
    statut VARCHAR(30) DEFAULT 'en attente',
    FOREIGN KEY (id_utilisateur) REFERENCES utilisateurs(id)
);
