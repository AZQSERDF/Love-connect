<?php
// Vérifier si l'utilisateur est connecté (pour affichage conditionnel)
session_start();
$isLoggedIn = isset($_SESSION['user_id']);
$userName = $isLoggedIn ? $_SESSION['user_prenom'] : '';
$userRole = $isLoggedIn ? ($_SESSION['user_role'] ?? 'user') : 'user';
?>
<!DOCTYPE html>
<html lang="fr">
    <head>
        <meta charset="UTF-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <title>LoveConnect - Trouvez l'amour</title>
        <style>
            * {
                margin: 0;
                padding: 0;
                box-sizing: border-box;
            }

            body {
                font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
                min-height: 100vh;
                position: relative;
                overflow-x: hidden;
            }

            .background-overlay {
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                /* Alternative si l'image n'existe pas */
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                z-index: -1;
            }

            /* Si vous avez l'image couple.jpg, utilisez ceci à la place : */
            /* 
            .background-overlay {
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: url("assets/image/couple.jpg") center/cover no-repeat;
                filter: brightness(0.4);
                z-index: -1;
            }
            
            .background-overlay::after {
                content: "";
                position: absolute;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: linear-gradient(
                    135deg,
                    rgba(102, 126, 234, 0.8) 0%,
                    rgba(118, 75, 162, 0.8) 100%
                );
            }
            */

            /* Header amélioré */
            header {
                background: rgba(255, 255, 255, 0.1);
                backdrop-filter: blur(15px);
                padding: 1rem 2rem;
                box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
                position: sticky;
                top: 0;
                z-index: 100;
                animation: slideDown 0.5s ease;
            }

            @keyframes slideDown {
                from {
                    transform: translateY(-100%);
                    opacity: 0;
                }
                to {
                    transform: translateY(0);
                    opacity: 1;
                }
            }

            .header-content {
                max-width: 1400px;
                margin: 0 auto;
                display: flex;
                justify-content: space-between;
                align-items: center;
                flex-wrap: wrap;
                gap: 1rem;
            }

            .logo-container {
                display: flex;
                align-items: center;
                gap: 1rem;
            }

            header h1 {
                color: white;
                font-size: 2rem;
                display: flex;
                align-items: center;
                gap: 0.5rem;
                text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
            }

            /* Badge utilisateur connecté */
            .user-badge {
                background: rgba(255, 255, 255, 0.2);
                color: white;
                padding: 0.5rem 1rem;
                border-radius: 20px;
                font-size: 0.9rem;
                display: flex;
                align-items: center;
                gap: 0.5rem;
                backdrop-filter: blur(10px);
                border: 1px solid rgba(255, 255, 255, 0.1);
            }

            .user-badge .welcome-text {
                font-weight: 500;
            }

            nav ul {
                list-style: none;
                display: flex;
                gap: 0.5rem;
                align-items: center;
                flex-wrap: wrap;
            }

            nav a {
                color: white;
                text-decoration: none;
                font-weight: 500;
                transition: all 0.3s ease;
                padding: 0.7rem 1.3rem;
                border-radius: 10px;
                font-size: 0.95rem;
                display: flex;
                align-items: center;
                gap: 0.5rem;
                white-space: nowrap;
            }

            nav a:hover {
                background: rgba(255, 255, 255, 0.25);
                transform: translateY(-2px);
            }

            nav a.logout {
                background: rgba(220, 53, 69, 0.2);
            }

            nav a.logout:hover {
                background: rgba(220, 53, 69, 0.4);
            }

            nav a.profile {
                background: rgba(40, 167, 69, 0.2);
            }

            nav a.profile:hover {
                background: rgba(40, 167, 69, 0.4);
            }

            nav a.admin {
                background: linear-gradient(135deg, #ffd700 0%, #ffa500 100%);
                color: #000;
                font-weight: bold;
                position: relative;
                padding-right: 2.5rem;
            }

            nav a.admin:hover {
                background: linear-gradient(135deg, #ffed4e 0%, #ffb347 100%);
                transform: translateY(-2px);
                box-shadow: 0 5px 15px rgba(255, 215, 0, 0.3);
            }

            .admin-badge {
                position: absolute;
                right: 0.8rem;
                top: 50%;
                transform: translateY(-50%);
                font-size: 0.7rem;
                background: #000;
                color: #ffd700;
                border-radius: 50%;
                width: 18px;
                height: 18px;
                display: flex;
                align-items: center;
                justify-content: center;
                font-weight: bold;
            }

            /* Hero section améliorée */
            .hero-section {
                min-height: calc(100vh - 80px);
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 2rem;
                text-align: center;
                position: relative;
            }

            .hero-content {
                max-width: 800px;
                animation: fadeInUp 1s ease;
                position: relative;
                z-index: 1;
            }

            @keyframes fadeInUp {
                from {
                    opacity: 0;
                    transform: translateY(50px);
                }
                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }

            .welcome-message {
                background: rgba(255, 255, 255, 0.1);
                backdrop-filter: blur(10px);
                padding: 1rem 2rem;
                border-radius: 20px;
                margin-bottom: 2rem;
                border: 1px solid rgba(255, 255, 255, 0.2);
                animation: pulse 2s infinite;
            }

            @keyframes pulse {
                0%, 100% { transform: scale(1); }
                50% { transform: scale(1.02); }
            }

            .hero-content h2 {
                color: white;
                font-size: 3.5rem;
                margin-bottom: 1.5rem;
                text-shadow: 3px 3px 6px rgba(0, 0, 0, 0.3);
                line-height: 1.2;
                background: linear-gradient(to right, #fff, #f8f9fa);
                -webkit-background-clip: text;
                -webkit-text-fill-color: transparent;
            }

            .hero-content p {
                color: rgba(255, 255, 255, 0.95);
                font-size: 1.4rem;
                margin-bottom: 3rem;
                text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
                line-height: 1.6;
            }

            .cta-buttons {
                display: flex;
                gap: 1.5rem;
                justify-content: center;
                flex-wrap: wrap;
            }

            .btn {
                padding: 1.2rem 3rem;
                border: none;
                border-radius: 50px;
                font-size: 1.1rem;
                font-weight: 600;
                cursor: pointer;
                transition: all 0.3s ease;
                text-decoration: none;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                gap: 0.5rem;
                box-shadow: 0 8px 20px rgba(0, 0, 0, 0.3);
                min-width: 200px;
            }

            .btn-primary {
                background: white;
                color: #667eea;
                border: 2px solid transparent;
            }

            .btn-primary:hover {
                transform: translateY(-5px) scale(1.05);
                box-shadow: 0 15px 35px rgba(255, 255, 255, 0.4);
                background: #667eea;
                color: white;
                border-color: white;
            }

            .btn-secondary {
                background: rgba(255, 255, 255, 0.2);
                color: white;
                border: 2px solid white;
                backdrop-filter: blur(10px);
            }

            .btn-secondary:hover {
                background: white;
                color: #667eea;
                transform: translateY(-5px) scale(1.05);
            }

            .btn-profile {
                background: linear-gradient(135deg, #28a745, #20c997);
                color: white;
                border: 2px solid transparent;
            }

            .btn-profile:hover {
                transform: translateY(-5px) scale(1.05);
                box-shadow: 0 15px 35px rgba(40, 167, 69, 0.4);
                background: white;
                color: #28a745;
                border-color: #28a745;
            }

            /* Features section */
            .features {
                padding: 5rem 2rem;
                background: rgba(255, 255, 255, 0.05);
                backdrop-filter: blur(10px);
                position: relative;
                z-index: 1;
            }

            .section-title {
                text-align: center;
                color: white;
                font-size: 2.5rem;
                margin-bottom: 3rem;
                text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
            }

            .features-container {
                max-width: 1200px;
                margin: 0 auto;
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
                gap: 2rem;
            }

            .feature-card {
                background: rgba(255, 255, 255, 0.15);
                backdrop-filter: blur(15px);
                padding: 2.5rem;
                border-radius: 20px;
                text-align: center;
                transition: all 0.4s ease;
                border: 1px solid rgba(255, 255, 255, 0.2);
                animation: fadeIn 1s ease;
                animation-fill-mode: both;
                position: relative;
                overflow: hidden;
            }

            .feature-card::before {
                content: '';
                position: absolute;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: linear-gradient(135deg, rgba(102, 126, 234, 0.1), rgba(118, 75, 162, 0.1));
                z-index: -1;
            }

            .feature-card:nth-child(1) {
                animation-delay: 0.2s;
            }
            .feature-card:nth-child(2) {
                animation-delay: 0.4s;
            }
            .feature-card:nth-child(3) {
                animation-delay: 0.6s;
            }

            @keyframes fadeIn {
                from {
                    opacity: 0;
                    transform: translateY(30px);
                }
                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }

            .feature-card:hover {
                transform: translateY(-10px);
                background: rgba(255, 255, 255, 0.25);
                box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
            }

            .feature-icon {
                font-size: 4rem;
                margin-bottom: 1.5rem;
                filter: drop-shadow(2px 2px 4px rgba(0, 0, 0, 0.2));
                display: inline-block;
                animation: float 3s ease-in-out infinite;
            }

            @keyframes float {
                0%, 100% { transform: translateY(0); }
                50% { transform: translateY(-10px); }
            }

            .feature-card h3 {
                color: white;
                font-size: 1.5rem;
                margin-bottom: 1rem;
                text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.2);
            }

            .feature-card p {
                color: rgba(255, 255, 255, 0.9);
                line-height: 1.6;
                text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.2);
            }

            /* Stats section */
            .stats {
                padding: 4rem 2rem;
                text-align: center;
                position: relative;
                z-index: 1;
            }

            .stats-container {
                max-width: 1000px;
                margin: 0 auto;
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
                gap: 3rem;
            }

            .stat-item {
                animation: countUp 2s ease;
                background: rgba(255, 255, 255, 0.1);
                backdrop-filter: blur(10px);
                padding: 2rem;
                border-radius: 20px;
                border: 1px solid rgba(255, 255, 255, 0.2);
                transition: transform 0.3s ease;
            }

            .stat-item:hover {
                transform: scale(1.05);
                background: rgba(255, 255, 255, 0.15);
            }

            .stat-number {
                color: white;
                font-size: 3.5rem;
                font-weight: bold;
                text-shadow: 3px 3px 6px rgba(0, 0, 0, 0.3);
                margin-bottom: 0.5rem;
                display: block;
            }

            .stat-label {
                color: rgba(255, 255, 255, 0.9);
                font-size: 1.1rem;
                text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
                display: block;
            }

            /* Footer */
            .footer {
                background: rgba(0, 0, 0, 0.2);
                backdrop-filter: blur(10px);
                padding: 3rem 2rem;
                text-align: center;
                margin-top: 4rem;
                border-top: 1px solid rgba(255, 255, 255, 0.1);
            }

            .footer-content {
                max-width: 1200px;
                margin: 0 auto;
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
                gap: 2rem;
            }

            .footer-section h4 {
                color: white;
                font-size: 1.2rem;
                margin-bottom: 1rem;
                text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.3);
            }

            .footer-links {
                list-style: none;
            }

            .footer-links li {
                margin-bottom: 0.5rem;
            }

            .footer-links a {
                color: rgba(255, 255, 255, 0.8);
                text-decoration: none;
                transition: color 0.3s ease;
                display: flex;
                align-items: center;
                gap: 0.5rem;
                justify-content: center;
            }

            .footer-links a:hover {
                color: white;
                transform: translateX(5px);
            }

            .copyright {
                color: rgba(255, 255, 255, 0.6);
                font-size: 0.9rem;
                margin-top: 2rem;
                padding-top: 2rem;
                border-top: 1px solid rgba(255, 255, 255, 0.1);
            }

            /* Responsive Design */
            @media (max-width: 1024px) {
                .hero-content h2 {
                    font-size: 2.8rem;
                }
                
                .header-content {
                    flex-direction: column;
                    text-align: center;
                }
                
                nav ul {
                    justify-content: center;
                }
            }

            @media (max-width: 768px) {
                header {
                    padding: 1rem;
                }

                header h1 {
                    font-size: 1.5rem;
                }

                nav ul {
                    gap: 0.3rem;
                }

                nav a {
                    padding: 0.5rem 0.8rem;
                    font-size: 0.85rem;
                }

                .hero-content h2 {
                    font-size: 2.2rem;
                }

                .hero-content p {
                    font-size: 1.1rem;
                }

                .cta-buttons {
                    flex-direction: column;
                    align-items: center;
                }

                .btn {
                    width: 100%;
                    max-width: 300px;
                }

                .features-container {
                    grid-template-columns: 1fr;
                }

                .stats-container {
                    grid-template-columns: 1fr;
                    gap: 1.5rem;
                }
                
                .section-title {
                    font-size: 2rem;
                }
            }

            @media (max-width: 480px) {
                .header-content {
                    gap: 1rem;
                }

                nav ul {
                    flex-wrap: wrap;
                    justify-content: center;
                }
                
                .hero-content h2 {
                    font-size: 1.8rem;
                }
                
                .feature-card {
                    padding: 1.5rem;
                }
                
                .footer-content {
                    grid-template-columns: 1fr;
                }
            }
        </style>
    </head>
    <body>
        <div class="background-overlay"></div>

        <header>
            <div class="header-content">
                <div class="logo-container">
                    <h1>💕 LoveConnect</h1>
                    <?php if ($isLoggedIn): ?>
                    <div class="user-badge">
                        <span class="welcome-text">👋 Bienvenue, <?php echo htmlspecialchars($userName); ?> !</span>
                        <?php if ($userRole === 'admin'): ?>
                        <span style="
                            background: linear-gradient(135deg, #ffd700, #ffa500);
                            color: #000;
                            padding: 0.2rem 0.6rem;
                            border-radius: 10px;
                            font-size: 0.8rem;
                            font-weight: bold;
                            margin-left: 0.5rem;
                        ">👑 ADMIN</span>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
                <nav>
                    <ul>
                        <li><a href="index.php">🏠 Accueil</a></li>
                        
                        <?php if ($isLoggedIn): ?>
                            <!-- Menu quand l'utilisateur est connecté -->
                            <li><a href="profile.php" class="profile">👤 Mon Profil</a></li>
                            <li><a href="swipe.php">💖 Swipe</a></li>
                            <li><a href="matches.php">💞 Matchs</a></li>
                            <li><a href="chat.html">💬 Chat</a></li>
                            <li><a href="parametres.html">⚙️ Paramètres</a></li>
                            
                            <!-- LIEN ADMIN - SEULEMENT POUR LES ADMINISTRATEURS -->
                            <?php if ($userRole === 'admin'): ?>
                            <li>
                                <a href="admin.php" class="admin">
                                    👑 Admin
                                    <span class="admin-badge">A</span>
                                </a>
                            </li>
                            <?php endif; ?>
                            <!-- FIN DU LIEN ADMIN -->
                            
                            <li><a href="logout.php" class="logout">🚪 Déconnexion</a></li>
                        <?php else: ?>
                            <!-- Menu quand l'utilisateur n'est pas connecté -->
                            <li><a href="inscription.php">📝 Inscription</a></li>
                            <li><a href="login.php">🔐 Connexion</a></li>
                            <li><a href="video-template.html">🎬 Vidéo</a></li>
                            <li><a href="statistiques.html">📊 Statistiques</a></li>
                        <?php endif; ?>
                    </ul>
                </nav>
            </div>
        </header>

        <section class="hero-section">
            <div class="hero-content">
                <?php if ($isLoggedIn): ?>
                <div class="welcome-message">
                    <h3 style="color: white; margin-bottom: 0.5rem;">Content de vous revoir, <?php echo htmlspecialchars($userName); ?> !</h3>
                    <p style="color: rgba(255, 255, 255, 0.9); font-size: 1.1rem;">
                        Continuez votre recherche de l'âme sœur ✨
                    </p>
                </div>
                <?php endif; ?>
                
                <h2>Rencontrez l'Amour de Votre Vie</h2>
                <p>
                    Rejoignez des milliers de célibataires qui ont trouvé leur
                    âme sœur. Votre histoire commence ici.
                </p>
                <div class="cta-buttons">
                    <?php if ($isLoggedIn): ?>
                        <a href="swipe.php" class="btn btn-primary">
                            💖 Continuer à Swiper
                        </a>
                        <a href="profile.php" class="btn btn-profile">
                            👤 Voir Mon Profil
                        </a>
                        <a href="chat.html" class="btn btn-secondary">
                            💬 Voir Mes Messages
                        </a>
                    <?php else: ?>
                        <a href="inscription.php" class="btn btn-primary">
                            ✨ Commencer Gratuitement
                        </a>
                        <a href="login.php" class="btn btn-secondary">
                            🔐 Se Connecter
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </section>

        <section class="features">
            <h2 class="section-title">Pourquoi Choisir LoveConnect ?</h2>
            <div class="features-container">
                <div class="feature-card">
                    <div class="feature-icon">💖</div>
                    <h3>Matchs Intelligents</h3>
                    <p>
                        Notre algorithme avancé analyse vos centres d'intérêt pour trouver 
                        les personnes les plus compatibles avec vous.
                    </p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">💬</div>
                    <h3>Chat Sécurisé</h3>
                    <p>
                        Discutez en toute sécurité avec vos matchs grâce à notre système 
                        de messagerie chiffrée et modérée.
                    </p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">🔒</div>
                    <h3>Confidentialité Totale</h3>
                    <p>
                        Vos données sont protégées et votre vie privée est notre priorité 
                        absolue.
                    </p>
                </div>
            </div>
        </section>

        <section class="stats">
            <h2 class="section-title">LoveConnect en Chiffres</h2>
            <div class="stats-container">
                <div class="stat-item">
                    <span class="stat-number" data-count="10000">0</span>
                    <span class="stat-label">Membres Actifs</span>
                </div>
                <div class="stat-item">
                    <span class="stat-number" data-count="5000">0</span>
                    <span class="stat-label">Couples Formés</span>
                </div>
                <div class="stat-item">
                    <span class="stat-number" data-count="98">0</span>
                    <span class="stat-label">% de Satisfaction</span>
                </div>
                <div class="stat-item">
                    <span class="stat-number" data-count="120">0</span>
                    <span class="stat-label">Pays Desservis</span>
                </div>
            </div>
        </section>

        <footer class="footer">
            <div class="footer-content">
                <div class="footer-section">
                    <h4>LoveConnect</h4>
                    <p style="color: rgba(255, 255, 255, 0.8); line-height: 1.6;">
                        La plateforme de rencontres sérieuses qui met l'accent sur 
                        les vraies connexions et les relations durables.
                    </p>
                </div>
                
                <div class="footer-section">
                    <h4>Navigation</h4>
                    <ul class="footer-links">
                        <li><a href="index.php">🏠 Accueil</a></li>
                        <?php if ($isLoggedIn): ?>
                            <li><a href="profile.php">👤 Mon Profil</a></li>
                            <li><a href="swipe.php">💖 Swipe</a></li>
                            <li><a href="chat.html">💬 Chat</a></li>
                            <?php if ($userRole === 'admin'): ?>
                            <li><a href="admin.php" style="color: #ffd700;">👑 Admin</a></li>
                            <?php endif; ?>
                        <?php else: ?>
                            <li><a href="inscription.php">📝 Inscription</a></li>
                            <li><a href="login.php">🔐 Connexion</a></li>
                        <?php endif; ?>
                        <li><a href="video-template.html">🎬 Présentation</a></li>
                    </ul>
                </div>
                
                <div class="footer-section">
                    <h4>Légal</h4>
                    <ul class="footer-links">
                        <li><a href="#">📄 Conditions d'utilisation</a></li>
                        <li><a href="#">🔒 Politique de confidentialité</a></li>
                        <li><a href="#">⚖️ Mentions légales</a></li>
                        <li><a href="#">📧 Nous contacter</a></li>
                    </ul>
                </div>
            </div>
            
            <div class="copyright">
                &copy; 2024 LoveConnect. Tous droits réservés. ❤️
            </div>
        </footer>

        <script>
            // Animation des chiffres au défilement
            document.addEventListener('DOMContentLoaded', function() {
                const observerOptions = {
                    threshold: 0.5,
                    rootMargin: "0px"
                };

                const observer = new IntersectionObserver(function(entries) {
                    entries.forEach((entry) => {
                        if (entry.isIntersecting) {
                            const statNumber = entry.target.querySelector('.stat-number');
                            if (statNumber && !statNumber.classList.contains('animated')) {
                                animateValue(statNumber);
                                statNumber.classList.add('animated');
                            }
                        }
                    });
                }, observerOptions);

                document.querySelectorAll('.stat-item').forEach((stat) => {
                    observer.observe(stat);
                });

                function animateValue(element) {
                    const text = element.textContent;
                    const target = parseInt(element.getAttribute('data-count'));
                    const duration = 2000;
                    const increment = target / (duration / 16);
                    let current = 0;
                    const hasPercent = element.textContent.includes('%');

                    const timer = setInterval(() => {
                        current += increment;
                        if (current >= target) {
                            current = target;
                            clearInterval(timer);
                        }

                        let displayValue = Math.floor(current);
                        if (hasPercent) displayValue += '%';

                        element.textContent = displayValue;
                    }, 16);
                }

                // Effet parallaxe amélioré
                let lastScrollY = window.scrollY;
                
                window.addEventListener('scroll', () => {
                    const overlay = document.querySelector('.background-overlay');
                    const scrolled = window.scrollY;
                    const speed = 0.3;
                    
                    // Effet parallaxe
                    if (overlay) {
                        overlay.style.transform = `translateY(${scrolled * speed}px)`;
                    }
                    
                    // Effet de flou sur le header lors du défilement
                    const header = document.querySelector('header');
                    if (header) {
                        if (scrolled > 50) {
                            header.style.background = 'rgba(255, 255, 255, 0.15)';
                            header.style.backdropFilter = 'blur(20px)';
                        } else {
                            header.style.background = 'rgba(255, 255, 255, 0.1)';
                            header.style.backdropFilter = 'blur(15px)';
                        }
                    }
                    
                    lastScrollY = scrolled;
                });

                // Animation des cartes features au hover
                document.querySelectorAll('.feature-card').forEach(card => {
                    card.addEventListener('mouseenter', () => {
                        card.style.transform = 'translateY(-15px) scale(1.02)';
                    });
                    
                    card.addEventListener('mouseleave', () => {
                        card.style.transform = 'translateY(0) scale(1)';
                    });
                });

                // Vérification des liens existants
                const links = document.querySelectorAll('a[href]');
                links.forEach(link => {
                    link.addEventListener('click', function(e) {
                        const href = this.getAttribute('href');
                        
                        // Si c'est un lien externe ou mailto, on laisse faire
                        if (href.startsWith('http') || href.startsWith('mailto') || href.startsWith('#')) {
                            return;
                        }
                        
                        // Vérification si le fichier existe (pour éviter les 404)
                        // Cette partie est optionnelle, mais utile pour le débogage
                        fetch(href, { method: 'HEAD' })
                            .then(response => {
                                if (!response.ok && href !== 'logout.php') {
                                    console.warn(`⚠️ Le fichier ${href} pourrait ne pas exister`);
                                }
                            })
                            .catch(() => {
                                // Ignorer les erreurs de fetch
                            });
                    });
                });

                // Animation de chargement
                document.body.style.opacity = '0';
                document.body.style.transition = 'opacity 0.5s ease';
                
                setTimeout(() => {
                    document.body.style.opacity = '1';
                }, 100);

                // Effet de particules dynamiques (optionnel)
                createParticles();
                
                function createParticles() {
                    const overlay = document.querySelector('.background-overlay');
                    if (!overlay) return;
                    
                    for (let i = 0; i < 20; i++) {
                        const particle = document.createElement('div');
                        particle.style.position = 'absolute';
                        particle.style.width = Math.random() * 3 + 2 + 'px';
                        particle.style.height = particle.style.width;
                        particle.style.background = 'rgba(255, 255, 255, 0.2)';
                        particle.style.borderRadius = '50%';
                        particle.style.left = Math.random() * 100 + '%';
                        particle.style.top = Math.random() * 100 + '%';
                        particle.style.animation = `floatParticle ${Math.random() * 10 + 10}s linear infinite`;
                        
                        const style = document.createElement('style');
                        style.textContent = `
                            @keyframes floatParticle {
                                0% {
                                    transform: translate(0, 0) rotate(0deg);
                                    opacity: 0;
                                }
                                10% {
                                    opacity: 0.5;
                                }
                                90% {
                                    opacity: 0.5;
                                }
                                100% {
                                    transform: translate(${Math.random() * 100 - 50}px, -100vh) rotate(${Math.random() * 360}deg);
                                    opacity: 0;
                                }
                            }
                        `;
                        document.head.appendChild(style);
                        overlay.appendChild(particle);
                    }
                }
            });

            // Fonction pour vérifier si un utilisateur est connecté
            function checkLoginStatus() {
                return <?php echo $isLoggedIn ? 'true' : 'false'; ?>;
            }

            // Message de bienvenue dynamique
            window.addEventListener('load', function() {
                if (checkLoginStatus()) {
                    const welcomeMessages = [
                        "Prêt à trouver l'amour ?",
                        "Votre prochain match vous attend !",
                        "Des personnes compatibles vous attendent !",
                        "L'aventure continue !"
                    ];
                    
                    const randomMessage = welcomeMessages[Math.floor(Math.random() * welcomeMessages.length)];
                    
                    setTimeout(() => {
                        console.log(`💖 ${randomMessage}`);
                    }, 1000);
                }
            });
        </script>
    </body>
</html>