<?php
header('Content-Type: application/json; charset=UTF-8');

$plats = lireFichierJsonFiltre(__DIR__ . '/data/plats.json');
$menus = lireFichierJsonFiltre(__DIR__ . '/data/menu.json');

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

    $remplacements = [
        'à' => 'a', 'â' => 'a', 'ä' => 'a', 'á' => 'a', 'ã' => 'a',
        'è' => 'e', 'é' => 'e', 'ê' => 'e', 'ë' => 'e',
        'î' => 'i', 'ï' => 'i', 'í' => 'i', 'ì' => 'i',
        'ô' => 'o', 'ö' => 'o', 'ó' => 'o', 'ò' => 'o', 'õ' => 'o',
        'ù' => 'u', 'û' => 'u', 'ü' => 'u', 'ú' => 'u',
        'ç' => 'c', 'ñ' => 'n', 'œ' => 'oe',
        'Ã ' => 'a', 'Ã¢' => 'a', 'Ã¤' => 'a', 'Ã¡' => 'a', 'Ã£' => 'a',
        'Ã¨' => 'e', 'Ã©' => 'e', 'Ãª' => 'e', 'Ã«' => 'e',
        'Ã®' => 'i', 'Ã¯' => 'i', 'Ã­' => 'i', 'Ã¬' => 'i',
        'Ã´' => 'o', 'Ã¶' => 'o', 'Ã³' => 'o', 'Ã²' => 'o', 'Ãµ' => 'o',
        'Ã¹' => 'u', 'Ã»' => 'u', 'Ã¼' => 'u', 'Ãº' => 'u',
        'Ã§' => 'c', 'Ã±' => 'n', 'Å“' => 'oe',
    ];

    $texte = strtr($texte, $remplacements);

    if (function_exists('iconv')) {
        $translitteration = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $texte);
        if ($translitteration !== false) {
            $texte = $translitteration;
        }
    }

    $texte = preg_replace('/[^a-z0-9]+/', '', $texte);
    return trim($texte);
}

function normaliserTableau(array $valeurs): array
{
    return array_values(array_filter(array_map('normaliser', $valeurs)));
}

$saveurs = isset($_GET['saveurs']) ? normaliserTableau(explode(',', $_GET['saveurs'])) : [];
$allergenes = isset($_GET['allergenes']) ? normaliserTableau(explode(',', $_GET['allergenes'])) : [];
$types = isset($_GET['types']) ? normaliserTableau(explode(',', $_GET['types'])) : [];

$platsFiltres = [];
foreach ($plats as $plat) {
    $saveursDuPlat = normaliserTableau($plat['informations']['saveurs'] ?? []);
    $allergenesDuPlat = normaliserTableau($plat['informations']['allergenes'] ?? []);
    $type = normaliser($plat['type'] ?? '');

    if (!empty($types) && !in_array($type, $types, true)) {
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
