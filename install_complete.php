<?php
// install_complete.php
echo "<h1>🚀 Installation Complète LoveConnect</h1>";

// Activer toutes les erreurs
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

try {
    // 1. Connexion à MySQL (sans spécifier la base)
    echo "<h3>1. Connexion à MySQL...</h3>";
    $conn = new PDO("mysql:host=localhost", "root", "");
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✅ Connecté à MySQL<br>";
    
    // 2. Créer la base de données
    echo "<h3>2. Création de la base...</h3>";
    $sql = "CREATE DATABASE IF NOT EXISTS loveconnect_db 
            CHARACTER SET utf8mb4 
            COLLATE utf8mb4_unicode_ci";
    $conn->exec($sql);
    echo "✅ Base 'loveconnect_db' créée<br>";
    
    // 3. Utiliser la base
    $conn->exec("USE loveconnect_db");
    
    // 4. Créer la table utilisateurs AVEC TOUTES LES COLONNES
    echo "<h3>3. Création de la table utilisateurs...</h3>";
    $sql = "CREATE TABLE IF NOT EXISTS `utilisateurs` (
        `id` INT PRIMARY KEY AUTO_INCREMENT,
        `email` VARCHAR(255) UNIQUE NOT NULL,
        `mot_de_passe` VARCHAR(255) NOT NULL,
        `prenom` VARCHAR(100) NOT NULL,
        `nom` VARCHAR(100) NOT NULL,
        `genre` ENUM('homme', 'femme', 'autre') NOT NULL,
        `date_naissance` DATE NOT NULL,
        `ville` VARCHAR(100) NOT NULL,
        `date_inscription` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `bio` TEXT NULL,
        `profession` VARCHAR(100) NULL,
        `photo_profil` VARCHAR(255) DEFAULT 'default.png'
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    
    $conn->exec($sql);
    echo "✅ Table 'utilisateurs' créée avec toutes les colonnes<br>";
    
    // 5. Vérifier la structure
    echo "<h3>4. Vérification de la structure...</h3>";
    $stmt = $conn->query("DESCRIBE utilisateurs");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' cellpadding='5' style='background:#f9f9f9;'>";
    echo "<tr style='background:#667eea;color:white;'>
            <th>Colonne</th>
            <th>Type</th>
            <th>Null</th>
            <th>Default</th>
          </tr>";
    
    $expected_columns = ['id', 'email', 'mot_de_passe', 'prenom', 'nom', 'genre', 
                         'date_naissance', 'ville', 'date_inscription', 'bio', 
                         'profession', 'photo_profil'];
    
    foreach ($columns as $col) {
        echo "<tr>";
        echo "<td><strong>{$col['Field']}</strong></td>";
        echo "<td>{$col['Type']}</td>";
        echo "<td>{$col['Null']}</td>";
        echo "<td>{$col['Default'] ?? 'NULL'}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // 6. Ajouter un utilisateur de test
    echo "<h3>5. Ajout d'un utilisateur de test...</h3>";
    $test_password = password_hash('test123', PASSWORD_BCRYPT);
    
    $sql = "INSERT INTO utilisateurs 
            (email, mot_de_passe, prenom, nom, genre, date_naissance, ville) 
            VALUES 
            ('test@loveconnect.com', :password, 'Jean', 'Dupont', 'homme', '1990-05-15', 'Paris')";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute([':password' => $test_password]);
    
    echo "✅ Utilisateur test créé<br>";
    echo "• Email: test@loveconnect.com<br>";
    echo "• Mot de passe: test123<br>";
    
    // 7. Vérifier le contenu
    echo "<h3>6. Vérification des données...</h3>";
    $stmt = $conn->query("SELECT COUNT(*) as total FROM utilisateurs");
    $count = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "✅ Nombre d'utilisateurs : {$count['total']}<br>";
    
    // 8. Afficher les utilisateurs
    $stmt = $conn->query("SELECT id, email, prenom, nom FROM utilisateurs");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($users)) {
        echo "<h4>Utilisateurs dans la base :</h4>";
        echo "<ul>";
        foreach ($users as $user) {
            echo "<li>#{$user['id']} : {$user['prenom']} {$user['nom']} ({$user['email']})</li>";
        }
        echo "</ul>";
    }
    
    // 9. SUCCÈS !
    echo "<div style='
        background: #d4edda;
        border: 2px solid #28a745;
        color: #155724;
        padding: 2rem;
        border-radius: 10px;
        margin: 2rem 0;
        text-align: center;
    '>";
    echo "<h2 style='color:#28a745;'>🎉 INSTALLATION RÉUSSIE !</h2>";
    echo "<p>La base de données est prête à être utilisée.</p>";
    echo "</div>";
    
    // 10. Liens de test
    echo "<h3>📋 Testez maintenant :</h3>";
    echo "<div style='display:flex;gap:1rem;flex-wrap:wrap;'>";
    echo "<a href='index.html' style='
        background: #667eea;
        color: white;
        padding: 1rem 2rem;
        border-radius: 10px;
        text-decoration: none;
        font-weight: bold;
    '>🏠 Accueil</a>";
    
    echo "<a href='inscription.php' style='
        background: #28a745;
        color: white;
        padding: 1rem 2rem;
        border-radius: 10px;
        text-decoration: none;
        font-weight: bold;
    '>📝 Inscription</a>";
    
    echo "<a href='login.php' style='
        background: #17a2b8;
        color: white;
        padding: 1rem 2rem;
        border-radius: 10px;
        text-decoration: none;
        font-weight: bold;
    '>🔐 Connexion</a>";
    echo "</div>";
    
    echo "<h3 style='margin-top:2rem;'>🔧 Pour vos fichiers PHP :</h3>";
    echo "<pre style='background:#f8f9fa;padding:1rem;border-radius:5px;'>";
    echo "// Dans database.php, login.php, inscription.php :\n";
    echo "\$conn = new PDO('mysql:host=localhost;dbname=loveconnect_db', 'root', '');";
    echo "</pre>";
    
} catch(PDOException $e) {
    echo "<div style='
        background: #f8d7da;
        border: 2px solid #f5c6cb;
        color: #721c24;
        padding: 2rem;
        border-radius: 10px;
        margin: 2rem 0;
    '>";
    echo "<h3 style='color:#dc3545;'>❌ ERREUR D'INSTALLATION</h3>";
    echo "<p><strong>Message :</strong> " . $e->getMessage() . "</p>";
    
    echo "<h4>Solutions possibles :</h4>";
    echo "<ol>";
    echo "<li><strong>MySQL n'est pas démarré</strong> : Ouvrez XAMPP et démarrez MySQL</li>";
    echo "<li><strong>Mauvais port</strong> : Essayez avec port 3307 :<br>";
    echo "<code>new PDO('mysql:host=localhost;port=3307', 'root', '')</code></li>";
    echo "<li><strong>Mot de passe root</strong> : Si vous avez défini un mot de passe :<br>";
    echo "<code>new PDO('mysql:host=localhost', 'root', 'VOTRE_MOT_DE_PASSE')</code></li>";
    echo "</ol>";
    
    // Test de diagnostic
    echo "<h4>Diagnostic :</h4>";
    echo "<a href='test_mysql.php'>🔍 Tester la connexion MySQL</a>";
    echo "</div>";
}
?>