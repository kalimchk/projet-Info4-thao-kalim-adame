<?php
session_start();
require_once __DIR__ . '/config/function.php';
verifierEtatSessionUtilisateur();

$messageErreurConnexion = '';
$messageInformation = '';

if (($_GET['message'] ?? '') === 'compte_bloque') {
    $messageInformation = 'Votre compte est bloque. Vous ne pouvez plus utiliser le site.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $emailUtilisateur = trim($_POST['email'] ?? '');
    $motDePasseUtilisateur = trim($_POST['password'] ?? '');

    $utilisateurConnecte = trouverUtilisateurParEmail($emailUtilisateur);

    if ($utilisateurConnecte !== null && utilisateurEstBloque($utilisateurConnecte)) {
        $messageErreurConnexion = 'Votre compte est bloque.';
    } elseif ($utilisateurConnecte !== null && ($utilisateurConnecte['password'] ?? '') === $motDePasseUtilisateur) {
        $_SESSION['user'] = $utilisateurConnecte;

        if (($utilisateurConnecte['statut'] ?? '') === 'restaurateur') {
            header('Location: commande.php'); exit();
        }
        if (($utilisateurConnecte['statut'] ?? '') === 'admin') {
            header('Location: administateur.php'); exit();
        }
        if (($utilisateurConnecte['statut'] ?? '') === 'livreur') {
            header('Location: livraison.php'); exit();
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
        <a class="logo" href="accueil.php">
            <img class="logo-img" src="logo/logo-pasta-la-vista.png" alt="Logo Pasta La Vista">
            <span class="logo-text">Pasta La Vista</span>
        </a>
        <nav class="navbar">
            <a href="accueil.php">Accueil</a>
            <a href="carte.php">Carte</a>
            <?php if (isset($_SESSION['user'])): ?>
                <a href="profil.php">Mon Profil</a>
                <a href="deconnexion.php" style="color:#a45742; font-weight:600;">Déconnexion</a>
            <?php else: ?>
                <a href="connexion.php">Connexion</a>
                <a href="inscription.php">Inscription</a>
            <?php endif; ?>
        </nav>
    </header>

    <main>
        <h1>Connexion</h1>

        <?php if ($messageInformation !== ''): ?>
            <p class="message-retour"><?= htmlspecialchars($messageInformation, ENT_QUOTES, 'UTF-8') ?></p>
        <?php endif; ?>

        <?php if ($messageErreurConnexion !== ''): ?>
            <p class="message-erreur"><?= htmlspecialchars($messageErreurConnexion, ENT_QUOTES, 'UTF-8') ?></p>
        <?php endif; ?>

        <form method="POST" action="" id="form-connexion" novalidate>
            <label for="email">Email</label>
            <input id="email" type="email" name="email" placeholder="Email" required autocomplete="email">
            <p class="erreur-champ" id="erreur-email" style="display:none;"></p>

            <label for="password">Mot de passe</label>
            <div class="mdp-wrapper">
                <input id="password" type="password" name="password" placeholder="Mot de passe" required maxlength="64">
                <button type="button" class="btn-oeil" id="toggle-mdp-connexion" title="Afficher/Cacher">👁️</button>
            </div>
            <p class="erreur-champ" id="erreur-password" style="display:none;"></p>
            <p class="compteur-chars" id="compteur-mdp-connexion">0 / 64 caractères</p>

            <button type="submit">Se connecter</button>
        </form>

        <p>Compte restaurateur : restaurateur@pasta.fr / resto123</p>
        <p>Compte admin : admin@pasta.fr / admin123</p>
        <p>Compte livreur : livreur@pasta.fr / livreur123</p>
    </main>

    <footer class="site-footer">
        <p>&copy; 2026 Pasta La Vista - Restaurant italien.</p>
    </footer>

    <script src="js/connexion.js"></script>
</body>
</html>
