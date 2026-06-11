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
$nom = substr(trim($donnees['nom'] ?? ''), 0, 60);
$prenom = substr(trim($donnees['prenom'] ?? ''), 0, 60);
$email = substr(normaliserEmail($donnees['email'] ?? ''), 0, 100);
$telephone = substr(trim($donnees['telephone'] ?? ''), 0, 20);
$csrfToken = $donnees['csrf_token'] ?? '';

if (!verifierTokenCsrf($csrfToken)) {
    refuserRequeteJson('Requête invalide.');
}

if (!$nom || !$prenom || !$email || !$telephone) {
    refuserRequeteJson('Tous les champs sont obligatoires.');
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    refuserRequeteJson('Email invalide.');
}

if (!telephoneValide($telephone)) {
    refuserRequeteJson('Téléphone invalide.');
}

$listeUtilisateurs = lireUtilisateurs();
$idConnecte = (int) ($utilisateurConnecte['id'] ?? 0);
$trouve = false;

foreach ($listeUtilisateurs as $i => $u) {
    if (normaliserEmail($u['email'] ?? '') === $email && (int) ($u['id'] ?? 0) !== $idConnecte) {
        refuserRequeteJson('Cette adresse email est déjà utilisée.');
    }

    if ((int) ($u['id'] ?? 0) === $idConnecte) {
        $listeUtilisateurs[$i]['nom'] = $nom;
        $listeUtilisateurs[$i]['prenom'] = $prenom;
        $listeUtilisateurs[$i]['email'] = $email;
        $listeUtilisateurs[$i]['telephone'] = $telephone;
        $trouve = true;
        break;
    }
}

if (!$trouve) {
    refuserRequeteJson('Utilisateur introuvable.');
}

sauvegarderUtilisateurs($listeUtilisateurs);
$_SESSION['user']['nom'] = $nom;
$_SESSION['user']['prenom'] = $prenom;
$_SESSION['user']['email'] = $email;
$_SESSION['user']['telephone'] = $telephone;

echo json_encode(['succes' => true, 'message' => 'Profil mis à jour.', 'user' => compact('nom', 'prenom', 'email', 'telephone')]);
