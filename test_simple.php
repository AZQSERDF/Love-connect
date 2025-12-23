<?php
// test_simple.php
echo "<h1>🧪 Test Simple LoveConnect</h1>";

// 1. Vérifier si PHP marche
echo "✅ PHP fonctionne<br>";

// 2. Essayer de se connecter à MySQL
echo "<h3>Tentative de connexion MySQL...</h3>";

// ESSAYEZ CES OPTIONS UNE PAR UNE :
$options = [
    ["mysql:host=localhost", "root", ""],
    ["mysql:host=localhost;port=3306", "root", ""],
    ["mysql:host=localhost;port=3307", "root", ""],
    ["mysql:host=127.0.0.1", "root", ""]
];

foreach ($options as $option) {
    list($dsn, $user, $pass) = $option;
    
    try {
        $conn = new PDO($dsn, $user, $pass);
        echo "✅ CONNEXION RÉUSSIE avec : $dsn<br>";
        
        // Essayer de créer la base
        $conn->exec("CREATE DATABASE IF NOT EXISTS loveconnect_db");
        echo "  → Base créée<br>";
        
        $conn->exec("USE loveconnect_db");
        
        // Créer table simplifiée
        $sql = "CREATE TABLE IF NOT EXISTS utilisateurs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            email VARCHAR(255),
            mot_de_passe VARCHAR(255),
            prenom VARCHAR(100)
        )";
        
        $conn->exec($sql);
        echo "  → Table 'utilisateurs' créée<br>";
        
        // Sortir de la boucle si ça marche
        echo "<h3 style='color:green;'>🎉 TOUT FONCTIONNE !</h3>";
        break;
        
    } catch(PDOException $e) {
        echo "❌ Échec avec $dsn : " . $e->getMessage() . "<br>";
    }
}

echo "<hr>";
echo "<h3>📋 Prochaines étapes :</h3>";
echo "1. <a href='index.html'>Aller à l'accueil</a><br>";
echo "2. <a href='inscription.php'>Tester l'inscription</a><br>";
echo "3. <a href='login.php'>Tester la connexion</a><br>";
?>