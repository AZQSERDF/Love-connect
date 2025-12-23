<?php
session_start();

// Vérifier les permissions admin
if (!isset($_SESSION['user_id'])) {
    die(json_encode(['error' => 'Non connecté']));
}

try {
    $conn = new PDO("mysql:host=localhost;port=3306;dbname=loveconnect_db", "root", "");
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Vérifier le rôle admin
    $stmt = $conn->prepare("SELECT role FROM utilisateurs WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    
    if (!$user || $user['role'] !== 'admin') {
        die(json_encode(['error' => 'Permissions insuffisantes']));
    }
    
    $action = $_GET['action'] ?? '';
    
    switch ($action) {
        case 'get_all_users':
            $stmt = $conn->query("SELECT * FROM utilisateurs ORDER BY date_inscription DESC");
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode($users);
            break;
            
        case 'search_users':
            $search = $_GET['q'] ?? '';
            $sql = "SELECT * FROM utilisateurs WHERE 
                    prenom LIKE ? OR 
                    nom LIKE ? OR 
                    email LIKE ? OR 
                    ville LIKE ? OR 
                    id LIKE ? 
                    ORDER BY date_inscription DESC 
                    LIMIT 50";
            
            $searchTerm = "%$search%";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm]);
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode($users);
            break;
            
        default:
            echo json_encode(['error' => 'Action non reconnue']);
    }
    
} catch(PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>