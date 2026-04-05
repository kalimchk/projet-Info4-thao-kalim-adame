<?php

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
    return lireFichierJson($cheminDesUtilisateurs);
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
