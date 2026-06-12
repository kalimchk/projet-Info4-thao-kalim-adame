<?php
session_start();
require_once __DIR__ . '/config/function.php';
$utilisateurConnecte = obtenirUtilisateurConnecteOuRediriger();

if (($utilisateurConnecte['statut'] ?? '') !== 'admin') {
    header('Location: accueil.php');
    exit();
}

$listeDesUtilisateurs = lireUtilisateurs();
$nombreTotalUtilisateurs = count($listeDesUtilisateurs);
$avisClients = [];

foreach (lireCommandes() as $commande) {
    if (isset($commande['note'])) {
        $avisClients[] = $commande;
    }
}
?>
<?php
$isDark = isset($_COOKIE['darkmode']) && $_COOKIE['darkmode'] === '1';
$darkClass = $isDark ? ' class="dark-mode"' : '';
?>
<!DOCTYPE html>
<html lang="fr"<?php echo $darkClass; ?>>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/darkmode.css">
    <title>Administration</title>
</head>
<body class="page-administateur" data-surveillance-session="1">
    <?php include 'navbar.php';?>

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
                        <div class="user-status-row">
                            <div class="badges">
                                <span class="badge"><?php echo htmlspecialchars($utilisateur['statut'] ?? '', ENT_QUOTES, 'UTF-8'); ?></span>
                                <?php if (!empty($utilisateur['est_bloque'])): ?>
                                    <span class="badge badge-blocage badge-bloque">Bloqué</span>
                                <?php endif; ?>
                            </div>
                            <div class="user-action-slot">
                                <?php if ((int) ($utilisateur['id'] ?? 0) !== (int) ($utilisateurConnecte['id'] ?? 0)): ?>
                                    <button
                                        type="button"
                                        class="js-action-blocage <?php echo !empty($utilisateur['est_bloque']) ? 'btn-debloquer' : 'btn-bloquer'; ?>"
                                        data-user-id="<?php echo (int) ($utilisateur['id'] ?? 0); ?>"
                                        data-est-bloque="<?php echo !empty($utilisateur['est_bloque']) ? '1' : '0'; ?>"
                                    >
                                        <?php echo !empty($utilisateur['est_bloque']) ? 'Débloquer' : 'Bloquer'; ?>
                                    </button>
                                <?php else: ?>
                                    <span class="user-action-note">Compte courant</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="card">
            <div class="head">
                <div>
                    <h2>Avis clients</h2>
                    <p class="subtitle">Total avis : <?php echo count($avisClients); ?></p>
                </div>
            </div>

            <?php if (empty($avisClients)): ?>
                <p class="etat-vide">Aucun avis client pour le moment.</p>
            <?php else: ?>
                <div class="users">
                    <?php foreach ($avisClients as $commande): ?>
                        <article class="user">
                            <div class="user-top">
                                <p class="user-name">
                                    Commande <?php echo e($commande['numero_commande'] ?? ''); ?>
                                </p>
                            </div>
                            <p class="user-meta"><strong>Restaurant :</strong> <?php echo e($commande['restaurant_nom'] ?? ''); ?></p>
                            <p class="user-meta"><strong>Client :</strong> <?php echo e($commande['client_nom'] ?? ''); ?></p>
                            <p class="user-meta"><strong>Date :</strong> <?php echo e($commande['heure_commande'] ?? ''); ?></p>
                            <div class="user-status-row">
                                <div class="badges">
                                    <span class="badge"><?php echo (int) ($commande['note'] ?? 0); ?>/5</span>
                                </div>
                            </div>
                            <?php if (($commande['commentaire_note'] ?? '') !== ''): ?>
                                <p class="user-meta"><strong>Commentaire :</strong> <?php echo e($commande['commentaire_note']); ?></p>
                            <?php endif; ?>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    </main>

    <footer class="site-footer">
        <p>&copy; 2026 Pasta La Vista - Restaurant italien.</p>
    </footer>
    <script>
    window.CSRF_TOKEN = <?= json_encode(genererTokenCsrf()) ?>;
    </script>
    <script src="js/admin_blocage.js"></script>
    <script src="js/darkmode.js"></script>
    <script src="js/session_surveillance.js"></script>
</body>
</html>
