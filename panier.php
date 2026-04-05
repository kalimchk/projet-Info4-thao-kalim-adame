<?php
session_start();
require_once __DIR__ . '/config/function.php';

// Action pour vider le panier
if (isset($_GET['action']) && $_GET['action'] === 'vider') {
    unset($_SESSION['panier']);
    header('Location: panier.php');
    exit();
}

$panier = $_SESSION['panier'] ?? [];
$montantTotal = 0;
$nombre_articles_panier = 0;

foreach ($panier as $article) {
    $montantTotal += $article['prix'] * $article['quantite'];
    $nombre_articles_panier += $article['quantite'];
}

// Vérification si l'utilisateur est connecté pour la validation (Phase 2)
$utilisateurConnecte = $_SESSION['user'] ?? null;
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/png" href="logo/logo-pasta-la-vista.png">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Mon Panier - Pasta La Vista</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .panier-container { max-width: 800px; margin: 40px auto; padding: 0 20px; }
        .panier-table { width: 100%; border-collapse: collapse; margin-bottom: 24px; text-align: left; }
        .panier-table th, .panier-table td { padding: 16px; border-bottom: 1px solid var(--line-soft); }
        .panier-table th { font-family: "Cormorant Garamond", serif; font-size: 1.2rem; color: var(--muted); }
        .panier-actions { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 20px; }
        .total-panier { font-size: 1.5rem; font-family: "Cormorant Garamond", serif; font-weight: bold; color: var(--ink); }
        .btn-vider { color: #a45742; text-decoration: none; font-weight: 600; font-size: 0.9rem; }
        .btn-vider:hover { text-decoration: underline; }
        .cybank-box { background: var(--surface-alt); padding: 20px; border-radius: 12px; border: 1px solid var(--accent); margin-top: 30px; text-align: center; }
        .cybank-logo { font-weight: bold; color: #0055A4; font-size: 1.2rem; margin-bottom: 10px; }
    </style>
</head>
<body class="page-carte">
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
        </nav>
    </header>

    <main class="panier-container">
        <section class="panel">
            <h2>Votre Panier</h2>
            
            <?php if (empty($panier)): ?>
                <p class="intro">Votre panier est actuellement vide.</p>
                <div style="margin-top: 20px;">
                    <a href="carte.php" class="btn" style="padding: 10px 20px; border-radius: 999px; background: var(--accent); color: var(--bg); text-decoration: none;">Retour à la carte</a>
                </div>
            <?php else: ?>
                <table class="panier-table">
                    <thead>
                        <tr>
                            <th>Produit</th>
                            <th>Prix Unitaire</th>
                            <th>Quantité</th>
                            <th>Sous-total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($panier as $id => $article): ?>
                            <tr>
                                <td>
                                    <strong><?= htmlspecialchars($article['nom']) ?></strong><br>
                                    <small style="color: var(--muted); text-transform: uppercase; font-size: 0.8rem;"><?= htmlspecialchars($article['type']) ?></small>
                                </td>
                                <td><?= number_format($article['prix'], 2, ',', ' ') ?> €</td>
                                <td><?= $article['quantite'] ?></td>
                                <td><strong><?= number_format($article['prix'] * $article['quantite'], 2, ',', ' ') ?> €</strong></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <div class="panier-actions">
                    <a href="panier.php?action=vider" class="btn-vider">🗑️ Vider le panier</a>
                    <div class="total-panier">
                        Total : <?= number_format($montantTotal, 2, ',', ' ') ?> €
                    </div>
                </div>

                <div class="cybank-box">
                    <div class="cybank-logo">💳 Validation & Paiement Sécurisé</div>
                    <p>Pour finaliser votre commande de <strong><?= number_format($montantTotal, 2, ',', ' ') ?> €</strong>, veuillez choisir vos options.</p>
                    
                    <?php if ($utilisateurConnecte): ?>
                        <form action="traitement_paiement.php" method="POST" style="margin-top: 15px; text-align: left;">
                            <input type="hidden" name="montant" value="<?= $montantTotal ?>">
                            
                            <div style="background: var(--bg); padding: 15px; border-radius: 8px; border: 1px solid var(--line-soft); margin-bottom: 20px;">
                                <h3 style="margin-top: 0; font-size: 1.1rem; color: var(--ink);">Options de retrait</h3>
                                
                                <div style="margin-bottom: 15px;">
                                    <strong>Mode :</strong><br>
                                    <label><input type="radio" name="mode_retrait" value="livraison" checked> Livraison</label>
                                    <label style="margin-left: 15px;"><input type="radio" name="mode_retrait" value="emporter"> À emporter</label>
                                </div>

                                <div style="margin-bottom: 15px;">
                                    <strong>Moment :</strong><br>
                                    <label><input type="radio" name="moment_preparation" value="immediat" checked> Dès que possible</label>
                                    <label style="margin-left: 15px;"><input type="radio" name="moment_preparation" value="planifie"> Planifier pour plus tard</label>
                                </div>

                                <div style="padding-top: 10px; border-top: 1px dashed var(--line-strong);">
                                    <p style="margin-top: 0; margin-bottom: 10px; font-size: 0.9rem; color: var(--muted);"><em>Si vous planifiez pour plus tard, choisissez la date et l'heure :</em></p>
                                    
                                    <label for="date_planifiee" style="display:inline-block; width: 60px;">Date :</label>
                                    <input type="date" name="date_planifiee" id="date_planifiee" min="<?= date('Y-m-d') ?>" style="padding: 5px; border: 1px solid var(--line-strong); border-radius: 4px;">
                                    <br><br>
                                    
                                    <label for="heure_planifiee" style="display:inline-block; width: 60px;">Heure :</label>
                                    <input type="time" name="heure_planifiee" id="heure_planifiee" style="padding: 5px; border: 1px solid var(--line-strong); border-radius: 4px;">
                                </div>
                            </div>

                            <div style="text-align: center;">
                                <button type="submit" style="padding: 12px 24px; border-radius: 999px; background: var(--accent); color: var(--bg); border: none; font-weight: bold; cursor: pointer; font-size: 1rem;">
                                    Payer avec CYBank
                                </button>
                            </div>
                        </form>
                    <?php else: ?>
                        <p style="color: #a45742; font-weight: bold; margin-top: 15px;">Veuillez vous connecter pour valider votre commande.</p>
                        <a href="connexion.php" style="display: inline-block; padding: 10px 20px; border-radius: 999px; background: var(--muted); color: var(--bg); text-decoration: none;">Se connecter</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </section>
    </main>

    <footer class="site-footer">
        <p>&copy; 2026 Pasta La Vista - Restaurant italien.</p>
    </footer>
</body>
</html>