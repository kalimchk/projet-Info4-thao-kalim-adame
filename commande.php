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

$messageDeConfirmation = '';

$actionsDeStatut = [
    'demarrer_preparation' => 'en_cours',
    'mettre_en_attente' => 'en_attente',
    'envoyer_en_livraison' => 'en_livraison',
    'marquer_livree' => 'livree',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $actionStatut = $_POST['action_statut'] ?? '';
    $identifiantCommande = (int) ($_POST['commande_id'] ?? 0);

    if (isset($actionsDeStatut[$actionStatut]) && $identifiantCommande > 0) {
        $miseAJourEffectuee = mettreAJourStatutCommande($identifiantCommande, $actionsDeStatut[$actionStatut]);

        if ($miseAJourEffectuee) {
            $messageDeConfirmation = 'Le statut de la commande a bien ete mis a jour.';
        }
    }
}

$identifiantRestaurant = (int) ($utilisateurConnecte['restaurant_id'] ?? 0);
$nomRestaurant = $utilisateurConnecte['restaurant_nom'] ?? 'Restaurant';
$listeDesCommandes = lireCommandesDuRestaurant($identifiantRestaurant);
$commandesParStatut = regrouperCommandesParStatut($listeDesCommandes);
$compteursParStatut = compterCommandesParStatut($listeDesCommandes);
$definitionsDesStatuts = obtenirDefinitionDesStatutsCommande();

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

function obtenirActionsDisponiblesPourCommande(string $statutCommande): array
{
    $actionsParStatut = [
        'a_preparer' => [
            ['code' => 'demarrer_preparation', 'libelle' => 'Passer en cours', 'classe' => 'btn-principal'],
        ],
        'en_cours' => [
            ['code' => 'mettre_en_attente', 'libelle' => 'Mettre en attente', 'classe' => 'btn-secondaire'],
            ['code' => 'envoyer_en_livraison', 'libelle' => 'Passer en livraison', 'classe' => 'btn-principal'],
        ],
        'en_attente' => [
            ['code' => 'envoyer_en_livraison', 'libelle' => 'Envoyer en livraison', 'classe' => 'btn-principal'],
        ],
        'en_livraison' => [
            ['code' => 'marquer_livree', 'libelle' => 'Marquer comme livree', 'classe' => 'btn-secondaire'],
        ],
        'livree' => [],
    ];

    return $actionsParStatut[$statutCommande] ?? [];
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

    <?php if ($messageDeConfirmation !== ''): ?>
        <p class="message-confirmation"><?php echo echapperTexte($messageDeConfirmation); ?></p>
    <?php endif; ?>

    <section class="resume-commandes">
        <?php foreach ($definitionsDesStatuts as $codeStatut => $definitionDuStatut): ?>
            <article class="resume-card">
                <p class="resume-valeur"><?php echo (int) ($compteursParStatut[$codeStatut] ?? 0); ?></p>
                <p class="resume-libelle"><?php echo echapperTexte(obtenirLibelleCourtStatut($codeStatut)); ?></p>
            </article>
        <?php endforeach; ?>
    </section>

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

                        <?php $listeDesActions = obtenirActionsDisponiblesPourCommande($codeStatut); ?>
                        <?php if (!empty($listeDesActions)): ?>
                            <div class="actions-commande">
                                <?php foreach ($listeDesActions as $actionDisponible): ?>
                                    <form method="POST" action="">
                                        <input type="hidden" name="commande_id" value="<?php echo (int) ($commande['id'] ?? 0); ?>">
                                        <input type="hidden" name="action_statut" value="<?php echo echapperTexte($actionDisponible['code']); ?>">
                                        <button type="submit" class="<?php echo echapperTexte($actionDisponible['classe']); ?>">
                                            <?php echo echapperTexte($actionDisponible['libelle']); ?>
                                        </button>
                                    </form>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
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
