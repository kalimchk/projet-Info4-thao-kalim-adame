<<?php
session_start();
require_once 'data/functions.php';
if (!isset($_SESSION["user"])) {
    header("Location: connexion.php");
    exit();
}

$users = lireUtilisateurs();

$totalUsers = count($users);
$vipCount = 0;
$withOrders = 0;
?>

<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<link rel="stylesheet" href="css/style.css">
<title>Admin</title>
</head>

<body>

<h1>Administration</h1>

<p>Total utilisateurs : <?php echo $totalUsers; ?></p>

<div class="users">

<?php foreach ($users as $user): ?>

<div class="user">
    <p><b><?php echo $user["prenom"] . " " . $user["nom"]; ?></b></p>
    <p><?php echo $user["email"]; ?></p>
    <p><?php echo $user["telephone"]; ?></p>
    <button>Voir profil</button>
    <button>Bloquer</button>
    <button>VIP</button>
    <button>Remise</button>
</div>

<hr>

<?php endforeach; ?>

</div>

</body>
</html>