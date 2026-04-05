<?php
require_once __DIR__ . '/config/function.php';

$messageConfirmationInscription = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nomUtilisateur = trim($_POST['nom'] ?? '');
    $prenomUtilisateur = trim($_POST['prenom'] ?? '');
    $emailUtilisateur = trim($_POST['email'] ?? '');
    $telephoneUtilisateur = trim($_POST['telephone'] ?? '');
    $motDePasseUtilisateur = trim($_POST['password'] ?? '');

    if ($nomUtilisateur !== '' && $prenomUtilisateur !== '' && $emailUtilisateur !== '' && $telephoneUtilisateur !== '' && $motDePasseUtilisateur !== '') {
        ajouterUtilisateur(
            $nomUtilisateur,
            $prenomUtilisateur,
            $emailUtilisateur,
            $telephoneUtilisateur,
            $motDePasseUtilisateur
        );

        $messageConfirmationInscription = 'Compte créé avec succès.';
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="css/style.css">
    <title>Inscription</title>
</head>
<body class="page-inscription">
    <header class="site-header">
        <<a class="logo" href="accueil.php"><img class="logo-img" src="logo/logo-pasta-la-vista.png" alt="Logo Pasta La Vista"><span class="logo-text">Pasta La Vista</span></a>
        <nav class="navbar">
            
            <a class="active" href="accueil.php">Accueil</a>
            <a href="carte.php">Carte</a>
            <?php if (isset($_SESSION['user'])): ?>
                <a href="profil.php">Mon Profil</a>
                <a href="deconnexion.php" style="color: #a45742; font-weight: bold;">Déconnexion</a>
            <?php else: ?>
                <a href="connexion.php">Connexion</a>
            <?php endif; ?>
        </nav>
    </header>

    <main>
        <h1>Inscription</h1>

        <?php if ($messageConfirmationInscription !== ''): ?>
            <p><?php echo htmlspecialchars($messageConfirmationInscription, ENT_QUOTES, 'UTF-8'); ?></p>
        <?php endif; ?>

        <form method="POST" action="">
            <label for="nom">Nom</label>
            <input id="nom" type="text" name="nom" placeholder="Nom" required>

            <label for="prenom">Prénom</label>
            <input id="prenom" type="text" name="prenom" placeholder="Prénom" required>

            <label for="email">Email</label>
            <input id="email" type="email" name="email" placeholder="Email" required>

            <label for="telephone">Téléphone</label>
            <input id="telephone" type="text" name="telephone" placeholder="Téléphone" required>

            <label for="password">Mot de passe</label>
            <input id="password" type="password" name="password" placeholder="Mot de passe" required>

            <button type="submit">S'inscrire</button>
        </form>
    </main>

    <footer class="site-footer">
        <p>&copy; 2026 Pasta La Vista - Restaurant italien.</p>
    </footer>
</body>
</html>
