<?php
session_start();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Test Session</title>
    <style>
        body { font-family: Arial; padding: 20px; }
        pre { background: #f4f4f4; padding: 15px; border-radius: 5px; }
    </style>
</head>
<body>
    <h1>🔍 Test des Sessions PHP</h1>
    
    <h2>Variables de session :</h2>
    <pre><?php print_r($_SESSION); ?></pre>
    
    <h2>Informations :</h2>
    <p>Session ID : <?php echo session_id(); ?></p>
    <p>Nom de session : <?php echo session_name(); ?></p>
    
    <h2>Actions :</h2>
    <a href="login.php">← Retour à la connexion</a>
</body>
</html>