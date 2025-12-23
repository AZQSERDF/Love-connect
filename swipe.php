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
    
    // Récupérer un profil à swiper (pas encore swipé par cet utilisateur)
    $sql = "SELECT u.* FROM utilisateurs u 
            WHERE u.id != :user_id 
            AND u.id NOT IN (
                SELECT profile_id FROM swipes 
                WHERE user_id = :user_id
            )
            ORDER BY RAND() 
            LIMIT 1";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute([':user_id' => $_SESSION['user_id']]);
    $profile = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Si plus de profils à swiper
    if (!$profile) {
        $no_more_profiles = true;
    } else {
        // Calculer l'âge
        $date_naissance = new DateTime($profile['date_naissance']);
        $today = new DateTime();
        $age = $today->diff($date_naissance)->y;
        
        // Récupérer le nombre de matchs pour l'afficher
        $sql_matches = "SELECT COUNT(*) as match_count FROM (
                        SELECT DISTINCT u.id FROM utilisateurs u
                        INNER JOIN swipes s1 ON u.id = s1.profile_id
                        INNER JOIN swipes s2 ON s1.user_id = s2.profile_id
                        WHERE s1.user_id = :user_id 
                        AND s2.user_id = u.id
                        AND s1.action = 'like'
                        AND s2.action = 'like'
                    ) as matches";
        
        $stmt_matches = $conn->prepare($sql_matches);
        $stmt_matches->execute([':user_id' => $_SESSION['user_id']]);
        $match_count = $stmt_matches->fetchColumn();
    }
    
} catch(PDOException $e) {
    die("Erreur : " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Swipe - LoveConnect</title>
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
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        header {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
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
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .match-count {
            background: #ff6b6b;
            color: white;
            padding: 0.2rem 0.6rem;
            border-radius: 10px;
            font-size: 0.8rem;
            font-weight: bold;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }

        .container {
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 2rem;
            position: relative;
        }

        /* Carte de profil */
        .profile-card {
            width: 400px;
            height: 600px;
            background: white;
            border-radius: 25px;
            overflow: hidden;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            position: relative;
            transition: transform 0.3s ease;
            cursor: grab;
        }

        .profile-card:active {
            cursor: grabbing;
        }

        .profile-card:hover {
            transform: translateY(-5px);
        }

        .profile-image {
            width: 100%;
            height: 70%;
            background: <?php echo isset($profile) ? ($profile['genre'] == 'homme' ? '#667eea' : ($profile['genre'] == 'femme' ? '#e667aa' : '#9b59b6')) : '#ccc'; ?>;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 5rem;
            font-weight: bold;
            position: relative;
            overflow: hidden;
        }

        .profile-info {
            padding: 2rem;
        }

        .profile-name {
            font-size: 2rem;
            font-weight: bold;
            color: #333;
            margin-bottom: 0.5rem;
        }

        .profile-details {
            color: #666;
            font-size: 1.1rem;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .profile-bio {
            color: #777;
            line-height: 1.6;
            margin-top: 1rem;
            font-size: 0.95rem;
            max-height: 100px;
            overflow-y: auto;
        }

        /* Boutons d'action */
        .action-buttons {
            display: flex;
            justify-content: center;
            gap: 3rem;
            margin-top: 2rem;
            position: relative;
            z-index: 10;
        }

        .action-btn {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            border: none;
            font-size: 2.5rem;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);
        }

        .dislike-btn {
            background: #ff6b6b;
            color: white;
        }

        .dislike-btn:hover {
            background: #ff4c4c;
            transform: scale(1.1);
            box-shadow: 0 12px 25px rgba(255, 107, 107, 0.4);
        }

        .like-btn {
            background: #51cf66;
            color: white;
        }

        .like-btn:hover {
            background: #38b24a;
            transform: scale(1.1);
            box-shadow: 0 12px 25px rgba(81, 207, 102, 0.4);
        }

        /* Animation pour les swipes */
        @keyframes swipeRight {
            0% { transform: translateX(0) rotate(0); opacity: 1; }
            100% { transform: translateX(500px) rotate(20deg); opacity: 0; }
        }

        @keyframes swipeLeft {
            0% { transform: translateX(0) rotate(0); opacity: 1; }
            100% { transform: translateX(-500px) rotate(-20deg); opacity: 0; }
        }

        @keyframes heartPop {
            0% { transform: scale(1); opacity: 1; }
            50% { transform: scale(1.5); opacity: 1; }
            100% { transform: scale(2); opacity: 0; }
        }

        @keyframes crossPop {
            0% { transform: scale(1); opacity: 1; }
            50% { transform: scale(1.5); opacity: 1; }
            100% { transform: scale(2); opacity: 0; }
        }

        @keyframes matchCelebration {
            0% { transform: scale(0); opacity: 0; }
            50% { transform: scale(1.2); opacity: 1; }
            100% { transform: scale(1); opacity: 1; }
        }

        .swipe-right {
            animation: swipeRight 0.5s ease forwards;
        }

        .swipe-left {
            animation: swipeLeft 0.5s ease forwards;
        }

        .heart-animation {
            position: absolute;
            font-size: 8rem;
            color: #51cf66;
            z-index: 100;
            pointer-events: none;
            animation: heartPop 1s ease forwards;
        }

        .cross-animation {
            position: absolute;
            font-size: 8rem;
            color: #ff6b6b;
            z-index: 100;
            pointer-events: none;
            animation: crossPop 1s ease forwards;
        }

        /* Message quand plus de profils */
        .no-profiles {
            text-align: center;
            color: white;
            padding: 3rem;
            max-width: 500px;
        }

        .no-profiles h2 {
            font-size: 2.5rem;
            margin-bottom: 1.5rem;
        }

        .no-profiles p {
            font-size: 1.2rem;
            margin-bottom: 2rem;
            opacity: 0.9;
        }

        .action-link {
            background: white;
            color: #667eea;
            border: none;
            padding: 1rem 3rem;
            border-radius: 50px;
            font-size: 1.2rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            margin: 0.5rem;
        }

        .action-link:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(255, 255, 255, 0.3);
        }

        /* Compteur */
        .swipe-counter {
            position: absolute;
            top: 20px;
            right: 20px;
            background: rgba(0, 0, 0, 0.7);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.9rem;
        }

        /* Match popup */
        .match-popup {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.85);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 1000;
            animation: fadeIn 0.3s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .match-content {
            background: white;
            border-radius: 25px;
            padding: 3rem;
            text-align: center;
            max-width: 500px;
            animation: matchCelebration 0.5s ease;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
        }

        .match-icon {
            font-size: 4rem;
            margin-bottom: 1.5rem;
            animation: heartPop 2s infinite;
        }

        .match-title {
            color: #51cf66;
            font-size: 2.5rem;
            margin-bottom: 1rem;
        }

        .match-message {
            color: #666;
            font-size: 1.2rem;
            margin-bottom: 2rem;
            line-height: 1.6;
        }

        .match-profile {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 1rem;
            margin-bottom: 2rem;
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 15px;
        }

        .match-profile-avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: <?php echo isset($profile) ? ($profile['genre'] == 'homme' ? '#667eea' : ($profile['genre'] == 'femme' ? '#e667aa' : '#9b59b6')) : '#ccc'; ?>;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5rem;
            font-weight: bold;
        }

        .match-profile-name {
            font-size: 1.5rem;
            font-weight: bold;
            color: #333;
        }

        .match-buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
        }

        .match-btn {
            padding: 1rem 2rem;
            border: none;
            border-radius: 10px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .continue-btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .continue-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.4);
        }

        .matches-btn {
            background: white;
            color: #667eea;
            border: 2px solid #667eea;
        }

        .matches-btn:hover {
            background: #667eea;
            color: white;
            transform: translateY(-3px);
        }

        /* Responsive */
        @media (max-width: 768px) {
            .profile-card {
                width: 90vw;
                height: 70vh;
                max-width: 350px;
                max-height: 500px;
            }
            
            .action-buttons {
                gap: 2rem;
            }
            
            .action-btn {
                width: 70px;
                height: 70px;
                font-size: 2rem;
            }
            
            .match-content {
                margin: 1rem;
                padding: 2rem;
            }
        }

        @media (max-width: 480px) {
            .profile-card {
                height: 65vh;
            }
            
            .action-buttons {
                gap: 1.5rem;
            }
            
            .action-btn {
                width: 60px;
                height: 60px;
                font-size: 1.8rem;
            }
            
            header {
                padding: 1rem;
                flex-direction: column;
                gap: 1rem;
            }
            
            nav ul {
                gap: 1rem;
            }
            
            .match-buttons {
                flex-direction: column;
            }
            
            .match-btn {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <div id="matchPopup" class="match-popup">
        <div class="match-content">
            <div class="match-icon">💕</div>
            <h2 class="match-title">C'EST UN MATCH !</h2>
            <p class="match-message" id="matchMessage"></p>
            <div class="match-profile">
                <div class="match-profile-avatar" id="matchAvatar"></div>
                <div class="match-profile-name" id="matchName"></div>
            </div>
            <div class="match-buttons">
                <button class="match-btn continue-btn" onclick="continueSwiping()">
                    💖 Continuer à swiper
                </button>
                <button class="match-btn matches-btn" onclick="viewMatches()">
                    💞 Voir mes matchs
                </button>
            </div>
        </div>
    </div>

    <header>
        <h1>💕 LoveConnect</h1>
        <nav>
            <ul>
                <li><a href="index.php">🏠 Accueil</a></li>
                <li><a href="profile.php">👤 Profil</a></li>
                <li><a href="matches.php">💞 Matchs</a></li>
                <li><a href="chat.html">💬 Chat</a></li>
            </ul>
        </nav>
        <div class="user-info">
            👋 <?php echo htmlspecialchars($_SESSION['user_prenom'] ?? 'Utilisateur'); ?>
            <?php if (isset($match_count) && $match_count > 0): ?>
                <span class="match-count">💞 <?php echo $match_count; ?></span>
            <?php endif; ?>
        </div>
    </header>

    <div class="container">
        <?php if (isset($no_more_profiles) && $no_more_profiles): ?>
            <!-- Plus de profils à swiper -->
            <div class="no-profiles">
                <h2>🎉 Félicitations !</h2>
                <p>Vous avez swipé tous les profils disponibles pour le moment.</p>
                <p>Revenez plus tard pour découvrir de nouvelles personnes !</p>
                <div>
                    <a href="index.php" class="action-link">🏠 Accueil</a>
                    <a href="matches.php" class="action-link">💞 Voir mes matchs</a>
                    <a href="profile.php" class="action-link">✏️ Modifier mon profil</a>
                </div>
            </div>
        <?php else: ?>
            <!-- Carte de profil -->
            <div class="profile-card" id="profileCard">
                <div class="profile-image" id="profileImage">
                    <?php 
                    if (isset($profile)) {
                        echo strtoupper(substr($profile['prenom'], 0, 1) . substr($profile['nom'], 0, 1));
                    }
                    ?>
                </div>
                <div class="profile-info">
                    <div class="profile-name">
                        <?php echo htmlspecialchars($profile['prenom'] ?? ''); ?>, <?php echo $age ?? ''; ?>
                    </div>
                    <div class="profile-details">
                        📍 <?php echo htmlspecialchars($profile['ville'] ?? ''); ?>
                    </div>
                    <div class="profile-details">
                        👤 <?php echo htmlspecialchars(ucfirst($profile['genre'] ?? '')); ?>
                    </div>
                    <div class="profile-bio" id="profileBio">
                        <?php 
                        if (!empty($profile['bio'])) {
                            echo htmlspecialchars($profile['bio']);
                        } else {
                            echo "J'aime rencontrer de nouvelles personnes et partager de bons moments. ";
                            echo "Passionné" . ($profile['genre'] == 'femme' ? 'e' : '') . " par la vie !";
                        }
                        ?>
                    </div>
                </div>
                
                <!-- Compteur de swipe -->
                <div class="swipe-counter" id="swipeCounter">
                    🔥 Nouveau profil
                </div>
            </div>

            <!-- Boutons d'action -->
            <div class="action-buttons">
                <button class="action-btn dislike-btn" id="dislikeBtn" data-profile-id="<?php echo $profile['id'] ?? ''; ?>">
                    ❌
                </button>
                <button class="action-btn like-btn" id="likeBtn" data-profile-id="<?php echo $profile['id'] ?? ''; ?>">
                    ❤️
                </button>
            </div>
        <?php endif; ?>
    </div>

    <script>
        // Récupérer les éléments
        const profileCard = document.getElementById('profileCard');
        const likeBtn = document.getElementById('likeBtn');
        const dislikeBtn = document.getElementById('dislikeBtn');
        const swipeCounter = document.getElementById('swipeCounter');
        const matchPopup = document.getElementById('matchPopup');
        const matchMessage = document.getElementById('matchMessage');
        const matchAvatar = document.getElementById('matchAvatar');
        const matchName = document.getElementById('matchName');

        // Variables pour le drag & drop
        let isDragging = false;
        let startX, startY, currentX, currentY;
        let swipeDirection = null;

        // Compteur de temps sur le profil
        let timeOnProfile = 0;
        const timer = setInterval(() => {
            timeOnProfile++;
            if (timeOnProfile === 3) {
                swipeCounter.textContent = "💡 Conseil: Lisez la bio !";
            } else if (timeOnProfile === 10) {
                swipeCounter.textContent = "⏰ Prenez votre temps";
            }
        }, 1000);

        // Fonction pour créer une animation
        function createAnimation(type, x, y) {
            const animation = document.createElement('div');
            animation.className = type === 'like' ? 'heart-animation' : 'cross-animation';
            animation.textContent = type === 'like' ? '❤️' : '❌';
            animation.style.left = x + 'px';
            animation.style.top = y + 'px';
            document.body.appendChild(animation);
            
            // Supprimer après l'animation
            setTimeout(() => {
                animation.remove();
            }, 1000);
        }

        // Fonction pour afficher le popup de match
        function showMatchPopup(profileName, profileInitials) {
            matchMessage.textContent = `Félicitations ! Vous avez matché avec ${profileName} !`;
            matchAvatar.textContent = profileInitials;
            matchName.textContent = profileName;
            matchPopup.style.display = 'flex';
            
            // Jouer un son de match (optionnel)
            // new Audio('match_sound.mp3').play();
        }

        // Fonction pour swiper
        function swipeProfile(action, profileId) {
            // Animation
            const rect = profileCard.getBoundingClientRect();
            const centerX = rect.left + rect.width / 2;
            const centerY = rect.top + rect.height / 2;
            
            createAnimation(action, centerX - 40, centerY - 40);
            
            // Animation de la carte
            profileCard.classList.add(action === 'like' ? 'swipe-right' : 'swipe-left');
            
            // Envoyer l'action au serveur
            fetch('process_swipe.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=${action}&profile_id=${profileId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    if (data.match) {
                        // Afficher le popup de match
                        setTimeout(() => {
                            showMatchPopup(
                                data.match_data?.profile_name || 'Quelqu\'un',
                                document.querySelector('.profile-image').textContent.trim()
                            );
                        }, 500);
                    } else {
                        // Pas de match, juste recharger
                        setTimeout(() => {
                            window.location.reload();
                        }, 500);
                    }
                } else {
                    alert('Erreur: ' + data.message);
                    // Recharger quand même en cas d'erreur
                    setTimeout(() => {
                        window.location.reload();
                    }, 500);
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                // Recharger quand même en cas d'erreur
                setTimeout(() => {
                    window.location.reload();
                }, 500);
            });
            
            // Arrêter le timer
            clearInterval(timer);
        }

        // Fonctions pour le popup de match
        function continueSwiping() {
            matchPopup.style.display = 'none';
            window.location.reload();
        }

        function viewMatches() {
            window.location.href = 'matches.php';
        }

        // Événements pour les boutons
        if (likeBtn) {
            likeBtn.addEventListener('click', function() {
                const profileId = this.getAttribute('data-profile-id');
                swipeProfile('like', profileId);
            });
        }

        if (dislikeBtn) {
            dislikeBtn.addEventListener('click', function() {
                const profileId = this.getAttribute('data-profile-id');
                swipeProfile('dislike', profileId);
            });
        }

        // Swipe avec le clavier
        document.addEventListener('keydown', (e) => {
            if (!profileCard || matchPopup.style.display === 'flex') return;
            
            const profileId = likeBtn ? likeBtn.getAttribute('data-profile-id') : null;
            
            if (e.key === 'ArrowLeft' || e.key === 'q' || e.key === 'Q') {
                // Swipe gauche (dislike)
                e.preventDefault();
                if (dislikeBtn) dislikeBtn.click();
            } else if (e.key === 'ArrowRight' || e.key === 'd' || e.key === 'D') {
                // Swipe droite (like)
                e.preventDefault();
                if (likeBtn) likeBtn.click();
            } else if (e.key === 'Escape' && matchPopup.style.display === 'flex') {
                // Fermer le popup avec Escape
                continueSwiping();
            }
        });

        // Drag & Drop pour mobile/desktop
        if (profileCard) {
            profileCard.addEventListener('mousedown', startDrag);
            profileCard.addEventListener('touchstart', startDragTouch);
            
            document.addEventListener('mousemove', drag);
            document.addEventListener('touchmove', dragTouch);
            
            document.addEventListener('mouseup', endDrag);
            document.addEventListener('touchend', endDrag);
        }

        function startDrag(e) {
            isDragging = true;
            startX = e.clientX;
            startY = e.clientY;
            profileCard.style.cursor = 'grabbing';
        }

        function startDragTouch(e) {
            isDragging = true;
            startX = e.touches[0].clientX;
            startY = e.touches[0].clientY;
        }

        function drag(e) {
            if (!isDragging) return;
            e.preventDefault();
            
            currentX = e.clientX;
            currentY = e.clientY;
            
            const deltaX = currentX - startX;
            const deltaY = currentY - startY;
            
            // Appliquer la transformation
            profileCard.style.transform = `translate(${deltaX}px, ${deltaY}px) rotate(${deltaX * 0.1}deg)`;
            
            // Changer l'opacité selon la distance
            const distance = Math.abs(deltaX);
            const opacity = Math.max(0.5, 1 - distance / 300);
            profileCard.style.opacity = opacity;
            
            // Déterminer la direction
            if (deltaX > 50) {
                swipeDirection = 'like';
                profileCard.style.boxShadow = '0 10px 30px rgba(81, 207, 102, 0.3)';
            } else if (deltaX < -50) {
                swipeDirection = 'dislike';
                profileCard.style.boxShadow = '0 10px 30px rgba(255, 107, 107, 0.3)';
            } else {
                swipeDirection = null;
                profileCard.style.boxShadow = '0 20px 60px rgba(0, 0, 0, 0.3)';
            }
        }

        function dragTouch(e) {
            if (!isDragging) return;
            e.preventDefault();
            
            currentX = e.touches[0].clientX;
            currentY = e.touches[0].clientY;
            
            const deltaX = currentX - startX;
            const deltaY = currentY - startY;
            
            profileCard.style.transform = `translate(${deltaX}px, ${deltaY}px) rotate(${deltaX * 0.1}deg)`;
            
            const distance = Math.abs(deltaX);
            const opacity = Math.max(0.5, 1 - distance / 300);
            profileCard.style.opacity = opacity;
            
            if (deltaX > 50) {
                swipeDirection = 'like';
                profileCard.style.boxShadow = '0 10px 30px rgba(81, 207, 102, 0.3)';
            } else if (deltaX < -50) {
                swipeDirection = 'dislike';
                profileCard.style.boxShadow = '0 10px 30px rgba(255, 107, 107, 0.3)';
            } else {
                swipeDirection = null;
                profileCard.style.boxShadow = '0 20px 60px rgba(0, 0, 0, 0.3)';
            }
        }

        function endDrag() {
            if (!isDragging) return;
            isDragging = false;
            profileCard.style.cursor = 'grab';
            
            const deltaX = currentX - startX;
            
            if (swipeDirection && Math.abs(deltaX) > 100) {
                // Swipe suffisamment long, déclencher l'action
                const profileId = likeBtn ? likeBtn.getAttribute('data-profile-id') : null;
                if (profileId) {
                    swipeProfile(swipeDirection, profileId);
                }
            } else {
                // Annuler le swipe, revenir à la position initiale
                profileCard.style.transition = 'transform 0.3s ease, opacity 0.3s ease';
                profileCard.style.transform = 'translate(0, 0) rotate(0)';
                profileCard.style.opacity = '1';
                profileCard.style.boxShadow = '0 20px 60px rgba(0, 0, 0, 0.3)';
                
                setTimeout(() => {
                    profileCard.style.transition = '';
                }, 300);
            }
            
            swipeDirection = null;
        }

        // Instructions au chargement
        setTimeout(() => {
            if (swipeCounter) {
                swipeCounter.textContent = "🎮 Tips: Utilisez Q (passer) et D (like)";
            }
        }, 2000);

        // Mettre à jour le compteur de matchs périodiquement
        function updateMatchCount() {
            fetch('check_new_matches.php')
                .then(response => response.json())
                .then(data => {
                    const matchCountElement = document.querySelector('.match-count');
                    if (data.total_matches > 0) {
                        if (!matchCountElement) {
                            const userInfo = document.querySelector('.user-info');
                            if (userInfo) {
                                const countSpan = document.createElement('span');
                                countSpan.className = 'match-count';
                                countSpan.textContent = `💞 ${data.total_matches}`;
                                userInfo.appendChild(countSpan);
                            }
                        } else {
                            matchCountElement.textContent = `💞 ${data.total_matches}`;
                        }
                    }
                })
                .catch(error => console.error('Erreur mise à jour matchs:', error));
        }

        // Mettre à jour toutes les 30 secondes
        setInterval(updateMatchCount, 30000);
    </script>
</body>
</html>