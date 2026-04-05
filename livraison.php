<?php
session_start();
require_once __DIR__ . '/config/function.php';

if (!isset($_SESSION['user'])) {
    header('Location: connexion.php');
    exit();
}

$utilisateurConnecte = $_SESSION['user'];

if (($utilisateurConnecte['statut'] ?? '') !== 'livreur') {
    header('Location: accueil.html');
    exit();
}

$identifiantLivreur = (int) ($utilisateurConnecte['id'] ?? 0);
$nomLivreur = trim((string) (($utilisateurConnecte['prenom'] ?? '') . ' ' . ($utilisateurConnecte['nom'] ?? '')));
$commandeAttribuee = lireCommandeAttribueeAuLivreur($identifiantLivreur);

function echapperTexteLivraison(?string $texte): string
{
    return htmlspecialchars((string) $texte, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/png" href="logo/logo-pasta-la-vista.png">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Livraison - Pasta La Vista</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="page-livraison">
<header class="site-header">
    <a class="logo" href="accueil.html"><img class="logo-img" src="logo/logo-pasta-la-vista.png" alt="Logo Pasta La Vista"><span class="logo-text">Pasta La Vista</span></a>
    <nav class="navbar">
        <a href="accueil.html">Accueil</a>
        <a href="carte.php">Carte</a>
        <a class="active" href="livraison.php">Livraison</a>
        <a href="connexion.php">Connexion</a>
    </nav>
</header>

<main class="livraison-page">
    <h1 class="livraison-titre">Commande attribuee au livreur</h1>
    <p class="livraison-intro">Livreur connecte : <?php echo echapperTexteLivraison($nomLivreur); ?></p>

    <?php if ($commandeAttribuee === null): ?>
        <section class="livraison-card">
            <h2>Aucune commande attribuee</h2>
            <p>Il n y a actuellement aucune commande en livraison pour ce livreur.</p>
        </section>
    <?php else: ?>
        <?php $montantTotalCommande = calculerMontantTotalCommande($commandeAttribuee['articles'] ?? []); ?>

        <section class="livraison-detail">
            <article class="livraison-card">
                <h2><?php echo echapperTexteLivraison($commandeAttribuee['numero_commande'] ?? ''); ?></h2>
                <p><strong>Client :</strong> <?php echo echapperTexteLivraison($commandeAttribuee['client_nom'] ?? ''); ?></p>
                <p><strong>Telephone :</strong> <?php echo echapperTexteLivraison($commandeAttribuee['client_telephone'] ?? ''); ?></p>
                <p><strong>Adresse :</strong> <?php echo echapperTexteLivraison($commandeAttribuee['adresse_livraison'] ?? ''); ?></p>
                <p><strong>Code interphone :</strong> <?php echo echapperTexteLivraison($commandeAttribuee['code_interphone'] ?? 'Non renseigne'); ?></p>
                <p><strong>Commentaire :</strong> <?php echo echapperTexteLivraison($commandeAttribuee['commentaire_client'] ?? 'Aucun commentaire'); ?></p>
                <p><strong>Statut actuel :</strong> <?php echo echapperTexteLivraison(obtenirLibelleCourtStatut($commandeAttribuee['statut_commande'] ?? '')); ?></p>
                <p><strong>Restaurant :</strong> <?php echo echapperTexteLivraison($commandeAttribuee['restaurant_nom'] ?? ''); ?></p>
            </article>

            <article class="livraison-card">
                <h2>Detail de la commande</h2>
                <ul class="liste-produits">
                    <?php foreach (($commandeAttribuee['articles'] ?? []) as $article): ?>
                        <li>
                            <?php echo (int) ($article['quantite'] ?? 0); ?> x
                            <?php echo echapperTexteLivraison($article['nom_produit'] ?? ''); ?>
                            - <?php echo number_format((float) ($article['prix_unitaire'] ?? 0), 2, ',', ' '); ?> EUR
                        </li>
                    <?php endforeach; ?>
                </ul>
                <p class="total">Total : <?php echo number_format($montantTotalCommande, 2, ',', ' '); ?> EUR</p>
                <a href="https://www.google.com/maps" target="_blank" class="lien-bouton">
                    <span class="btn-principal full-width">Ouvrir dans Maps</span>
                </a>
            </article>
        </section>

        <section class="livraison-actions">
            <article class="livraison-card">
                <h2>Changer l etat de la livraison</h2>
                <p><strong>Statut possible :</strong> Livree ou abandonnee.</p>
                <p><strong>Motif d abandon :</strong> adresse introuvable, client absent, acces impossible.</p>

                <div class="actions-livraison">
                    <button type="button" class="btn-secondaire bouton-inactif">Marquer comme livree</button>
                    <button type="button" class="btn-abandon bouton-inactif">Marquer comme abandonnee</button>
                </div>
            </article>
        </section>
    <?php endif; ?>
</main>

<footer class="site-footer">
    <p>&copy; 2026 Pasta La Vista - Restaurant italien.</p>
</footer>

</body>
</html>
