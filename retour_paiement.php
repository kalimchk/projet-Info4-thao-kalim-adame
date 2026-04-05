<?php
session_start();
require_once __DIR__ . '/config/function.php';
require_once __DIR__ . '/config/getapikey.php';

$transaction = $_GET['transaction'] ?? '';
$montant = $_GET['montant'] ?? '';
$vendeur = $_GET['vendeur'] ?? '';
$statut = $_GET['statut'] ?? ($_GET['status'] ?? ''); 
$control_recu = $_GET['control'] ?? '';


$api_key = getAPIKey($vendeur);
$chaine_verif = $api_key . "#" . $transaction . "#" . $montant . "#" . $vendeur . "#" . $statut . "#";
$control_calcule = md5($chaine_verif);

$paiementReussi = false;
$messageErreur = "";

if ($control_calcule === $control_recu) {
    if ($statut === 'accepted') {
        $paiementReussi = true;
        
        $utilisateur = $_SESSION['user'];
        $options = $_SESSION['options_commande'] ?? [];
        $listeCommandes = lireCommandes();
        
      
        $nouvelId = count($listeCommandes) > 0 ? max(array_column($listeCommandes, 'id')) + 1 : 101;
        

        $articlesCommande = [];
        foreach ($_SESSION['panier'] as $article) {
            $articlesCommande[] = [
                'nom_produit' => $article['nom'],
                'quantite' => $article['quantite'],
                'prix_unitaire' => $article['prix']
            ];
        }

       
        $texteCommentaire = "Mode : " . ucfirst($options['mode_retrait']) . ". ";
        if (($options['moment_preparation'] ?? '') === 'planifie') {
            $texteCommentaire .= "A PREPARER POUR LE : " . ($options['date_planifiee'] ?? '') . " à " . ($options['heure_planifiee'] ?? '');
        } else {
            $texteCommentaire .= "Préparation immédiate requise.";
        }

  
        $nouvelleCommande = [
            'id' => $nouvelId,
            'restaurant_id' => 1,
            'restaurant_nom' => 'Pasta La Vista',
            'numero_commande' => 'PLV-' . $nouvelId,
            'statut_commande' => 'a_preparer',
            'heure_commande' => date('Y-m-d H:i:s'),
            'client_nom' => $utilisateur['prenom'] . ' ' . $utilisateur['nom'],
            'client_telephone' => $utilisateur['telephone'] ?? '',
            'adresse_livraison' => 'Adresse liée au compte client', 
            'commentaire_client' => $texteCommentaire,
            'temps_estime' => 'En attente',
            'articles' => $articlesCommande
        ];

        $listeCommandes[] = $nouvelleCommande;
        sauvegarderCommandes($listeCommandes);

        unset($_SESSION['panier']);
        unset($_SESSION['options_commande']);
    } else {
        $messageErreur = "Le paiement a été refusé par la banque ou annulé par l'utilisateur.";
    }
} else {
    $messageErreur = "Erreur de sécurité : la signature des données provenant de la banque est invalide.";
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Retour de Paiement - Pasta La Vista</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="page-carte">
    <header class="site-header">
        <a class="logo" href="accueil.php"><img class="logo-img" src="logo/logo-pasta-la-vista.png" alt="Logo Pasta La Vista"><span class="logo-text">Pasta La Vista</span></a>
        <nav class="navbar">
            
            <a class="active" href="accueil.html">Accueil</a>
            <a href="carte.php">Carte</a>
            <?php if (isset($_SESSION['user'])): ?>
                <a href="profil.php">Mon Profil</a>
                <a href="deconnexion.php" style="color: #a45742; font-weight: bold;">Déconnexion</a>
            <?php else: ?>
                <a href="connexion.php">Connexion</a>
            <?php endif; ?>
        </nav>
    </header>

    <main style="max-width: 600px; margin: 60px auto; text-align: center;">
        <section class="panel">
            <?php if ($paiementReussi): ?>
                <h2 style="color: var(--accent-deep);">🎉 Paiement réussi !</h2>
                <p class="intro">Merci pour votre commande, <strong><?= htmlspecialchars($utilisateur['prenom']) ?></strong>.</p>
                <p>Votre commande a été transmise à nos cuisines et son statut est passé à "À préparer".</p>
                <div style="margin-top: 30px;">
                    <a href="profil.php" class="btn" style="padding: 12px 24px; border-radius: 999px; background: var(--accent); color: var(--bg); text-decoration: none;">Suivre ma commande</a>
                </div>
            <?php else: ?>
                <h2 style="color: #a45742;">❌ Échec du paiement</h2>
                <p class="intro"><?= htmlspecialchars($messageErreur) ?></p>
                <p>Vos articles sont toujours dans votre panier.</p>
                <div style="margin-top: 30px;">
                    <a href="panier.php" class="btn" style="padding: 12px 24px; border-radius: 999px; background: var(--muted); color: var(--bg); text-decoration: none;">Retour au panier</a>
                </div>
            <?php endif; ?>
        </section>
    </main>

    <footer class="site-footer">
        <p>&copy; 2026 Pasta La Vista - Restaurant italien.</p>
    </footer>
</body>
</html>