<?php
session_start();
require_once __DIR__ . '/config/function.php';
verifierEtatSessionUtilisateur();

if (!isset($_SESSION['panier'])) {
    $_SESSION['panier'] = [];
}

$menuPath = __DIR__ . '/data/menu.json';
$platsPath = __DIR__ . '/data/plats.json';

$menu = file_exists($menuPath) ? json_decode(file_get_contents($menuPath), true) : [];
$plats = file_exists($platsPath) ? json_decode(file_get_contents($platsPath), true) : [];

if (isset($_GET['id']) && isset($_GET['type'])) {
    $id_choisi = $_GET['id'];
    $type_choisi = $_GET['type'];

    $produit_ajouter = NULL;

    if ($type_choisi === 'Plat' || $type_choisi === 'Entree' || $type_choisi === 'Dessert') {
        foreach ($plats as $p) {
            if (isset($p['id']) && $p['id'] === $id_choisi) {
                $produit_ajouter = $p;
                break;
            }
        }
    } elseif ($type_choisi === 'menu') {
        foreach ($menu as $m) {
            if (isset($m['idm']) && $m['idm'] === $id_choisi) {
                $produit_ajouter = $m;
                break;
            }
        }
    }

    if ($produit_ajouter !== null) {
        if (isset($_SESSION['panier'][$id_choisi])) {
            $_SESSION['panier'][$id_choisi]['quantite']++;
        } else {
            $prix_a_enregistrer = isset($produit_ajouter['prix']) ? $produit_ajouter['prix'] : (isset($produit_ajouter['prix_total']) ? $produit_ajouter['prix_total'] : 0);
            $_SESSION['panier'][$id_choisi] = [
                'nom' => isset($produit_ajouter['nom']) ? $produit_ajouter['nom'] : 'Produit inconnu',
                'prix' => $prix_a_enregistrer,
                'type' => $type_choisi,
                'quantite' => 1
            ];
        }
    }

    header('Location: carte.php');
    exit();
}

