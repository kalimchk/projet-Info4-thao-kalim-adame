<?php
session_start();
require_once __DIR__ . '/config/function.php';
require_once __DIR__ . '/config/getapikey.php';

$utilisateurConnecte = obtenirUtilisateurConnecteOuRediriger('connexion.php');
$transaction = (string) ($_GET['transaction'] ?? '');
$paiementEnAttente = $transaction !== '' ? trouverPaiementEnAttenteParTransaction($transaction) : null;

if (
    $paiementEnAttente === null
    || ($paiementEnAttente['type_paiement'] ?? '') !== 'complement_commande'
    || (int) ($paiementEnAttente['utilisateur']['id'] ?? 0) !== (int) ($utilisateurConnecte['id'] ?? 0)
) {
    header('Location: profil.php');
    exit();
}

$montantFormate = number_format((float) ($paiementEnAttente['montant'] ?? 0), 2, '.', '');
$vendeur = (string) ($paiementEnAttente['vendeur'] ?? 'TEST');
$api_key = getAPIKey($vendeur);

if (!preg_match('/^[0-9a-zA-Z]{10,24}$/', $transaction) || !is_numeric($montantFormate) || !preg_match('/^[0-9a-zA-Z]{15}$/', $api_key)) {
    header('Location: profil.php');
    exit();
}

$estHttps = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on');
$protocol = $estHttps ? 'https' : 'http';
$host = preg_replace('/[^A-Za-z0-9.:\-]/', '', $_SERVER['HTTP_HOST'] ?? 'localhost');

$path = str_replace('\\', '/', dirname($_SERVER['PHP_SELF']));
if ($path === '/') {
    $path = '';
}

$retour = $protocol . '://' . $host . $path . '/retour_paiement.php';
$chaine_a_hacher = $api_key . '#' . $transaction . '#' . $montantFormate . '#' . $vendeur . '#' . $retour . '#';
$control = md5($chaine_a_hacher);
?>
<?php
$isDark = isset($_COOKIE['darkmode']) && $_COOKIE['darkmode'] === '1';
$darkClass = $isDark ? ' class="dark-mode"' : '';
?>
<!DOCTYPE html>
<html lang="fr"<?php echo $darkClass; ?>>
<head>
    <meta charset="UTF-8">
    <title>Complement de paiement - Pasta La Vista</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/darkmode.css">
    <style>
        .loader-page { display: flex; flex-direction: column; align-items: center; justify-content: center; height: 100vh; text-align: center; }
        .spinner { border: 4px solid var(--line-soft); border-top: 4px solid var(--accent); border-radius: 50%; width: 50px; height: 50px; animation: spin 1s linear infinite; margin-bottom: 20px;}
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
    </style>
</head>
<body class="page-carte loader-page" onload="document.getElementById('cybank_form').submit();">
    <div class="spinner"></div>
    <h2 style="font-family: 'Cormorant Garamond', serif;">Redirection vers CYBank...</h2>
    <p style="color: var(--muted);">Paiement du complement : <?= e($montantFormate) ?> EUR</p>

    <form id="cybank_form" action="https://www.plateforme-smc.fr/cybank/index.php" method="POST" style="display: none;">
        <input type="hidden" name="transaction" value="<?= e($transaction) ?>">
        <input type="hidden" name="montant" value="<?= e($montantFormate) ?>">
        <input type="hidden" name="vendeur" value="<?= e($vendeur) ?>">
        <input type="hidden" name="retour" value="<?= e($retour) ?>">
        <input type="hidden" name="control" value="<?= e($control) ?>">
    </form>
    <script src="js/darkmode.js"></script>
</body>
</html>
