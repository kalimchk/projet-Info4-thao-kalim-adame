<?php
session_start();
require_once __DIR__ . '/config/function.php';
header('Content-Type: application/json');

$utilisateurConnecte = obtenirUtilisateurConnecteOuErreurJson();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['succes' => false, 'message' => 'Methode non autorisee.']);
    exit();
}

if (($utilisateurConnecte['statut'] ?? '') !== 'livreur') {
    echo json_encode(['succes' => false, 'message' => 'Acces refuse : vous n etes pas livreur.']);
    exit();
}

$donnees = json_decode(file_get_contents('php://input'), true);
$idCommande = (int) ($donnees['id_commande'] ?? 0);
$nouveauStatut = trim($donnees['nouveau_statut'] ?? '');
$motifAbandon = trim($donnees['motif_abandon'] ?? '');

if ($idCommande <= 0) {
    echo json_encode(['succes' => false, 'message' => 'Identifiant de commande manquant.']);
    exit();
}

$statutsAutorises = ['livree', 'abandonnee'];
if (!in_array($nouveauStatut, $statutsAutorises, true)) {
    echo json_encode(['succes' => false, 'message' => 'Statut non autorise pour le livreur.']);
    exit();
}

$listeDesCommandes = lireCommandes();
$indexCommande = -1;

foreach ($listeDesCommandes as $index => $commande) {
    if (($commande['id'] ?? 0) === $idCommande) {
        $indexCommande = $index;
        break;
    }
}

if ($indexCommande === -1) {
    echo json_encode(['succes' => false, 'message' => 'Commande introuvable.']);
    exit();
}

$commandeCible = $listeDesCommandes[$indexCommande];


$identifiantLivreur = (int) ($utilisateurConnecte['id'] ?? 0);
if (($commandeCible['livreur_id'] ?? 0) !== $identifiantLivreur) {
    echo json_encode(['succes' => false, 'message' => 'Cette commande ne vous est pas attribuee.']);
    exit();
}


if (($commandeCible['statut_commande'] ?? '') !== 'en_livraison') {
    echo json_encode(['succes' => false, 'message' => 'Cette commande n est pas en cours de livraison.']);
    exit();
}

$listeDesCommandes[$indexCommande]['statut_commande'] = $nouveauStatut;

if ($nouveauStatut === 'abandonnee' && $motifAbandon !== '') {
    $motifsValides = ['adresse_introuvable', 'client_absent', 'acces_impossible'];
    if (in_array($motifAbandon, $motifsValides, true)) {
        $listeDesCommandes[$indexCommande]['motif_abandon'] = $motifAbandon;
    }
}

$listeDesCommandes[$indexCommande]['heure_fin_livraison'] = date('Y-m-d H:i:s');

sauvegarderCommandes($listeDesCommandes);

$message = $nouveauStatut === 'livree'
    ? 'Livraison confirmee avec succes.'
    : 'Livraison marquee comme abandonnee.';

echo json_encode([
    'succes' => true,
    'nouveau_statut' => $nouveauStatut,
    'message' => $message,
]);
