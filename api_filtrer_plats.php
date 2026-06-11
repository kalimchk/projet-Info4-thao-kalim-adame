<?php
header('Content-Type: application/json; charset=UTF-8');

function lireFichierJsonFiltre(string $chemin): array
{
    if (!file_exists($chemin)) {
        return [];
    }

    $donnees = json_decode(file_get_contents($chemin), true);
    return is_array($donnees) ? $donnees : [];
}

function normaliser(string $texte): string
{
    $texte = strtolower(trim($texte));
    return preg_replace('/[^a-z0-9]+/', '', $texte);
}

function normaliserTableau(array $valeurs): array
{
    return array_values(array_filter(array_map('normaliser', $valeurs)));
}

$plats = lireFichierJsonFiltre(__DIR__ . '/data/plats.json');
$menus = lireFichierJsonFiltre(__DIR__ . '/data/menu.json');

$saveurs = isset($_GET['saveurs']) ? normaliserTableau(explode(',', $_GET['saveurs'])) : [];
$allergenes = isset($_GET['allergenes']) ? normaliserTableau(explode(',', $_GET['allergenes'])) : [];
$types = isset($_GET['types']) ? normaliserTableau(explode(',', $_GET['types'])) : [];

$platsFiltres = [];

foreach ($plats as $plat) {
    $saveursDuPlat = normaliserTableau($plat['informations']['saveurs'] ?? []);
    $allergenesDuPlat = normaliserTableau($plat['informations']['allergenes'] ?? []);
    $typeDuPlat = normaliser($plat['type'] ?? '');

    if (!empty($types) && !in_array($typeDuPlat, $types, true)) {
        continue;
    }

    if (!empty($saveurs) && empty(array_intersect($saveurs, $saveursDuPlat))) {
        continue;
    }

    if (!empty($allergenes) && !empty(array_intersect($allergenes, $allergenesDuPlat))) {
        continue;
    }

    $platsFiltres[] = $plat;
}

$menusFiltres = [];
if ((empty($types) || in_array('menu', $types, true)) && empty($saveurs) && empty($allergenes)) {
    $menusFiltres = $menus;
}

echo json_encode([
    'succes' => true,
    'plats' => array_values($platsFiltres),
    'menus' => array_values($menusFiltres),
    'total' => count($platsFiltres) + count($menusFiltres),
], JSON_UNESCAPED_UNICODE);
