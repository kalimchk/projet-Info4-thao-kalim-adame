<?php

function e(?string $texte): string
{
    return htmlspecialchars((string) $texte, ENT_QUOTES, 'UTF-8');
}

function genererTokenCsrf(): string
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function verifierTokenCsrf(?string $token): bool
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    return is_string($token)
        && isset($_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $token);
}

function refuserRequeteJson(string $message = 'Requête refusée.'): void
{
    echo json_encode([
        'succes' => false,
        'message' => $message,
    ]);
    exit();
}

function normaliserEmail(string $email): string
{
    return strtolower(trim($email));
}

function telephoneValide(string $telephone): bool
{
    return preg_match('/^[0-9 .+\-]{10,20}$/', $telephone) === 1;
}

function motDePasseValide(string $motDePasse): bool
{
    return strlen($motDePasse) >= 8 && strlen($motDePasse) <= 72;
}

function hacherMotDePasse(string $motDePasse): string
{
    return password_hash($motDePasse, PASSWORD_DEFAULT);
}

function verifierMotDePasse(string $motDePasse, array $utilisateur): bool
{
    $hash = (string) ($utilisateur['password_hash'] ?? '');

    if ($hash !== '') {
        if (strpos($hash, 'sha256$') === 0) {
            $morceaux = explode('$', $hash, 3);
            if (count($morceaux) !== 3) {
                return false;
            }

            return hash_equals($morceaux[2], hash('sha256', $morceaux[1] . $motDePasse));
        }

        return password_verify($motDePasse, $hash);
    }

    return hash_equals((string) ($utilisateur['password'] ?? ''), $motDePasse);
}

function migrerMotDePasseUtilisateur(int $identifiantUtilisateur, string $motDePasse): void
{
    $listeDesUtilisateurs = lireUtilisateurs();

    foreach ($listeDesUtilisateurs as $indexUtilisateur => $utilisateur) {
        if ((int) ($utilisateur['id'] ?? 0) === $identifiantUtilisateur) {
            $listeDesUtilisateurs[$indexUtilisateur]['password_hash'] = hacherMotDePasse($motDePasse);
            unset($listeDesUtilisateurs[$indexUtilisateur]['password']);
            sauvegarderUtilisateurs($listeDesUtilisateurs);
            return;
        }
    }
}

