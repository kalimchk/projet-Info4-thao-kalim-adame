<?php
header('Content-Type: application/json; charset=UTF-8');

$cheminPlats = __DIR__ . '/data/plats.json';
$cheminMenus = __DIR__ . '/data/menu.json';

$plats = [];
if (file_exists($cheminPlats)) {
    $donneesPlats = json_decode(file_get_contents($cheminPlats), true);
    if (is_array($donneesPlats)) {
        $plats = $donneesPlats;
    }
}

$menus = [];
if (file_exists($cheminMenus)) {
    $donneesMenus = json_decode(file_get_contents($cheminMenus), true);
    if (is_array($donneesMenus)) {
        $menus = $donneesMenus;
    }
}

$saveurs = [];
if (isset($_GET['saveurs'])) {
    foreach (explode(',', $_GET['saveurs']) as $saveur) {
        $saveur = preg_replace('/[^a-z0-9]+/', '', strtolower(trim($saveur)));
        if ($saveur !== '') {
            $saveurs[] = $saveur;
        }
    }
}

$allergenes = [];
if (isset($_GET['allergenes'])) {
    foreach (explode(',', $_GET['allergenes']) as $allergene) {
        $allergene = preg_replace('/[^a-z0-9]+/', '', strtolower(trim($allergene)));
        if ($allergene !== '') {
            $allergenes[] = $allergene;
        }
    }
}

$types = [];
if (isset($_GET['types'])) {
    foreach (explode(',', $_GET['types']) as $type) {
        $type = preg_replace('/[^a-z0-9]+/', '', strtolower(trim($type)));
        if ($type !== '') {
            $types[] = $type;
        }
    }
}

$platsFiltres = [];

foreach ($plats as $plat) {
    $saveursDuPlat = [];
    foreach (($plat['informations']['saveurs'] ?? []) as $saveurDuPlat) {
        $saveurDuPlat = preg_replace('/[^a-z0-9]+/', '', strtolower(trim($saveurDuPlat)));
        if ($saveurDuPlat !== '') {
            $saveursDuPlat[] = $saveurDuPlat;
        }
    }

    $allergenesDuPlat = [];
    foreach (($plat['informations']['allergenes'] ?? []) as $allergeneDuPlat) {
        $allergeneDuPlat = preg_replace('/[^a-z0-9]+/', '', strtolower(trim($allergeneDuPlat)));
        if ($allergeneDuPlat !== '') {
            $allergenesDuPlat[] = $allergeneDuPlat;
        }
    }

    $typeDuPlat = preg_replace('/[^a-z0-9]+/', '', strtolower(trim($plat['type'] ?? '')));

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
