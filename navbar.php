<?php

$page_active = basename($_SERVER['PHP_SELF']);
?>

<header class="site-header">
    <a class="logo" href="accueil.php">
        <img class="logo-img" src="logo/logo-pasta-la-vista.png" alt="Logo Pasta La Vista">
        <span class="logo-text">Pasta La Vista</span>
    </a>

    <nav class="navbar">
        <a href="accueil.php" <?php echo $page_active === 'accueil.php' ? 'class="active"' : ''; ?>>Accueil</a>
        <a href="carte.php" <?php echo $page_active === 'carte.php' ? 'class="active"' : ''; ?>>Carte</a>

        <a href="panier.php" class="lien-panier <?php echo $page_active === 'panier.php' ? 'active' : ''; ?>">
            Mon Panier
            <?php if (isset($nombre_articles_panier) && $nombre_articles_panier > 0): ?>
                <span class="badge-panier">(<?= $nombre_articles_panier ?>)</span>
            <?php endif; ?>
        </a>

        <?php if (isset($_SESSION['user'])): ?>
            <?php if (($_SESSION['user']['statut'] ?? '') === 'admin'): ?>
                <a href="administateur.php" <?php echo $page_active === 'administateur.php' ? 'class="active"' : ''; ?>>Administration</a>
            <?php endif; ?>
            <?php if (($_SESSION['user']['statut'] ?? '') === 'livreur'): ?>
                <a href="livraison.php" <?php echo $page_active === 'livraison.php' ? 'class="active"' : ''; ?>>Ma livraison</a>
            <?php endif; ?>
            <?php if (($_SESSION['user']['statut'] ?? '') === 'restaurateur'): ?>
                <a href="commande.php" <?php echo $page_active === 'commande.php' ? 'class="active"' : ''; ?>>Commande</a>
            <?php endif; ?>
            <a href="profil.php" <?php echo $page_active === 'profil.php' ? 'class="active"' : ''; ?>>Mon Profil</a>
            <a href="deconnexion.php" style="color:#a45742;font-weight:600;">Deconnexion</a>
        <?php else: ?>
            <a href="connexion.php" <?php echo $page_active === 'connexion.php' ? 'class="active"' : ''; ?>>Connexion</a>
            <a href="inscription.php" <?php echo $page_active === 'inscription.php' ? 'class="active"' : ''; ?>>Inscription</a>
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