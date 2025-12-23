<?php
// test_final.php
echo "<h1>🎯 Test Final LoveConnect</h1>";

// Test de connexion ULTRA simple
try {
    // Connexion SANS port (essayez d'abord)
    $conn = new PDO("mysql:host=localhost;dbname=loveconnect_db", "root", "");
    
    echo "<div style='background:#d4edda;color:#155724;padding:1rem;border-radius:5px;'>
          ✅ Connecté à MySQL !</div><br>";
    
    // 1. Vérifier la table
    $stmt = $conn->query("SHOW TABLES LIKE 'utilisateurs'");
    if ($stmt->rowCount() > 0) {
        echo "✅ Table 'utilisateurs' existe<br>";
    } else {
        echo "❌ Table manquante - <a href='#sql'>voir commande SQL</a><br>";
    }
    
    // 2. Vérifier les colonnes
    $stmt = $conn->query("DESCRIBE utilisateurs");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
    
    echo "<h3>Colonnes dans la table :</h3>";
    echo "<div style='background:#f8f9fa;padding:1rem;border-radius:5px;'>";
    foreach ($columns as $col) {
        echo "• $col<br>";
    }
    echo "</div>";
    
    // 3. Tester l'insertion RÉELLE (un utilisateur test)
    echo "<h3>Test d'insertion :</h3>";
    
    $test_password = password_hash('test123', PASSWORD_BCRYPT);
    
    $sql = "INSERT INTO utilisateurs 
            (email, mot_de_passe, prenom, nom, genre, date_naissance, ville) 
            VALUES 
            ('test@loveconnect.com', :password, 'Jean', 'Dupont', 'homme', '1990-05-15', 'Paris')";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute([':password' => $test_password]);
    
    echo "✅ Utilisateur test inséré !<br>";
    echo "• Email: test@loveconnect.com<br>";
    echo "• Mot de passe: test123<br>";
    
    // 4. Compter les utilisateurs
    $stmt = $conn->query("SELECT COUNT(*) as total FROM utilisateurs");
    $count = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "• Total utilisateurs: {$count['total']}<br>";
    
    // 5. AFFICHER LES UTILISATEURS
    $stmt = $conn->query("SELECT id, email, prenom, nom, ville FROM utilisateurs");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($users)) {
        echo "<h3>Utilisateurs dans la base :</h3>";
        echo "<table border='1' cellpadding='5' style='border-collapse:collapse;'>";
        echo "<tr style='background:#667eea;color:white;'>
                <th>ID</th><th>Email</th><th>Prénom</th><th>Nom</th><th>Ville</th>
              </tr>";
        foreach ($users as $user) {
            echo "<tr>";
            echo "<td>{$user['id']}</td>";
            echo "<td>{$user['email']}</td>";
            echo "<td>{$user['prenom']}</td>";
            echo "<td>{$user['nom']}</td>";
            echo "<td>{$user['ville']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // 6. SUCCÈS COMPLET !
    echo "<div style='
        background: linear-gradient(135deg, #667eea, #764ba2);
        color: white;
        padding: 2rem;
        border-radius: 10px;
        margin: 2rem 0;
        text-align: center;
    '>";
    echo "<h2>🎉 TOUT FONCTIONNE !</h2>";
    echo "<p>La base de données est prête pour LoveConnect.</p>";
    echo "</div>";
    
    // 7. LIENS DE TEST
    echo "<h3>Testez maintenant :</h3>";
    echo "<div style='display:flex;gap:1rem;flex-wrap:wrap;'>";
    echo "<a href='inscription.php' style='
        background: #28a745;
        color: white;
        padding: 1rem 2rem;
        border-radius: 10px;
        text-decoration: none;
        font-weight: bold;
    '>📝 Inscription Réelle</a>";
    
    echo "<a href='login.php' style='
        background: #17a2b8;
        color: white;
        padding: 1rem 2rem;
        border-radius: 10px;
        text-decoration: none;
        font-weight: bold;
    '>🔐 Connexion</a>";
    
    echo "<a href='profile.php' style='
        background: #6f42c1;
        color: white;
        padding: 1rem 2rem;
        border-radius: 10px;
        text-decoration: none;
        font-weight: bold;
    '>👤 Profil</a>";
    echo "</div>";
    
} catch(PDOException $e) {
    echo "<div style='background:#f8d7da;color:#721c24;padding:1rem;border-radius:5px;'>";
    echo "<h3>❌ ERREUR DE CONNEXION</h3>";
    echo "<p><strong>Message :</strong> " . $e->getMessage() . "</p>";
    
    // Solutions
    echo "<h4>Solutions :</h4>";
    echo "<ol>";
    echo "<li><strong>MySQL non démarré</strong> : Démarrez MySQL dans XAMPP</li>";
    echo "<li><strong>Mauvais port</strong> : Essayez :<br>";
    echo "<code>new PDO('mysql:host=localhost;port=3306;dbname=loveconnect_db', 'root', '')</code><br>";
    echo "ou<br>";
    echo "<code>new PDO('mysql:host=localhost;port=3307;dbname=loveconnect_db', 'root', '')</code></li>";
    echo "<li><strong>Base inexistante</strong> : Créez 'loveconnect_db' dans phpMyAdmin</li>";
    echo "</ol>";
    
    echo "<h4>Commande SQL à exécuter :</h4>";
    echo "<div id='sql' style='background:#e9ecef;padding:1rem;border-radius:5px;font-family:monospace;'>";
    echo "CREATE TABLE `utilisateurs` (<br>";
    echo "    `id` INT PRIMARY KEY AUTO_INCREMENT,<br>";
    echo "    `email` VARCHAR(255) UNIQUE NOT NULL,<br>";
    echo "    `mot_de_passe` VARCHAR(255) NOT NULL,<br>";
    echo "    `prenom` VARCHAR(100) NOT NULL,<br>";
    echo "    `nom` VARCHAR(100) NOT NULL,<br>";
    echo "    `genre` ENUM('homme', 'femme', 'autre') NOT NULL,<br>";
    echo "    `date_naissance` DATE NOT NULL,<br>";
    echo "    `ville` VARCHAR(100) NOT NULL,<br>";
    echo "    `date_inscription` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,<br>";
    echo "    `bio` TEXT NULL,<br>";
    echo "    `profession` VARCHAR(100) NULL,<br>";
    echo "    `photo_profil` VARCHAR(255) DEFAULT 'default.png'<br>";
    echo ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";
    echo "</div>";
    echo "</div>";
}
?>