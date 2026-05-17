<?php
session_start();
require_once __DIR__ . '/config/function.php';
$utilisateurConnecte = obtenirUtilisateurConnecteOuRediriger();
$nomCompletUtilisateur = trim($utilisateurConnecte['prenom'] . ' ' . $utilisateurConnecte['nom']);

$toutesLesCommandes = lireCommandes();
$mesCommandes = [];

foreach ($toutesLesCommandes as $commande) {
    if (($commande['client_nom'] ?? '') === $nomCompletUtilisateur) {
        $mesCommandes[] = $commande;
    }
}

$pointsFidelite = count($mesCommandes) * 10;
$statutFidelite = $pointsFidelite >= 50 ? 'Premium 🌟' : 'Classique';
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/darkmode.css">
    <title>Mon Profil - Pasta La Vista</title>
</head>
<body class="page-profil" data-surveillance-session="1">
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
            <a href="profil.php">Mon Profil</a>
            <a href="deconnexion.php" style="color: #a45742; font-weight: bold;">Déconnexion</a>
        <?php else: ?>
            <a href="connexion.php">Connexion</a>
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

<main class="page">

    <!-- ── Section profil éditable ── -->
    <section class="card">
        <h1>Mon profil</h1>
        <p id="message-retour-profil" class="message-retour" style="display:none;"></p>

        <div class="grid-2">
            <div class="field">
                <div class="label-row"><label>Nom</label></div>
                <p id="valeur-nom" class="value profil-valeur"><?= htmlspecialchars($utilisateurConnecte['nom'] ?? '') ?></p>
                <input id="input-nom" type="text" name="nom"
                       value="<?= htmlspecialchars($utilisateurConnecte['nom'] ?? '') ?>"
                       maxlength="60" class="champ-profil" style="display:none;">
            </div>

            <div class="field">
                <div class="label-row"><label>Prénom</label></div>
                <p id="valeur-prenom" class="value profil-valeur"><?= htmlspecialchars($utilisateurConnecte['prenom'] ?? '') ?></p>
                <input id="input-prenom" type="text" name="prenom"
                       value="<?= htmlspecialchars($utilisateurConnecte['prenom'] ?? '') ?>"
                       maxlength="60" class="champ-profil" style="display:none;">
            </div>

            <div class="field">
                <div class="label-row"><label>Email</label></div>
                <p id="valeur-email" class="value profil-valeur"><?= htmlspecialchars($utilisateurConnecte['email'] ?? '') ?></p>
                <input id="input-email" type="email" name="email"
                       value="<?= htmlspecialchars($utilisateurConnecte['email'] ?? '') ?>"
                       maxlength="100" class="champ-profil" style="display:none;">
            </div>

            <div class="field">
                <div class="label-row"><label>Téléphone</label></div>
                <p id="valeur-telephone" class="value profil-valeur"><?= htmlspecialchars($utilisateurConnecte['telephone'] ?? '') ?></p>
                <input id="input-telephone" type="tel" name="telephone"
                       value="<?= htmlspecialchars($utilisateurConnecte['telephone'] ?? '') ?>"
                       maxlength="20" class="champ-profil" style="display:none;">
            </div>

            <!-- Mot de passe : afficher/cacher uniquement, pas modifiable ici -->
            <div class="field">
                <div class="label-row"><label>Mot de passe</label></div>
                <div class="mdp-wrapper value" style="display:flex; align-items:center; gap:10px;">
                    <input id="affichage-mdp" type="password"
                           value="<?= htmlspecialchars($utilisateurConnecte['password'] ?? '') ?>"
                           readonly style="border:none; background:transparent; flex:1; font-size:1rem; color:var(--ink);">
                    <button type="button" id="toggle-mdp-profil" class="btn-oeil" title="Afficher/Cacher le mot de passe">👁️</button>
                </div>
            </div>
        </div>

        <div class="profil-actions" style="margin-top:20px; display:flex; gap:12px; flex-wrap:wrap;">
            <button id="btn-modifier-profil" type="button" class="btn">✏️ Modifier mes informations</button>
            <button id="btn-valider-profil"  type="button" class="btn" style="display:none;">✅ Valider</button>
            <button id="btn-annuler-profil"  type="button" class="btn btn-ghost" style="display:none;">Annuler</button>
        </div>
    </section>
    <!-- ── Section commandes ── -->
    <section class="card">
        <h2>Mes anciennes commandes</h2>
        <div class="orders">
            <?php if (empty($mesCommandes)): ?>
                <p style="text-align:center; color:var(--muted);">Vous n'avez passé aucune commande pour le moment.</p>
            <?php else: ?>
                <?php foreach ($mesCommandes as $commande): ?>

                    <article class="order">
                        <div>
                            <p class="order-title"><b>Commande <?= htmlspecialchars($commande['numero_commande']) ?></b></p>
                            <p class="order-meta">
                                Passée le : <?= htmlspecialchars($commande['heure_commande']) ?><br>
                                Total : <?= number_format(calculerMontantTotalCommande($commande['articles']), 2, ',', ' ') ?> €
                            </p>
                        </div>
                        <div style="text-align:right;">
                            <span class="badge badge-done" style="background:var(--accent-wash-strong); border:1px solid var(--accent); color:var(--ink); margin-bottom:10px; display:inline-block;">
                                <b>Statut : <?= htmlspecialchars(obtenirLibelleCourtStatut($commande['statut_commande'])) ?></b>
                            </span>
                            <br>
                            <?php
                                $estLivraison = stripos($commande['commentaire_client'] ?? '', 'Mode : Livraison') !== false;
                            ?>
                            <?php if (($commande['statut_commande'] === 'livree') && $estLivraison && !isset($commande['note'])): ?>
                                <a href="notes.php?id=<?= $commande['id'] ?>" class="btn" style="padding:6px 12px; font-size:0.85rem; background:var(--muted); text-decoration:none;">⭐ Noter</a>
                            <?php elseif (($commande['statut_commande'] === 'livree') && $estLivraison && isset($commande['note'])): ?>
                                <span style="color:var(--accent-deep); font-weight:bold;">Note : <?= $commande['note'] ?>/5 ⭐</span>
                            <?php endif; ?>
                        </div>
                    </article>

                    <?php if (($commande['statut_commande'] ?? '') === 'a_preparer'): ?>
                    <div class="commande-modifiable" data-id-commande="<?= (int)$commande['id'] ?>">
                        <h4>✏️ Modifier la commande <?= htmlspecialchars($commande['numero_commande']) ?></h4>
                        <p class="modif-intro">Cette commande est en attente de préparation. Vous pouvez encore ajouter ou retirer des articles.</p>

                        <ul class="liste-articles-modifiable">
                            <?php foreach ($commande['articles'] as $article): ?>
                            <li class="article-modifiable">
                                <span class="article-quantite"><?= (int)($article['quantite'] ?? 1) ?></span>
                                <span class="article-nom">× <?= htmlspecialchars($article['nom_produit'] ?? '') ?></span>
                                <span class="article-prix"><?= number_format((float)($article['prix_unitaire'] ?? 0), 2, ',', ' ') ?> €</span>
                                <span class="article-sous-total">
                                    (= <?= number_format((int)($article['quantite'] ?? 1) * (float)($article['prix_unitaire'] ?? 0), 2, ',', ' ') ?> €)
                                </span>
                                <div class="article-btns">
                                    <button type="button" class="btn-retirer-article"
                                            data-nom="<?= htmlspecialchars($article['nom_produit'] ?? '') ?>"
                                            data-prix="<?= (float)($article['prix_unitaire'] ?? 0) ?>"
                                            title="Retirer un exemplaire">−</button>
                                    <button type="button" class="btn-ajouter-article"
                                            data-nom="<?= htmlspecialchars($article['nom_produit'] ?? '') ?>"
                                            data-prix="<?= (float)($article['prix_unitaire'] ?? 0) ?>"
                                            title="Ajouter un exemplaire">+</button>
                                </div>
                            </li>
                            <?php endforeach; ?>
                        </ul>

                        <p class="total-commande">
                            Total : <?= number_format(calculerMontantTotalCommande($commande['articles']), 2, ',', ' ') ?> €
                        </p>

                        <p class="message-modification" style="display:none;"></p>
                        <div class="zone-paiement-supplementaire" style="display:none;"></div>
                    </div>
                    <?php endif; ?>

                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </section>

    <!-- ── Section fidélité ── -->
    <section class="card">
        <h2>Fidélité</h2>
        <div class="fidelite">
            <p><b>Points cumulés :</b> <?= $pointsFidelite ?> pts</p>
            <p><b>Statut actuel :</b> <?= $statutFidelite ?></p>
            <p><b>Avantage :</b> <?= $statutFidelite === 'Premium 🌟' ? 'Livraison offerte sur votre prochaine commande !' : 'Cumulez 50 points pour obtenir la livraison offerte.' ?></p>
        </div>
    </section>

</main>

<footer class="site-footer">
    <p>&copy; 2026 Pasta La Vista - Restaurant italien.</p>
</footer>

<script src="js/profil.js"></script>
<script src="js/modifier_commande.js"></script>
<script src="js/darkmode.js"></script>
<script src="js/session_surveillance.js"></script>
</body>
</html>
