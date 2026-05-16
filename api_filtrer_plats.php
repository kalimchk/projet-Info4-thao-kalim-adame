<?php
header('Content-Type: application/json');

$plats=file_exists(__DIR__.'/data/plats.json') ? json_decode(file_get_contents(__DIR__.'/data/plats.json'), true) : [];
$menus=file_exists(__DIR__.'/data/menu.json') ? json_decode(file_get_contents(__DIR__.'/data/menu.json'),  true) : [];

$saveurs=isset($_GET['saveurs']) ? array_filter(array_map('strtolower', explode(',', $_GET['saveurs']))) : [];
$allergenes=isset($_GET['allergenes']) ? array_filter(array_map('strtolower', explode(',', $_GET['allergenes']))) : [];
$types=isset($_GET['types']) ? array_filter(array_map('strtolower', explode(',', $_GET['types']))) : [];

$platsFiltres = [];
foreach ($plats as $plat) {
    $saveursDuPlat=array_map('strtolower', $plat['informations']['saveurs'] ?? []);
    $allergenesDuPlat=array_map('strtolower', $plat['informations']['allergenes'] ?? []);
    $type=strtolower($plat['type'] ?? '');

    if (!empty($types) && !in_array($type, $types)){
        continue;
    }
    if (!empty($saveurs) && empty(array_intersect($saveurs, $saveursDuPlat))){
        continue;
    }
    if (!empty($allergenes) && !empty(array_intersect($allergenes, $allergenesDuPlat))){
        continue;
    }
    $platsFiltres[] = $plat;
}

$menusFiltres = [];
if (empty($types) || in_array('menu', $types)) {
    if (empty($saveurs) && empty($allergenes)) $menusFiltres = $menus;
}

echo json_encode(['succes'=>true,'plats'=>array_values($platsFiltres),'menus'=>array_values($menusFiltres),'total'=>count($platsFiltres)+count($menusFiltres)]);