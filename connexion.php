<?php
session_start();
require_once __DIR__ . '/config/function.php';

$messageErreurConnexion = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $emailUtilisateur = trim($_POST['email'] ?? '');
    $motDePasseUtilisateur = trim($_POST['password'] ?? '');

    $utilisateurConnecte = trouverUtilisateurParEmail($emailUtilisateur);

    if ($utilisateurConnecte !== null && ($utilisateurConnecte['password'] ?? '') === $motDePasseUtilisateur) {
        $_SESSION['user'] = $utilisateurConnecte;

        if (($utilisateurConnecte['statut'] ?? '') === 'restaurateur') {
            header('Location: commande.php');
            exit();
        }

        if (($utilisateurConnecte['statut'] ?? '') === 'admin') {
            header('Location: administateur.php');
            exit();
        }

        if (($utilisateurConnecte['statut'] ?? '') === 'livreur') {
            header('Location: livraison.php');
            exit();
        }

        header('Location: accueil.php');
        exit();
    }

    $messageErreurConnexion = 'Email ou mot de passe incorrect.';
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="css/style.css">
    <title>Connexion</title>
</head>
<body class="page-connexion">
    <header class="site-header">
        <a class="logo" href="accueil.php"><img class="logo-img" src="logo/logo-pasta-la-vista.png" alt="Logo Pasta La Vista"><span class="logo-text">Pasta La Vista</span></a>
        <nav class="navbar">
            <a href="accueil.php">Accueil</a>
            <a href="carte.php">Carte</a>
            
            <a href="panier.php" class="lien-panier">
                🛒 Mon Panier 
                <?php if (isset($nombre_articles_panier) && $nombre_articles_panier > 0): ?>
                    <span class="badge-panier">(<?= $nombre_articles_panier ?>)</span>
                <?php endif; ?>
            </a>
            
            <?php if (isset($_SESSION['user'])): ?>
                <a href="profil.php">Mon Profil</a>
                <a href="deconnexion.php" style="color: #a45742; font-weight: 600;">Déconnexion</a>
            <?php else: ?>
                <a href="connexion.php">Connexion</a>
                <a href="inscription.php">Inscription</a>
            <?php endif; ?>
        </nav>
    </header>

    <main>
        <h1>Connexion</h1>

        <?php if ($messageErreurConnexion !== ''): ?>
            <p class="message-erreur"><?php echo htmlspecialchars($messageErreurConnexion, ENT_QUOTES, 'UTF-8'); ?></p>
        <?php endif; ?>

        <form method="POST" action="">
            <label for="email">Email</label>
            <input id="email" type="email" name="email" placeholder="Email" required>

            <label for="password">Mot de passe</label>
            <input id="password" type="password" name="password" placeholder="Mot de passe" required>

            <button type="submit">Se connecter</button>
        </form>

        <p>Compte restaurateur : restaurateur@pasta.fr / resto123</p>
        <p>Compte admin : admin@pasta.fr / admin123</p>
        <p>Compte livreur : livreur@pasta.fr / livreur123</p>
    </main>

    <footer class="site-footer">
        <p>&copy; 2026 Pasta La Vista - Restaurant italien.</p>
    </footer>
</body>
</html>
