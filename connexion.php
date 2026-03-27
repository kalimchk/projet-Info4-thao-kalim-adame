<?php
session_start();
require 'config/database.php';

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $email = $_POST['email'];
    $password = $_POST['password'];

    $sql = "SELECT * FROM utilisateurs WHERE email = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$email]);

    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {

        $_SESSION['user_id'] = $user['id'];
        $_SESSION['email'] = $user['email'];

        header("Location: accueil.html");
        exit;

    } else {
        $message = "Email ou mot de passe incorrect";
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/png" href="logo/logo-pasta-la-vista.png">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Connexion - Pasta La Vista</title>
    <link rel="stylesheet" href="css/style.css">
</head>

<body class="page-connexion">
<header class="site-header">
    <a class="logo" href="accueil.html">
        <img class="logo-img" src="logo/logo-pasta-la-vista.png" alt="Logo Pasta La Vista">
        <span class="logo-text">Pasta La Vista</span>
    </a>
    <nav class="navbar">
        <a href="accueil.html">Accueil</a>
        <a href="carte.html">Carte</a>
        <a class="active" href="connexion.php">Connexion</a>
    </nav>
</header>

<main>
    <h1>Connexion</h1>
    <?php if ($message != ""): ?>
        <p style="text-align:center; margin-bottom:15px;">
            <?php echo $message; ?>
        </p>
    <?php endif; ?>

    <form method="POST">

        <label for="email">Adresse e-mail</label>
        <input type="email" id="email" name="email" required>

        <label for="password">Mot de passe</label>
        <input type="password" id="password" name="password" required>

        <button type="submit">Se connecter</button>
    </form>

    <p>
        Pas encore de compte ?
        <a href="inscription.php">Créer un compte</a>
    </p>
</main>

<footer class="site-footer">
    <p>&copy; 2026 Pasta La Vista - Restaurant italien.</p>
</footer>

</body>
</html>


















