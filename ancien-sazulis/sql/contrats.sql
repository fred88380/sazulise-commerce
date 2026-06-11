CREATE TABLE contrats (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_utilisateur INT NOT NULL,
    titre VARCHAR(150) NOT NULL,
    contenu TEXT,
    date_signature DATETIME,
    statut VARCHAR(30) DEFAULT 'actif',
    FOREIGN KEY (id_utilisateur) REFERENCES utilisateurs(id)
);
