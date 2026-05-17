<?php

$isDark = isset($_COOKIE['darkmode']) && $_COOKIE['darkmode'] === '1';
$darkClass = $isDark ? ' class="dark-mode"' : '';
?>
<!DOCTYPE html>
<html lang="fr"<?php echo $darkClass; ?>>
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Ma page</title>


  <link rel="stylesheet" href="/css/darkmode.css">


</head>

<body>

  <h1>Bonjour !</h1>
  <p>Voici une page avec dark mode persistant.</p>


  <script src="/js/darkmode.js"></script>
</body>
</html>
