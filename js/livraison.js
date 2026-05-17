document.addEventListener('DOMContentLoaded', function () {
    const boutonLivree = document.getElementById('btn-marquer-livree');
    const boutonAbandonnee = document.getElementById('btn-marquer-abandonnee');
    const selectMotif = document.getElementById('select-motif-abandon');
    const idCommande = parseInt(document.getElementById('commande-id')?.value ?? '0', 10);

    if (!boutonLivree || !boutonAbandonnee || idCommande <= 0) {
        return;
    }

    boutonLivree.addEventListener('click', function () {
        if (!confirm('Confirmer la livraison de cette commande ?')) {
            return;
        }
        changerStatutLivraison('livree', '');
    });

    boutonAbandonnee.addEventListener('click', function () {
        const motif = selectMotif ? selectMotif.value : '';
        if (!motif) {
            afficherMessage('Veuillez selectionner un motif d abandon.', 'erreur');
            return;
        }
        if (!confirm('Confirmer l abandon de cette livraison ?')) {
            return;
        }
        changerStatutLivraison('abandonnee', motif);
    });

    function changerStatutLivraison(nouveauStatut, motifAbandon) {
        boutonLivree.disabled = true;
        boutonAbandonnee.disabled = true;

        fetch('api_livraison_statut.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                id_commande: idCommande,
                nouveau_statut: nouveauStatut,
                motif_abandon: motifAbandon,
            }),
        })
            .then(function (reponse) {
                return reponse.json();
            })
            .then(function (donnees) {
                if (donnees.succes) {
                    afficherMessage(donnees.message, 'succes');
                    masquerActionsLivraison();
                    mettreAJourAffichageStatut(nouveauStatut);
                } else {
                    afficherMessage(donnees.message || 'Une erreur est survenue.', 'erreur');
                    boutonLivree.disabled = false;
                    boutonAbandonnee.disabled = false;
                }
            })
            .catch(function () {
                afficherMessage('Erreur reseau. Veuillez reessayer.', 'erreur');
                boutonLivree.disabled = false;
                boutonAbandonnee.disabled = false;
            });
    }

    function afficherMessage(texte, type) {
        let zone = document.getElementById('livraison-message');
        if (!zone) {
            zone = document.createElement('p');
            zone.id = 'livraison-message';
            const section = document.querySelector('.livraison-actions .livraison-card');
            if (section) {
                section.appendChild(zone);
            }
        }
        zone.textContent = texte;
        zone.className = type === 'succes' ? 'message-succes' : 'message-erreur';
    }

    function masquerActionsLivraison() {
        const actionsDiv = document.querySelector('.actions-livraison');
        if (actionsDiv) {
            actionsDiv.style.display = 'none';
        }
        if (selectMotif) {
            selectMotif.closest('.motif-abandon')?.style.setProperty('display', 'none');
        }
    }

    function mettreAJourAffichageStatut(nouveauStatut) {
        const labels = {
            livree: 'Livree',
            abandonnee: 'Abandonnee',
        };
        const statutElement = document.getElementById('statut-commande-affiche');
        if (statutElement) {
            statutElement.textContent = labels[nouveauStatut] ?? nouveauStatut;
        }
    }
});
