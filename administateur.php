<?php
session_start();
require_once __DIR__ . '/config/function.php';
$utilisateurConnecte = obtenirUtilisateurConnecteOuRediriger();

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
<body class="page-administateur" data-surveillance-session="1">
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
            <p id="message-admin-utilisateur" class="message-retour" style="display:none;"></p>

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
                            <span class="badge badge-blocage <?php echo !empty($utilisateur['est_bloque']) ? 'badge-bloque' : 'badge-actif'; ?>">
                                <?php echo !empty($utilisateur['est_bloque']) ? 'Bloque' : 'Actif'; ?>
                            </span>
                        </div>
                        <?php if ((int) ($utilisateur['id'] ?? 0) !== (int) ($utilisateurConnecte['id'] ?? 0)): ?>
                            <button
                                type="button"
                                class="btn js-action-blocage <?php echo !empty($utilisateur['est_bloque']) ? 'btn-debloquer' : 'btn-bloquer'; ?>"
                                data-user-id="<?php echo (int) ($utilisateur['id'] ?? 0); ?>"
                                data-est-bloque="<?php echo !empty($utilisateur['est_bloque']) ? '1' : '0'; ?>"
                            >
                                <?php echo !empty($utilisateur['est_bloque']) ? 'Debloquer' : 'Bloquer'; ?>
                            </button>
                        <?php endif; ?>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>
    </main>

    <footer class="site-footer">
        <p>&copy; 2026 Pasta La Vista - Restaurant italien.</p>
    </footer>
    <script src="js/admin_blocage.js"></script>
    <script src="js/session_surveillance.js"></script>
</body>
</html>
