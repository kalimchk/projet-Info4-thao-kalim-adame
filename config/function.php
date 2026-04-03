<?php

function lireUtilisateurs() {
    $fichier = __DIR__ . '/../data/utilisateurs.json';

    if (!file_exists($fichier)) {
        return [];
    }

    $json = file_get_contents($fichier);
    return json_decode($json, true) ?? [];
}

function sauvegarderUtilisateurs($users) {
    $fichier = __DIR__ . '/../data/utilisateurs.json';
    file_put_contents($fichier, json_encode($users, JSON_PRETTY_PRINT));
}

function ajouterUtilisateur($nom, $prenom, $email, $telephone, $password) {
    $users = lireUtilisateurs();

    $users[] = [
        "id" => count($users) + 1,
        "nom" => $nom,
        "prenom" => $prenom,
        "email" => $email,
        "telephone" => $telephone,
        "password" => password_hash($password, PASSWORD_DEFAULT),
        "statut" => "client"
    ];

    sauvegarderUtilisateurs($users);
}

function trouverUtilisateur($email) {
    $users = lireUtilisateurs();

    foreach ($users as $user) {
        if ($user['email'] === $email) {
            return $user;
        }
    }

    return null;
}