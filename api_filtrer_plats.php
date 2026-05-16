<?php
header('Content-Type: application/json');

$plats = file_exists(__DIR__.'/data/plats.json') ? json_decode(file_get_contents(__DIR__.'/data/plats.json'), true) : [];
$menus = file_exists(__DIR__.'/data/menu.json')  ? json_decode(file_get_contents(__DIR__.'/data/menu.json'),  true) : [];

// Normalise : minuscules + supprime accents + supprime tirets et espaces
function normaliser(string $s): string {
    $s = mb_strtolower($s, 'UTF-8');
    $s = iconv('UTF-8', 'ASCII//TRANSLIT', $s);
    $s = preg_replace('/[-\s]+/', '', $s);
    return trim($s);
}

function normaliserTableau(array $t): array {
    return array_map('normaliser', $t);
}

$saveurs = isset($_GET['saveurs']) ? array_filter(array_map('normaliser', explode(',', $_GET['saveurs']))) : [];
$allergenes = isset($_GET['allergenes']) ? array_filter(array_map('normaliser', explode(',', $_GET['allergenes']))) : [];
$types = isset($_GET['types']) ? array_filter(array_map('normaliser', explode(',', $_GET['types']))) : [];

$platsFiltres = [];
foreach ($plats as $plat) {
    $saveursDuPlat = normaliserTableau($plat['informations']['saveurs']    ?? []);
    $allergenesDuPlat = normaliserTableau($plat['informations']['allergenes'] ?? []);
    $type = normaliser($plat['type'] ?? '');

    if (!empty($types) && !in_array($type, $types))                                continue;
    if (!empty($saveurs) && empty(array_intersect($saveurs, $saveursDuPlat)))         continue;
    if (!empty($allergenes) && !empty(array_intersect($allergenes, $allergenesDuPlat)))  continue;

    $platsFiltres[] = $plat;
}

$menusFiltres = [];
if (empty($types) || in_array('menu', $types)) {
    if (empty($saveurs) && empty($allergenes)) $menusFiltres = $menus;
}

echo json_encode([
    'succes' => true,
    'plats'  => array_values($platsFiltres),
    'menus'  => array_values($menusFiltres),
    'total'  => count($platsFiltres) + count($menusFiltres),
]);