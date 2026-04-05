<?php
session_start();
require_once __DIR__ . '/config/function.php';


if (!isset($_SESSION['user'])) {
    header('Location: connexion.php');
    exit();
}

$id_commande = (int)($_GET['id'] ?? 0);
$commandes = lireCommandes();
$maCommande = null;
$indexCommande = -1;


foreach ($commandes as $index => $c) {
    if (($c['id'] ?? 0) === $id_commande) {
        $maCommande = $c;
        $indexCommande = $index;
        break;
    }
}

$utilisateurConnecte = $_SESSION['user'];
$nomCompletUtilisateur = trim($utilisateurConnecte['prenom'] . ' ' . $utilisateurConnecte['nom']);


if (!$maCommande || ($maCommande['client_nom'] ?? '') !== $nomCompletUtilisateur || ($maCommande['statut_commande'] ?? '') !== 'livree' || isset($maCommande['note'])) {
    header('Location: profil.php');
    exit();
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $note = (int)($_POST['note'] ?? 0);
    $commentaire = trim($_POST['commentaire'] ?? '');

    if ($note >= 1 && $note <= 5) {
        
        $commandes[$indexCommande]['note'] = $note;
        $commandes[$indexCommande]['commentaire_note'] = $commentaire;
        
        sauvegarderCommandes($commandes);
        
        header('Location: profil.php');
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8" />
  <link rel="icon" type="image/png" href="logo/logo-pasta-la-vista.png">
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link rel="stylesheet" href="css/style.css">
  <title>Noter ma commande - Pasta La Vista</title>
</head>

<body class="page-notes">
  <header class="site-header">
    <a class="logo" href="accueil.php"><img class="logo-img" src="logo/logo-pasta-la-vista.png" alt="Logo Pasta La Vista"><span class="logo-text">Pasta La Vista</span></a>
    <nav class="navbar">
      <a href="accueil.php">Accueil</a>
      <a href="carte.php">Carte</a>
      <a href="panier.php">🛒 Mon Panier</a>
      <a href="profil.php">Mon Profil</a>
      <a href="deconnexion.php" style="color: #a45742; font-weight: bold;">Déconnexion</a>
    </nav>
  </header>

  <main class="page" style="max-width: 600px; margin: 40px auto;">
    <section class="card panel">
      <div class="head">
        <div>
          <h1 style="margin-bottom: 5px;">Noter ma commande</h1>
          <p class="subtitle" style="color: var(--muted);">Commande <b><?= htmlspecialchars($maCommande['numero_commande']) ?></b></p>
        </div>
      </div>

      <div class="order-summary" style="margin-top: 15px; margin-bottom: 30px;">
        <div class="summary-item">
          <p class="k">Livreur</p>
          <p class="v"><?= htmlspecialchars($maCommande['livreur_nom'] ?? 'Inconnu') ?></p>
        </div>
        <div class="summary-item">
          <p class="k">Total</p>
          <p class="v"><?= number_format(calculerMontantTotalCommande($maCommande['articles']), 2, ',', ' ') ?> €</p>
        </div>
      </div>

      <form method="POST" action="">
        <div class="field" style="margin-top: 20px;">
          <label for="note" style="display: block; margin-bottom: 8px; font-weight: bold; color: var(--ink);">Votre note globale sur 5 :</label>
          <select name="note" id="note" required style="width: 100%; padding: 12px; border-radius: 6px; border: 1px solid var(--line-strong); background: var(--bg); font-size: 1rem;">
            <option value="">-- Choisissez une note --</option>
            <option value="5">⭐⭐⭐⭐⭐ - Excellent, parfait !</option>
            <option value="4">⭐⭐⭐⭐ - Très bien</option>
            <option value="3">⭐⭐⭐ - Bien, mais peut mieux faire</option>
            <option value="2">⭐⭐ - Décevant</option>
            <option value="1">⭐ - Très mauvaise expérience</option>
          </select>
        </div>

        <div class="field" style="margin-top: 20px;">
          <label for="commentaire" style="display: block; margin-bottom: 8px; font-weight: bold; color: var(--ink);">Commentaire (optionnel) :</label>
          <textarea name="commentaire" id="commentaire" rows="4" placeholder="Dis-nous ce que tu as aimé / ce qu'on peut améliorer…" style="width: 100%; padding: 12px; border-radius: 6px; border: 1px solid var(--line-strong); font-family: inherit;"></textarea>
        </div>

        <div class="cta" style="margin-top: 30px; display: flex; justify-content: space-between;">
          <a href="profil.php" class="btn btn-ghost" style="text-decoration: none; padding: 10px 20px; border: 1px solid var(--line-strong); border-radius: 999px; color: var(--ink);">Annuler</a>
          <button class="btn" type="submit" style="padding: 10px 20px; border-radius: 999px; background: var(--accent); color: var(--bg); border: none; font-weight: bold; cursor: pointer;">Envoyer ma note</button>
        </div>
      </form>

    </section>
  </main>

  <footer class="site-footer">
    <p>&copy; 2026 Pasta La Vista - Restaurant italien.</p>
  </footer>

</body>
</html>