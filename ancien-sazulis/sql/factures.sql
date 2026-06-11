CREATE TABLE factures (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_commande INT NOT NULL,
    numero VARCHAR(50) NOT NULL,
    date_emission DATETIME DEFAULT CURRENT_TIMESTAMP,
    montant DECIMAL(10,2) NOT NULL,
    statut VARCHAR(30) DEFAULT 'en attente',
    FOREIGN KEY (id_commande) REFERENCES commandes(id)
);
