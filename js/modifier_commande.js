document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.commande-modifiable').forEach(function (section) {
        const idCommande = parseInt(section.dataset.idCommande, 10);
        const totalAffiche = section.querySelector('.total-commande');
        const listeArticles = section.querySelector('.liste-articles-modifiable');
        const messageFeedback = section.querySelector('.message-modification');

        if (!idCommande || !listeArticles) {
            return;
        }

        function animerTotal(nouveauMontant) {
            if (!totalAffiche) {
                return;
            }

            totalAffiche.classList.add('total-flash');
            totalAffiche.textContent = 'Total : ' + nouveauMontant.toFixed(2).replace('.', ',') + ' EUR';
            setTimeout(function () {
                totalAffiche.classList.remove('total-flash');
            }, 600);
        }

        function afficherMessage(texte, type) {
            if (!messageFeedback) {
                return;
            }

            messageFeedback.textContent = texte;
            messageFeedback.className = 'message-modification ' + type;
            messageFeedback.style.display = texte ? 'block' : 'none';
        }

        function echapper(texte) {
            const div = document.createElement('div');
            div.textContent = String(texte ?? '');
            return div.innerHTML;
        }

        function reconstruireListeArticles(articles) {
            listeArticles.innerHTML = '';

            articles.forEach(function (article) {
                const quantite = Number(article.quantite || 0);
                const prix = Number(article.prix_unitaire || 0);
                const li = document.createElement('li');
                li.className = 'article-modifiable';
                li.innerHTML = `
                    <span class="article-quantite">${quantite}</span>
                    <span class="article-nom">x ${echapper(article.nom_produit)}</span>
                    <span class="article-prix">${prix.toFixed(2).replace('.', ',')} EUR</span>
                    <span class="article-sous-total">(= ${(quantite * prix).toFixed(2).replace('.', ',')} EUR)</span>
                    <div class="article-btns">
                        <button type="button" class="btn-retirer-article"
                                data-nom="${echapper(article.nom_produit)}"
                                title="Retirer un exemplaire">-</button>
                    </div>`;
                listeArticles.appendChild(li);
            });

            attacherEcouteursBoutons();
        }

        async function modifierCommande(nomProduit, typeAction) {
            try {
                const reponse = await fetch('api_modifier_commande.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        id_commande: idCommande,
                        quantite: 1,
                        csrf_token: window.CSRF_TOKEN || '',
                        article: { nom_produit: nomProduit, type_action: typeAction }
                    })
                });
                const resultat = await reponse.json();

                if (!resultat.succes) {
                    afficherMessage('Erreur : ' + (resultat.message || 'action impossible.'), 'erreur');
                    return;
                }

                reconstruireListeArticles(resultat.articles);
                animerTotal(Number(resultat.nouveau_montant || 0));

                afficherMessage(resultat.message || 'Commande mise a jour.', 'succes');
            } catch (e) {
                afficherMessage('Erreur reseau. Veuillez reessayer.', 'erreur');
            }
        }

        function attacherEcouteursBoutons() {
            listeArticles.querySelectorAll('.btn-retirer-article').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    modifierCommande(btn.dataset.nom, 'retirer');
                });
            });
        }

        attacherEcouteursBoutons();
    });
});
