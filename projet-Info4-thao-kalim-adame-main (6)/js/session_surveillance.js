document.addEventListener('DOMContentLoaded', function () {
    if (!document.body || document.body.dataset.surveillanceSession !== '1') {
        return;
    }

    let redirectionEnCours = false;

    async function verifierSession() {
        if (redirectionEnCours) {
            return;
        }

        try {
            const reponse = await fetch('api_surveillance_session.php', {
                cache: 'no-store',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            const resultat = await reponse.json();

            if (resultat.connecte) {
                return;
            }

            redirectionEnCours = true;

            if (resultat.compte_bloque) {
                alert(resultat.message || 'Votre compte a ete bloque.');
                window.location.href = 'connexion.php?message=compte_bloque';
                return;
            }

            window.location.href = 'connexion.php';
        } catch (erreur) {
        }
    }

    window.setInterval(verifierSession, 1500);
});
