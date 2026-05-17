<?php
session_start();
require_once __DIR__ . '/config/function.php';
$utilisateurConnecte = obtenirUtilisateurConnecteOuRediriger();

if (($utilisateurConnecte['statut'] ?? '') !== 'restaurateur') {
    header('Location: accueil.html');
    exit();
}

$listeDesLivreursDisponibles = [
    [
        'id' => 4,
        'nom' => 'Nicolas Perrin',
        'telephone' => '06 10 20 30 40',
        'statut' => 'Disponible',
        'zone' => 'Cergy',
    ],
    [
        'id' => 5,
        'nom' => 'Lea Fontaine',
        'telephone' => '06 11 22 33 44',
        'statut' => 'Disponible',
        'zone' => 'Pontoise',
    ],
    [
        'id' => 6,
        'nom' => 'Sami Benali',
        'telephone' => '06 55 44 33 22',
        'statut' => 'Disponible',
        'zone' => 'Eragny',
    ],
];

$identifiantRestaurant = (int) ($utilisateurConnecte['restaurant_id'] ?? 0);
$nomRestaurant = $utilisateurConnecte['restaurant_nom'] ?? 'Restaurant';
$messageRetourCommande = '';
$typeMessageRetourCommande = 'succes';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $actionCommande = trim((string) ($_POST['action_commande'] ?? ''));
    $identifiantCommandeFormulaire = (int) ($_POST['commande_id'] ?? 0);
    $listeDesCommandesComplete = lireCommandes();
    $indexCommandeCible = -1;
    $commandeCible = null;

    foreach ($listeDesCommandesComplete as $indexCommande => $commande) {
        if (
            (int) ($commande['id'] ?? 0) === $identifiantCommandeFormulaire
            && (int) ($commande['restaurant_id'] ?? 0) === $identifiantRestaurant
        ) {
            $indexCommandeCible = $indexCommande;
            $commandeCible = $commande;
            break;
        }
    }

    if ($commandeCible === null) {
        $messageRetourCommande = 'Commande introuvable.';
        $typeMessageRetourCommande = 'erreur';
    } elseif ($actionCommande === 'mettre_a_jour_statut') {
        $nouveauStatut = trim((string) ($_POST['statut_commande'] ?? ''));
        $statutActuel = (string) ($commandeCible['statut_commande'] ?? '');
        $transitionValide = false;

        if ($statutActuel === 'a_preparer' && $nouveauStatut === 'en_cours') {
            $transitionValide = true;
            $listeDesCommandesComplete[$indexCommandeCible]['temps_estime'] = 'En preparation';
        }

        if ($statutActuel === 'en_cours' && $nouveauStatut === 'en_attente') {
            $transitionValide = true;
            $listeDesCommandesComplete[$indexCommandeCible]['temps_estime'] = 'Prete';
        }

        if (!$transitionValide) {
            $messageRetourCommande = 'Changement de statut non autorise.';
            $typeMessageRetourCommande = 'erreur';
        } else {
            $listeDesCommandesComplete[$indexCommandeCible]['statut_commande'] = $nouveauStatut;
            sauvegarderCommandes($listeDesCommandesComplete);
            header('Location: commande.php?commande_id=' . $identifiantCommandeFormulaire . '&message=statut_maj');
            exit();
        }
    } elseif ($actionCommande === 'attribuer_livreur') {
        $identifiantLivreur = (int) ($_POST['livreur_id'] ?? 0);
        $statutActuel = (string) ($commandeCible['statut_commande'] ?? '');
        $livreurSelectionne = null;

        foreach ($listeDesLivreursDisponibles as $livreurDisponible) {
            if ((int) $livreurDisponible['id'] === $identifiantLivreur) {
                $livreurSelectionne = $livreurDisponible;
                break;
            }
        }

        if ($statutActuel !== 'en_attente') {
            $messageRetourCommande = 'La commande doit etre prete avant attribution.';
            $typeMessageRetourCommande = 'erreur';
        } elseif ($livreurSelectionne === null) {
            $messageRetourCommande = 'Livreur invalide.';
            $typeMessageRetourCommande = 'erreur';
        } else {
            $listeDesCommandesComplete[$indexCommandeCible]['livreur_id'] = (int) $livreurSelectionne['id'];
            $listeDesCommandesComplete[$indexCommandeCible]['livreur_nom'] = $livreurSelectionne['nom'];
            $listeDesCommandesComplete[$indexCommandeCible]['statut_commande'] = 'en_livraison';
            $listeDesCommandesComplete[$indexCommandeCible]['temps_estime'] = 'Livraison en cours';
            sauvegarderCommandes($listeDesCommandesComplete);
            header('Location: commande.php?commande_id=' . $identifiantCommandeFormulaire . '&message=livreur_attribue');
            exit();
        }
    }
}

