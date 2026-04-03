<?php
require_once 'data/functions.php';

$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nom = $_POST["nom"];
    $prenom = $_POST["prenom"];
    $email = $_POST["email"];
    $telephone = $_POST["telephone"];
    $password = $_POST["password"];

    ajouterUtilisateur($nom, $prenom, $email, $telephone, $password);

    $message = "Compte créé avec succès !";
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<link rel="stylesheet" href="css/style.css">
<title>Inscription</title>
</head>

<body>

<h1>Inscription</h1>

<?php if ($message): ?>
<p><?php echo $message; ?></p>
<?php endif; ?>

<form method="POST">
    <input type="text" name="nom" placeholder="Nom" required>
    <input type="text" name="prenom" placeholder="Prénom" required>
    <input type="email" name="email" placeholder="Email" required>
    <input type="text" name="telephone" placeholder="Téléphone" required>
    <input type="password" name="password" placeholder="Mot de passe" required>

    <button type="submit">S'inscrire</button>
</form>

</body>
</html>



