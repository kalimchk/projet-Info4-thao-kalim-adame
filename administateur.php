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
        <a class="logo" href="accueil.html">Pasta La Vista</a>
        <nav class="navbar">
            <a href="accueil.html">Accueil</a>
            <a href="carte.php">Carte</a>
            <a class="active" href="administateur.php">Administration</a>
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
