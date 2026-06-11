CREATE TABLE creations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titre VARCHAR(150) NOT NULL,
    description TEXT,
    image VARCHAR(255),
    statut VARCHAR(20) DEFAULT 'done',
    date_creation DATETIME DEFAULT CURRENT_TIMESTAMP
);