function nettoyerUtilisateurPourSession(array $utilisateur): array
{
    unset($utilisateur['password'], $utilisateur['password_hash']);
    return $utilisateur;
}

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
    $dossier = dirname($cheminDuFichier);
    if (!is_dir($dossier)) {
        mkdir($dossier, 0755, true);
    }

    file_put_contents(
        $cheminDuFichier,
        json_encode($donnees, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
        LOCK_EX
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
    $emailNormalise = normaliserEmail($emailUtilisateur);

    foreach ($listeDesUtilisateurs as $utilisateur) {
        if (normaliserEmail($utilisateur['email'] ?? '') === $emailNormalise) {
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
): bool {
    $listeDesUtilisateurs = lireUtilisateurs();
    $emailNormalise = normaliserEmail($emailUtilisateur);

    foreach ($listeDesUtilisateurs as $utilisateur) {
        if (normaliserEmail($utilisateur['email'] ?? '') === $emailNormalise) {
            return false;
        }
    }

    $prochainId = 1;
    foreach ($listeDesUtilisateurs as $utilisateur) {
        $prochainId = max($prochainId, (int) ($utilisateur['id'] ?? 0) + 1);
    }

    $nouvelUtilisateur = [
        'id'             => $prochainId,
        'nom'            => $nomUtilisateur,
        'prenom'         => $prenomUtilisateur,
        'email'          => $emailNormalise,
        'telephone'      => $telephoneUtilisateur,
        'password_hash'  => hacherMotDePasse($motDePasseUtilisateur),
        'statut'         => 'client',
        'est_bloque'     => false,
        'restaurant_id'  => null,
        'restaurant_nom' => null,
    ];

    $listeDesUtilisateurs[] = $nouvelUtilisateur;
    sauvegarderUtilisateurs($listeDesUtilisateurs);

    return true;
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

    $_SESSION['user'] = nettoyerUtilisateurPourSession($utilisateur);

    return [
        'etat' => 'ok',
        'utilisateur' => $_SESSION['user'],
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
                ? 'Votre compte est bloqué.'
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
        if (
            ($commande['livreur_id'] ?? 0) === $identifiantLivreur
            && ($commande['statut_commande'] ?? '') === 'en_livraison'
        ) {
            return $commande;
        }
    }

    return null;
}

function obtenirDefinitionDesStatutsCommande(): array
{
    return [
        'a_preparer' => [
            'titre' => 'Commandes à préparer',
            'description' => 'Les commandes viennent d arriver et doivent entrer en cuisine.',
        ],
        'en_cours' => [
            'titre' => 'Commandes en cours',
            'description' => 'La préparation a commencé et l\'équipe est en train de les traiter.',
        ],
        'en_attente' => [
            'titre' => 'Commandes en attente',
            'description' => 'Les commandes sont pretes partiellement ou attendent une action avant de repartir.',
        ],
        'en_livraison' => [
            'titre' => 'Commandes en livraison',
            'description' => 'Le livreur a récupéré la commande et l\'achemine vers le client.',
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
        'a_preparer' => 'À préparer',
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

function lirePlats(): array
{
    return lireFichierJson(__DIR__ . '/../data/plats.json');
}

function lireMenus(): array
{
    return lireFichierJson(__DIR__ . '/../data/menu.json');
}

function trouverProduitCatalogue(string $identifiantProduit, string $typeProduit): ?array
{
    $typeNormalise = strtolower(trim($typeProduit));

    if (in_array($typeNormalise, ['plat', 'entree', 'dessert'], true)) {
        foreach (lirePlats() as $plat) {
            if (($plat['id'] ?? '') === $identifiantProduit) {
                return [
                    'id' => $plat['id'],
                    'nom' => $plat['nom'] ?? 'Produit',
                    'type' => $plat['type'] ?? $typeProduit,
                    'prix' => (float) ($plat['prix'] ?? 0),
                ];
            }
        }
    }

    if ($typeNormalise === 'menu') {
        foreach (lireMenus() as $menu) {
            if (($menu['idm'] ?? '') === $identifiantProduit) {
                return [
                    'id' => $menu['idm'],
                    'nom' => $menu['nom'] ?? 'Menu',
                    'type' => 'menu',
                    'prix' => (float) ($menu['prix_total'] ?? 0),
                ];
            }
        }
    }

    return null;
}

function trouverProduitCatalogueParNomEtType(string $nomProduit, string $typeProduit): ?array
{
    $nomNormalise = strtolower(trim($nomProduit));
    $typeNormalise = strtolower(trim($typeProduit));

    if ($nomNormalise === '') {
        return null;
    }

    foreach (lirePlats() as $plat) {
        if (
            strtolower(trim($plat['nom'] ?? '')) === $nomNormalise
            && strtolower(trim($plat['type'] ?? '')) === $typeNormalise
        ) {
            return [
                'id' => $plat['id'] ?? '',
                'nom' => $plat['nom'] ?? 'Produit',
                'type' => $plat['type'] ?? $typeProduit,
                'prix' => (float) ($plat['prix'] ?? 0),
            ];
        }
    }

    foreach (lireMenus() as $menu) {
        if (
            strtolower(trim($menu['nom'] ?? '')) === $nomNormalise
            && ($typeNormalise === 'menu' || $typeNormalise === '')
        ) {
            return [
                'id' => $menu['idm'] ?? '',
                'nom' => $menu['nom'] ?? 'Menu',
                'type' => 'menu',
                'prix' => (float) ($menu['prix_total'] ?? 0),
            ];
        }
    }

    return null;
}

function construireArticleCommandeDepuisProduit(array $produit, int $quantite): array
{
    return [
        'produit_id' => (string) ($produit['id'] ?? ''),
        'type_produit' => (string) ($produit['type'] ?? ''),
        'nom_produit' => (string) ($produit['nom'] ?? 'Produit'),
        'quantite' => max(1, $quantite),
        'prix_unitaire' => max(0, (float) ($produit['prix'] ?? 0)),
    ];
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
