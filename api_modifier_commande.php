<?php
session_start();
require_once __DIR__ . '/config/function.php';
header('Content-Type: application/json');

$utilisateurConnecte = obtenirUtilisateurConnecteOuErreurJson();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['succes' => false, 'message' => 'Methode non autorisee.']);
    exit();
}

$donnees = json_decode(file_get_contents('php://input'), true);
$idCmd = (int) ($donnees['id_commande'] ?? 0);
$article = $donnees['article'] ?? null;
$quantite = (int) ($donnees['quantite'] ?? 1);

if (!verifierTokenCsrf($donnees['csrf_token'] ?? '')) {
    refuserRequeteJson('Requête invalide.');
}

if (!$idCmd || !$article) {
    refuserRequeteJson('Données manquantes.');
}

if ($quantite < 1 || $quantite > 10) {
    refuserRequeteJson('Quantité invalide.');
}

$commandes = lireCommandes();
$idx = -1;
foreach ($commandes as $i => $c) {
    if (($c['id'] ?? 0) === $idCmd) {
        $idx = $i;
        break;
    }
}

if ($idx === -1) {
    refuserRequeteJson('Commande introuvable.');
}

$cmd = $commandes[$idx];
$nomComplet = trim($utilisateurConnecte['prenom'] . ' ' . $utilisateurConnecte['nom']);
$commandeAppartientUtilisateur = false;
if (isset($cmd['client_id'])) {
    $commandeAppartientUtilisateur = (int) ($cmd['client_id'] ?? 0) === (int) ($utilisateurConnecte['id'] ?? 0);
} else {
    $commandeAppartientUtilisateur = ($cmd['client_nom'] ?? '') === $nomComplet;
}

if (!$commandeAppartientUtilisateur) {
    refuserRequeteJson('Accès refusé.');
}
if (($cmd['statut_commande'] ?? '') !== 'a_preparer') {
    refuserRequeteJson('Commande non modifiable.');
}

$ancienMontant = calculerMontantTotalCommande($cmd['articles'] ?? []);
$articles = is_array($cmd['articles'] ?? null) ? $cmd['articles'] : [];
$typeAction = $article['type_action'] ?? 'ajouter';
$nomProduit = trim($article['nom_produit'] ?? '');
$produitId = trim((string) ($article['produit_id'] ?? ''));
$typeProduit = trim((string) ($article['type_produit'] ?? ($article['type'] ?? '')));

if (!in_array($typeAction, ['ajouter', 'retirer'], true) || ($nomProduit === '' && $produitId === '')) {
    refuserRequeteJson('Action invalide.');
}

$indexArticleCible = -1;
foreach ($articles as $i => $a) {
    $memeProduit = $produitId !== ''
        && (string) ($a['produit_id'] ?? '') === $produitId
        && strtolower((string) ($a['type_produit'] ?? '')) === strtolower($typeProduit);

    if ($memeProduit || ($nomProduit !== '' && strtolower($a['nom_produit'] ?? '') === strtolower($nomProduit))) {
        $indexArticleCible = $i;
        break;
    }
}

if ($typeAction === 'retirer' && $indexArticleCible === -1) {
    refuserRequeteJson('Article introuvable dans la commande.');
}

if ($typeAction === 'ajouter') {
    if ($indexArticleCible !== -1) {
        $articles[$indexArticleCible]['quantite'] += $quantite;
    } else {
        $produitCatalogue = $produitId !== '' ? trouverProduitCatalogue($produitId, $typeProduit) : null;

        if ($produitCatalogue === null && $nomProduit !== '') {
            $produitCatalogue = trouverProduitCatalogueParNomEtType($nomProduit, $typeProduit);
        }

        if ($produitCatalogue === null) {
            refuserRequeteJson('Produit introuvable dans la carte.');
        }

        $articles[] = construireArticleCommandeDepuisProduit($produitCatalogue, $quantite);
    }
} elseif ($typeAction === 'retirer') {
    $articles[$indexArticleCible]['quantite'] -= $quantite;
    if ($articles[$indexArticleCible]['quantite'] <= 0) {
        array_splice($articles, $indexArticleCible, 1);
    }
}

$nouveauMontant = calculerMontantTotalCommande($articles);
$difference = $nouveauMontant - $ancienMontant;
$montantPaye = (float) ($cmd['montant_paye'] ?? $ancienMontant);

if (empty($articles)) {
    refuserRequeteJson('Une commande ne peut pas être vide.');
}

if ($nouveauMontant > $montantPaye) {
    $montantComplement = $nouveauMontant - $montantPaye;
    $transaction = 'PLVC' . date('ymdHis') . strtoupper(bin2hex(random_bytes(3)));

    enregistrerPaiementEnAttente([
        'transaction' => $transaction,
        'type_paiement' => 'complement_commande',
        'montant' => number_format($montantComplement, 2, '.', ''),
        'vendeur' => 'TEST',
        'utilisateur' => [
            'id' => (int) ($utilisateurConnecte['id'] ?? 0),
            'nom' => $utilisateurConnecte['nom'] ?? '',
            'prenom' => $utilisateurConnecte['prenom'] ?? '',
            'telephone' => $utilisateurConnecte['telephone'] ?? ''
        ],
        'commande_id' => $idCmd,
        'articles' => array_values($articles),
        'ancien_montant' => $ancienMontant,
        'nouveau_montant' => $nouveauMontant,
        'montant_paye_avant' => $montantPaye,
        'date_creation' => date('Y-m-d H:i:s')
    ]);

    echo json_encode([
        'succes' => true,
        'paiement_requis' => true,
        'articles' => array_values($cmd['articles'] ?? []),
        'ancien_montant' => $ancienMontant,
        'nouveau_montant' => $ancienMontant,
        'difference' => $montantComplement,
        'url_paiement' => 'paiement_complement_commande.php?transaction=' . urlencode($transaction),
        'message' => 'Un paiement complementaire de ' . number_format($montantComplement, 2, ',', '') . ' EUR est necessaire.'
    ]);
    exit();
}

$commandes[$idx]['articles'] = $articles;
$commandes[$idx]['montant_paye'] = $montantPaye;
sauvegarderCommandes($commandes);

$messageModification = 'Commande mise à jour.';
if ($difference < 0) {
    $messageModification = 'Commande mise à jour. Le nouveau total est inférieur de ' . number_format(abs($difference), 2, ',', '') . ' EUR.';
}

echo json_encode([
    'succes' => true,
    'articles' => array_values($articles),
    'ancien_montant' => $ancienMontant,
    'nouveau_montant' => $nouveauMontant,
    'difference' => $difference,
    'message' => $messageModification,
]);
