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
$nom = trim($donnees['nom'] ?? '');
$prenom = trim($donnees['prenom'] ?? '');
$email = trim($donnees['email'] ?? '');
$telephone = trim($donnees['telephone'] ?? '');

if (!$nom || !$prenom || !$email || !$telephone) {
    echo json_encode(['succes' => false, 'message' => 'Tous les champs sont obligatoires.']);
    exit();
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['succes' => false, 'message' => 'Email invalide.']);
    exit();
}

$listeUtilisateurs = lireUtilisateurs();
$idConnecte = $utilisateurConnecte['id'];
$trouve = false;

foreach ($listeUtilisateurs as $i => $u) {
    if (($u['id'] ?? 0) === $idConnecte) {
        $listeUtilisateurs[$i]['nom'] = $nom;
        $listeUtilisateurs[$i]['prenom'] = $prenom;
        $listeUtilisateurs[$i]['email'] = $email;
        $listeUtilisateurs[$i]['telephone'] = $telephone;
        $trouve = true;
        break;
    }
}

if (!$trouve) {
    echo json_encode(['succes' => false, 'message' => 'Utilisateur introuvable.']);
    exit();
}

sauvegarderUtilisateurs($listeUtilisateurs);
$_SESSION['user']['nom'] = $nom;
$_SESSION['user']['prenom'] = $prenom;
$_SESSION['user']['email'] = $email;
$_SESSION['user']['telephone'] = $telephone;

echo json_encode(['succes' => true, 'message' => 'Profil mis a jour.', 'user' => compact('nom', 'prenom', 'email', 'telephone')]);
