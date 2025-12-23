<?php
session_start();

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Non connecté']);
    exit();
}

// Vérifier les données POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit();
}

// Récupérer et valider les données
$action = $_POST['action'] ?? '';
$profile_id = isset($_POST['profile_id']) ? (int)$_POST['profile_id'] : 0;
$user_id = $_SESSION['user_id'];

// Validation des données
if (empty($action) || $profile_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Données invalides']);
    exit();
}

if (!in_array($action, ['like', 'dislike'])) {
    echo json_encode(['success' => false, 'message' => 'Action invalide']);
    exit();
}

// Connexion à la base de données
try {
    $conn = new PDO("mysql:host=localhost;port=3306;dbname=loveconnect_db", "root", "");
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Vérifier que l'utilisateur ne swipe pas lui-même
    if ($profile_id == $user_id) {
        echo json_encode(['success' => false, 'message' => 'Vous ne pouvez pas swiper votre propre profil']);
        exit();
    }
    
    // Vérifier que le profil existe et est actif
    $stmt = $conn->prepare("SELECT id, prenom FROM utilisateurs WHERE id = ?");
    $stmt->execute([$profile_id]);
    $profile = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$profile) {
        echo json_encode(['success' => false, 'message' => 'Profil inexistant']);
        exit();
    }
    
    // Vérifier qu'on n'a pas déjà swipé ce profil
    $stmt = $conn->prepare("SELECT id, action FROM swipes WHERE user_id = ? AND profile_id = ?");
    $stmt->execute([$user_id, $profile_id]);
    $existing_swipe = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($existing_swipe) {
        echo json_encode([
            'success' => false, 
            'message' => 'Vous avez déjà swipé ce profil (' . $existing_swipe['action'] . ')'
        ]);
        exit();
    }
    
    // Enregistrer le swipe dans la base
    $stmt = $conn->prepare("INSERT INTO swipes (user_id, profile_id, action) VALUES (?, ?, ?)");
    $stmt->execute([$user_id, $profile_id, $action]);
    
    $is_match = false;
    $match_data = null;
    
    // Vérifier si c'est un match (si l'autre personne nous a aussi liké)
    if ($action === 'like') {
        $stmt = $conn->prepare("SELECT id, created_at FROM swipes WHERE user_id = ? AND profile_id = ? AND action = 'like'");
        $stmt->execute([$profile_id, $user_id]);
        $other_like = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($other_like) {
            // C'EST UN MATCH ! 🎉
            $is_match = true;
            
            // Préparer les données du match pour la réponse
            $match_data = [
                'match_id' => uniqid(),
                'profile_id' => $profile_id,
                'profile_name' => $profile['prenom'],
                'matched_at' => date('Y-m-d H:i:s')
            ];
            
            // Optionnel : Ajouter une notification dans la base
            // $stmt = $conn->prepare("INSERT INTO notifications (user_id, type, message) VALUES (?, 'match', ?)");
            // $stmt->execute([$user_id, "Vous avez matché avec " . $profile['prenom'] . " !"]);
            // $stmt->execute([$profile_id, "Vous avez matché avec " . $_SESSION['user_prenom'] . " !"]);
        }
    }
    
    // Réponse JSON
    $response = [
        'success' => true, 
        'message' => 'Swipe enregistré avec succès',
        'match' => $is_match,
        'action' => $action,
        'profile_id' => $profile_id
    ];
    
    // Ajouter les données du match si c'est un match
    if ($is_match && $match_data) {
        $response['match_data'] = $match_data;
        $response['message'] = '🎉 MATCH ! Vous avez matché avec ' . $profile['prenom'] . ' !';
    }
    
    echo json_encode($response);
    
} catch(PDOException $e) {
    // Journaliser l'erreur (dans un vrai projet, utiliser error_log)
    $error_message = 'Erreur base de données: ' . $e->getMessage();
    
    // Réponse d'erreur
    echo json_encode([
        'success' => false, 
        'message' => 'Une erreur est survenue',
        'debug' => $error_message // À enlever en production
    ]);
}
?>