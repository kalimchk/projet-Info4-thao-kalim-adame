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
    refuserRequeteJson('Requete invalide.');
}

if (!$idCmd || !$article) {
    refuserRequeteJson('Donnees manquantes.');
}

if ($quantite < 1 || $quantite > 10) {
    refuserRequeteJson('Quantite invalide.');
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
    refuserRequeteJson('Acces refuse.');
}
if (($cmd['statut_commande'] ?? '') !== 'a_preparer') {
    refuserRequeteJson('Commande non modifiable.');
}

$ancienMontant = calculerMontantTotalCommande($cmd['articles'] ?? []);
$articles = is_array($cmd['articles'] ?? null) ? $cmd['articles'] : [];
$typeAction = $article['type_action'] ?? 'ajouter';
$nomProduit = trim($article['nom_produit'] ?? '');

if (!in_array($typeAction, ['ajouter', 'retirer'], true) || $nomProduit === '') {
    refuserRequeteJson('Action invalide.');
}

$indexArticleCible = -1;
foreach ($articles as $i => $a) {
    if (strtolower($a['nom_produit'] ?? '') === strtolower($nomProduit)) {
        $indexArticleCible = $i;
        break;
    }
}

if ($indexArticleCible === -1) {
    refuserRequeteJson('Article introuvable dans la commande.');
}

if ($typeAction === 'ajouter') {
    $articles[$indexArticleCible]['quantite'] += $quantite;
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
    refuserRequeteJson('Une commande ne peut pas etre vide.');
}

if ($nouveauMontant > $montantPaye) {
    refuserRequeteJson('Cette modification augmente le montant deja paye. Elle est refusee pour eviter un contournement du paiement.');
}

$commandes[$idx]['articles'] = $articles;
$commandes[$idx]['montant_paye'] = $montantPaye;
sauvegarderCommandes($commandes);

$ticket = null;
if ($difference < 0) {
    $ticket = ['montant' => abs($difference), 'message' => 'Ticket de reduction de ' . number_format(abs($difference), 2, ',', '') . ' EUR sur votre prochaine commande.'];
}

echo json_encode([
    'succes' => true,
    'articles' => array_values($articles),
    'ancien_montant' => $ancienMontant,
    'nouveau_montant' => $nouveauMontant,
    'difference' => $difference,
    'paiement_requis' => false,
    'ticket_reduction' => $ticket,
    'message' => $difference < 0 ? $ticket['message'] : 'Commande mise a jour.',
]);
