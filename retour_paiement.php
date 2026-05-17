<?php
session_start();
require_once __DIR__ . '/config/function.php';
require_once __DIR__ . '/config/getapikey.php';
verifierEtatSessionUtilisateur();

$transaction = $_GET['transaction'] ?? '';
$montant = $_GET['montant'] ?? '';
$vendeur = $_GET['vendeur'] ?? '';
$statut = $_GET['statut'] ?? ($_GET['status'] ?? '');
$control_recu = $_GET['control'] ?? '';

$api_key = getAPIKey($vendeur);
$chaine_verif = $api_key . '#' . $transaction . '#' . $montant . '#' . $vendeur . '#' . $statut . '#';
$control_calcule = md5($chaine_verif);

$paiementReussi = false;
$messageErreur = '';
$utilisateur = null;

if ($control_calcule === $control_recu) {
    if ($statut === 'accepted') {
        $paiementEnAttente = trouverPaiementEnAttenteParTransaction($transaction);

        if ($paiementEnAttente === null) {
            $messageErreur = 'Paiement valide mais commande introuvable.';
        } else {
            $paiementReussi = true;
            $utilisateur = $paiementEnAttente['utilisateur'];
            $options = $paiementEnAttente['options_commande'] ?? [];
            $listeCommandes = lireCommandes();
            $nouvelId = count($listeCommandes) > 0 ? max(array_column($listeCommandes, 'id')) + 1 : 101;
            $articlesCommande = $paiementEnAttente['articles'] ?? [];

            $texteCommentaire = 'Mode : ' . ucfirst($options['mode_retrait'] ?? 'livraison') . '. ';
            if (($options['moment_preparation'] ?? '') === 'planifie') {
                $texteCommentaire .= 'A PREPARER POUR LE : ' . ($options['date_planifiee'] ?? '') . ' a ' . ($options['heure_planifiee'] ?? '');
            } else {
                $texteCommentaire .= 'Preparation immediate requise.';
            }

            $nouvelleCommande = [
                'id' => $nouvelId,
                'restaurant_id' => 1,
                'restaurant_nom' => 'Pasta La Vista',
                'numero_commande' => 'PLV-' . $nouvelId,
                'statut_commande' => 'a_preparer',
                'heure_commande' => date('Y-m-d H:i:s'),
                'client_nom' => ($utilisateur['prenom'] ?? '') . ' ' . ($utilisateur['nom'] ?? ''),
                'client_telephone' => $utilisateur['telephone'] ?? '',
                'adresse_livraison' => 'Adresse liee au compte client',
                'commentaire_client' => $texteCommentaire,
                'temps_estime' => 'En attente',
                'articles' => $articlesCommande
            ];

            $listeCommandes[] = $nouvelleCommande;
            sauvegarderCommandes($listeCommandes);
            supprimerPaiementEnAttenteParTransaction($transaction);

            unset($_SESSION['panier']);
            unset($_SESSION['options_commande']);
        }
    } else {
        supprimerPaiementEnAttenteParTransaction($transaction);
        $messageErreur = "Le paiement a ete refuse par la banque ou annule par l'utilisateur.";
    }
} else {
    $messageErreur = 'Erreur de securite : la signature des donnees provenant de la banque est invalide.';
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
    <title>Retour de Paiement - Pasta La Vista</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/darkmode.css">
</head>
<body class="page-carte" data-surveillance-session="<?php echo isset($_SESSION['user']) ? '1' : '0'; ?>">
    <?php include 'navbar.php'; ?>

    <main style="max-width: 600px; margin: 60px auto; text-align: center;">
        <section class="panel">
            <?php if ($paiementReussi): ?>
                <h2 style="color: var(--accent-deep);">Paiement reussi !</h2>
                <p class="intro">Merci pour votre commande, <strong><?= htmlspecialchars($utilisateur['prenom'] ?? '') ?></strong>.</p>
                <p>Votre commande a ete transmise a nos cuisines et son statut est passe a "A preparer".</p>
                <div style="margin-top: 30px;">
                    <a href="profil.php" class="btn" style="padding: 12px 24px; border-radius: 999px; background: var(--accent); color: var(--bg); text-decoration: none;">Suivre ma commande</a>
                </div>
            <?php else: ?>
                <h2 style="color: #a45742;">Echec du paiement</h2>
                <p class="intro"><?= htmlspecialchars($messageErreur) ?></p>
                <p>Vous pouvez revenir au panier pour verifier votre commande.</p>
                <div style="margin-top: 30px;">
                    <a href="panier.php" class="btn" style="padding: 12px 24px; border-radius: 999px; background: var(--muted); color: var(--bg); text-decoration: none;">Retour au panier</a>
                </div>
            <?php endif; ?>
        </section>
    </main>

    <footer class="site-footer">
        <p>&copy; 2026 Pasta La Vista - Restaurant italien.</p>
    </footer>
    <script src="js/darkmode.js"></script>
    <script src="js/session_surveillance.js"></script>
</body>
</html>