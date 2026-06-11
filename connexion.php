<?php
session_start();
require_once __DIR__ . '/config/function.php';
verifierEtatSessionUtilisateur();

$messageErreurConnexion = '';
$messageInformation = '';

if (($_GET['message'] ?? '') === 'compte_bloque') {
    $messageInformation = 'Votre compte est bloque. Vous ne pouvez plus utiliser le site.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $emailUtilisateur = normaliserEmail($_POST['email'] ?? '');
    $motDePasseUtilisateur = trim($_POST['password'] ?? '');

    if (!verifierTokenCsrf($_POST['csrf_token'] ?? '')) {
        $utilisateurConnecte = null;
        $messageErreurConnexion = 'Requete invalide, veuillez recommencer.';
    } else {
        $utilisateurConnecte = trouverUtilisateurParEmail($emailUtilisateur);

        if ($utilisateurConnecte !== null && utilisateurEstBloque($utilisateurConnecte)) {
            $messageErreurConnexion = 'Votre compte est bloque.';
        } elseif ($utilisateurConnecte !== null && verifierMotDePasse($motDePasseUtilisateur, $utilisateurConnecte)) {
            session_regenerate_id(true);

            if (empty($utilisateurConnecte['password_hash']) || strpos((string) ($utilisateurConnecte['password_hash'] ?? ''), 'sha256$') === 0) {
                migrerMotDePasseUtilisateur((int) ($utilisateurConnecte['id'] ?? 0), $motDePasseUtilisateur);
                $utilisateurConnecte = trouverUtilisateurParId((int) ($utilisateurConnecte['id'] ?? 0)) ?? $utilisateurConnecte;
            }

            $_SESSION['user'] = nettoyerUtilisateurPourSession($utilisateurConnecte);

            if (($utilisateurConnecte['statut'] ?? '') === 'restaurateur') {
                header('Location: commande.php'); exit();
            }
            if (($utilisateurConnecte['statut'] ?? '') === 'admin') {
                header('Location: administateur.php'); exit();
            }
            if (($utilisateurConnecte['statut'] ?? '') === 'livreur') {
                header('Location: livraison.php'); exit();
            }

            header('Location: accueil.php');
            exit();
        } else {
            $messageErreurConnexion = 'Email ou mot de passe incorrect.';
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
    <title>Connexion</title>
</head>
<body class="page-connexion">
    <?php include 'navbar.php'; ?>

    <main>
        <h1>Connexion</h1>

        <?php if ($messageInformation !== ''): ?>
            <p class="message-retour"><?= htmlspecialchars($messageInformation, ENT_QUOTES, 'UTF-8') ?></p>
        <?php endif; ?>

        <?php if ($messageErreurConnexion !== ''): ?>
            <p class="message-erreur"><?= htmlspecialchars($messageErreurConnexion, ENT_QUOTES, 'UTF-8') ?></p>
        <?php endif; ?>

        <form method="POST" action="" id="form-connexion" novalidate>
            <input type="hidden" name="csrf_token" value="<?= e(genererTokenCsrf()) ?>">
            <label for="email">Email</label>
            <input id="email" type="email" name="email" placeholder="Email" required autocomplete="email">
            <p class="erreur-champ" id="erreur-email" style="display:none;"></p>

            <label for="password">Mot de passe</label>
            <div class="mdp-wrapper">
                <input id="password" type="password" name="password" placeholder="Mot de passe" required maxlength="64">
                <button type="button" class="btn-oeil" id="toggle-mdp-connexion" title="Afficher/Cacher">👁️</button>
            </div>
            <p class="erreur-champ" id="erreur-password" style="display:none;"></p>
            <p class="compteur-chars" id="compteur-mdp-connexion">0 / 64 caractères</p>

            <button type="submit">Se connecter</button>
        </form>

    </main>

    <footer class="site-footer">
        <p>&copy; 2026 Pasta La Vista - Restaurant italien.</p>
    </footer>

    <script src="js/connexion.js"></script>
    <script src="js/darkmode.js"></script>
</body>
</html>
