<?php
session_start();
require_once __DIR__ . '/config/function.php';
header('Content-Type: application/json');

$utilisateurConnecte = obtenirUtilisateurConnecteOuErreurJson();

if (($utilisateurConnecte['statut'] ?? '') !== 'admin') {
    echo json_encode([
        'succes' => false,
        'message' => 'Acces refuse.',
    ]);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'succes' => false,
        'message' => 'Methode non autorisee.',
    ]);
    exit();
}

$donnees = json_decode(file_get_contents('php://input'), true);
$identifiantUtilisateur = (int) ($donnees['user_id'] ?? 0);
$action = trim((string) ($donnees['action'] ?? ''));

if ($identifiantUtilisateur <= 0 || !in_array($action, ['bloquer', 'debloquer'], true)) {
    echo json_encode([
        'succes' => false,
        'message' => 'Donnees invalides.',
    ]);
    exit();
}

if ($identifiantUtilisateur === (int) ($utilisateurConnecte['id'] ?? 0)) {
    echo json_encode([
        'succes' => false,
        'message' => 'Vous ne pouvez pas modifier votre propre compte.',
    ]);
    exit();
}

$listeDesUtilisateurs = lireUtilisateurs();
$utilisateurMisAJour = null;

foreach ($listeDesUtilisateurs as $indexUtilisateur => $utilisateur) {
    if ((int) ($utilisateur['id'] ?? 0) === $identifiantUtilisateur) {
        $listeDesUtilisateurs[$indexUtilisateur]['est_bloque'] = $action === 'bloquer';
        $utilisateurMisAJour = normaliserUtilisateur($listeDesUtilisateurs[$indexUtilisateur]);
        break;
    }
}

if ($utilisateurMisAJour === null) {
    echo json_encode([
        'succes' => false,
        'message' => 'Utilisateur introuvable.',
    ]);
    exit();
}

sauvegarderUtilisateurs($listeDesUtilisateurs);

echo json_encode([
    'succes' => true,
    'message' => $action === 'bloquer'
        ? 'Utilisateur bloque.'
        : 'Utilisateur debloque.',
    'utilisateur' => [
        'id' => (int) ($utilisateurMisAJour['id'] ?? 0),
        'est_bloque' => (bool) ($utilisateurMisAJour['est_bloque'] ?? false),
    ],
]);
