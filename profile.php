<?php
session_start();

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Connexion à la base de données
try {
    $conn = new PDO("mysql:host=localhost;port=3306;dbname=loveconnect_db", "root", "");
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Récupérer les infos de l'utilisateur
    $sql = "SELECT * FROM utilisateurs WHERE id = :user_id";
    $stmt = $conn->prepare($sql);
    $stmt->execute([':user_id' => $_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        // Utilisateur non trouvé dans la base
        session_destroy();
        header("Location: login.php");
        exit();
    }
    
    // Calculer l'âge à partir de la date de naissance
    $date_naissance = new DateTime($user['date_naissance']);
    $today = new DateTime();
    $age = $today->diff($date_naissance)->y;
    
} catch(PDOException $e) {
    die("Erreur de connexion à la base : " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil - LoveConnect</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }

        header {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            padding: 1rem 2rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        header h1 {
            color: white;
            font-size: 1.8rem;
        }

        nav ul {
            list-style: none;
            display: flex;
            gap: 2rem;
        }

        nav a {
            color: white;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
            padding: 0.5rem 1rem;
            border-radius: 8px;
        }

        nav a:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateY(-2px);
        }

        .user-info {
            color: white;
            font-size: 0.9rem;
            background: rgba(255, 255, 255, 0.2);
            padding: 0.5rem 1rem;
            border-radius: 20px;
        }

        .container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 2rem;
        }

        .profile-card {
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            animation: slideUp 0.5s ease;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .profile-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 3rem 2rem 8rem;
            text-align: center;
            position: relative;
        }

        .profile-header h2 {
            color: white;
            font-size: 1.5rem;
            font-weight: 600;
        }

        .profile-image-container {
            position: absolute;
            bottom: -60px;
            left: 50%;
            transform: translateX(-50%);
        }

        .profile-image {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            border: 6px solid white;
            background: <?php echo $user['genre'] == 'homme' ? '#667eea' : ($user['genre'] == 'femme' ? '#e667aa' : '#9b59b6'); ?>;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            font-weight: bold;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            transition: transform 0.3s ease;
        }

        .profile-image:hover {
            transform: scale(1.05);
        }

        .profile-content {
            padding: 5rem 2rem 2rem;
        }

        .profile-name {
            text-align: center;
            margin-bottom: 2rem;
        }

        .profile-name h1 {
            color: #333;
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }

        .profile-name .status {
            color: #28a745;
            font-size: 0.9rem;
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
        }

        .status-dot {
            width: 8px;
            height: 8px;
            background: #28a745;
            border-radius: 50%;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }

        .profile-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .info-card {
            background: #f8f9fa;
            padding: 1.5rem;
            border-radius: 15px;
            transition: all 0.3s ease;
            border: 2px solid transparent;
        }

        .info-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.2);
            border-color: #667eea;
        }

        .info-card .icon {
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
        }

        .info-card strong {
            display: block;
            color: #667eea;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 0.5rem;
        }

        .info-card p {
            color: #333;
            font-size: 1.1rem;
            font-weight: 600;
        }

        .bio-section {
            background: #f8f9fa;
            padding: 2rem;
            border-radius: 15px;
            margin-bottom: 2rem;
        }

        .bio-section h3 {
            color: #333;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .bio-section p {
            color: #666;
            line-height: 1.8;
            font-size: 1.05rem;
        }

        .stats-container {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 1.5rem;
            border-radius: 15px;
            text-align: center;
            color: white;
        }

        .stat-card .number {
            font-size: 2rem;
            font-weight: bold;
            margin-bottom: 0.3rem;
        }

        .stat-card .label {
            font-size: 0.9rem;
            opacity: 0.9;
        }

        .action-buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
        }

        .btn {
            padding: 1rem 2.5rem;
            border: none;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            text-decoration: none;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.4);
        }

        .btn-secondary {
            background: white;
            color: #667eea;
            border: 2px solid #667eea;
        }

        .btn-secondary:hover {
            background: #667eea;
            color: white;
            transform: translateY(-3px);
        }

        .btn-logout {
            background: #dc3545;
            color: white;
        }

        .btn-logout:hover {
            background: #c82333;
            transform: translateY(-3px);
        }

        @media (max-width: 768px) {
            .profile-info {
                grid-template-columns: 1fr;
            }

            .stats-container {
                grid-template-columns: 1fr;
            }

            .action-buttons {
                flex-direction: column;
            }

            .btn {
                width: 100%;
                justify-content: center;
            }

            header {
                padding: 1rem;
                flex-direction: column;
                gap: 1rem;
            }

            nav ul {
                gap: 1rem;
            }
        }
    </style>
