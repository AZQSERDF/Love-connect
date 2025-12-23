<?php
session_start();

// Traitement du formulaire quand il est soumis
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        // 1. Connexion à la base de données
        $conn = new PDO("mysql:host=localhost;port=3306;dbname=loveconnect_db", "root", "");
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // 2. Récupération des données du formulaire
        $email = $_POST['email'] ?? '';
        $password = password_hash($_POST['password'] ?? '', PASSWORD_BCRYPT);
        $prenom = $_POST['firstName'] ?? '';
        $nom = $_POST['lastName'] ?? '';
        $genre = $_POST['gender'] ?? '';
        $age = $_POST['age'] ?? '';
        $ville = $_POST['city'] ?? '';
        
        // 3. Calcul de la date de naissance
        $annee_actuelle = date('Y');
        $annee_naissance = $annee_actuelle - $age;
        $date_naissance = $annee_naissance . "-06-15"; // Date moyenne
        
        // 4. Insertion dans la base de données
        $sql = "INSERT INTO utilisateurs 
                (email, mot_de_passe, prenom, nom, genre, date_naissance, ville, date_inscription) 
                VALUES (:email, :password, :prenom, :nom, :genre, :date_naissance, :ville, NOW())";
        
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            ':email' => $email,
            ':password' => $password,
            ':prenom' => $prenom,
            ':nom' => $nom,
            ':genre' => $genre,
            ':date_naissance' => $date_naissance,
            ':ville' => $ville
        ]);
        
        // 5. Message de succès
        $inscription_reussie = true;
        
    } catch(PDOException $e) {
        // En cas d'erreur
        $erreur_message = "Erreur : " . $e->getMessage();
        if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
            $erreur_message = "Cette adresse email est déjà utilisée.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
    <head>
        <meta charset="UTF-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <title>Inscription - LoveConnect</title>
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
                position: relative;
                overflow-x: hidden;
            }

            /* Particules animées en arrière-plan */
            .particles {
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                overflow: hidden;
                z-index: 0;
            }

            .particle {
                position: absolute;
                width: 6px;
                height: 6px;
                background: rgba(255, 255, 255, 0.3);
                border-radius: 50%;
                animation: float 20s infinite;
            }

            @keyframes float {
                0%, 100% {
                    transform: translateY(0) translateX(0);
                    opacity: 0;
                }
                10% {
                    opacity: 1;
                }
                90% {
                    opacity: 1;
                }
                100% {
                    transform: translateY(-100vh) translateX(100px);
                    opacity: 0;
                }
            }

            header {
                background: rgba(255, 255, 255, 0.1);
                backdrop-filter: blur(15px);
                padding: 1rem 2rem;
                box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
                position: relative;
                z-index: 10;
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
                position: relative;
                z-index: 10;
            }

            .signup-card {
                background: white;
                border-radius: 20px;
                padding: 3rem;
                box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
                width: 100%;
                max-width: 550px;
                animation: slideUp 0.6s ease;
                position: relative;
            }

            @keyframes slideUp {
                from {
                    opacity: 0;
                    transform: translateY(50px);
                }
                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }

            .signup-header {
                text-align: center;
                margin-bottom: 2.5rem;
            }

            .signup-header h2 {
                color: #333;
                font-size: 2.2rem;
                margin-bottom: 0.5rem;
            }

            .subtitle {
                color: #666;
                font-size: 0.95rem;
            }

            .progress-bar {
                height: 4px;
                background: #e0e0e0;
                border-radius: 10px;
                margin: 1.5rem 0;
                overflow: hidden;
            }

            .progress-fill {
                height: 100%;
                background: linear-gradient(90deg, #667eea, #764ba2);
                width: 0%;
                transition: width 0.3s ease;
                border-radius: 10px;
            }

            .form-row {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 1rem;
            }

            .form-group {
                margin-bottom: 1.5rem;
            }

            .form-group.full-width {
                grid-column: 1 / -1;
            }

            label {
                display: block;
                color: #333;
                font-weight: 600;
                margin-bottom: 0.5rem;
                font-size: 0.9rem;
                display: flex;
                align-items: center;
                gap: 0.3rem;
            }

            .required {
                color: #e74c3c;
            }

            input, select {
                width: 100%;
                padding: 1rem;
                border: 2px solid #e0e0e0;
                border-radius: 10px;
                font-size: 1rem;
                transition: all 0.3s ease;
                background: #f8f9fa;
                font-family: inherit;
            }

            input:focus, select:focus {
                outline: none;
                border-color: #667eea;
                background: white;
                box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
            }

            input:valid {
                border-color: #28a745;
            }

            select {
                cursor: pointer;
                appearance: none;
                background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%23667eea' d='M6 9L1 4h10z'/%3E%3C/svg%3E");
                background-repeat: no-repeat;
                background-position: right 1rem center;
                padding-right: 2.5rem;
            }

            .password-strength {
                height: 4px;
                background: #e0e0e0;
                border-radius: 10px;
                margin-top: 0.5rem;
                overflow: hidden;
            }

            .password-strength-fill {
                height: 100%;
                width: 0%;
                transition: all 0.3s ease;
                border-radius: 10px;
            }

            .password-strength-fill.weak {
                width: 33%;
                background: #e74c3c;
            }

            .password-strength-fill.medium {
                width: 66%;
                background: #f39c12;
            }

            .password-strength-fill.strong {
                width: 100%;
                background: #28a745;
            }

            .password-hint {
                font-size: 0.8rem;
                color: #999;
                margin-top: 0.3rem;
            }

            .gender-options {
                display: grid;
                grid-template-columns: repeat(3, 1fr);
                gap: 0.8rem;
            }

            .gender-option {
                position: relative;
            }

            .gender-option input[type="radio"] {
                position: absolute;
                opacity: 0;
            }

            .gender-label {
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                padding: 1rem;
                border: 2px solid #e0e0e0;
                border-radius: 10px;
                cursor: pointer;
                transition: all 0.3s ease;
                background: #f8f9fa;
                text-align: center;
            }

            .gender-icon {
                font-size: 2rem;
                margin-bottom: 0.5rem;
            }

            .gender-text {
                font-size: 0.9rem;
                color: #666;
                font-weight: 500;
            }

            .gender-option input:checked + .gender-label {
                border-color: #667eea;
                background: rgba(102, 126, 234, 0.1);
            }

            .gender-option input:checked + .gender-label .gender-text {
                color: #667eea;
                font-weight: 600;
            }

            .terms-checkbox {
                display: flex;
                align-items: center;
                gap: 0.8rem;
                margin: 1.5rem 0;
            }

            .terms-checkbox input[type="checkbox"] {
                width: 20px;
                height: 20px;
                cursor: pointer;
            }

            .terms-checkbox label {
                margin: 0;
                font-weight: normal;
                font-size: 0.9rem;
                color: #666;
            }

            .terms-checkbox a {
                color: #667eea;
                text-decoration: none;
            }

            .terms-checkbox a:hover {
                text-decoration: underline;
            }

            button {
                width: 100%;
                padding: 1.2rem;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: white;
                border: none;
                border-radius: 10px;
                font-size: 1.1rem;
                font-weight: 600;
                cursor: pointer;
                transition: all 0.3s ease;
                margin-top: 1rem;
                position: relative;
                overflow: hidden;
            }

            button::before {
                content: "";
                position: absolute;
                top: 50%;
                left: 50%;
                width: 0;
                height: 0;
                border-radius: 50%;
                background: rgba(255, 255, 255, 0.3);
                transform: translate(-50%, -50%);
                transition: width 0.6s, height 0.6s;
            }

            button:hover::before {
                width: 300px;
                height: 300px;
            }

            button:hover {
                transform: translateY(-2px);
                box-shadow: 0 10px 25px rgba(102, 126, 234, 0.4);
            }

            button:active {
                transform: translateY(0);
            }

            button:disabled {
                opacity: 0.6;
                cursor: not-allowed;
            }

            .login-link {
                text-align: center;
                margin-top: 1.5rem;
                color: #666;
                font-size: 0.95rem;
            }

            .login-link a {
                color: #667eea;
                text-decoration: none;
                font-weight: 600;
            }

            .login-link a:hover {
                text-decoration: underline;
            }

            .alert-message {
                position: fixed;
                top: 20px;
                right: 20px;
                padding: 15px 20px;
                border-radius: 10px;
                color: white;
                font-weight: 600;
                z-index: 9999;
                animation: slideInRight 0.3s ease;
                box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            }
            
            .alert-success {
                background: #28a745;
                border-left: 5px solid #1e7e34;
            }
            
            .alert-error {
                background: #dc3545;
                border-left: 5px solid #bd2130;
            }
            
            @keyframes slideInRight {
                from {
                    transform: translateX(100%);
                    opacity: 0;
                }
                to {
                    transform: translateX(0);
                    opacity: 1;
                }
            }

            .success-message {
                display: none;
                background: #d4edda;
                border: 2px solid #28a745;
                color: #155724;
                padding: 1rem;
                border-radius: 10px;
                margin-bottom: 1rem;
                animation: slideDown 0.3s ease;
            }

            @keyframes slideDown {
                from {
                    opacity: 0;
                    transform: translateY(-20px);
                }
                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }

            .input-icon {
                position: relative;
            }

            .input-icon input {
                padding-left: 2.8rem;
            }

            .icon {
                position: absolute;
                left: 1rem;
                top: 50%;
                transform: translateY(-50%);
                font-size: 1.2rem;
                color: #999;
            }

            @media (max-width: 600px) {
                .signup-card {
                    padding: 2rem;
                }

                .form-row {
                    grid-template-columns: 1fr;
                }

                .gender-options {
                    grid-template-columns: 1fr;
                }

                header {
                    padding: 1rem;
                }

                nav ul {
                    gap: 1rem;
                }
                
                .alert-message {
                    top: 10px;
                    right: 10px;
                    left: 10px;
                    font-size: 0.9rem;
                }
            }
        </style>
    </head>
    <body>
        <?php
        // AFFICHAGE DES MESSAGES PHP
        if (isset($erreur_message)) {
            echo '<div class="alert-message alert-error">❌ ' . htmlspecialchars($erreur_message) . '</div>';
        }
        if (isset($inscription_reussie) && $inscription_reussie) {
            echo '<div class="alert-message alert-success">✅ Compte créé avec succès ! Redirection...</div>';
        }
        ?>
        
        <div class="particles" id="particles"></div>

        <header>
            <h1>💕 LoveConnect</h1>
            <nav>
                <ul>
                    <li><a href="index.php">Accueil</a></li>
                    <li><a href="login.php">Connexion</a></li>
                </ul>
            </nav>
        </header>

        <div class="container">
            <div class="signup-card">
                <div class="signup-header">
                    <h2>Créez Votre Compte</h2>
                    <p class="subtitle">
                        Rejoignez des milliers de célibataires qui ont trouvé l'amour
                    </p>
                </div>

                <div class="progress-bar">
                    <div class="progress-fill" id="progressBar"></div>
                </div>

                <div class="success-message" id="successMessage">
                    ✓ Compte créé avec succès ! Redirection...
                </div>

                <form id="signupForm" method="POST" action="">
                    <div class="form-row">
                        <div class="form-group">
                            <label>Prénom <span class="required">*</span></label>
                            <div class="input-icon">
                                <span class="icon">👤</span>
                                <input
                                    type="text"
                                    id="firstName"
                                    name="firstName"
                                    required
                                    placeholder="Votre prénom"
                                    value="<?php echo isset($_POST['firstName']) ? htmlspecialchars($_POST['firstName']) : ''; ?>"
                                />
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Nom <span class="required">*</span></label>
                            <div class="input-icon">
                                <span class="icon">👤</span>
                                <input
                                    type="text"
                                    id="lastName"
                                    name="lastName"
                                    required
                                    placeholder="Votre nom"
                                    value="<?php echo isset($_POST['lastName']) ? htmlspecialchars($_POST['lastName']) : ''; ?>"
                                />
                            </div>
                        </div>
                    </div>

                    <div class="form-group full-width">
                        <label>Email <span class="required">*</span></label>
                        <div class="input-icon">
                            <span class="icon">📧</span>
                            <input
                                type="email"
                                id="email"
                                name="email"
                                required
                                placeholder="votre@email.com"
                                value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                            />
                        </div>
                    </div>

                    <div class="form-group full-width">
                        <label>Mot de passe <span class="required">*</span></label>
                        <div class="input-icon">
                            <span class="icon">🔒</span>
                            <input
                                type="password"
                                id="password"
                                name="password"
                                required
                                placeholder="••••••••"
                                minlength="6"
                            />
                        </div>
                        <div class="password-strength">
                            <div class="password-strength-fill" id="passwordStrength"></div>
                        </div>
                        <p class="password-hint" id="passwordHint">Au moins 6 caractères</p>
                    </div>

                    <div class="form-group full-width">
                        <label>Genre <span class="required">*</span></label>
                        <div class="gender-options">
                            <div class="gender-option">
                                <input
                                    type="radio"
                                    name="gender"
                                    id="male"
                                    value="homme"
                                    required
                                    <?php echo (isset($_POST['gender']) && $_POST['gender'] == 'homme') ? 'checked' : ''; ?>
                                />
                                <label for="male" class="gender-label">
                                    <span class="gender-icon">👨</span>
                                    <span class="gender-text">Homme</span>
                                </label>
                            </div>
                            <div class="gender-option">
                                <input
                                    type="radio"
                                    name="gender"
                                    id="female"
                                    value="femme"
                                    required
                                    <?php echo (isset($_POST['gender']) && $_POST['gender'] == 'femme') ? 'checked' : ''; ?>
                                />
                                <label for="female" class="gender-label">
                                    <span class="gender-icon">👩</span>
                                    <span class="gender-text">Femme</span>
                                </label>
                            </div>
                            <div class="gender-option">
                                <input
                                    type="radio"
                                    name="gender"
                                    id="other"
                                    value="autre"
                                    required
                                    <?php echo (isset($_POST['gender']) && $_POST['gender'] == 'autre') ? 'checked' : ''; ?>
                                />
                                <label for="other" class="gender-label">
                                    <span class="gender-icon">⚧️</span>
                                    <span class="gender-text">Autre</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Âge <span class="required">*</span></label>
                            <div class="input-icon">
                                <span class="icon">🎂</span>
                                <input
                                    type="number"
                                    id="age"
                                    name="age"
                                    required
                                    min="18"
                                    max="100"
                                    placeholder="25"
                                    value="<?php echo isset($_POST['age']) ? htmlspecialchars($_POST['age']) : ''; ?>"
                                />
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Ville <span class="required">*</span></label>
                            <div class="input-icon">
                                <span class="icon">📍</span>
                                <input
                                    type="text"
                                    id="city"
                                    name="city"
                                    required
                                    placeholder="Paris"
                                    value="<?php echo isset($_POST['city']) ? htmlspecialchars($_POST['city']) : ''; ?>"
                                />
                            </div>
                        </div>
                    </div>

                    <div class="terms-checkbox">
                        <input type="checkbox" id="terms" name="terms" required />
                        <label for="terms">
                            J'accepte les
                            <a href="#">conditions d'utilisation</a> et la
                            <a href="#">politique de confidentialité</a>
                        </label>
                    </div>

                    <button type="submit" id="submitBtn" name="submit">
                        ✨ Créer mon compte
                    </button>
                </form>

                <div class="login-link">
                    Vous avez déjà un compte ?
                    <a href="login.php">Se connecter</a>
                </div>
            </div>
        </div>

        <script>
            // Créer des particules animées
            const particlesContainer = document.getElementById("particles");
            for (let i = 0; i < 50; i++) {
                const particle = document.createElement("div");
                particle.className = "particle";
                particle.style.left = Math.random() * 100 + "%";
                particle.style.animationDelay = Math.random() * 20 + "s";
                particle.style.animationDuration = Math.random() * 10 + 10 + "s";
                particlesContainer.appendChild(particle);
            }

            const form = document.getElementById("signupForm");
            const passwordInput = document.getElementById("password");
            const passwordStrength = document.getElementById("passwordStrength");
            const passwordHint = document.getElementById("passwordHint");
            const progressBar = document.getElementById("progressBar");
            const inputs = form.querySelectorAll("input[required]");

            // Vérifier la force du mot de passe
            passwordInput.addEventListener("input", function () {
                const password = this.value;
                let strength = 0;

                if (password.length >= 6) strength++;
                if (password.length >= 10) strength++;
                if (/[a-z]/.test(password) && /[A-Z]/.test(password)) strength++;
                if (/[0-9]/.test(password)) strength++;
                if (/[^a-zA-Z0-9]/.test(password)) strength++;

                passwordStrength.className = "password-strength-fill";

                if (strength <= 2) {
                    passwordStrength.classList.add("weak");
                    passwordHint.textContent = "Mot de passe faible";
                    passwordHint.style.color = "#e74c3c";
                } else if (strength <= 4) {
                    passwordStrength.classList.add("medium");
                    passwordHint.textContent = "Mot de passe moyen";
                    passwordHint.style.color = "#f39c12";
                } else {
                    passwordStrength.classList.add("strong");
                    passwordHint.textContent = "Mot de passe fort ! ✓";
                    passwordHint.style.color = "#28a745";
                }
            });

            // Mettre à jour la barre de progression
            function updateProgress() {
                let filledInputs = 0;
                inputs.forEach((input) => {
                    if (input.type === "checkbox" || input.type === "radio") {
                        if (input.checked) filledInputs++;
                    } else if (input.value.trim() !== "") {
                        filledInputs++;
                    }
                });

                const progress = (filledInputs / inputs.length) * 100;
                progressBar.style.width = progress + "%";
            }

            inputs.forEach((input) => {
                input.addEventListener("input", updateProgress);
                input.addEventListener("change", updateProgress);
            });

            // Validation de l'âge
            document.getElementById("age").addEventListener("input", function () {
                if (this.value < 18) {
                    this.setCustomValidity("Vous devez avoir au moins 18 ans");
                } else {
                    this.setCustomValidity("");
                }
            });

            <?php if (isset($inscription_reussie) && $inscription_reussie): ?>
            // Rediriger vers la page de connexion après 3 secondes
            setTimeout(function() {
                window.location.href = "login.php?registered=true";
            }, 3000);
            
            // Désactiver le formulaire après succès
            document.getElementById("signupForm").style.opacity = "0.5";
            document.getElementById("signupForm").querySelectorAll("input, button").forEach(el => {
                el.disabled = true;
            });
            <?php endif; ?>
            
            // Auto-hide les messages
            setTimeout(function() {
                const alerts = document.querySelectorAll('.alert-message');
                alerts.forEach(alert => {
                    alert.style.transition = 'opacity 0.5s';
                    alert.style.opacity = '0';
                    setTimeout(() => alert.remove(), 500);
                });
            }, 5000);
        </script>
    </body>
</html>