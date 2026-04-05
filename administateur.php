<?php
session_start();
require_once __DIR__ . '/config/function.php';

if (!isset($_SESSION['user'])) {
    header('Location: connexion.php');
    exit();
}

$utilisateurConnecte = $_SESSION['user'];

if (($utilisateurConnecte['statut'] ?? '') !== 'admin') {
    header('Location: accueil.html');
    exit();
}

$listeDesUtilisateurs = lireUtilisateurs();
$nombreTotalUtilisateurs = count($listeDesUtilisateurs);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="css/style.css">
    <title>Administration</title>
</head>
<body class="page-administateur">
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

    <main class="page">
        <section class="card">
            <div class="head">
                <div>
                    <h1>Administration</h1>
                    <p class="subtitle">Total utilisateurs : <?php echo $nombreTotalUtilisateurs; ?></p>
                </div>
            </div>

            <div class="users">
                <?php foreach ($listeDesUtilisateurs as $utilisateur): ?>
                    <article class="user">
                        <div class="user-top">
                            <p class="user-name">
                                <?php echo htmlspecialchars(($utilisateur['prenom'] ?? '') . ' ' . ($utilisateur['nom'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>
                            </p>
                        </div>
                        <p class="user-meta"><?php echo htmlspecialchars($utilisateur['email'] ?? '', ENT_QUOTES, 'UTF-8'); ?></p>
                        <p class="user-meta"><?php echo htmlspecialchars($utilisateur['telephone'] ?? '', ENT_QUOTES, 'UTF-8'); ?></p>
                        <div class="badges">
                            <span class="badge"><?php echo htmlspecialchars($utilisateur['statut'] ?? '', ENT_QUOTES, 'UTF-8'); ?></span>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>
    </main>

    <footer class="site-footer">
        <p>&copy; 2026 Pasta La Vista - Restaurant italien.</p>
    </footer>
</body>
</html>
