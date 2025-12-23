<?php
session_start();

// Si l'utilisateur est déjà connecté, rediriger vers le profil
if (isset($_SESSION['user_id'])) {
    // On redirigera vers profile.php quand il sera créé
    // Pour l'instant, on reste sur la page de connexion avec un message
    $deja_connecte = true;
}

$error = "";
$success = "";

// Traitement du formulaire
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        // Connexion à MySQL (port 3306)
        $conn = new PDO("mysql:host=localhost;port=3306;dbname=loveconnect_db", "root", "");
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $email = $_POST['email'];
        $password = $_POST['password'];
        
        // Chercher l'utilisateur
        $sql = "SELECT * FROM utilisateurs WHERE email = :email";
        $stmt = $conn->prepare($sql);
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user && password_verify($password, $user['mot_de_passe'])) {
            // Connexion réussie
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_prenom'] = $user['prenom'];
            $_SESSION['user_nom'] = $user['nom'];
            $_SESSION['user_ville'] = $user['ville'];
            $_SESSION['user_genre'] = $user['genre'];
            $_SESSION['user_photo'] = $user['photo_profil'] ?? 'default.png';
            
            // AJOUT ICI : Rôle utilisateur
            $_SESSION['user_role'] = $user['role'] ?? 'user'; // Si pas de colonne role, par défaut 'user'
            
            // Message de succès
            $success = "Connexion réussie ! Bienvenue " . htmlspecialchars($user['prenom']) . " !";
            
            // Pour l'instant, on reste sur la page avec un message
            // Plus tard, on redirigera vers profile.php
            // header("Location: profile.php");
            // exit();
            
        } else {
            $error = "Email ou mot de passe incorrect";
        }
        
    } catch(PDOException $e) {
        $error = "Erreur de connexion à la base de données : " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
    <head>
        <meta charset="UTF-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <title>Connexion - LoveConnect</title>
        <style>
            * {
                margin: 0;
                padding: 0;
                box-sizing: border-box;
            }

            body {
                font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                min-height: 100vh;
                display: flex;
                flex-direction: column;
            }

            header {
                background: rgba(255, 255, 255, 0.1);
                backdrop-filter: blur(10px);
                padding: 1rem 2rem;
                box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            }

            header h1 {
                color: white;
                font-size: 1.8rem;
                margin-bottom: 0.5rem;
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

            .container {
                flex: 1;
                display: flex;
                justify-content: center;
                align-items: center;
                padding: 2rem;
            }

            .login-card {
                background: white;
                border-radius: 20px;
                padding: 3rem;
                box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
                width: 100%;
                max-width: 500px;
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

            .login-card h2 {
                color: #333;
                margin-bottom: 0.5rem;
                font-size: 2rem;
            }

            .subtitle {
                color: #666;
                margin-bottom: 2rem;
                font-size: 0.95rem;
            }

            .form-group {
                margin-bottom: 1.5rem;
            }

            label {
                display: block;
                color: #333;
                font-weight: 600;
                margin-bottom: 0.5rem;
                font-size: 0.9rem;
            }

            input {
                width: 100%;
                padding: 0.9rem;
                border: 2px solid #e0e0e0;
                border-radius: 10px;
                font-size: 1rem;
                transition: all 0.3s ease;
                background: #f8f9fa;
            }

            input:focus {
                outline: none;
                border-color: #667eea;
                background: white;
                box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
            }

            button {
                width: 100%;
                padding: 1rem;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: white;
                border: none;
                border-radius: 10px;
                font-size: 1.1rem;
                font-weight: 600;
                cursor: pointer;
                transition: all 0.3s ease;
                margin-top: 1rem;
            }

            button:hover {
                transform: translateY(-2px);
                box-shadow: 0 10px 25px rgba(102, 126, 234, 0.4);
            }

            button:active {
                transform: translateY(0);
            }

            .forgot-password {
                text-align: right;
                margin-top: 0.5rem;
            }

            .forgot-password a {
                color: #667eea;
                text-decoration: none;
                font-size: 0.9rem;
                transition: color 0.3s ease;
            }

            .forgot-password a:hover {
                color: #764ba2;
            }

            .divider {
                text-align: center;
                margin: 2rem 0;
                position: relative;
            }

            .divider::before {
                content: "";
                position: absolute;
                left: 0;
                top: 50%;
                width: 100%;
                height: 1px;
                background: #e0e0e0;
            }

            .divider span {
                background: white;
                padding: 0 1rem;
                position: relative;
                color: #999;
                font-size: 0.9rem;
            }

            .signup-link {
                text-align: center;
                color: #666;
                font-size: 0.95rem;
            }

            .signup-link a {
                color: #667eea;
                text-decoration: none;
                font-weight: 600;
            }

            .signup-link a:hover {
                text-decoration: underline;
            }

            /* Messages */
            .error-message {
                background: #f8d7da;
                color: #721c24;
                padding: 1rem;
                border-radius: 10px;
                margin-bottom: 1.5rem;
                border: 2px solid #f5c6cb;
                text-align: center;
                font-weight: 500;
            }

            .success-message {
                background: #d4edda;
                color: #155724;
                padding: 1rem;
                border-radius: 10px;
                margin-bottom: 1.5rem;
                border: 2px solid #c3e6cb;
                text-align: center;
                font-weight: 500;
            }
            
            .info-message {
                background: #d1ecf1;
                color: #0c5460;
                padding: 1rem;
                border-radius: 10px;
                margin-bottom: 1.5rem;
                border: 2px solid #bee5eb;
                text-align: center;
                font-weight: 500;
            }
            
            .session-info {
                background: #f8f9fa;
                border: 2px solid #667eea;
                border-radius: 10px;
                padding: 1.5rem;
                margin-bottom: 1.5rem;
            }
            
            .session-info h3 {
                color: #667eea;
                margin-bottom: 0.5rem;
            }
            
            .session-info p {
                margin: 0.3rem 0;
                color: #555;
            }
            
            .action-buttons {
                display: flex;
                gap: 1rem;
                margin-top: 1rem;
            }
            
            .action-buttons a {
                flex: 1;
                text-align: center;
                padding: 0.8rem;
                background: #667eea;
                color: white;
                text-decoration: none;
                border-radius: 8px;
                transition: all 0.3s ease;
            }
            
            .action-buttons a:hover {
                background: #764ba2;
                transform: translateY(-2px);
            }

            @media (max-width: 600px) {
                .login-card {
                    padding: 2rem;
                }

                header {
                    padding: 1rem;
                }

                nav ul {
                    gap: 1rem;
                }
                
                .action-buttons {
                    flex-direction: column;
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
                    <li><a href="inscription.php">Inscription</a></li>
                </ul>
            </nav>
        </header>

        <div class="container">
            <div class="login-card">
                <h2>Bienvenue !</h2>
                <p class="subtitle">
                    Connectez-vous pour trouver votre âme sœur
                </p>
                
                <?php if (isset($deja_connecte) && $deja_connecte): ?>
                    <div class="info-message">
                        ℹ️ Vous êtes déjà connecté !
                    </div>
                    
                    <div class="session-info">
                        <h3>Informations de session :</h3>
                        <p>👤 ID: <?php echo $_SESSION['user_id'] ?? 'Non défini'; ?></p>
                        <p>📧 Email: <?php echo htmlspecialchars($_SESSION['user_email'] ?? ''); ?></p>
                        <p>👋 Prénom: <?php echo htmlspecialchars($_SESSION['user_prenom'] ?? ''); ?></p>
                        <p>🎭 Rôle: <?php echo htmlspecialchars($_SESSION['user_role'] ?? 'Non défini'); ?></p>
                        
                        <div class="action-buttons">
                            <a href="test_session.php">Test Session</a>
                            <a href="logout.php">Déconnexion</a>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if (!empty($error)): ?>
                <div class="error-message">
                    ❌ <?php echo htmlspecialchars($error); ?>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($success)): ?>
                <div class="success-message">
                    ✅ <?php echo $success; ?>
                </div>
                
                <div class="session-info">
                    <h3>Connexion réussie !</h3>
                    <p>👤 ID: <?php echo $_SESSION['user_id']; ?></p>
                    <p>📧 Email: <?php echo htmlspecialchars($_SESSION['user_email']); ?></p>
                    <p>👋 Prénom: <?php echo htmlspecialchars($_SESSION['user_prenom']); ?></p>
                    <p>📍 Ville: <?php echo htmlspecialchars($_SESSION['user_ville']); ?></p>
                    <p>🎭 Rôle: <?php echo htmlspecialchars($_SESSION['user_role'] ?? 'user'); ?></p>
                    
                    <div class="action-buttons">
                        <a href="test_session.php">Voir ma session</a>
                        <a href="profile.php">Aller au profil</a>
                    </div>
                </div>
                <?php endif; ?>

                <?php if (isset($_GET['registered']) && $_GET['registered'] == 'true'): ?>
                <div class="success-message">
                    ✅ Inscription réussie ! Vous pouvez maintenant vous connecter.
                </div>
                <?php endif; ?>
                
                <?php if (!isset($deja_connecte) && empty($success)): ?>
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input
                            type="email"
                            id="email"
                            name="email"
                            required
                            placeholder="votre@email.com"
                            value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                        />
                    </div>

                    <div class="form-group">
                        <label for="password">Mot de passe</label>
                        <input
                            type="password"
                            id="password"
                            name="password"
                            required
                            placeholder="••••••••"
                        />
                    </div>

                    <div class="forgot-password">
                        <a href="#">Mot de passe oublié ?</a>
                    </div>

                    <button type="submit">Se connecter</button>
                </form>

                <div class="divider">
                    <span>OU</span>
                </div>

                <div class="signup-link">
                    Pas encore de compte ?
                    <a href="inscription.php">Inscrivez-vous</a>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <script>
            // Auto-hide messages after 8 seconds
            setTimeout(function() {
                const messages = document.querySelectorAll('.error-message, .success-message, .info-message');
                messages.forEach(msg => {
                    if (!msg.classList.contains('session-info') && !msg.closest('.session-info')) {
                        msg.style.transition = 'opacity 0.5s';
                        msg.style.opacity = '0';
                        setTimeout(() => {
                            if (msg.parentNode) msg.remove();
                        }, 500);
                    }
                });
            }, 8000);
        </script>
    </body>
</html>