$nombre_articles_panier = 0;
if (isset($_SESSION['panier'])) {
    foreach ($_SESSION['panier'] as $article) {
        $nombre_articles_panier += $article['quantite'];
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/png" href="logo/logo-pasta-la-vista.png">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Carte - Pasta La Vista</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="page-carte" data-surveillance-session="<?php echo isset($_SESSION['user']) ? '1' : '0'; ?>">
    <header class="site-header">
        <a class="logo" href="accueil.php">
            <img class="logo-img" src="logo/logo-pasta-la-vista.png" alt="Logo Pasta La Vista">
            <span class="logo-text">Pasta La Vista</span>
        </a>
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
                <?php if (($_SESSION['user']['statut'] ?? '') === 'admin'): ?>
                    <a href="administateur.php">Administration</a>
                <?php endif; ?>
                <a href="profil.php">Mon Profil</a>
                <a href="deconnexion.php" style="color: #a45742; font-weight: 600;">Déconnexion</a>
            <?php else: ?>
                <a href="connexion.php">Connexion</a>
                <a href="inscription.php">Inscription</a>
            <?php endif; ?>
        </nav>
    </header>

    <main>
        <section class="filters panel" aria-labelledby="filters-title">
            <h2 id="filters-title">Filtres</h2>
            <p class="filters-intro">Sélectionnez les saveurs et allergènes à afficher dans la carte.</p>
            <form class="filters-form">
                <fieldset class="filter-group">
                    <legend>Saveurs</legend>
                    <div class="filter-options">
                        <label><input type="checkbox" name="saveurs" value="varie">Varie</label>
                        <label><input type="checkbox" name="saveurs" value="salin">Salin</label>
                        <label><input type="checkbox" name="saveurs" value="gourmand">Gourmand</label>
                        <label><input type="checkbox" name="saveurs" value="frais">Frais</label>
                        <label><input type="checkbox" name="saveurs" value="delicat">Délicat</label>
                        <label><input type="checkbox" name="saveurs" value="umami">Umami</label>
                        <label><input type="checkbox" name="saveurs" value="boise">Boisé</label>
                        <label><input type="checkbox" name="saveurs" value="cremeux">Crémeux</label>
                        <label><input type="checkbox" name="saveurs" value="legementtoaste">Légèrement toasté</label>
                        <label><input type="checkbox" name="saveurs" value="marin">Marin</label>
                        <label><input type="checkbox" name="saveurs" value="iode">Iodé</label>
                        <label><input type="checkbox" name="saveurs" value="legementacidule">Légèrement acidulé</label>
                        <label><input type="checkbox" name="saveurs" value="intense">Intense</label>
                        <label><input type="checkbox" name="saveurs" value="beurre">Beurre</label>
                        <label><input type="checkbox" name="saveurs" value="doux">Doux</label>
                        <label><input type="checkbox" name="saveurs" value="herbace">Herbacé</label>
                        <label><input type="checkbox" name="saveurs" value="fondant">Fondant</label>
                        <label><input type="checkbox" name="saveurs" value="riche">Riche</label>
                        <label><input type="checkbox" name="saveurs" value="parfume">Parfumé</label>
                        <label><input type="checkbox" name="saveurs" value="legementcitronne">Légèrement citronné</label>
                        <label><input type="checkbox" name="saveurs" value="puissant">Puissant</label>
                        <label><input type="checkbox" name="saveurs" value="vineux">Vineux</label>
                        <label><input type="checkbox" name="saveurs" value="acidule">Acidulé</label>
                        <label><input type="checkbox" name="saveurs" value="mediteraneen">Méditerranéen</label>
                        <label><input type="checkbox" name="saveurs" value="tomate">Tomate</label>
                        <label><input type="checkbox" name="saveurs" value="fromager">Fromager</label>
                        <label><input type="checkbox" name="saveurs" value="croustillant">Croustillant</label>
                        <label><input type="checkbox" name="saveurs" value="legementamer">Légèrement amer</label>
                        <label><input type="checkbox" name="saveurs" value="cafe">Café</label>
                        <label><input type="checkbox" name="saveurs" value="vanille">Vanille</label>
                    </div>
                </fieldset>

                <fieldset class="filter-group">
                    <legend>Allergènes (à exclure)</legend>
                    <div class="filter-options">
                        <label><input type="checkbox" name="allergenes" value="gluten">Gluten</label>
                        <label><input type="checkbox" name="allergenes" value="lait">Lait</label>
                        <label><input type="checkbox" name="allergenes" value="fruitacoque">Fruits à coque</label>
                        <label><input type="checkbox" name="allergenes" value="oeuf">Œufs</label>
                        <label><input type="checkbox" name="allergenes" value="crustaces">Crustacés</label>
                        <label><input type="checkbox" name="allergenes" value="mollusques">Mollusques</label>
                        <label><input type="checkbox" name="allergenes" value="sulfites">Sulfites</label>
                        <label><input type="checkbox" name="allergenes" value="celeri">Céleri</label>
                        <label><input type="checkbox" name="allergenes" value="poisson">Poisson</label>
                    </div>
                </fieldset>

                <div class="filters-actions">
                    <button type="reset">Réinitialiser</button>
                </div>
            </form>
        </section>

        <section class="hero panel">
            <p class="tagline">Cuisine italienne gastronomique</p>
            <h1>Notre carte</h1>
            <p class="intro">
                Une sélection de spécialités maison, entre produits italiens d'exception,
                recettes traditionnelles et assiettes généreuses.
            </p>
            <div class="quick-links">
                <a href="#menus">Menus</a>
                <a href="#entrees">Entrées</a>
                <a href="#plats">Plats</a>
                <a href="#desserts">Desserts</a>
            </div>

            <!-- Barre de tri Phase 3 -->
            <div style="display:flex; align-items:center; gap:16px; margin-top:16px; flex-wrap:wrap;">
                <label for="select-tri" style="font-weight:600; color:var(--ink);">Trier par :</label>
                <select id="select-tri" style="padding:8px 12px; border-radius:8px; border:1px solid var(--line-strong); background:var(--bg); font-size:0.95rem; color:var(--ink);">
                    <option value="defaut">Ordre par défaut</option>
                    <option value="prix-asc">Prix croissant</option>
                    <option value="prix-desc">Prix décroissant</option>
                </select>
                <span id="compteur-resultats" style="color:var(--muted); font-size:0.9rem;"></span>
                <span id="indicateur-chargement" style="display:none; color:var(--muted); font-size:0.9rem;">⏳ Chargement…</span>
            </div>
        </section>

        <section id="menus" class="menu-section">
            <h2>Menus & Formules</h2>
            <div class="menu-grid" id="grille-menus">
                <?php foreach ($menu as $m): ?>
                    <article class="dish-card" data-prix="<?= (float)($m['prix_total'] ?? 0) ?>" data-type="menu">
                        <div class="dish-head">
                            <h3><?= htmlspecialchars($m['nom']) ?></h3>
                            <span class="price"><?= number_format($m['prix_total'], 2, ',', ' ') ?> EUR</span>
                        </div>
                        <p class="type">Type : Menu</p>
                        <p class="desc"><?= htmlspecialchars($m['description']) ?></p>
                        <p class="flavors">
                            <strong>Créneaux :</strong> <?= htmlspecialchars(implode(', ', $m['creneaux_limites'] ?? [])) ?>
                            <br>
                            <strong>Personnes min :</strong> <?= htmlspecialchars($m['nb_personnes_min'] ?? 1) ?>
                        </p>
                        <a class="add-cart" href="carte.php?type=menu&id=<?= urlencode($m['idm']) ?>">Ajouter au panier</a>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>

        <section id="entrees" class="menu-section">
            <h2>Entrées</h2>
            <div class="menu-grid" id="grille-plats">
                <?php foreach ($plats as $p): ?>
                    <article class="dish-card"
                             data-prix="<?= (float)($p['prix'] ?? 0) ?>"
                             data-type="<?= strtolower(htmlspecialchars($p['type'] ?? '')) ?>">
                        <div class="dish-head">
                            <h3><?= htmlspecialchars($p['nom']) ?></h3>
                            <span class="price"><?= number_format($p['prix'], 2, ',', ' ') ?> EUR</span>
                        </div>
                        <p class="type">Type : <?= htmlspecialchars($p['type']) ?></p>
                        <p class="desc"><?= htmlspecialchars($p['description']) ?></p>
                        <?php if (!empty($p['informations']['saveurs'])): ?>
                            <p class="flavors"><strong>Saveurs :</strong> <?= htmlspecialchars(implode(', ', $p['informations']['saveurs'])) ?></p>
                        <?php endif; ?>
                        <?php if (!empty($p['informations']['allergenes'])): ?>
                            <p class="allergens"><strong>Allergènes :</strong> <?= htmlspecialchars(implode(', ', $p['informations']['allergenes'])) ?></p>
                        <?php endif; ?>
                        <a class="add-cart" href="carte.php?type=<?= urlencode($p['type']) ?>&id=<?= urlencode($p['id']) ?>">Ajouter au panier</a>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>

    </main>

    <footer class="site-footer">
        <p>&copy; 2026 Pasta La Vista - Restaurant italien.</p>
    </footer>

    <script src="js/carte.js"></script>
    <script src="js/session_surveillance.js"></script>
</body>
</html>
