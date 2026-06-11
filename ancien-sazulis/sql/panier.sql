CREATE TABLE panier (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_utilisateur INT NOT NULL,
    id_produit INT NOT NULL,
    quantite INT DEFAULT 1,
    total DECIMAL(10,2) DEFAULT 0.00,
    acompte DECIMAL(10,2) DEFAULT 0.00,
    solde DECIMAL(10,2) DEFAULT 0.00,
    remise DECIMAL(10,2) DEFAULT 0.00,
    nom_remise VARCHAR(100),
    date_ajout DATETIME DEFAULT CURRENT_TIMESTAMP,
    date_modif DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    statut VARCHAR(20) DEFAULT 'actif',
    FOREIGN KEY (id_utilisateur) REFERENCES utilisateurs(id),
    FOREIGN KEY (id_produit) REFERENCES produits(id)
);
