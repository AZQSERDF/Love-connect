<?php
session_start();

// Vérifier si l'utilisateur est connecté ET admin
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Connexion à la base de données
try {
    $conn = new PDO("mysql:host=localhost;port=3306;dbname=loveconnect_db", "root", "");
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Vérifier si l'utilisateur est admin
    $sql = "SELECT role FROM utilisateurs WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    
    if (!$user || $user['role'] !== 'admin') {
        header("Location: index.php");
        exit();
    }
    
    // Statistiques générales
    $stats = [];
    
    // Nombre total d'utilisateurs
    $stmt = $conn->query("SELECT COUNT(*) as total FROM utilisateurs");
    $stats['total_users'] = $stmt->fetchColumn();
    
    // Utilisateurs actifs (connectés dans les 7 derniers jours)
    $stmt = $conn->query("SELECT COUNT(DISTINCT user_id) as active FROM swipes WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
    $stats['active_users'] = $stmt->fetchColumn();
    
    // Nombre total de matchs
    $stmt = $conn->query("SELECT COUNT(*) as total FROM (
        SELECT DISTINCT s1.user_id, s1.profile_id 
        FROM swipes s1 
        INNER JOIN swipes s2 ON s1.user_id = s2.profile_id AND s1.profile_id = s2.user_id 
        WHERE s1.action = 'like' AND s2.action = 'like'
    ) as matches");
    $stats['total_matches'] = $stmt->fetchColumn();
    
    // Nouveaux utilisateurs (7 derniers jours)
    $stmt = $conn->query("SELECT COUNT(*) as new FROM utilisateurs WHERE date_inscription >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
    $stats['new_users'] = $stmt->fetchColumn();
    
    // Signalements en attente
    $stmt = $conn->query("SELECT COUNT(*) as pending FROM signalements WHERE statut = 'en_attente'");
    $stats['pending_reports'] = $stmt->fetchColumn();
    
    // Récupérer les derniers utilisateurs
    $stmt = $conn->query("SELECT * FROM utilisateurs ORDER BY date_inscription DESC LIMIT 10");
    $recent_users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Récupérer les signalements
    $stmt = $conn->query("SELECT s.*, 
        u1.prenom as reporter_name, 
        u2.prenom as reported_name 
        FROM signalements s
        LEFT JOIN utilisateurs u1 ON s.user_id = u1.id
        LEFT JOIN utilisateurs u2 ON s.profile_id = u2.id
        ORDER BY s.created_at DESC");
    $reports = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Récupérer les logs admin
    $stmt = $conn->query("SELECT al.*, u.prenom as admin_name 
        FROM admin_logs al
        LEFT JOIN utilisateurs u ON al.admin_id = u.id
        ORDER BY al.created_at DESC LIMIT 20");
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch(PDOException $e) {
    die("Erreur : " . $e->getMessage());
}

// Traitement des actions admin
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    try {
        switch ($action) {
            case 'delete_user':
                $user_id = (int)$_POST['user_id'];
                $reason = $_POST['reason'] ?? 'Suppression par admin';
                
                // Log l'action
                $stmt = $conn->prepare("INSERT INTO admin_logs (admin_id, action, details) VALUES (?, ?, ?)");
                $stmt->execute([$_SESSION['user_id'], 'delete_user', "Suppression utilisateur #$user_id: $reason"]);
                
                // Supprimer l'utilisateur
                $stmt = $conn->prepare("DELETE FROM utilisateurs WHERE id = ?");
                $stmt->execute([$user_id]);
                
                // Supprimer aussi ses swipes et matchs
                $stmt = $conn->prepare("DELETE FROM swipes WHERE user_id = ? OR profile_id = ?");
                $stmt->execute([$user_id, $user_id]);
                
                $_SESSION['admin_message'] = "✅ Utilisateur supprimé avec succès";
                break;
                
            case 'update_role':
                $user_id = (int)$_POST['user_id'];
                $new_role = $_POST['role'];
                
                $stmt = $conn->prepare("UPDATE utilisateurs SET role = ? WHERE id = ?");
                $stmt->execute([$new_role, $user_id]);
                
                // Log
                $stmt = $conn->prepare("INSERT INTO admin_logs (admin_id, action, details) VALUES (?, ?, ?)");
                $stmt->execute([$_SESSION['user_id'], 'update_role', "Changement rôle utilisateur #$user_id -> $new_role"]);
                
                $_SESSION['admin_message'] = "✅ Rôle mis à jour";
                break;
                
            case 'handle_report':
                $report_id = (int)$_POST['report_id'];
                $decision = $_POST['decision'];
                
                $stmt = $conn->prepare("UPDATE signalements SET statut = ? WHERE id = ?");
                $stmt->execute([$decision, $report_id]);
                
                // Log
                $stmt = $conn->prepare("INSERT INTO admin_logs (admin_id, action, details) VALUES (?, ?, ?)");
                $stmt->execute([$_SESSION['user_id'], 'handle_report', "Signalement #$report_id -> $decision"]);
                
                $_SESSION['admin_message'] = "✅ Signalement traité";
                break;
                
            case 'send_notification':
                $user_id = $_POST['user_id'];
                $message = $_POST['message'];
                
                // Ici, tu pourrais stocker la notification dans une table
                // Pour l'instant, on log juste
                $stmt = $conn->prepare("INSERT INTO admin_logs (admin_id, action, details) VALUES (?, ?, ?)");
                
                if ($user_id === 'all') {
                    $stmt->execute([$_SESSION['user_id'], 'notification_all', "Notification à tous: $message"]);
                    $_SESSION['admin_message'] = "✅ Notification envoyée à tous les utilisateurs";
                } else {
                    $stmt->execute([$_SESSION['user_id'], 'notification_user', "Notification à #$user_id: $message"]);
                    $_SESSION['admin_message'] = "✅ Notification envoyée à l'utilisateur";
                }
                break;
        }
        
        // Redirection pour éviter le re-POST
        header("Location: admin.php");
        exit();
        
    } catch(PDOException $e) {
        $_SESSION['admin_error'] = "Erreur: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Admin - LoveConnect</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f7fa;
            color: #333;
        }

        /* Sidebar */
        .sidebar {
            width: 250px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            height: 100vh;
            position: fixed;
            left: 0;
            top: 0;
            padding: 20px;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
        }

        .sidebar h1 {
            font-size: 1.5rem;
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .nav-links {
            list-style: none;
        }

        .nav-links li {
            margin-bottom: 10px;
        }

        .nav-links a {
            color: white;
            text-decoration: none;
            padding: 12px 15px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s ease;
        }

        .nav-links a:hover,
        .nav-links a.active {
            background: rgba(255,255,255,0.2);
        }

        .admin-info {
            position: absolute;
            bottom: 20px;
            left: 20px;
            right: 20px;
            background: rgba(255,255,255,0.1);
            padding: 15px;
            border-radius: 10px;
        }

        /* Main content */
        .main-content {
            margin-left: 250px;
            padding: 20px;
        }

        /* Header */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid #e0e0e0;
        }

        .header h2 {
            color: #667eea;
            font-size: 1.8rem;
        }

        .current-time {
            color: #666;
            font-size: 0.9rem;
        }

        /* Stats cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            text-align: center;
            transition: transform 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-icon {
            font-size: 2.5rem;
            margin-bottom: 15px;
        }

        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            color: #667eea;
            margin-bottom: 5px;
        }

        .stat-label {
            color: #666;
            font-size: 0.9rem;
        }

        /* Sections */
        .section {
            background: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }

        .section-title {
            font-size: 1.3rem;
            color: #667eea;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        /* Tables */
        .data-table {
            width: 100%;
            border-collapse: collapse;
        }

        .data-table th {
            background: #f8f9fa;
            padding: 12px 15px;
            text-align: left;
            font-weight: 600;
            color: #495057;
            border-bottom: 2px solid #e9ecef;
        }

        .data-table td {
            padding: 12px 15px;
            border-bottom: 1px solid #e9ecef;
        }

        .data-table tr:hover {
            background: #f8f9fa;
        }

        /* Badges */
        .badge {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .badge-user {
            background: #e3f2fd;
            color: #1976d2;
        }

        .badge-admin {
            background: #f3e5f5;
            color: #7b1fa2;
        }

        .badge-moderator {
            background: #e8f5e9;
            color: #388e3c;
        }

        .badge-pending {
            background: #fff3e0;
            color: #f57c00;
        }

        .badge-treated {
            background: #e8f5e9;
            color: #388e3c;
        }

        .badge-rejected {
            background: #ffebee;
            color: #d32f2f;
        }

        /* Buttons */
        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .btn-sm {
            padding: 5px 10px;
            font-size: 0.85rem;
        }

        .btn-primary {
            background: #667eea;
            color: white;
        }

        .btn-primary:hover {
            background: #5a6fd8;
            transform: translateY(-2px);
        }

        .btn-danger {
            background: #ff6b6b;
            color: white;
        }

        .btn-danger:hover {
            background: #ff5252;
        }

        .btn-success {
            background: #51cf66;
            color: white;
        }

        .btn-success:hover {
            background: #40c057;
        }

        .btn-warning {
            background: #ffa94d;
            color: white;
        }

        .btn-warning:hover {
            background: #ff922b;
        }

        /* Forms */
        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #495057;
        }

        .form-control {
            width: 100%;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s ease;
        }

        .form-control:focus {
            outline: none;
            border-color: #667eea;
        }

        /* Modals */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }

        .modal-content {
            background: white;
            padding: 30px;
            border-radius: 15px;
            max-width: 500px;
            width: 90%;
            max-height: 80vh;
            overflow-y: auto;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }

        .modal-title {
            font-size: 1.3rem;
            color: #667eea;
        }

        .close-modal {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: #666;
        }

        /* Messages */
        .alert {
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        /* Responsive */
        @media (max-width: 1024px) {
            .sidebar {
                width: 200px;
            }
            
            .main-content {
                margin-left: 200px;
            }
        }

        @media (max-width: 768px) {
            .sidebar {
                width: 100%;
                height: auto;
                position: relative;
                margin-bottom: 20px;
            }
            
            .main-content {
                margin-left: 0;
            }
            
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .admin-info {
                position: relative;
                bottom: auto;
                margin-top: 20px;
            }
        }

        @media (max-width: 480px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .section {
                padding: 15px;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <h1>👑 LoveConnect Admin</h1>
        
        <ul class="nav-links">
            <li><a href="#dashboard" class="active" onclick="showSection('dashboard')">
                📊 Tableau de bord
            </a></li>
            <li><a href="#users" onclick="showSection('users')">
                👥 Utilisateurs
            </a></li>
            <li><a href="#reports" onclick="showSection('reports')">
                ⚠️ Signalements
            </a></li>
            <li><a href="#notifications" onclick="showSection('notifications')">
                🔔 Notifications
            </a></li>
            <li><a href="#logs" onclick="showSection('logs')">
                📝 Logs Admin
            </a></li>
            <li><a href="index.php" target="_blank">
                🌐 Voir le site
            </a></li>
            <li><a href="logout.php">
                🚪 Déconnexion
            </a></li>
        </ul>
        
        <div class="admin-info">
            <strong>👤 <?php echo htmlspecialchars($_SESSION['user_prenom'] ?? 'Admin'); ?></strong>
            <div style="font-size: 0.8rem; opacity: 0.8;">
                Connecté en tant qu'administrateur
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Header -->
        <div class="header">
            <h2 id="pageTitle">Tableau de bord</h2>
            <div class="current-time" id="currentTime"></div>
        </div>

        <!-- Messages -->
        <?php if (isset($_SESSION['admin_message'])): ?>
        <div class="alert alert-success">
            ✅ <?php echo $_SESSION['admin_message']; unset($_SESSION['admin_message']); ?>
        </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['admin_error'])): ?>
        <div class="alert alert-error">
            ❌ <?php echo $_SESSION['admin_error']; unset($_SESSION['admin_error']); ?>
        </div>
        <?php endif; ?>

        <!-- Dashboard Section -->
        <div id="dashboard" class="section active">
            <div class="section-header">
                <h3 class="section-title">📊 Vue d'ensemble</h3>
            </div>

            <!-- Stats -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">👥</div>
                    <div class="stat-number"><?php echo $stats['total_users']; ?></div>
                    <div class="stat-label">Utilisateurs totaux</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">🔥</div>
                    <div class="stat-number"><?php echo $stats['active_users']; ?></div>
                    <div class="stat-label">Utilisateurs actifs</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">💞</div>
                    <div class="stat-number"><?php echo $stats['total_matches']; ?></div>
                    <div class="stat-label">Matchs totaux</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">🆕</div>
                    <div class="stat-number"><?php echo $stats['new_users']; ?></div>
                    <div class="stat-label">Nouveaux (7j)</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">⚠️</div>
                    <div class="stat-number"><?php echo $stats['pending_reports']; ?></div>
                    <div class="stat-label">Signalements en attente</div>
                </div>
            </div>

            <!-- Recent Users -->
            <div class="section">
                <div class="section-header">
                    <h3 class="section-title">👥 Derniers utilisateurs inscrits</h3>
                    <button class="btn btn-primary" onclick="showSection('users')">
                        Voir tous
                    </button>
                </div>
                
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nom</th>
                            <th>Email</th>
                            <th>Rôle</th>
                            <th>Inscription</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_users as $user): 
                            $inscription = new DateTime($user['date_inscription']);
                        ?>
                        <tr>
                            <td>#<?php echo $user['id']; ?></td>
                            <td>
                                <strong><?php echo htmlspecialchars($user['prenom'] . ' ' . $user['nom']); ?></strong>
                            </td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td>
                                <span class="badge badge-<?php echo $user['role']; ?>">
                                    <?php echo ucfirst($user['role']); ?>
                                </span>
                            </td>
                            <td><?php echo $inscription->format('d/m/Y H:i'); ?></td>
                            <td>
                                <button class="btn btn-sm btn-primary" onclick="editUser(<?php echo $user['id']; ?>)">
                                    ✏️
                                </button>
                                <?php if ($user['role'] !== 'admin'): ?>
                                <button class="btn btn-sm btn-danger" onclick="confirmDelete(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['prenom']); ?>')">
                                    🗑️
                                </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Users Section -->
        <div id="users" class="section" style="display: none;">
            <div class="section-header">
                <h3 class="section-title">👥 Gestion des utilisateurs</h3>
                <div>
                    <button class="btn btn-primary" onclick="searchUsers()">
                        🔍 Rechercher
                    </button>
                </div>
            </div>
            
            <!-- Search form -->
            <div class="form-group">
                <input type="text" id="userSearch" class="form-control" 
                       placeholder="Rechercher par nom, email ou ID..." 
                       onkeyup="searchUsers()">
            </div>
            
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nom</th>
                        <th>Email</th>
                        <th>Ville</th>
                        <th>Rôle</th>
                        <th>Inscription</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="usersTableBody">
                    <!-- Les utilisateurs seront chargés ici via JavaScript -->
                </tbody>
            </table>
        </div>

        <!-- Reports Section -->
        <div id="reports" class="section" style="display: none;">
            <div class="section-header">
                <h3 class="section-title">⚠️ Signalements</h3>
            </div>
            
            <?php if (empty($reports)): ?>
                <p style="text-align: center; color: #666; padding: 40px;">
                    Aucun signalement pour le moment.
                </p>
            <?php else: ?>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Signalé par</th>
                            <th>Profil signalé</th>
                            <th>Raison</th>
                            <th>Statut</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($reports as $report): 
                            $date = new DateTime($report['created_at']);
                            $status_class = 'badge-' . str_replace('_', '-', $report['statut']);
                        ?>
                        <tr>
                            <td>#<?php echo $report['id']; ?></td>
                            <td><?php echo htmlspecialchars($report['reporter_name'] ?? 'Inconnu'); ?></td>
                            <td><?php echo htmlspecialchars($report['reported_name'] ?? 'Inconnu'); ?></td>
                            <td><?php echo htmlspecialchars(substr($report['raison'], 0, 50)) . (strlen($report['raison']) > 50 ? '...' : ''); ?></td>
                            <td>
                                <span class="badge <?php echo $status_class; ?>">
                                    <?php echo ucfirst(str_replace('_', ' ', $report['statut'])); ?>
                                </span>
                            </td>
                            <td><?php echo $date->format('d/m/Y H:i'); ?></td>
                            <td>
                                <?php if ($report['statut'] === 'en_attente'): ?>
                                <button class="btn btn-sm btn-success" 
                                        onclick="handleReport(<?php echo $report['id']; ?>, 'traite')">
                                    ✅ Traiter
                                </button>
                                <button class="btn btn-sm btn-danger" 
                                        onclick="handleReport(<?php echo $report['id']; ?>, 'rejete')">
                                    ❌ Rejeter
                                </button>
                                <?php endif; ?>
                                <button class="btn btn-sm btn-primary" 
                                        onclick="viewReportDetails(<?php echo $report['id']; ?>)">
                                    👁️ Voir
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <!-- Notifications Section -->
        <div id="notifications" class="section" style="display: none;">
            <div class="section-header">
                <h3 class="section-title">🔔 Envoyer une notification</h3>
            </div>
            
            <form method="POST" onsubmit="return sendNotification(this)">
                <input type="hidden" name="action" value="send_notification">
                
                <div class="form-group">
                    <label>Destinataire</label>
                    <select name="user_id" class="form-control" required>
                        <option value="all">👥 Tous les utilisateurs</option>
                        <option value="specific">👉 Sélectionner un utilisateur spécifique</option>
                    </select>
                </div>
                
                <div class="form-group" id="specificUserField" style="display: none;">
                    <label>Sélectionner l'utilisateur</label>
                    <select class="form-control" name="specific_user_id">
                        <option value="">Choisir un utilisateur...</option>
                        <?php 
                        $stmt = $conn->query("SELECT id, prenom, nom FROM utilisateurs ORDER BY prenom");
                        $all_users = $stmt->fetchAll();
                        foreach ($all_users as $u): ?>
                        <option value="<?php echo $u['id']; ?>">
                            <?php echo htmlspecialchars($u['prenom'] . ' ' . $u['nom']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Message</label>
                    <textarea name="message" class="form-control" rows="5" 
                              placeholder="Entrez votre message de notification..." required></textarea>
                </div>
                
                <div class="form-group">
                    <button type="submit" class="btn btn-primary">
                        📤 Envoyer la notification
                    </button>
                </div>
            </form>
        </div>

        <!-- Logs Section -->
        <div id="logs" class="section" style="display: none;">
            <div class="section-header">
                <h3 class="section-title">📝 Logs d'administration</h3>
                <button class="btn btn-warning" onclick="exportLogs()">
                    📄 Exporter les logs
                </button>
            </div>
            
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Admin</th>
                        <th>Action</th>
                        <th>Détails</th>
                        <th>Date</th>
                        <th>IP</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($logs as $log): 
                        $date = new DateTime($log['created_at']);
                    ?>
                    <tr>
                        <td>#<?php echo $log['id']; ?></td>
                        <td><?php echo htmlspecialchars($log['admin_name'] ?? 'Admin #' . $log['admin_id']); ?></td>
                        <td><?php echo htmlspecialchars($log['action']); ?></td>
                        <td><?php echo htmlspecialchars($log['details'] ?? ''); ?></td>
                        <td><?php echo $date->format('d/m/Y H:i:s'); ?></td>
                        <td><?php echo htmlspecialchars($log['ip_address'] ?? 'N/A'); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modals -->
    <div id="deleteModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">🗑️ Supprimer un utilisateur</h3>
                <button class="close-modal" onclick="closeModal('deleteModal')">×</button>
            </div>
            <form method="POST" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cet utilisateur ? Cette action est irréversible.');">
                <input type="hidden" name="action" value="delete_user">
                <input type="hidden" id="deleteUserId" name="user_id">
                
                <div class="form-group">
                    <label>Utilisateur à supprimer</label>
                    <input type="text" id="deleteUserName" class="form-control" readonly>
                </div>
                
                <div class="form-group">
                    <label>Raison de la suppression</label>
                    <textarea name="reason" class="form-control" rows="3" required 
                              placeholder="Pourquoi supprimez-vous cet utilisateur ?"></textarea>
                </div>
                
                <div style="display: flex; gap: 10px; margin-top: 20px;">
                    <button type="button" class="btn" onclick="closeModal('deleteModal')" style="flex: 1;">
                        Annuler
                    </button>
                    <button type="submit" class="btn btn-danger" style="flex: 1;">
                        Confirmer la suppression
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div id="editUserModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">✏️ Modifier un utilisateur</h3>
                <button class="close-modal" onclick="closeModal('editUserModal')">×</button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="update_role">
                <input type="hidden" id="editUserId" name="user_id">
                
                <div class="form-group">
                    <label>Nom complet</label>
                    <input type="text" id="editUserName" class="form-control" readonly>
                </div>
                
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" id="editUserEmail" class="form-control" readonly>
                </div>
                
                <div class="form-group">
                    <label>Rôle</label>
                    <select name="role" class="form-control" required>
                        <option value="user">👤 Utilisateur</option>
                        <option value="moderator">🛡️ Modérateur</option>
                        <option value="admin">👑 Administrateur</option>
                    </select>
                </div>
                
                <div style="display: flex; gap: 10px; margin-top: 20px;">
                    <button type="button" class="btn" onclick="closeModal('editUserModal')" style="flex: 1;">
                        Annuler
                    </button>
                    <button type="submit" class="btn btn-primary" style="flex: 1;">
                        Mettre à jour
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Gestion des sections
        function showSection(sectionId) {
            // Cacher toutes les sections
            document.querySelectorAll('.section').forEach(section => {
                section.style.display = 'none';
                section.classList.remove('active');
            });
            
            // Afficher la section demandée
            const section = document.getElementById(sectionId);
            if (section) {
                section.style.display = 'block';
                section.classList.add('active');
            }
            
            // Mettre à jour le titre
            const titles = {
                'dashboard': 'Tableau de bord',
                'users': 'Gestion des utilisateurs',
                'reports': 'Signalements',
                'notifications': 'Notifications',
                'logs': 'Logs d\'administration'
            };
            
            document.getElementById('pageTitle').textContent = titles[sectionId] || 'Panel Admin';
            
            // Charger les utilisateurs si nécessaire
            if (sectionId === 'users') {
                loadAllUsers();
            }
        }

        // Heure en temps réel
        function updateTime() {
            const now = new Date();
            const timeString = now.toLocaleDateString('fr-FR', {
                weekday: 'long',
                year: 'numeric',
                month: 'long',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit'
            });
            document.getElementById('currentTime').textContent = timeString;
        }
        setInterval(updateTime, 1000);
        updateTime();

        // Modals
        function openModal(modalId) {
            document.getElementById(modalId).style.display = 'flex';
        }

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }

        // Suppression d'utilisateur
        function confirmDelete(userId, userName) {
            document.getElementById('deleteUserId').value = userId;
            document.getElementById('deleteUserName').value = userName;
            openModal('deleteModal');
        }

        // Édition d'utilisateur
        function editUser(userId) {
            // En production, tu ferais une requête AJAX pour récupérer les infos
            // Pour l'instant, on utilise les données affichées
            const row = document.querySelector(`button[onclick="editUser(${userId})"]`).closest('tr');
            const cells = row.querySelectorAll('td');
            
            document.getElementById('editUserId').value = userId;
            document.getElementById('editUserName').value = cells[1].textContent.trim();
            document.getElementById('editUserEmail').value = cells[2].textContent.trim();
            
            // Déterminer le rôle actuel
            const roleBadge = cells[3].querySelector('.badge');
            const currentRole = roleBadge ? roleBadge.textContent.toLowerCase() : 'user';
            document.querySelector('select[name="role"]').value = currentRole;
            
            openModal('editUserModal');
        }

        // Gestion des signalements
        function handleReport(reportId, decision) {
            if (confirm(`Voulez-vous vraiment ${decision === 'traite' ? 'traiter' : 'rejeter'} ce signalement ?`)) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.style.display = 'none';
                
                const actionInput = document.createElement('input');
                actionInput.name = 'action';
                actionInput.value = 'handle_report';
                form.appendChild(actionInput);
                
                const reportInput = document.createElement('input');
                reportInput.name = 'report_id';
                reportInput.value = reportId;
                form.appendChild(reportInput);
                
                const decisionInput = document.createElement('input');
                decisionInput.name = 'decision';
                decisionInput.value = decision;
                form.appendChild(decisionInput);
                
                document.body.appendChild(form);
                form.submit();
            }
        }

        function viewReportDetails(reportId) {
            alert(`Détails du signalement #${reportId}\n\nCette fonctionnalité sera implémentée prochainement.`);
        }

        // Notifications
        document.querySelector('select[name="user_id"]').addEventListener('change', function() {
            const specificField = document.getElementById('specificUserField');
            if (this.value === 'specific') {
                specificField.style.display = 'block';
            } else {
                specificField.style.display = 'none';
            }
        });

        function sendNotification(form) {
            const userId = form.user_id.value;
            const message = form.message.value.trim();
            
            if (!message) {
                alert('Veuillez entrer un message');
                return false;
            }
            
            if (userId === 'specific') {
                const specificId = form.specific_user_id.value;
                if (!specificId) {
                    alert('Veuillez sélectionner un utilisateur');
                    return false;
                }
                form.user_id.value = specificId;
            }
            
            return confirm('Envoyer cette notification ?');
        }

        // Recherche d'utilisateurs
        async function searchUsers() {
            const searchTerm = document.getElementById('userSearch').value.toLowerCase();
            const response = await fetch('admin_ajax.php?action=search_users&q=' + encodeURIComponent(searchTerm));
            const users = await response.json();
            
            const tbody = document.getElementById('usersTableBody');
            tbody.innerHTML = '';
            
            users.forEach(user => {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>#${user.id}</td>
                    <td><strong>${escapeHtml(user.prenom)} ${escapeHtml(user.nom)}</strong></td>
                    <td>${escapeHtml(user.email)}</td>
                    <td>${escapeHtml(user.ville || 'Non spécifiée')}</td>
                    <td><span class="badge badge-${user.role}">${user.role}</span></td>
                    <td>${new Date(user.date_inscription).toLocaleDateString('fr-FR')}</td>
                    <td>
                        <button class="btn btn-sm btn-primary" onclick="editUser(${user.id})">✏️</button>
                        ${user.role !== 'admin' ? `<button class="btn btn-sm btn-danger" onclick="confirmDelete(${user.id}, '${escapeHtml(user.prenom)}')">🗑️</button>` : ''}
                    </td>
                `;
                tbody.appendChild(row);
            });
        }

        async function loadAllUsers() {
            const response = await fetch('admin_ajax.php?action=get_all_users');
            const users = await response.json();
            
            const tbody = document.getElementById('usersTableBody');
            tbody.innerHTML = '';
            
            users.forEach(user => {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>#${user.id}</td>
                    <td><strong>${escapeHtml(user.prenom)} ${escapeHtml(user.nom)}</strong></td>
                    <td>${escapeHtml(user.email)}</td>
                    <td>${escapeHtml(user.ville || 'Non spécifiée')}</td>
                    <td><span class="badge badge-${user.role}">${user.role}</span></td>
                    <td>${new Date(user.date_inscription).toLocaleDateString('fr-FR')}</td>
                    <td>
                        <button class="btn btn-sm btn-primary" onclick="editUser(${user.id})">✏️</button>
                        ${user.role !== 'admin' ? `<button class="btn btn-sm btn-danger" onclick="confirmDelete(${user.id}, '${escapeHtml(user.prenom)}')">🗑️</button>` : ''}
                    </td>
                `;
                tbody.appendChild(row);
            });
        }

        // Export logs
        function exportLogs() {
            window.open('admin_export.php?type=logs', '_blank');
        }

        // Utilitaires
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        // Initialisation
        document.addEventListener('DOMContentLoaded', function() {
            // Gestion des liens de navigation
            document.querySelectorAll('.nav-links a').forEach(link => {
                link.addEventListener('click', function(e) {
                    if (!this.getAttribute('href').startsWith('#')) return;
                    
                    e.preventDefault();
                    const sectionId = this.getAttribute('href').substring(1);
                    showSection(sectionId);
                    
                    // Mettre à jour l'état actif
                    document.querySelectorAll('.nav-links a').forEach(a => {
                        a.classList.remove('active');
                    });
                    this.classList.add('active');
                });
            });
        });
    </script>
</body>
</html>