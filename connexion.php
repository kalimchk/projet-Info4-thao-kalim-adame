<?php
session_start();
require_once 'data/functions.php';

$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = $_POST["email"];
    $password = $_POST["password"];

    $user = trouverUtilisateur($email);

    if ($user && password_verify($password, $user["password"])) {
        $_SESSION["user"] = $user;
        header("Location: admin.php");
        exit();
    } else {
        $message = "Email ou mot de passe incorrect";
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<link rel="stylesheet" href="css/style.css">
<title>Connexion</title>
</head>

<body>

<h1>Connexion</h1>

<?php if ($message): ?>
<p><?php echo $message; ?></p>
<?php endif; ?>

<form method="POST">
    <input type="email" name="email" placeholder="Email" required>
    <input type="password" name="password" placeholder="Mot de passe" required>

    <button type="submit">Se connecter</button>
</form>

</body>
</html>












