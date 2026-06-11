<?php
session_start();
require_once __DIR__ . '/config/function.php';

$messageConfirmationInscription = '';
$typeMessageInscription = 'succes';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nomUtilisateur = substr(trim($_POST['nom'] ?? ''), 0, 60);
    $prenomUtilisateur = substr(trim($_POST['prenom'] ?? ''), 0, 60);
    $emailUtilisateur = substr(normaliserEmail($_POST['email'] ?? ''), 0, 100);
    $telephoneUtilisateur = substr(trim($_POST['telephone'] ?? ''), 0, 20);
    $motDePasseUtilisateur = trim($_POST['password'] ?? '');

    
if (!verifierTokenCsrf($_POST['csrf_token'] ?? '')) {
    $messageConfirmationInscription = 'Requete invalide, veuillez recommencer.';
    $typeMessageInscription = 'erreur';
} elseif ($nomUtilisateur === '' || $prenomUtilisateur === '' || $emailUtilisateur === '' || $telephoneUtilisateur === '' || $motDePasseUtilisateur === '') {
    $messageConfirmationInscription = 'Tous les champs sont obligatoires.';
    $typeMessageInscription = 'erreur';
} elseif (!filter_var($emailUtilisateur, FILTER_VALIDATE_EMAIL)) {
    $messageConfirmationInscription = 'Adresse email invalide.';
    $typeMessageInscription = 'erreur';
} elseif (!telephoneValide($telephoneUtilisateur)) {
    $messageConfirmationInscription = 'Numero de telephone invalide.';
    $typeMessageInscription = 'erreur';
} elseif (!motDePasseValide($motDePasseUtilisateur)) {
    $messageConfirmationInscription = 'Le mot de passe doit contenir entre 8 et 72 caracteres.';
    $typeMessageInscription = 'erreur';
} else {
    $inscriptionReussie = ajouterUtilisateur(
        $nomUtilisateur,
        $prenomUtilisateur,
        $emailUtilisateur,
        $telephoneUtilisateur,
        $motDePasseUtilisateur
    );

    if ($inscriptionReussie) {
        $messageConfirmationInscription = 'Compte créé avec succès.';
    } else {
        $messageConfirmationInscription = 'Un compte existe déjà avec cette adresse email.';
        $typeMessageInscription = 'erreur';
    }
}
}
?>
<?php
$isDark = isset($_COOKIE['darkmode']) && $_COOKIE['darkmode'] === '1';
$darkClass = $isDark ? ' class="dark-mode"' : '';
?>
<!DOCTYPE html>
<html lang="fr"<?php echo $darkClass; ?>>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/darkmode.css">
    <title>Inscription</title>
</head>
<body class="page-inscription">
    <header class="site-header">
        <a class="logo" href="accueil.php">
            <img class="logo-img" src="logo/logo-pasta-la-vista.png" alt="Logo Pasta La Vista">
            <span class="logo-text">Pasta La Vista</span>
        </a>
        <nav class="navbar">
            <a class="active" href="accueil.php">Accueil</a>
            <a href="carte.php">Carte</a>
            <?php if (isset($_SESSION['user'])): ?>
                <?php if (($_SESSION['user']['statut'] ?? '') === 'admin'): ?>
                    <a href="administateur.php">Administration</a>
                <?php endif; ?>
                <?php if (($_SESSION['user']['statut'] ?? '') === 'livreur'): ?>
                    <a href="livraison.php">Ma livraison</a>
                <?php endif; ?>
                <?php if (($_SESSION['user']['statut'] ?? '') === 'restaurateur'): ?>
                    <a href="commande.php">Commande</a>
                <?php endif; ?>
                <a href="profil.php">Mon Profil</a>
                <a href="deconnexion.php" style="color:#a45742; font-weight:600;">Déconnexion</a>
            <?php else: ?>
                <a href="connexion.php">Connexion</a>
                <a href="inscription.php">Inscription</a>
            <?php endif; ?>
            <label class="switch">
                <input class="switch__input" id="dm-switch" type="checkbox" role="switch"
                       <?php echo $isDark ? 'checked' : ''; ?>>
                <span class="switch__icon">
                    <span class="switch__icon-part switch__icon-part--1"></span>
                    <span class="switch__icon-part switch__icon-part--2"></span>
                    <span class="switch__icon-part switch__icon-part--3"></span>
                    <span class="switch__icon-part switch__icon-part--4"></span>
                    <span class="switch__icon-part switch__icon-part--5"></span>
                    <span class="switch__icon-part switch__icon-part--6"></span>
                    <span class="switch__icon-part switch__icon-part--7"></span>
                    <span class="switch__icon-part switch__icon-part--8"></span>
                    <span class="switch__icon-part switch__icon-part--9"></span>
                    <span class="switch__icon-part switch__icon-part--10"></span>
                    <span class="switch__icon-part switch__icon-part--11"></span>
                </span>
                <span class="switch__sr">Dark Mode</span>
            </label>
        </nav>
    </header>

    <main>
        <h1>Inscription</h1>

        <?php if ($messageConfirmationInscription !== ''): ?>
            <p class="<?= $typeMessageInscription === 'erreur' ? 'message-erreur' : 'message-succes' ?>"><?= e($messageConfirmationInscription) ?></p>
        <?php endif; ?>

        <form method="POST" action="" id="form-inscription" novalidate>
            <input type="hidden" name="csrf_token" value="<?= e(genererTokenCsrf()) ?>">

            <label for="nom">Nom</label>
            <input id="nom" type="text" name="nom" placeholder="Nom" required maxlength="60">
            <p class="erreur-champ" id="erreur-nom" style="display:none;"></p>

            <label for="prenom">Prénom</label>
            <input id="prenom" type="text" name="prenom" placeholder="Prénom" required maxlength="60">
            <p class="erreur-champ" id="erreur-prenom" style="display:none;"></p>

            <label for="email">Email</label>
            <input id="email" type="email" name="email" placeholder="Email" required maxlength="100">
            <p class="erreur-champ" id="erreur-email" style="display:none;"></p>

            <label for="telephone">Téléphone</label>
            <input id="telephone" type="tel" name="telephone" placeholder="0612345678" required maxlength="15">
            <p class="erreur-champ" id="erreur-telephone" style="display:none;"></p>

            <label for="password">Mot de passe</label>
            <div class="mdp-wrapper">
                <input id="password" type="password" name="password" placeholder="Mot de passe" required maxlength="64">
                <button type="button" class="btn-oeil" id="toggle-mdp-inscription" title="Afficher/Cacher">👁️</button>
            </div>
            <p class="erreur-champ" id="erreur-password" style="display:none;"></p>
            <p class="compteur-chars" id="compteur-mdp-inscription">0 / 64 caractères</p>

            <button type="submit">S'inscrire</button>
        </form>
    </main>

    <footer class="site-footer">
        <p>&copy; 2026 Pasta La Vista - Restaurant italien.</p>
    </footer>

    <script src="js/inscription.js"></script>
    <script src="js/darkmode.js"></script>
</body>
</html>
