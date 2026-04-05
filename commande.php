<?php
session_start();
require_once __DIR__ . '/config/function.php';

if (!isset($_SESSION['user'])) {
    header('Location: connexion.php');
    exit();
}

$utilisateurConnecte = $_SESSION['user'];

if (($utilisateurConnecte['statut'] ?? '') !== 'restaurateur') {
    header('Location: accueil.html');
    exit();
}

$identifiantRestaurant = (int) ($utilisateurConnecte['restaurant_id'] ?? 0);
$nomRestaurant = $utilisateurConnecte['restaurant_nom'] ?? 'Restaurant';
$listeDesCommandes = lireCommandesDuRestaurant($identifiantRestaurant);
$commandesParStatut = regrouperCommandesParStatut($listeDesCommandes);
$compteursParStatut = compterCommandesParStatut($listeDesCommandes);
$definitionsDesStatuts = obtenirDefinitionDesStatutsCommande();
$identifiantCommandeSelectionnee = (int) ($_GET['commande_id'] ?? 0);

$listeDesLivreursDisponibles = [
    [
        'id' => 1,
        'nom' => 'Nicolas Perrin',
        'telephone' => '06 10 20 30 40',
        'statut' => 'Disponible',
        'zone' => 'Cergy',
    ],
    [
        'id' => 2,
        'nom' => 'Lea Fontaine',
        'telephone' => '06 11 22 33 44',
        'statut' => 'Disponible',
        'zone' => 'Pontoise',
    ],
    [
        'id' => 3,
        'nom' => 'Sami Benali',
        'telephone' => '06 55 44 33 22',
        'statut' => 'Disponible',
        'zone' => 'Eragny',
    ],
];

$commandeSelectionnee = null;

foreach ($listeDesCommandes as $commande) {
    if (($commande['id'] ?? 0) === $identifiantCommandeSelectionnee) {
        $commandeSelectionnee = $commande;
        break;
    }
}

function echapperTexte(?string $texte): string
{
    return htmlspecialchars((string) $texte, ENT_QUOTES, 'UTF-8');
}

function obtenirClasseBadgeCommande(string $statutCommande): string
{
    $classesParStatut = [
        'a_preparer' => 'badge-prepare',
        'en_cours' => 'badge-en-cours',
        'en_attente' => 'badge-attente',
        'en_livraison' => 'badge-livraison',
        'livree' => 'badge-livree',
    ];

    return $classesParStatut[$statutCommande] ?? 'badge-prepare';
}