$listeDesCommandes = lireCommandesDuRestaurant($identifiantRestaurant);
$commandesParStatut = regrouperCommandesParStatut($listeDesCommandes);
$compteursParStatut = compterCommandesParStatut($listeDesCommandes);
$definitionsDesStatuts = obtenirDefinitionDesStatutsCommande();
$identifiantCommandeSelectionnee = (int) ($_POST['commande_id'] ?? ($_GET['commande_id'] ?? 0));

$commandeSelectionnee = null;

foreach ($listeDesCommandes as $commande) {
    if (($commande['id'] ?? 0) === $identifiantCommandeSelectionnee) {
        $commandeSelectionnee = $commande;
        break;
    }
}

if ($messageRetourCommande === '') {
    if (($_GET['message'] ?? '') === 'statut_maj') {
        $messageRetourCommande = 'Statut mis a jour.';
    }

    if (($_GET['message'] ?? '') === 'livreur_attribue') {
        $messageRetourCommande = 'Livreur attribue avec succes.';
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

function obtenirOptionsStatutDisponibles(string $statutActuel): array
{
    if ($statutActuel === 'a_preparer') {
        return [
            'en_cours' => 'Passer en preparation',
            'en_attente' => 'Passer directement a prete',
        ];
    }

    if ($statutActuel === 'en_cours') {
        return [
            'en_attente' => 'Passer a prete',
        ];
    }

    return [];
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
    <title>Commandes - Pasta La Vista</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/darkmode.css">
</head>
<body class="page-commande" data-surveillance-session="1">

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

<main class="commandes-container">
    <h1>Liste detaillee des commandes</h1>
    <p class="commande-intro">Restaurant connecte : <?php echo echapperTexte($nomRestaurant); ?></p>
    <?php if ($messageRetourCommande !== ''): ?>
        <p class="message-retour <?php echo $typeMessageRetourCommande === 'erreur' ? 'erreur' : 'succes'; ?>">
            <?php echo echapperTexte($messageRetourCommande); ?>
        </p>
    <?php endif; ?>

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
        <?php $optionsStatutDisponibles = obtenirOptionsStatutDisponibles((string) ($commandeSelectionnee['statut_commande'] ?? '')); ?>

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
                    <form class="formulaire-detail" method="POST" action="">
                        <input type="hidden" name="action_commande" value="mettre_a_jour_statut">
                        <input type="hidden" name="commande_id" value="<?php echo (int) ($commandeSelectionnee['id'] ?? 0); ?>">
                        <label for="statut_commande">Nouveau statut</label>
                        <select id="statut_commande" name="statut_commande">
                            <?php if (!empty($optionsStatutDisponibles)): ?>
                                <option value="">Choisir une action</option>
                                <?php foreach ($optionsStatutDisponibles as $codeStatut => $libelleStatut): ?>
                                    <option value="<?php echo echapperTexte($codeStatut); ?>">
                                        <?php echo echapperTexte($libelleStatut); ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <option value="">Aucune action disponible</option>
                            <?php endif; ?>
                        </select>
                        <button
                            type="submit"
                            class="btn-principal"
                            <?php echo !empty($optionsStatutDisponibles) ? '' : 'disabled'; ?>
                        >
                            Mettre a jour le statut
                        </button>
                    </form>
                </article>

                <article class="detail-card">
                    <h3>Attribuer a un livreur disponible</h3>
                    <form class="formulaire-detail" method="POST" action="">
                        <input type="hidden" name="action_commande" value="attribuer_livreur">
                        <input type="hidden" name="commande_id" value="<?php echo (int) ($commandeSelectionnee['id'] ?? 0); ?>">
                        <label for="livreur_commande">Livreur disponible</label>
                        <select id="livreur_commande" name="livreur_id">
                            <option value="">Choisir un livreur</option>
                            <?php foreach ($listeDesLivreursDisponibles as $livreurDisponible): ?>
                                <option
                                    value="<?php echo (int) $livreurDisponible['id']; ?>"
                                    <?php echo (($commandeSelectionnee['livreur_id'] ?? 0) === (int) $livreurDisponible['id']) ? 'selected' : ''; ?>
                                >
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
                        <button
                            type="submit"
                            class="btn-secondaire"
                            <?php echo (($commandeSelectionnee['statut_commande'] ?? '') === 'en_attente') ? '' : 'disabled'; ?>
                        >
                            Attribuer le livreur
                        </button>
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

<script src="js/darkmode.js"></script>
<script src="js/session_surveillance.js"></script>
</body>
</html>
