<?php
// simple_inscription.php - Version simplifiée

// SIMULE l'insertion sans modifier ta base
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    echo "<div style='background:green;color:white;padding:10px;'>
          Formulaire reçu ! (Simulation)</div>";
    
    // Affiche les données reçues
    echo "<pre>";
    print_r($_POST);
    echo "</pre>";
    
    echo "<a href='inscription.html'>Retour au formulaire</a>";
    exit();
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Test Inscription</title>
    <style>
        body { font-family: Arial; padding: 20px; }
        input, button { padding: 10px; margin: 5px; }
    </style>
</head>
<body>
    <h2>Test Formulaire</h2>
    <form method="POST">
        <input type="text" name="prenom" placeholder="Prénom" required><br>
        <input type="email" name="email" placeholder="Email" required><br>
        <input type="password" name="password" placeholder="Mot de passe" required><br>
        <button type="submit">S'inscrire (test)</button>
    </form>
</body>
</html>