</head>
<body>
    <header>
        <h1>💕 LoveConnect</h1>
        <nav>
            <ul>
                <li><a href="index.html">Accueil</a></li>
                <li><a href="chat.html">Chat</a></li>
                <li><a href="swipe.php">Swipe</a></li>
                <li><a href="logout.php" class="btn-logout" style="padding: 0.5rem 1rem; font-size: 0.9rem;">Déconnexion</a></li>
            </ul>
        </nav>
        <div class="user-info">
            👋 <?php echo htmlspecialchars($user['prenom']); ?> | 📧 <?php echo htmlspecialchars($user['email']); ?>
        </div>
    </header>

    <div class="container">
        <div class="profile-card">
            <div class="profile-header">
                <h2>Mon Profil</h2>
                <div class="profile-image-container">
                    <div class="profile-image">
                        <?php echo strtoupper(substr($user['prenom'], 0, 1) . substr($user['nom'], 0, 1)); ?>
                    </div>
                </div>
            </div>

            <div class="profile-content">
                <div class="profile-name">
                    <h1><?php echo htmlspecialchars($user['prenom'] . ' ' . $user['nom']); ?></h1>
                    <span class="status">
                        <span class="status-dot"></span>
                        En ligne
                    </span>
                </div>

                <div class="stats-container">
                    <div class="stat-card">
                        <div class="number">0</div>
                        <div class="label">Matchs</div>
                    </div>
                    <div class="stat-card">
                        <div class="number">0</div>
                        <div class="label">Likes reçus</div>
                    </div>
                    <div class="stat-card">
                        <div class="number"><?php echo rand(70, 99); ?>%</div>
                        <div class="label">Compatibilité</div>
                    </div>
                </div>

                <div class="profile-info">
                    <div class="info-card">
                        <div class="icon">🎂</div>
                        <strong>Âge</strong>
                        <p><?php echo $age; ?> ans</p>
                    </div>
                    <div class="info-card">
                        <div class="icon">📍</div>
                        <strong>Ville</strong>
                        <p><?php echo htmlspecialchars($user['ville']); ?></p>
                    </div>
                    <div class="info-card">
                        <div class="icon">👤</div>
                        <strong>Genre</strong>
                        <p><?php echo htmlspecialchars(ucfirst($user['genre'])); ?></p>
                    </div>
                    <div class="info-card">
                        <div class="icon">📧</div>
                        <strong>Email</strong>
                        <p><?php echo htmlspecialchars($user['email']); ?></p>
                    </div>
                    <div class="info-card">
                        <div class="icon">📅</div>
                        <strong>Membre depuis</strong>
                        <p><?php echo date('d/m/Y', strtotime($user['date_inscription'])); ?></p>
                    </div>
                    <?php if (!empty($user['profession'])): ?>
                    <div class="info-card">
                        <div class="icon">💼</div>
                        <strong>Profession</strong>
                        <p><?php echo htmlspecialchars($user['profession']); ?></p>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="bio-section">
                    <h3>
                        <span>✨</span>
                        À propos de moi
                    </h3>
                    <p>
                        <?php 
                        if (!empty($user['bio'])) {
                            echo htmlspecialchars($user['bio']);
                        } else {
                            echo "Je suis " . htmlspecialchars($user['prenom']) . ", " . $age . " ans, de " . htmlspecialchars($user['ville']) . ". ";
                            echo "J'aime rencontrer de nouvelles personnes et partager de bons moments. ";
                            echo "Passionné" . ($user['genre'] == 'femme' ? 'e' : '') . " par la vie et les nouvelles expériences !";
                        }
                        ?>
                    </p>
                </div>

                <div class="action-buttons">
                    <button class="btn btn-primary" onclick="editProfile()">
                        ✏️ Modifier mon profil
                    </button>
                    <a href="logout.php" class="btn btn-logout">
                        🚪 Déconnexion
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script>
        function editProfile() {
            const btn = event.target;
            btn.innerHTML = '⏳ Modification...';
            btn.style.background = '#28a745';
            
            setTimeout(() => {
                alert('Cette fonctionnalité sera disponible bientôt !');
                btn.innerHTML = '✏️ Modifier mon profil';
                btn.style.background = 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)';
            }, 1000);
        }

        // Rafraîchir le statut "En ligne" toutes les 30 secondes
        setInterval(() => {
            const statusDot = document.querySelector('.status-dot');
            statusDot.style.animation = 'none';
            setTimeout(() => {
                statusDot.style.animation = 'pulse 2s infinite';
            }, 10);
        }, 30000);
    </script>
</body>
</html>