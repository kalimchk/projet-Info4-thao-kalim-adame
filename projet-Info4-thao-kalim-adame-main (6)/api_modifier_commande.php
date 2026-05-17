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

if (!$idCmd || !$article) {
    echo json_encode(['succes' => false, 'message' => 'Donnees manquantes.']);
    exit();
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
    echo json_encode(['succes' => false, 'message' => 'Commande introuvable.']);
    exit();
}

$cmd = $commandes[$idx];
$nomComplet = trim($utilisateurConnecte['prenom'] . ' ' . $utilisateurConnecte['nom']);
if (($cmd['client_nom'] ?? '') !== $nomComplet) {
    echo json_encode(['succes' => false, 'message' => 'Acces refuse.']);
    exit();
}
if (($cmd['statut_commande'] ?? '') !== 'a_preparer') {
    echo json_encode(['succes' => false, 'message' => 'Commande non modifiable.']);
    exit();
}

$ancienMontant = calculerMontantTotalCommande($cmd['articles'] ?? []);
$articles = $cmd['articles'];
$typeAction = $article['type_action'] ?? 'ajouter';
$nomProduit = trim($article['nom_produit'] ?? '');
$prix = (float) ($article['prix_unitaire'] ?? 0);

if ($typeAction === 'ajouter') {
    $trouve = false;
    foreach ($articles as $i => $a) {
        if (strtolower($a['nom_produit'] ?? '') === strtolower($nomProduit)) {
            $articles[$i]['quantite'] += $quantite;
            $trouve = true;
            break;
        }
    }
    if (!$trouve) {
        $articles[] = ['nom_produit' => $nomProduit, 'quantite' => $quantite, 'prix_unitaire' => $prix];
    }
} elseif ($typeAction === 'retirer') {
    foreach ($articles as $i => $a) {
        if (strtolower($a['nom_produit'] ?? '') === strtolower($nomProduit)) {
            $articles[$i]['quantite'] -= $quantite;
            if ($articles[$i]['quantite'] <= 0) {
                array_splice($articles, $i, 1);
            }
            break;
        }
    }
}

$nouveauMontant = calculerMontantTotalCommande($articles);
$difference = $nouveauMontant - $ancienMontant;

$commandes[$idx]['articles'] = $articles;
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
    'paiement_requis' => $difference > 0,
    'ticket_reduction' => $ticket,
    'message' => $difference > 0
        ? 'Commande plus chere de ' . number_format($difference, 2, ',', '') . ' EUR. Un paiement supplementaire est requis.'
        : ($difference < 0 ? $ticket['message'] : 'Commande mise a jour.'),
]);
