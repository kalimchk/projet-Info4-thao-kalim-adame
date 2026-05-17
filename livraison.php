<?php
session_start();
require_once __DIR__ . '/config/function.php';
$utilisateurConnecte = obtenirUtilisateurConnecteOuRediriger();

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
<?php
$isDark = isset($_COOKIE['darkmode']) && $_COOKIE['darkmode'] === '1';
$darkClass = $isDark ? ' class="dark-mode"' : '';
?>
<!DOCTYPE html>
<html lang="fr"<?php echo $darkClass; ?>>
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/png" href="logo/logo-pasta-la-vista.png">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Livraison - Pasta La Vista</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/darkmode.css">
</head>
<body class="page-livraison" data-surveillance-session="1">
<header class="site-header">
    <a class="logo" href="accueil.php"><img class="logo-img" src="logo/logo-pasta-la-vista.png" alt="Logo Pasta La Vista"><span class="logo-text">Pasta La Vista</span></a>
        <nav class="navbar">
            <a href="accueil.php">Accueil</a>
            <a href="carte.php">Carte</a>
            
            <a href="panier.php" class="lien-panier">
                🛒 Mon Panier 
                <?php if (isset($nombre_articles_panier) && $nombre_articles_panier > 0): ?>
                    <span class="badge-panier">(<?= $nombre_articles_panier ?>)</span>
                <?php endif; ?>
            </a>
            
            <?php if (isset($_SESSION['user'])): ?>
                <a href="profil.php">Mon Profil</a>
                <a href="deconnexion.php" style="color: #a45742; font-weight: 600;">Déconnexion</a>
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

        <input type="hidden" id="commande-id" value="<?php echo (int) ($commandeAttribuee['id'] ?? 0); ?>">

        <section class="livraison-detail">
            <article class="livraison-card">
                <h2><?php echo echapperTexteLivraison($commandeAttribuee['numero_commande'] ?? ''); ?></h2>
                <p><strong>Client :</strong> <?php echo echapperTexteLivraison($commandeAttribuee['client_nom'] ?? ''); ?></p>
                <p><strong>Telephone :</strong> <?php echo echapperTexteLivraison($commandeAttribuee['client_telephone'] ?? ''); ?></p>
                <p><strong>Adresse :</strong> <?php echo echapperTexteLivraison($commandeAttribuee['adresse_livraison'] ?? ''); ?></p>
                <p><strong>Code interphone :</strong> <?php echo echapperTexteLivraison($commandeAttribuee['code_interphone'] ?? 'Non renseigne'); ?></p>
                <p><strong>Commentaire :</strong> <?php echo echapperTexteLivraison($commandeAttribuee['commentaire_client'] ?? 'Aucun commentaire'); ?></p>
                <p><strong>Statut actuel :</strong> <span id="statut-commande-affiche"><?php echo echapperTexteLivraison(obtenirLibelleCourtStatut($commandeAttribuee['statut_commande'] ?? '')); ?></span></p>
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

                <div class="motif-abandon">
                    <label for="select-motif-abandon"><strong>Motif d abandon :</strong></label>
                    <select id="select-motif-abandon">
                        <option value="">-- Choisir un motif --</option>
                        <option value="adresse_introuvable">Adresse introuvable</option>
                        <option value="client_absent">Client absent</option>
                        <option value="acces_impossible">Acces impossible</option>
                    </select>
                </div>

                <div class="actions-livraison">
                    <button type="button" id="btn-marquer-livree" class="btn-secondaire">Marquer comme livree</button>
                    <button type="button" id="btn-marquer-abandonnee" class="btn-abandon">Marquer comme abandonnee</button>
                </div>
            </article>
        </section>
    <?php endif; ?>
</main>

<footer class="site-footer">
    <p>&copy; 2026 Pasta La Vista - Restaurant italien.</p>
</footer>

<script src="js/darkmode.js"></script>
<script src="js/session_surveillance.js"></script>
<script src="js/livraison.js"></script>
</body>
</html>
