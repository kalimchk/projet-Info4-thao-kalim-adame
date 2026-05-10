<?php
/**
 * darkmode_head.php
 * À inclure dans le <head> de chaque page, AVANT le <body>.
 * Lit le cookie PHP-side pour pré-appliquer la classe sans flash (FOUC).
 */

$isDark = isset($_COOKIE['darkmode']) && $_COOKIE['darkmode'] === '1';
$darkClass = $isDark ? ' class="dark-mode"' : '';
?>
<!DOCTYPE html>
<html lang="fr"<?php echo $darkClass; ?>>
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Ma page</title>

  <!-- Dark mode CSS en premier pour éviter tout flash -->
  <link rel="stylesheet" href="/css/darkmode.css">

  <!--
    IMPORTANT : le script doit être chargé avec defer ou en fin de body.
    La classe dark-mode est déjà appliquée côté PHP sur le <html>,
    donc pas besoin de l'appliquer en inline JS.
  -->
</head>

<body>

  <!-- Ton contenu ici -->
  <h1>Bonjour !</h1>
  <p>Voici une page avec dark mode persistant.</p>

  <!-- Script dark mode AVANT </body> -->
  <script src="/js/darkmode.js"></script>
</body>
</html>
