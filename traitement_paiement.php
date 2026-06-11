<?php
session_start();
require_once __DIR__ . '/config/function.php';
require_once __DIR__ . '/config/getapikey.php';

$utilisateurConnecte = obtenirUtilisateurConnecteOuRediriger('connexion.php');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verifierTokenCsrf($_POST['csrf_token'] ?? '')) {
    header('Location: panier.php');
    exit();
}

if (empty($_SESSION['panier'])) {
    header('Location: panier.php');
    exit();
}

$modesRetraitAutorises = ['livraison', 'emporter'];
$momentsAutorises = ['immediat', 'planifie'];
$modeRetrait = in_array($_POST['mode_retrait'] ?? '', $modesRetraitAutorises, true)
    ? $_POST['mode_retrait']
    : 'livraison';
$momentPreparation = in_array($_POST['moment_preparation'] ?? '', $momentsAutorises, true)
    ? $_POST['moment_preparation']
    : 'immediat';
$datePlanifiee = trim($_POST['date_planifiee'] ?? '');
$heurePlanifiee = trim($_POST['heure_planifiee'] ?? '');

if ($momentPreparation === 'planifie') {
    $dateValide = preg_match('/^\d{4}-\d{2}-\d{2}$/', $datePlanifiee) === 1 && $datePlanifiee >= date('Y-m-d');
    $heureValide = preg_match('/^\d{2}:\d{2}$/', $heurePlanifiee) === 1;

    if (!$dateValide || !$heureValide) {
        header('Location: panier.php');
        exit();
    }
} else {
    $datePlanifiee = '';
    $heurePlanifiee = '';
}

$_SESSION['options_commande'] = [
    'mode_retrait' => $modeRetrait,
    'moment_preparation' => $momentPreparation,
    'date_planifiee' => $datePlanifiee,
    'heure_planifiee' => $heurePlanifiee
];

$montantTotal = 0;
$articlesCommande = [];

foreach ($_SESSION['panier'] as $article) {
    $produit = trouverProduitCatalogue(
        (string) ($article['produit_id'] ?? ''),
        (string) ($article['type'] ?? '')
    );

    if ($produit === null) {
        $produit = trouverProduitCatalogueParNomEtType(
            (string) ($article['nom'] ?? ''),
            (string) ($article['type'] ?? '')
        );
    }

    if ($produit === null) {
        header('Location: panier.php');
        exit();
    }

    $quantite = max(1, (int) ($article['quantite'] ?? 1));
    $articleCommande = construireArticleCommandeDepuisProduit($produit, $quantite);
    $articlesCommande[] = $articleCommande;
    $montantTotal += $articleCommande['prix_unitaire'] * $articleCommande['quantite'];
}

$montantFormate = number_format($montantTotal, 2, '.', '');

if ($montantTotal <= 0) {
    header('Location: panier.php');
    exit();
}

$transaction = 'PLV' . date('ymdHis') . strtoupper(bin2hex(random_bytes(3)));
$vendeur = 'TEST';
$api_key = getAPIKey($vendeur);

if (!preg_match('/^[0-9a-zA-Z]{10,24}$/', $transaction) || !is_numeric($montantFormate) || !preg_match('/^[0-9a-zA-Z]{15}$/', $api_key)) {
    header('Location: panier.php');
    exit();
}

enregistrerPaiementEnAttente([
    'transaction' => $transaction,
    'montant' => $montantFormate,
    'vendeur' => $vendeur,
    'utilisateur' => [
        'id' => (int) ($utilisateurConnecte['id'] ?? 0),
        'nom' => $utilisateurConnecte['nom'] ?? '',
        'prenom' => $utilisateurConnecte['prenom'] ?? '',
        'telephone' => $utilisateurConnecte['telephone'] ?? ''
    ],
    'options_commande' => $_SESSION['options_commande'],
    'articles' => $articlesCommande,
    'date_creation' => date('Y-m-d H:i:s')
]);

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
    <title>Redirection CYBank</title>
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
    <p style="color: var(--muted);">Veuillez patienter pendant la securisation de votre paiement.</p>

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
