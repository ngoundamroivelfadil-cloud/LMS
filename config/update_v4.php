<?php
require_once 'database.php';

$sql = "
CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_utilisateur INT NOT NULL,
    type VARCHAR(50) NOT NULL, -- 'message', 'cours', 'evaluation', 'bravo'
    titre VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    lien VARCHAR(255) DEFAULT NULL,
    lu TINYINT(1) DEFAULT 0,
    date_notification TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_utilisateur) REFERENCES utilisateurs(id) ON DELETE CASCADE
);

-- Index pour la performance
CREATE INDEX idx_notif_user_lu ON notifications(id_utilisateur, lu);
";

// On utilise multi_query pour les requêtes multiples
if ($conn->multi_query($sql)) {
    do {
        if ($result = $conn->store_result()) { $result->free(); }
    } while ($conn->next_result());
    echo "Mise à jour v4 réussie : Table 'notifications' créée.\n";
} else {
    echo "Erreur lors de la mise à jour : " . $conn->error . "\n";
}
?>