function obtenirLibelleCourtStatut(string $statutCommande): string
{
    $libellesCourts = [
        'a_preparer' => 'A preparer',
        'en_cours' => 'En cours',
        'en_attente' => 'En attente',
        'en_livraison' => 'En livraison',
        'livree' => 'Livree',
    ];

    return $libellesCourts[$statutCommande] ?? 'Inconnu';
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/png" href="logo/logo-pasta-la-vista.png">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Commandes - Pasta La Vista</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="page-commande">

<header class="site-header">
    <a class="logo" href="accueil.html"><img class="logo-img" src="logo/logo-pasta-la-vista.png" alt="Logo Pasta La Vista"><span class="logo-text">Pasta La Vista</span></a>
    <nav class="navbar">
        <a href="accueil.html">Accueil</a>
        <a href="carte.html">Carte</a>
        <a class="active" href="commande.php">Commandes</a>
        <a href="connexion.php">Connexion</a>
    </nav>
</header>

<main class="commandes-container">
    <h1>Liste detaillee des commandes</h1>
    <p class="commande-intro">Restaurant connecte : <?php echo echapperTexte($nomRestaurant); ?></p>

    <section class="resume-commandes">
        <?php foreach ($definitionsDesStatuts as $codeStatut => $definitionDuStatut): ?>
            <article class="resume-card">
                <p class="resume-valeur"><?php echo (int) ($compteursParStatut[$codeStatut] ?? 0); ?></p>
                <p class="resume-libelle"><?php echo echapperTexte(obtenirLibelleCourtStatut($codeStatut)); ?></p>
            </article>
        <?php endforeach; ?>
    </section>

    <?php if ($commandeSelectionnee !== null): ?>
        <?php $montantCommandeSelectionnee = calculerMontantTotalCommande($commandeSelectionnee['articles'] ?? []); ?>

        <section class="detail-commande">
            <div class="detail-header">
                <div>
                    <h2>Detail de la commande <?php echo echapperTexte($commandeSelectionnee['numero_commande'] ?? ''); ?></h2>
                    <p class="description-section">Affichage complet prevu pour la phase suivante. Cette zone est uniquement visuelle.</p>
                </div>
                <a class="btn-retour-detail" href="commande.php">Fermer le detail</a>
            </div>

            <div class="detail-grid">
                <article class="detail-card">
                    <h3>Informations generales</h3>
                    <p><strong>Client :</strong> <?php echo echapperTexte($commandeSelectionnee['client_nom'] ?? ''); ?></p>
                    <p><strong>Telephone :</strong> <?php echo echapperTexte($commandeSelectionnee['client_telephone'] ?? ''); ?></p>
                    <p><strong>Adresse :</strong> <?php echo echapperTexte($commandeSelectionnee['adresse_livraison'] ?? ''); ?></p>
                    <p><strong>Heure :</strong> <?php echo echapperTexte($commandeSelectionnee['heure_commande'] ?? ''); ?></p>
                    <p><strong>Statut actuel :</strong> <?php echo echapperTexte(obtenirLibelleCourtStatut($commandeSelectionnee['statut_commande'] ?? '')); ?></p>
                    <p><strong>Delai estime :</strong> <?php echo echapperTexte($commandeSelectionnee['temps_estime'] ?? ''); ?></p>
                    <?php if (($commandeSelectionnee['commentaire_client'] ?? '') !== ''): ?>
                        <p><strong>Commentaire :</strong> <?php echo echapperTexte($commandeSelectionnee['commentaire_client']); ?></p>
                    <?php endif; ?>
                </article>

                <article class="detail-card">
                    <h3>Produits commandes</h3>
                    <ul class="liste-produits">
                        <?php foreach (($commandeSelectionnee['articles'] ?? []) as $article): ?>
                            <li>
                                <?php echo (int) ($article['quantite'] ?? 0); ?> x
                                <?php echo echapperTexte($article['nom_produit'] ?? ''); ?>
                                - <?php echo number_format((float) ($article['prix_unitaire'] ?? 0), 2, ',', ' '); ?> EUR
                            </li>
                        <?php endforeach; ?>
                    </ul>
                    <p class="total">Total : <?php echo number_format($montantCommandeSelectionnee, 2, ',', ' '); ?> EUR</p>
                </article>
            </div>

            <div class="detail-grid">
                <article class="detail-card">
                    <h3>Changer le statut</h3>
                    <form class="formulaire-detail" method="GET" action="">
                        <input type="hidden" name="commande_id" value="<?php echo (int) ($commandeSelectionnee['id'] ?? 0); ?>">
                        <label for="statut_commande">Nouveau statut</label>
                        <select id="statut_commande" name="statut_commande_affichage">
                            <?php foreach ($definitionsDesStatuts as $codeStatut => $definitionDuStatut): ?>
                                <option value="<?php echo echapperTexte($codeStatut); ?>" <?php echo (($commandeSelectionnee['statut_commande'] ?? '') === $codeStatut) ? 'selected' : ''; ?>>
                                    <?php echo echapperTexte($definitionDuStatut['titre']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <button type="button" class="btn-principal bouton-inactif">Mettre a jour le statut</button>
                    </form>
                </article>

                <article class="detail-card">
                    <h3>Attribuer a un livreur disponible</h3>
                    <form class="formulaire-detail" method="GET" action="">
                        <input type="hidden" name="commande_id" value="<?php echo (int) ($commandeSelectionnee['id'] ?? 0); ?>">
                        <label for="livreur_commande">Livreur disponible</label>
                        <select id="livreur_commande" name="livreur_affichage">
                            <option value="">Choisir un livreur</option>
                            <?php foreach ($listeDesLivreursDisponibles as $livreurDisponible): ?>
                                <option value="<?php echo (int) $livreurDisponible['id']; ?>">
                                    <?php
                                    echo echapperTexte(
                                        $livreurDisponible['nom']
                                        . ' - '
                                        . $livreurDisponible['statut']
                                        . ' - Zone '
                                        . $livreurDisponible['zone']
                                    );
                                    ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <button type="button" class="btn-secondaire bouton-inactif">Attribuer le livreur</button>
                    </form>
                </article>
            </div>

            <article class="detail-card">
                <h3>Livreurs actuellement disponibles</h3>
                <div class="liste-livreurs">
                    <?php foreach ($listeDesLivreursDisponibles as $livreurDisponible): ?>
                        <div class="livreur-card">
                            <p><strong><?php echo echapperTexte($livreurDisponible['nom']); ?></strong></p>
                            <p><?php echo echapperTexte($livreurDisponible['telephone']); ?></p>
                            <p><?php echo echapperTexte($livreurDisponible['statut']); ?></p>
                            <p>Zone : <?php echo echapperTexte($livreurDisponible['zone']); ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>
            </article>
        </section>
    <?php endif; ?>

    <?php foreach ($definitionsDesStatuts as $codeStatut => $definitionDuStatut): ?>
        <section class="bloc-statut">
            <h2 class="titre-section"><?php echo echapperTexte($definitionDuStatut['titre']); ?></h2>
            <p class="description-section"><?php echo echapperTexte($definitionDuStatut['description']); ?></p>

            <?php if (empty($commandesParStatut[$codeStatut])): ?>
                <p class="etat-vide">Aucune commande dans cette categorie pour le moment.</p>
            <?php else: ?>
                <?php foreach ($commandesParStatut[$codeStatut] as $commande): ?>
                    <?php $montantTotal = calculerMontantTotalCommande($commande['articles'] ?? []); ?>

                    <article class="commande-card">
                        <div class="commande-header">
                            <span class="commande-numero"><?php echo echapperTexte($commande['numero_commande'] ?? ''); ?></span>
                            <span class="badge <?php echo echapperTexte(obtenirClasseBadgeCommande($codeStatut)); ?>">
                                <?php echo echapperTexte(obtenirLibelleCourtStatut($codeStatut)); ?>
                            </span>
                        </div>

                        <div class="commande-infos">
                            <p><strong>Client :</strong> <?php echo echapperTexte($commande['client_nom'] ?? ''); ?></p>
                            <p><strong>Telephone :</strong> <?php echo echapperTexte($commande['client_telephone'] ?? ''); ?></p>
                            <p><strong>Heure :</strong> <?php echo echapperTexte($commande['heure_commande'] ?? ''); ?></p>
                            <p><strong>Adresse :</strong> <?php echo echapperTexte($commande['adresse_livraison'] ?? ''); ?></p>
                            <p><strong>Delai estime :</strong> <?php echo echapperTexte($commande['temps_estime'] ?? ''); ?></p>
                        </div>

                        <?php if (($commande['commentaire_client'] ?? '') !== ''): ?>
                            <p class="commentaire-client"><strong>Commentaire client :</strong> <?php echo echapperTexte($commande['commentaire_client']); ?></p>
                        <?php endif; ?>

                        <ul class="liste-produits">
                            <?php foreach (($commande['articles'] ?? []) as $article): ?>
                                <li>
                                    <?php echo (int) ($article['quantite'] ?? 0); ?> x
                                    <?php echo echapperTexte($article['nom_produit'] ?? ''); ?>
                                    - <?php echo number_format((float) ($article['prix_unitaire'] ?? 0), 2, ',', ' '); ?> EUR
                                </li>
                            <?php endforeach; ?>
                        </ul>

                        <p class="total">Total : <?php echo number_format($montantTotal, 2, ',', ' '); ?> EUR</p>

                        <div class="actions-commande">
                            <a class="btn-principal lien-action" href="commande.php?commande_id=<?php echo (int) ($commande['id'] ?? 0); ?>">
                                Voir le detail
                            </a>
                        </div>
                    </article>
                <?php endforeach; ?>
            <?php endif; ?>
        </section>
    <?php endforeach; ?>
</main>

<footer class="site-footer">
    <p>&copy; 2026 Pasta La Vista - Restaurant italien.</p>
</footer>

</body>
</html>
