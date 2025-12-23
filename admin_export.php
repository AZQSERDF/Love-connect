<?php
session_start();

// Vérifier admin
if (!isset($_SESSION['user_id'])) {
    die('Accès non autorisé');
}

try {
    $conn = new PDO("mysql:host=localhost;port=3306;dbname=loveconnect_db", "root", "");
    
    // Vérifier le rôle
    $stmt = $conn->prepare("SELECT role FROM utilisateurs WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    
    if (!$user || $user['role'] !== 'admin') {
        die('Permissions insuffisantes');
    }
    
    $type = $_GET['type'] ?? 'logs';
    
    if ($type === 'logs') {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="admin_logs_' . date('Y-m-d') . '.csv"');
        
        $output = fopen('php://output', 'w');
        fputcsv($output, ['ID', 'Admin', 'Action', 'Détails', 'Date', 'IP']);
        
        $stmt = $conn->query("SELECT al.*, u.prenom as admin_name 
                             FROM admin_logs al
                             LEFT JOIN utilisateurs u ON al.admin_id = u.id
                             ORDER BY al.created_at DESC");
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            fputcsv($output, [
                $row['id'],
                $row['admin_name'] ?? 'Admin #' . $row['admin_id'],
                $row['action'],
                $row['details'],
                $row['created_at'],
                $row['ip_address']
            ]);
        }
        
        fclose($output);
    }
    
} catch(PDOException $e) {
    die('Erreur: ' . $e->getMessage());
}
?>