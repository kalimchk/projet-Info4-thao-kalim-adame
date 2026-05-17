<?php

function normaliserUtilisateur(array $utilisateur): array
{
    $utilisateur['est_bloque'] = (bool) ($utilisateur['est_bloque'] ?? false);
    return $utilisateur;
}

function lireFichierJson(string $cheminDuFichier): array
{
    if (!file_exists($cheminDuFichier)) {
        return [];
    }

    $contenuJson = file_get_contents($cheminDuFichier);
    $donnees = json_decode($contenuJson, true);

    return is_array($donnees) ? $donnees : [];
}

function enregistrerFichierJson(string $cheminDuFichier, array $donnees): void
{
    file_put_contents(
        $cheminDuFichier,
        json_encode($donnees, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
    );
}

function lireUtilisateurs(): array
{
    $cheminDesUtilisateurs = __DIR__ . '/../data/utilisateurs.json';
    $listeDesUtilisateurs = lireFichierJson($cheminDesUtilisateurs);

    foreach ($listeDesUtilisateurs as $indexUtilisateur => $utilisateur) {
        $listeDesUtilisateurs[$indexUtilisateur] = normaliserUtilisateur($utilisateur);
    }

    return $listeDesUtilisateurs;
}

function sauvegarderUtilisateurs(array $listeDesUtilisateurs): void
{
    $cheminDesUtilisateurs = __DIR__ . '/../data/utilisateurs.json';
    enregistrerFichierJson($cheminDesUtilisateurs, $listeDesUtilisateurs);
}

function trouverUtilisateurParEmail(string $emailUtilisateur): ?array
{
    $listeDesUtilisateurs = lireUtilisateurs();

    foreach ($listeDesUtilisateurs as $utilisateur) {
        if (($utilisateur['email'] ?? '') === $emailUtilisateur) {
            return $utilisateur;
        }
    }

    return null;
}

function trouverUtilisateurParId(int $identifiantUtilisateur): ?array
{
    $listeDesUtilisateurs = lireUtilisateurs();

    foreach ($listeDesUtilisateurs as $utilisateur) {
        if ((int) ($utilisateur['id'] ?? 0) === $identifiantUtilisateur) {
            return $utilisateur;
        }
    }

    return null;
}

function utilisateurEstBloque(array $utilisateur): bool
{
    return (bool) ($utilisateur['est_bloque'] ?? false);
}

function ajouterUtilisateur(
    string $nomUtilisateur,
    string $prenomUtilisateur,
    string $emailUtilisateur,
    string $telephoneUtilisateur,
    string $motDePasseUtilisateur
): void {
    $listeDesUtilisateurs = lireUtilisateurs();

    $nouvelUtilisateur = [
        'id' => count($listeDesUtilisateurs) + 1,
        'nom' => $nomUtilisateur,
        'prenom' => $prenomUtilisateur,
        'email' => $emailUtilisateur,
        'telephone' => $telephoneUtilisateur,
        'password' => $motDePasseUtilisateur,
        'statut' => 'client',
        'est_bloque' => false,
        'restaurant_id' => null,
        'restaurant_nom' => null,
    ];

    $listeDesUtilisateurs[] = $nouvelUtilisateur;
    sauvegarderUtilisateurs($listeDesUtilisateurs);
}

function lireCommandes(): array
{
    $cheminDesCommandes = __DIR__ . '/../data/commandes.json';
    return lireFichierJson($cheminDesCommandes);
}

function sauvegarderCommandes(array $listeDesCommandes): void
{
    $cheminDesCommandes = __DIR__ . '/../data/commandes.json';
    enregistrerFichierJson($cheminDesCommandes, $listeDesCommandes);
}

function lirePaiementsEnAttente(): array
{
    $cheminDesPaiements = __DIR__ . '/../data/paiements_en_attente.json';
    return lireFichierJson($cheminDesPaiements);
}

function sauvegarderPaiementsEnAttente(array $listeDesPaiements): void
{
    $cheminDesPaiements = __DIR__ . '/../data/paiements_en_attente.json';
    enregistrerFichierJson($cheminDesPaiements, $listeDesPaiements);
}

function enregistrerPaiementEnAttente(array $paiementEnAttente): void
{
    $listeDesPaiements = lirePaiementsEnAttente();
    $transaction = (string) ($paiementEnAttente['transaction'] ?? '');
    $paiementMisAJour = false;

    foreach ($listeDesPaiements as $indexPaiement => $paiement) {
        if (($paiement['transaction'] ?? '') === $transaction) {
            $listeDesPaiements[$indexPaiement] = $paiementEnAttente;
            $paiementMisAJour = true;
            break;
        }
    }

    if (!$paiementMisAJour) {
        $listeDesPaiements[] = $paiementEnAttente;
    }

    sauvegarderPaiementsEnAttente($listeDesPaiements);
}

function trouverPaiementEnAttenteParTransaction(string $transaction): ?array
{
    $listeDesPaiements = lirePaiementsEnAttente();

    foreach ($listeDesPaiements as $paiement) {
        if (($paiement['transaction'] ?? '') === $transaction) {
            return $paiement;
        }
    }

    return null;
}

function supprimerPaiementEnAttenteParTransaction(string $transaction): void
{
    $listeDesPaiements = lirePaiementsEnAttente();

    foreach ($listeDesPaiements as $indexPaiement => $paiement) {
        if (($paiement['transaction'] ?? '') === $transaction) {
            array_splice($listeDesPaiements, $indexPaiement, 1);
            break;
        }
    }

    sauvegarderPaiementsEnAttente($listeDesPaiements);
}

function deconnecterUtilisateurSession(): void
{
    $_SESSION = [];

    if (session_status() === PHP_SESSION_ACTIVE) {
        session_destroy();
    }
}

function verifierEtatSessionUtilisateur(): array
{
    if (session_status() !== PHP_SESSION_ACTIVE || !isset($_SESSION['user'])) {
        return [
            'etat' => 'absent',
            'utilisateur' => null,
        ];
    }

    $identifiantUtilisateur = (int) ($_SESSION['user']['id'] ?? 0);

    if ($identifiantUtilisateur <= 0) {
        deconnecterUtilisateurSession();
        return [
            'etat' => 'absent',
            'utilisateur' => null,
        ];
    }

    $utilisateur = trouverUtilisateurParId($identifiantUtilisateur);

    if ($utilisateur === null) {
        deconnecterUtilisateurSession();
        return [
            'etat' => 'absent',
            'utilisateur' => null,
        ];
    }

    if (utilisateurEstBloque($utilisateur)) {
        deconnecterUtilisateurSession();
        return [
            'etat' => 'bloque',
            'utilisateur' => null,
        ];
    }

    $_SESSION['user'] = $utilisateur;

    return [
        'etat' => 'ok',
        'utilisateur' => $utilisateur,
    ];
}

function obtenirUtilisateurConnecteOuRediriger(string $urlDeConnexion = 'connexion.php'): array
{
    $etatDeSession = verifierEtatSessionUtilisateur();

    if (($etatDeSession['etat'] ?? '') !== 'ok') {
        $separateur = strpos($urlDeConnexion, '?') !== false ? '&' : '?';
        $urlDeRedirection = $urlDeConnexion;

        if (($etatDeSession['etat'] ?? '') === 'bloque') {
            $urlDeRedirection .= $separateur . 'message=compte_bloque';
        }

        header('Location: ' . $urlDeRedirection);
        exit();
    }

    return $etatDeSession['utilisateur'];
}

function obtenirUtilisateurConnecteOuErreurJson(): array
{
    $etatDeSession = verifierEtatSessionUtilisateur();

    if (($etatDeSession['etat'] ?? '') !== 'ok') {
        echo json_encode([
            'succes' => false,
            'session_valide' => false,
            'compte_bloque' => ($etatDeSession['etat'] ?? '') === 'bloque',
            'message' => ($etatDeSession['etat'] ?? '') === 'bloque'
                ? 'Votre compte est bloque.'
                : 'Non connecte.',
        ]);
        exit();
    }

    return $etatDeSession['utilisateur'];
}

function lireCommandesDuRestaurant(int $identifiantRestaurant): array
{
    $listeDesCommandes = lireCommandes();
    $commandesDuRestaurant = [];

    foreach ($listeDesCommandes as $commande) {
        if (($commande['restaurant_id'] ?? 0) === $identifiantRestaurant) {
            $commandesDuRestaurant[] = $commande;
        }
    }

    usort($commandesDuRestaurant, function (array $commandeA, array $commandeB): int {
        return strcmp($commandeB['heure_commande'] ?? '', $commandeA['heure_commande'] ?? '');
    });

    return $commandesDuRestaurant;
}

function lireCommandeAttribueeAuLivreur(int $identifiantLivreur): ?array
{
    $listeDesCommandes = lireCommandes();

    foreach ($listeDesCommandes as $commande) {
        if (($commande['livreur_id'] ?? 0) === $identifiantLivreur) {
            return $commande;
        }
    }

    return null;
}

function obtenirDefinitionDesStatutsCommande(): array
{
    return [
        'a_preparer' => [
            'titre' => 'Commandes a preparer',
            'description' => 'Les commandes viennent d arriver et doivent entrer en cuisine.',
        ],
        'en_cours' => [
            'titre' => 'Commandes en cours',
            'description' => 'La preparation a commence et l equipe est en train de les traiter.',
        ],
        'en_attente' => [
            'titre' => 'Commandes en attente',
            'description' => 'Les commandes sont pretes partiellement ou attendent une action avant de repartir.',
        ],
        'en_livraison' => [
            'titre' => 'Commandes en livraison',
            'description' => 'Le livreur a recupere la commande et l achemine vers le client.',
        ],
        'livree' => [
            'titre' => 'Commandes livrees',
            'description' => 'Les commandes ont ete remises au client.',
        ],
    ];
}

function obtenirLibelleCourtStatut(string $statutCommande): string
{
    $libellesCourts = [
        'a_preparer' => 'A preparer',
        'en_cours' => 'En cours',
        'en_attente' => 'En attente',
        'en_livraison' => 'En livraison',
        'livree' => 'Livree',
        'abandonnee' => 'Abandonnee',
        'adresse_introuvable' => 'Adresse introuvable',
    ];

    return $libellesCourts[$statutCommande] ?? 'Inconnu';
}

function regrouperCommandesParStatut(array $listeDesCommandes): array
{
    $definitionsDesStatuts = obtenirDefinitionDesStatutsCommande();
    $commandesParStatut = [];

    foreach ($definitionsDesStatuts as $codeStatut => $definitionDuStatut) {
        $commandesParStatut[$codeStatut] = [];
    }

    foreach ($listeDesCommandes as $commande) {
        $codeStatut = $commande['statut_commande'] ?? '';

        if (!isset($commandesParStatut[$codeStatut])) {
            $commandesParStatut[$codeStatut] = [];
        }

        $commandesParStatut[$codeStatut][] = $commande;
    }

    return $commandesParStatut;
}

function compterCommandesParStatut(array $listeDesCommandes): array
{
    $definitionsDesStatuts = obtenirDefinitionDesStatutsCommande();
    $compteurs = [];

    foreach ($definitionsDesStatuts as $codeStatut => $definitionDuStatut) {
        $compteurs[$codeStatut] = 0;
    }

    foreach ($listeDesCommandes as $commande) {
        $codeStatut = $commande['statut_commande'] ?? '';

        if (!isset($compteurs[$codeStatut])) {
            $compteurs[$codeStatut] = 0;
        }

        $compteurs[$codeStatut]++;
    }

    return $compteurs;
}

function calculerMontantTotalCommande(array $listeDesArticles): float
{
    $montantTotalCommande = 0;

    foreach ($listeDesArticles as $article) {
        $quantiteArticle = (int) ($article['quantite'] ?? 0);
        $prixUnitaireArticle = (float) ($article['prix_unitaire'] ?? 0);
        $montantTotalCommande += $quantiteArticle * $prixUnitaireArticle;
    }

    return $montantTotalCommande;
}

function mettreAJourStatutCommande(int $identifiantCommande, string $nouveauStatutCommande): bool
{
    $listeDesCommandes = lireCommandes();
    $commandeTrouvee = false;

    foreach ($listeDesCommandes as $indexCommande => $commande) {
        if (($commande['id'] ?? 0) === $identifiantCommande) {
            $listeDesCommandes[$indexCommande]['statut_commande'] = $nouveauStatutCommande;
            $commandeTrouvee = true;
            break;
        }
    }

    if ($commandeTrouvee) {
        sauvegarderCommandes($listeDesCommandes);
    }

    return $commandeTrouvee;
}
