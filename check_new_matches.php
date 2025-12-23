<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['new_matches' => 0, 'total_matches' => 0]);
    exit();
}

try {
    $conn = new PDO("mysql:host=localhost;port=3306;dbname=loveconnect_db", "root", "");
    
    // Compter les matchs
    $sql = "SELECT COUNT(DISTINCT u.id) as total 
            FROM utilisateurs u
            INNER JOIN swipes s1 ON u.id = s1.profile_id
            INNER JOIN swipes s2 ON s1.user_id = s2.profile_id
            WHERE s1.user_id = ? 
            AND s2.user_id = u.id
            AND s1.action = 'like'
            AND s2.action = 'like'";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute([$_SESSION['user_id']]);
    $total = $stmt->fetchColumn();
    
    // Ici, tu pourrais aussi vérifier les NOUVEAUX matchs
    // en comparant avec un timestamp stocké en session
    
    echo json_encode([
        'new_matches' => 0, // À implémenter avec des timestamps
        'total_matches' => $total
    ]);
    
} catch(PDOException $e) {
    echo json_encode(['new_matches' => 0, 'total_matches' => 0]);
}
?>