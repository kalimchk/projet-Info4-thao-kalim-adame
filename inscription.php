<?php
require 'config/database.php';

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $nom = $_POST['nom'];
    $prenom = $_POST['prenom'];
    $email = $_POST['email'];
    $telephone = $_POST['telephone'];
    $adresse = $_POST['adresse'];
    $password = $_POST['password'];
    $confirm = $_POST['confirm'];

    if ($password !== $confirm) {
        $message = "Les mots de passe ne correspondent pas";
    } else {

        // Vérifier si email existe déjà
        $check = $pdo->prepare("SELECT id FROM utilisateurs WHERE email = ?");
        $check->execute([$email]);

        if ($check->fetch()) {
            $message = "Cet email est déjà utilisé";
        } else {

            $hash = password_hash($password, PASSWORD_DEFAULT);

            $sql = "INSERT INTO utilisateurs (nom, prenom, email, telephone, adresse, password)
                    VALUES (?, ?, ?, ?, ?, ?)";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([$nom, $prenom, $email, $telephone, $adresse, $hash]);

            $message = "Compte créé avec succès !";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/png" href="logo/logo-pasta-la-vista.png">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Inscription - Pasta La Vista</title>
    <link rel="stylesheet" href="css/style.css">
</head>

<body class="page-inscription">

<header class="site-header">
    <a class="logo" href="accueil.html">
        <img class="logo-img" src="logo/logo-pasta-la-vista.png">
        <span class="logo-text">Pasta La Vista</span>
    </a>
    <nav class="navbar">
        <a href="accueil.html">Accueil</a>
        <a href="carte.html">Carte</a>
        <a href="connexion.php">Connexion</a>
    </nav>
</header>

<main>
    <h1>Inscription</h1>
    <?php if ($message != ""): ?>
        <p style="text-align:center; margin-bottom:15px;">
            <?php echo $message; ?>
        </p>
    <?php endif; ?>

    <form method="POST">

        <label>Nom</label>
        <input type="text" name="nom" required>

        <label>Prénom</label>
        <input type="text" name="prenom" required>

        <label>Adresse e-mail</label>
        <input type="email" name="email" required>

        <label>Numéro de téléphone</label>
        <input type="tel" name="telephone">

        <label>Adresse</label>
        <input type="text" name="adresse">

        <label>Mot de passe</label>
        <input type="password" name="password" required>

        <label>Confirmer le mot de passe</label>
        <input type="password" name="confirm" required>

        <button type="submit">Créer mon compte</button>
    </form>
</main>

<footer class="site-footer">
    <p>&copy; 2026 Pasta La Vista - Restaurant italien.</p>
</footer>

</body>
</html>

















