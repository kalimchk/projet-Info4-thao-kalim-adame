<?php
session_start();
require_once __DIR__ . '/config/function.php';
header('Content-Type: application/json');

$etatDeSession = verifierEtatSessionUtilisateur();

echo json_encode([
    'connecte' => ($etatDeSession['etat'] ?? '') === 'ok',
    'compte_bloque' => ($etatDeSession['etat'] ?? '') === 'bloque',
    'message' => ($etatDeSession['etat'] ?? '') === 'bloque'
        ? 'Votre compte a ete bloque par un administrateur.'
        : '',
]);
