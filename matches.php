<?php
session_start();

// Activer l'affichage des erreurs temporairement
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$matches = [];
$total_matches = 0;

// Connexion à la base de données
try {
    $conn = new PDO("mysql:host=localhost;port=3306;dbname=loveconnect_db", "root", "");
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // REQUÊTE SIMPLIFIÉE ET CORRECTE POUR LES MATCHS
    $sql = "SELECT DISTINCT 
                u.id,
                u.prenom,
                u.nom,
                u.genre,
                u.ville,
                u.date_naissance,
                u.photo_profil,
                GREATEST(s1.created_at, s2.created_at) as matched_at
            FROM utilisateurs u
            INNER JOIN swipes s1 ON u.id = s1.profile_id
            INNER JOIN swipes s2 ON s1.user_id = s2.profile_id
            WHERE s1.user_id = :user_id 
            AND s2.user_id = u.id
            AND s1.action = 'like'
            AND s2.action = 'like'
            ORDER BY matched_at DESC";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute([':user_id' => $_SESSION['user_id']]);
    $matches = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calculer l'âge pour chaque match
    if ($matches) {
        foreach ($matches as &$match) {
            if (!empty($match['date_naissance'])) {
                $date_naissance = new DateTime($match['date_naissance']);
                $today = new DateTime();
                $match['age'] = $today->diff($date_naissance)->y;
            } else {
                $match['age'] = '?';
            }
        }
    }
    
    // Compter le nombre total de matchs
    $total_matches = count($matches);
    
} catch(PDOException $e) {
    // Afficher l'erreur pour déboguer
    echo "<div style='background: #f8d7da; color: #721c24; padding: 20px; margin: 20px; border-radius: 10px;'>";
    echo "<h3>Erreur SQL :</h3>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p><strong>Astuce :</strong> Assurez-vous que la table 'swipes' existe et contient des données.</p>";
    echo "</div>";
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes Matchs - LoveConnect</title>
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
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            position: sticky;
            top: 0;
            z-index: 100;
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

        .matches-header {
            text-align: center;
            padding: 3rem 2rem 1rem;
            color: white;
        }

        .matches-header h2 {
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
        }

        .matches-count {
            font-size: 1.2rem;
            opacity: 0.9;
            background: rgba(255, 255, 255, 0.2);
            display: inline-block;
            padding: 0.5rem 1.5rem;
            border-radius: 20px;
            margin-top: 1rem;
        }

        .matches-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 2rem;
        }

        /* Carte de match */
        .match-card {
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            transition: all 0.3s ease;
            position: relative;
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

        .match-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
        }

        .match-badge {
            position: absolute;
            top: 15px;
            right: 15px;
            background: #ff6b6b;
            color: white;
            padding: 0.3rem 0.8rem;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 600;
            z-index: 2;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }

        .match-header {
            padding: 2rem;
            text-align: center;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            position: relative;
        }

        .match-avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: #667eea; /* Couleur par défaut */
            margin: 0 auto 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 2.5rem;
            font-weight: bold;
            border: 5px solid white;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }

        .match-name {
            color: white;
            font-size: 1.8rem;
            font-weight: 600;
            margin-bottom: 0.3rem;
        }

        .match-details {
            color: rgba(255, 255, 255, 0.9);
            font-size: 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            margin-bottom: 0.5rem;
        }

        .match-content {
            padding: 1.5rem;
        }

        .match-info {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .info-item {
            text-align: center;
            padding: 0.8rem;
            background: #f8f9fa;
            border-radius: 10px;
        }

        .info-label {
            display: block;
            color: #667eea;
            font-size: 0.8rem;
            font-weight: 600;
            margin-bottom: 0.3rem;
            text-transform: uppercase;
        }

        .info-value {
            color: #333;
            font-weight: 600;
        }

        .match-actions {
            display: flex;
            gap: 1rem;
            justify-content: center;
        }

        .match-btn {
            flex: 1;
            padding: 0.8rem;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .chat-btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .chat-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }

        .profile-btn {
            background: white;
            color: #667eea;
            border: 2px solid #667eea;
        }

        .profile-btn:hover {
            background: #667eea;
            color: white;
            transform: translateY(-3px);
        }

        .match-time {
            text-align: center;
            color: #999;
            font-size: 0.8rem;
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid #eee;
        }

        /* Pas de matchs */
        .no-matches {
            text-align: center;
            color: white;
            padding: 5rem 2rem;
            max-width: 600px;
            margin: 0 auto;
        }

        .no-matches h3 {
            font-size: 2rem;
            margin-bottom: 1.5rem;
        }

        .no-matches p {
            font-size: 1.2rem;
            margin-bottom: 2rem;
            opacity: 0.9;
        }

        .action-buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
        }

        .action-btn {
            padding: 1rem 2rem;
            border: none;
            border-radius: 50px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .primary-btn {
            background: white;
            color: #667eea;
        }

        .primary-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(255, 255, 255, 0.3);
        }

        .secondary-btn {
            background: transparent;
            color: white;
            border: 2px solid white;
        }

        .secondary-btn:hover {
            background: white;
            color: #667eea;
            transform: translateY(-3px);
        }

        /* Animation de match */
        .match-animation {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 1000;
            animation: fadeIn 0.5s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .match-popup {
            background: white;
            padding: 3rem;
            border-radius: 20px;
            text-align: center;
            animation: popIn 0.5s ease;
        }

        @keyframes popIn {
            0% { transform: scale(0.5); opacity: 0; }
            100% { transform: scale(1); opacity: 1; }
        }

        .match-popup h2 {
            color: #51cf66;
            font-size: 3rem;
            margin-bottom: 1rem;
        }

        /* Debug info (à enlever en production) */
        .debug-info {
            background: rgba(0,0,0,0.7);
            color: white;
            padding: 15px;
            margin: 20px;
            border-radius: 10px;
            font-family: monospace;
            font-size: 12px;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .matches-container {
                grid-template-columns: 1fr;
                padding: 1rem;
            }

            header {
                padding: 1rem;
                flex-direction: column;
                gap: 1rem;
            }

            nav ul {
                gap: 1rem;
            }

            .matches-header {
                padding: 2rem 1rem;
            }

            .matches-header h2 {
                font-size: 2rem;
            }

            .match-actions {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <?php
    // Debug info (à commenter en production)
    if (isset($_GET['debug'])) {
        echo "<div class='debug-info'>";
        echo "<strong>Debug Info:</strong><br>";
        echo "User ID: " . $_SESSION['user_id'] . "<br>";
        echo "Total matches found: " . $total_matches . "<br>";
        echo "Matches array: ";
        echo "<pre>" . print_r($matches, true) . "</pre>";
        
        // Vérifier les swipes
        try {
            $stmt = $conn->query("SELECT * FROM swipes ORDER BY created_at DESC LIMIT 10");
            $swipes = $stmt->fetchAll();
            echo "Last 10 swipes: <pre>" . print_r($swipes, true) . "</pre>";
        } catch(Exception $e) {
            echo "Error checking swipes: " . $e->getMessage();
        }
        echo "</div>";
    }
    ?>

    <header>
        <h1>💕 LoveConnect</h1>
        <nav>
            <ul>
                <li><a href="index.php">🏠 Accueil</a></li>
                <li><a href="swipe.php">💖 Swipe</a></li>
                <li><a href="matches.php" style="background: rgba(255, 255, 255, 0.2);">💞 Matchs</a></li>
                <li><a href="chat.html">💬 Chat</a></li>
                <li><a href="logout.php">🚪 Déconnexion</a></li>
            </ul>
        </nav>
        <div class="user-info">
            👋 <?php echo htmlspecialchars($_SESSION['user_prenom'] ?? 'Utilisateur'); ?>
            <?php if ($total_matches > 0): ?>
                | 💞 <?php echo $total_matches; ?> match<?php echo $total_matches > 1 ? 's' : ''; ?>
            <?php endif; ?>
        </div>
    </header>

    <div class="matches-header">
        <h2>💞 Mes Matchs</h2>
        <div class="matches-count">
            <?php if ($total_matches > 0): ?>
                Vous avez <?php echo $total_matches; ?> match<?php echo $total_matches > 1 ? 's' : ''; ?> !
            <?php else: ?>
                Aucun match pour le moment
            <?php endif; ?>
        </div>
    </div>

    <?php if ($total_matches > 0): ?>
        <div class="matches-container">
            <?php foreach ($matches as $index => $match): 
                // Déterminer la couleur selon le genre
                $avatar_color = '#667eea'; // bleu par défaut
                if (isset($match['genre'])) {
                    if ($match['genre'] == 'femme') {
                        $avatar_color = '#e667aa'; // rose pour femme
                    } elseif ($match['genre'] == 'autre') {
                        $avatar_color = '#9b59b6'; // violet pour autre
                    }
                }
                
                // Calculer l'âge si pas déjà fait
                if (!isset($match['age']) && !empty($match['date_naissance'])) {
                    $date_naissance = new DateTime($match['date_naissance']);
                    $today = new DateTime();
                    $match['age'] = $today->diff($date_naissance)->y;
                }
            ?>
                <div class="match-card" data-match-id="<?php echo $match['id']; ?>">
                    <?php if ($index < 3): ?>
                        <div class="match-badge">NOUVEAU</div>
                    <?php endif; ?>
                    
                    <div class="match-header">
                        <div class="match-avatar" style="background: <?php echo $avatar_color; ?>;">
                            <?php echo strtoupper(substr($match['prenom'], 0, 1) . substr($match['nom'], 0, 1)); ?>
                        </div>
                        <div class="match-name"><?php echo htmlspecialchars($match['prenom']); ?></div>
                        <div class="match-details">
                            <?php if (!empty($match['ville'])): ?>
                                <span>📍 <?php echo htmlspecialchars($match['ville']); ?></span>
                                <span>•</span>
                            <?php endif; ?>
                            <span>🎂 <?php echo $match['age'] ?? '?'; ?> ans</span>
                        </div>
                    </div>
                    
                    <div class="match-content">
                        <div class="match-info">
                            <div class="info-item">
                                <span class="info-label">Genre</span>
                                <span class="info-value"><?php echo htmlspecialchars(ucfirst($match['genre'] ?? 'Non spécifié')); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Compatibilité</span>
                                <span class="info-value"><?php echo rand(75, 98); ?>%</span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Intérêts</span>
                                <span class="info-value"><?php echo rand(3, 8); ?> communs</span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Distance</span>
                                <span class="info-value"><?php echo rand(1, 20); ?> km</span>
                            </div>
                        </div>
                        
                        <div class="match-actions">
                            <button class="match-btn chat-btn" onclick="startChat(<?php echo $match['id']; ?>, '<?php echo htmlspecialchars($match['prenom']); ?>')">
                                💬 Discuter
                            </button>
                            <button class="match-btn profile-btn" onclick="viewProfile(<?php echo $match['id']; ?>)">
                                👤 Profil
                            </button>
                        </div>
                        
                        <?php if (!empty($match['matched_at'])): ?>
                        <div class="match-time">
                            Matché <?php 
                            try {
                                $matched_date = new DateTime($match['matched_at']);
                                $now = new DateTime();
                                $interval = $now->diff($matched_date);
                                
                                if ($interval->d == 0) {
                                    echo "aujourd'hui";
                                } elseif ($interval->d == 1) {
                                    echo "hier";
                                } else {
                                    echo "il y a " . $interval->d . " jours";
                                }
                            } catch(Exception $e) {
                                echo "récemment";
                            }
                            ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="no-matches">
            <h3>✨ Pas encore de matchs</h3>
            <p>Continuez à swiper pour rencontrer des personnes compatibles !</p>
            <p>Quand quelqu'un vous like en retour, il apparaîtra ici.</p>
            
            <div class="action-buttons">
                <a href="swipe.php" class="action-btn primary-btn">
                    💖 Continuer à Swiper
                </a>
                <a href="profile.php" class="action-btn secondary-btn">
                    ✏️ Améliorer mon profil
                </a>
            </div>
            
            <div style="margin-top: 3rem; color: rgba(255, 255, 255, 0.7);">
                <h4>💡 Conseils pour plus de matchs :</h4>
                <ul style="text-align: left; display: inline-block; margin-top: 1rem;">
                    <li>Ajoutez une photo de profil claire</li>
                    <li>Complétez votre bio avec vos passions</li>
                    <li>Soyez actif(ve) sur la plateforme</li>
                    <li>Swipez régulièrement pour plus de chances</li>
                </ul>
            </div>
            
            <!-- Debug link -->
            <div style="margin-top: 2rem;">
                <a href="matches.php?debug=1" style="color: rgba(255,255,255,0.5); font-size: 0.9rem;">
                    🔍 Mode debug
                </a>
            </div>
        </div>
    <?php endif; ?>

    <script>
        // Vérifier si on vient d'avoir un nouveau match
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.has('new_match')) {
            showMatchAnimation();
        }

        function showMatchAnimation() {
            const animation = document.createElement('div');
            animation.className = 'match-animation';
            animation.style.display = 'flex';
            animation.innerHTML = `
                <div class="match-popup">
                    <h2>🎉 MATCH !</h2>
                    <p style="font-size: 1.2rem; color: #666; margin-bottom: 2rem;">
                        Vous avez un nouveau match !
                    </p>
                    <button onclick="this.closest('.match-animation').remove()" 
                            style="padding: 1rem 2rem; background: #667eea; color: white; 
                                   border: none; border-radius: 10px; font-size: 1.1rem; 
                                   cursor: pointer;">
                        Super ! 😍
                    </button>
                </div>
            `;
            document.body.appendChild(animation);
            
            // Supprimer après 5 secondes
            setTimeout(() => {
                if (animation.parentNode) {
                    animation.remove();
                }
            }, 5000);
        }

        function startChat(userId, userName) {
            alert(`Démarrer une conversation avec ${userName} (ID: ${userId})`);
            // window.location.href = `chat.php?with=${userId}`;
        }

        function viewProfile(userId) {
            alert(`Voir le profil de l'utilisateur #${userId}`);
            // window.location.href = `view_profile.php?id=${userId}`;
        }

        // Animation au survol des cartes
        document.querySelectorAll('.match-card').forEach(card => {
            card.addEventListener('mouseenter', () => {
                card.style.transform = 'translateY(-10px)';
            });
            
            card.addEventListener('mouseleave', () => {
                card.style.transform = 'translateY(0)';
            });
        });
    </script>
</body>
</html>