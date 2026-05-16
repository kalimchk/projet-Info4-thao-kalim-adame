document.addEventListener('DOMContentLoaded', function () {

    document.querySelectorAll('.commande-modifiable').forEach(function (section) {

        const idCommande= parseInt(section.dataset.idCommande, 10);
        const totalAffiche= section.querySelector('.total-commande');
        const listeArticles= section.querySelector('.liste-articles-modifiable');
        const messageFeedback= section.querySelector('.message-modification');
        const zonePaiement= section.querySelector('.zone-paiement-supplementaire');

        if (!idCommande || !listeArticles) return;

        // Animation du total qui change 
        function animerTotal(nouveauMontant) {
            if (!totalAffiche) return;
            totalAffiche.classList.add('total-flash');
            totalAffiche.textContent = 'Total : ' + nouveauMontant.toFixed(2).replace('.', ',') + ' €';
            setTimeout(function () {
                totalAffiche.classList.remove('total-flash');
            }, 600);
        }

        function afficherMessage(texte, type) {
            if (!messageFeedback) return;
            messageFeedback.textContent   = texte;
            messageFeedback.className     = 'message-modification ' + type;
            messageFeedback.style.display = texte ? 'block' : 'none';
            if (texte) {
                messageFeedback.style.opacity = '0';
                requestAnimationFrame(function () {
                    messageFeedback.style.transition = 'opacity 0.3s';
                    messageFeedback.style.opacity = '1';
                });
            }
        }

        function afficherPaiementSupplementaire(difference) {
            if (!zonePaiement) return;
            zonePaiement.innerHTML = `
                <p class="alerte-paiement">
                    ⚠️ Votre commande est désormais plus chère de
                    <strong>${difference.toFixed(2).replace('.', ',')} €</strong>.
                    Un paiement supplémentaire est requis.
                </p>
                <form action="traitement_paiement.php" method="POST">
                    <input type="hidden" name="montant" value="${difference.toFixed(2)}">
                    <input type="hidden" name="id_commande" value="${idCommande}">
                    <input type="hidden" name="mode_retrait" value="supplement">
                    <input type="hidden" name="moment_preparation" value="immediat">
                    <button type="submit" class="btn-paiement-supplement">
                        Payer ${difference.toFixed(2).replace('.', ',')} € avec CYBank
                    </button>
                </form>`;
            zonePaiement.style.display = 'block';
        }

        function echapper(texte) {
            const div = document.createElement('div');
            div.textContent = String(texte ?? '');
            return div.innerHTML;
        }

        // Reconstruire la liste avec animation 
        function reconstruireListeArticles(articles) {
            listeArticles.style.transition = 'opacity 0.15s';
            listeArticles.style.opacity = '0';

            setTimeout(function () {
                listeArticles.innerHTML = '';
                articles.forEach(function (article) {
                    const li = document.createElement('li');
                    li.className = 'article-modifiable';
                    li.innerHTML = `
                        <span class="article-quantite">${article.quantite}</span>
                        <span class="article-nom">× ${echapper(article.nom_produit)}</span>
                        <span class="article-prix">${parseFloat(article.prix_unitaire).toFixed(2).replace('.', ',')} €</span>
                        <span class="article-sous-total">
                            (= ${(article.quantite * article.prix_unitaire).toFixed(2).replace('.', ',')} €)
                        </span>
                        <div class="article-btns">
                            <button type="button" class="btn-retirer-article"
                                    data-nom="${echapper(article.nom_produit)}"
                                    data-prix="${article.prix_unitaire}"
                                    title="Retirer un exemplaire">−</button>
                            <button type="button" class="btn-ajouter-article"
                                    data-nom="${echapper(article.nom_produit)}"
                                    data-prix="${article.prix_unitaire}"
                                    title="Ajouter un exemplaire">+</button>
                        </div>`;
                    listeArticles.appendChild(li);
                });
                attacherEcouteursBoutons();
                listeArticles.style.opacity = '1';
            }, 150);
        }

        async function modifierCommande(nomProduit, prixUnitaire, typeAction) {
            try {
                const reponse  = await fetch('api_modifier_commande.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body:    JSON.stringify({
                        id_commande: idCommande,
                        quantite: 1,
                        article: { nom_produit: nomProduit, prix_unitaire: prixUnitaire, type_action: typeAction }
                    })
                });
                const resultat = await reponse.json();

                if (!resultat.succes) {
                    afficherMessage('❌ ' + resultat.message, 'erreur');
                    return;
                }

                reconstruireListeArticles(resultat.articles);
                animerTotal(resultat.nouveau_montant);

                if (resultat.paiement_requis) {
                    afficherMessage('⚠️ ' + resultat.message, 'avertissement');
                    afficherPaiementSupplementaire(resultat.difference);
                } else if (resultat.ticket_reduction) {
                    afficherMessage('🎟️ ' + resultat.message, 'succes');
                    if (zonePaiement) zonePaiement.style.display = 'none';
                } else {
                    afficherMessage('✅ Commande mise à jour.', 'succes');
                    if (zonePaiement) zonePaiement.style.display = 'none';
                }

            } catch (e) {
                afficherMessage('❌ Erreur réseau. Veuillez réessayer.', 'erreur');
            }
        }

        function attacherEcouteursBoutons() {
            listeArticles.querySelectorAll('.btn-retirer-article').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    modifierCommande(btn.dataset.nom, parseFloat(btn.dataset.prix), 'retirer');
                });
            });
            listeArticles.querySelectorAll('.btn-ajouter-article').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    modifierCommande(btn.dataset.nom, parseFloat(btn.dataset.prix), 'ajouter');
                });
            });
        }

        attacherEcouteursBoutons();
    });
});