<?php
// test_mysql.php - Juste pour voir si PHP et MySQL marchent

echo "<h1>🔄 Test PHP + MySQL</h1>";

// 1. Test si PHP fonctionne
echo "✅ PHP fonctionne<br>";

// 2. Test connexion MySQL
try {
    $conn = new PDO("mysql:host=localhost;port=3306;dbname=loveconnect_db", "root", "");
    echo "✅ Connexion MySQL réussie<br>";
    
    // 3. Test lecture utilisateurs
    $query = "SELECT COUNT(*) as total FROM utilisateurs";
    $result = $conn->query($query);
    $data = $result->fetch(PDO::FETCH_ASSOC);
    
    echo "✅ Base loveconnect_db accessible<br>";
    echo "👥 Utilisateurs dans la base : " . $data['total'] . "<br>";
    
    // 4. Afficher les utilisateurs
    $query = "SELECT id, prenom, nom, email FROM utilisateurs";
    $result = $conn->query($query);
    
    echo "<h3>Liste des utilisateurs :</h3>";
    while($row = $result->fetch(PDO::FETCH_ASSOC)) {
        echo "- " . $row['prenom'] . " " . $row['nom'] . " (" . $row['email'] . ")<br>";
    }
    
    $conn = null;
    
} catch(PDOException $e) {
    echo "❌ Erreur MySQL : " . $e->getMessage();
}
?>