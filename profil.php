<?php
session_start();
require_once __DIR__ . '/config/function.php';


if (!isset($_SESSION['user'])) {
    header('Location: connexion.php');
    exit();
}

$utilisateurConnecte = $_SESSION['user'];
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

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/png" href="logo/logo-pasta-la-vista.png">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/style.css">
    <title>Mon Profil - Pasta La Vista</title>
</head>
<body class="page-profil">
<header class="site-header">
   <a class="logo" href="accueil.php"><img class="logo-img" src="logo/logo-pasta-la-vista.png" alt="Logo Pasta La Vista"><span class="logo-text">Pasta La Vista</span></a>
        <nav class="navbar">
            
            <a class="active" href="accueil.php">Accueil</a>
            <a href="carte.php">Carte</a>
            <?php if (isset($_SESSION['user'])): ?>
                <a href="profil.php">Mon Profil</a>
                <a href="deconnexion.php" style="color: #a45742; font-weight: bold;">Déconnexion</a>
            <?php else: ?>
                <a href="connexion.php">Connexion</a>
            <?php endif; ?>
        </nav>
</header>

<main class="page">
  <section class="card">
    <h1>Mon profil</h1>
    <p style="text-align: center; color: var(--muted); margin-bottom: 20px;">
        Modification de profil prévue pour la Phase 3.
    </p>

    <div class="grid-2">
      <div class="field">
        <div class="label-row">
          <label>Nom</label>
          <button class="icon-btn" type="button" aria-label="Modifier le nom">✏️</button>
        </div>
        <p class="value"><?= htmlspecialchars($utilisateurConnecte['nom'] ?? '') ?></p>
      </div>

      <div class="field">
        <div class="label-row">
          <label>Prénom</label>
          <button class="icon-btn" type="button" aria-label="Modifier le prénom">✏️</button>
        </div>
        <p class="value"><?= htmlspecialchars($utilisateurConnecte['prenom'] ?? '') ?></p>
      </div>
      
      <div class="field">
        <div class="label-row">
          <label>Email</label>
          <button class="icon-btn" type="button" aria-label="Modifier l'email">✏️</button>
        </div>
        <p class="value"><?= htmlspecialchars($utilisateurConnecte['email'] ?? '') ?></p>
      </div>
      
      <div class="field">
        <div class="label-row">
          <label>Téléphone</label>
          <button class="icon-btn" type="button" aria-label="Modifier le téléphone">✏️</button>
        </div>
        <p class="value"><?= htmlspecialchars($utilisateurConnecte['telephone'] ?? '') ?></p>
      </div>
    </div>
  </section>

  <section class="card">
    <h2>Mes anciennes commandes</h2>
    <div class="orders">
        <?php if (empty($mesCommandes)): ?>
            <p style="text-align: center; color: var(--muted);">Vous n'avez passé aucune commande pour le moment.</p>
        <?php else: ?>
            <?php foreach ($mesCommandes as $commande): ?>
              <article class="order">
                <div>
                  <p class="order-title"><b>Commande <?= htmlspecialchars($commande['numero_commande']) ?></b></p>
                  <p class="order-meta">
                      Passée le : <?= htmlspecialchars($commande['heure_commande']) ?> 
                      <br>
                      Total : <?= number_format(calculerMontantTotalCommande($commande['articles']), 2, ',', ' ') ?> €
                  </p>
                </div>
                <span class="badge badge-done" style="background: var(--accent-wash-strong); border: 1px solid var(--accent); color: var(--ink);">
                    <b>Statut : <?= htmlspecialchars(obtenirLibelleCourtStatut($commande['statut_commande'])) ?></b>
                </span>
              </article>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
  </section>

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

</body>
</html>