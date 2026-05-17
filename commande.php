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
        $transitionValide = in_array($nouveauStatut, ['a_preparer', 'en_cours', 'en_attente', 'en_livraison', 'livree'], true);

        if (!$transitionValide) {
            $messageRetourCommande = 'Changement de statut non autorise.';
            $typeMessageRetourCommande = 'erreur';
        } else {
            $listeDesCommandesComplete[$indexCommandeCible]['statut_commande'] = $nouveauStatut;

            if ($nouveauStatut === 'a_preparer') {
                $listeDesCommandesComplete[$indexCommandeCible]['temps_estime'] = 'En attente';
            } elseif ($nouveauStatut === 'en_cours') {
                $listeDesCommandesComplete[$indexCommandeCible]['temps_estime'] = 'En preparation';
            } elseif ($nouveauStatut === 'en_attente') {
                $listeDesCommandesComplete[$indexCommandeCible]['temps_estime'] = 'Prete';
            } elseif ($nouveauStatut === 'en_livraison') {
                $listeDesCommandesComplete[$indexCommandeCible]['temps_estime'] = 'Livraison en cours';
            } elseif ($nouveauStatut === 'livree') {
                $listeDesCommandesComplete[$indexCommandeCible]['temps_estime'] = 'Terminee';
            }

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
    return [
        'a_preparer' => 'A preparer',
        'en_cours' => 'En cours',
        'en_attente' => 'En attente',
        'en_livraison' => 'En livraison',
        'livree' => 'Livree',
    ];
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

    <section id="detail-commande" class="detail-commande" style="display:none;">
        <div class="detail-header">
            <div>
                <h2 id="detail-titre">Detail de la commande</h2>
                <p class="description-section">Consultez puis modifiez la commande sans recharger la page.</p>
            </div>
            <button type="button" id="fermer-detail-commande" class="btn-retour-detail">Fermer le detail</button>
        </div>

        <div class="detail-grid">
            <article class="detail-card">
                <h3>Informations generales</h3>
                <p><strong>Client :</strong> <span id="detail-client"></span></p>
                <p><strong>Telephone :</strong> <span id="detail-telephone"></span></p>
                <p><strong>Adresse :</strong> <span id="detail-adresse"></span></p>
                <p><strong>Heure :</strong> <span id="detail-heure"></span></p>
                <p><strong>Statut actuel :</strong> <span id="detail-statut"></span></p>
                <p><strong>Delai estime :</strong> <span id="detail-delai"></span></p>
                <p id="detail-commentaire-ligne" style="display:none;"><strong>Commentaire :</strong> <span id="detail-commentaire"></span></p>
            </article>

            <article class="detail-card">
                <h3>Produits commandes</h3>
                <ul id="detail-produits" class="liste-produits"></ul>
                <p class="total">Total : <span id="detail-total">0,00</span> EUR</p>
            </article>
        </div>

        <div class="detail-grid">
            <article class="detail-card">
                <h3>Changer le statut</h3>
                <form class="formulaire-detail" method="POST" action="">
                    <input type="hidden" name="action_commande" value="mettre_a_jour_statut">
                    <input type="hidden" id="detail-commande-id-statut" name="commande_id" value="">
                    <label for="statut_commande">Nouveau statut</label>
                    <select id="statut_commande" name="statut_commande"></select>
                    <button type="submit" id="detail-bouton-statut" class="btn-principal">Mettre a jour le statut</button>
                </form>
            </article>

            <article class="detail-card">
                <h3>Attribuer a un livreur disponible</h3>
                <form class="formulaire-detail" method="POST" action="">
                    <input type="hidden" name="action_commande" value="attribuer_livreur">
                    <input type="hidden" id="detail-commande-id-livreur" name="commande_id" value="">
                    <label for="livreur_commande">Livreur disponible</label>
                    <select id="livreur_commande" name="livreur_id">
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
                    <button type="submit" id="detail-bouton-livreur" class="btn-secondaire">Attribuer le livreur</button>
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

    <?php foreach ($definitionsDesStatuts as $codeStatut => $definitionDuStatut): ?>
        <section class="bloc-statut">
            <h2 class="titre-section"><?php echo echapperTexte($definitionDuStatut['titre']); ?></h2>
            <p class="description-section"><?php echo echapperTexte($definitionDuStatut['description']); ?></p>

            <?php if (empty($commandesParStatut[$codeStatut])): ?>
                <p class="etat-vide">Aucune commande dans cette categorie pour le moment.</p>
            <?php else: ?>
                <?php foreach ($commandesParStatut[$codeStatut] as $commande): ?>
                    <?php $montantTotal = calculerMontantTotalCommande($commande['articles'] ?? []); ?>

                    <article
                        class="commande-card"
                        data-commande="<?php echo htmlspecialchars(json_encode($commande, JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8'); ?>"
                    >
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
                            <button type="button" class="btn-principal lien-action js-voir-detail">
                                Voir le detail
                            </button>
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
<script>
document.addEventListener('DOMContentLoaded', function () {
    const blocDetail = document.getElementById('detail-commande');
    const boutonFermer = document.getElementById('fermer-detail-commande');
    const boutonsVoirDetail = document.querySelectorAll('.js-voir-detail');
    const selectStatut = document.getElementById('statut_commande');
    const selectLivreur = document.getElementById('livreur_commande');
    const boutonStatut = document.getElementById('detail-bouton-statut');
    const boutonLivreur = document.getElementById('detail-bouton-livreur');
    const commentaireLigne = document.getElementById('detail-commentaire-ligne');

    function obtenirLibelleStatut(statutActuel) {
        const libelles = {
            a_preparer: 'A preparer',
            en_cours: 'En cours',
            en_attente: 'En attente',
            en_livraison: 'En livraison',
            livree: 'Livree'
        };

        return libelles[statutActuel] || 'Inconnu';
    }

    function obtenirOptionsStatut(statutActuel) {
        return [
            { value: 'a_preparer', label: 'A preparer' },
            { value: 'en_cours', label: 'En cours' },
            { value: 'en_attente', label: 'En attente' },
            { value: 'en_livraison', label: 'En livraison' },
            { value: 'livree', label: 'Livree' }
        ];
    }

    function formaterMontant(nombre) {
        return Number(nombre || 0).toFixed(2).replace('.', ',');
    }

    function ouvrirDetail(commande) {
        document.getElementById('detail-titre').textContent = 'Detail de la commande ' + (commande.numero_commande || '');
        document.getElementById('detail-client').textContent = commande.client_nom || '';
        document.getElementById('detail-telephone').textContent = commande.client_telephone || '';
        document.getElementById('detail-adresse').textContent = commande.adresse_livraison || '';
        document.getElementById('detail-heure').textContent = commande.heure_commande || '';
        document.getElementById('detail-statut').textContent = obtenirLibelleStatut(commande.statut_commande || '');
        document.getElementById('detail-delai').textContent = commande.temps_estime || '';
        document.getElementById('detail-commentaire').textContent = commande.commentaire_client || '';
        commentaireLigne.style.display = (commande.commentaire_client || '') !== '' ? 'block' : 'none';
        document.getElementById('detail-commande-id-statut').value = commande.id || '';
        document.getElementById('detail-commande-id-livreur').value = commande.id || '';

        const listeProduits = document.getElementById('detail-produits');
        listeProduits.innerHTML = '';
        let total = 0;

        (commande.articles || []).forEach(function (article) {
            const quantite = Number(article.quantite || 0);
            const prix = Number(article.prix_unitaire || 0);
            total += quantite * prix;

            const li = document.createElement('li');
            li.textContent = quantite + ' x ' + (article.nom_produit || '') + ' - ' + formaterMontant(prix) + ' EUR';
            listeProduits.appendChild(li);
        });

        document.getElementById('detail-total').textContent = formaterMontant(total);

        const optionsStatut = obtenirOptionsStatut(commande.statut_commande || '');
        selectStatut.innerHTML = '';
        optionsStatut.forEach(function (optionStatut) {
            const option = document.createElement('option');
            option.value = optionStatut.value;
            option.textContent = optionStatut.label;
            option.selected = optionStatut.value === (commande.statut_commande || '');
            selectStatut.appendChild(option);
        });

        boutonStatut.disabled = false;
        boutonLivreur.disabled = commande.statut_commande !== 'en_attente';
        selectLivreur.disabled = commande.statut_commande !== 'en_attente';

        if (commande.livreur_id) {
            selectLivreur.value = String(commande.livreur_id);
        } else {
            selectLivreur.value = '';
        }

        blocDetail.style.display = 'block';
        blocDetail.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    boutonsVoirDetail.forEach(function (bouton) {
        bouton.addEventListener('click', function () {
            const carte = bouton.closest('.commande-card');
            if (!carte || !carte.dataset.commande) {
                return;
            }

            ouvrirDetail(JSON.parse(carte.dataset.commande));
        });
    });

    if (boutonFermer) {
        boutonFermer.addEventListener('click', function () {
            blocDetail.style.display = 'none';
        });
    }
});
</script>
</body>
</html>
