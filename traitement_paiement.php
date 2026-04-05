<?php
session_start();
require_once __DIR__ . '/config/function.php';
require_once __DIR__ . '/config/getapikey.php'; 

if (!isset($_SESSION['user']) || empty($_SESSION['panier'])) {
    header('Location: panier.php');
    exit();
}


$_SESSION['options_commande'] = [
    'mode_retrait' => $_POST['mode_retrait'] ?? 'livraison',
    'moment_preparation' => $_POST['moment_preparation'] ?? 'immediat',
    'date_planifiee' => $_POST['date_planifiee'] ?? '',
    'heure_planifiee' => $_POST['heure_planifiee'] ?? ''
];

$montantTotal = 0;
foreach ($_SESSION['panier'] as $article) {
    $montantTotal += $article['prix'] * $article['quantite'];
}

$montantFormate = number_format($montantTotal, 2, '.', ''); 

$transaction = uniqid('PLV'); 
$vendeur = 'TEST'; 
$api_key = getAPIKey($vendeur); 


$estHttps = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on');
$protocol = $estHttps ? "https" : "http";
$host = $_SERVER['HTTP_HOST'];


$path = str_replace('\\', '/', dirname($_SERVER['PHP_SELF']));
if ($path === '/') {
    $path = '';
}

$retour = $protocol . "://" . $host . $path . "/retour_paiement.php";

$chaine_a_hacher = $api_key . "#" . $transaction . "#" . $montantFormate . "#" . $vendeur . "#" . $retour . "#";
$control = md5($chaine_a_hacher);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Redirection CYBank</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .loader-page { display: flex; flex-direction: column; align-items: center; justify-content: center; height: 100vh; text-align: center; }
        .spinner { border: 4px solid var(--line-soft); border-top: 4px solid var(--accent); border-radius: 50%; width: 50px; height: 50px; animation: spin 1s linear infinite; margin-bottom: 20px;}
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
    </style>
</head>
<body class="page-carte loader-page" onload="document.getElementById('cybank_form').submit();">
    <div class="spinner"></div>
    <h2 style="font-family: 'Cormorant Garamond', serif;">Redirection vers CYBank...</h2>
    <p style="color: var(--muted);">Veuillez patienter pendant la sécurisation de votre paiement.</p>

    <form id="cybank_form" action="https://www.plateforme-smc.fr/cybank/index.php" method="POST" style="display: none;">
        <input type="hidden" name="transaction" value="<?= $transaction ?>">
        <input type="hidden" name="montant" value="<?= $montantFormate ?>">
        <input type="hidden" name="vendeur" value="<?= $vendeur ?>">
        <input type="hidden" name="retour" value="<?= $retour ?>">
        <input type="hidden" name="control" value="<?= $control ?>">
    </form>
</body>
</html>