document.addEventListener('DOMContentLoaded', function () {
    const message = document.getElementById('message-admin-utilisateur');
    const boutons = document.querySelectorAll('.js-action-blocage');

    function afficherMessage(texte, estSucces) {
        if (!message) {
            return;
        }

        message.textContent = texte;
        message.className = estSucces ? 'message-retour succes' : 'message-retour erreur';
        message.style.display = 'block';
    }

    boutons.forEach(function (bouton) {
        bouton.addEventListener('click', async function () {
            const userId = parseInt(bouton.dataset.userId || '0', 10);
            const estBloque = bouton.dataset.estBloque === '1';
            const action = estBloque ? 'debloquer' : 'bloquer';

            bouton.disabled = true;

            try {
                const reponse = await fetch('api_gestion_blocage_utilisateur.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        user_id: userId,
                        action: action
                    })
                });

                const resultat = await reponse.json();

                if (!resultat.succes) {
                    afficherMessage(resultat.message || 'Action impossible.', false);
                    return;
                }

                const nouveauStatut = resultat.utilisateur && resultat.utilisateur.est_bloque;
                const ligneUtilisateur = bouton.closest('.user');
                const badge = ligneUtilisateur ? ligneUtilisateur.querySelector('.badge-blocage') : null;

                bouton.dataset.estBloque = nouveauStatut ? '1' : '0';
                bouton.textContent = nouveauStatut ? 'Debloquer' : 'Bloquer';
                bouton.classList.toggle('btn-debloquer', nouveauStatut);
                bouton.classList.toggle('btn-bloquer', !nouveauStatut);

                if (badge) {
                    badge.textContent = nouveauStatut ? 'Bloque' : 'Actif';
                    badge.classList.toggle('badge-bloque', nouveauStatut);
                    badge.classList.toggle('badge-actif', !nouveauStatut);
                }

                afficherMessage(resultat.message || 'Action terminee.', true);
            } catch (erreur) {
                afficherMessage('Erreur reseau.', false);
            } finally {
                bouton.disabled = false;
            }
        });
    });
});
