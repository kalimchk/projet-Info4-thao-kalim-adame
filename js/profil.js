document.addEventListener('DOMContentLoaded', function () {

    const boutonModifier  = document.getElementById('btn-modifier-profil');
    const boutonValider   = document.getElementById('btn-valider-profil');
    const boutonAnnuler   = document.getElementById('btn-annuler-profil');
    const messageRetour   = document.getElementById('message-retour-profil');

    const champs = ['nom', 'prenom', 'email', 'telephone'];

    function activerModeEdition() {
        champs.forEach(function (champ) {
            const texte = document.getElementById('valeur-' + champ);
            const input = document.getElementById('input-' + champ);
            if (texte && input) {
                input.value = texte.textContent.trim();
                texte.style.display = 'none';
                input.style.display = 'block';
                input.focus();
            }
        });
        boutonModifier.style.display = 'none';
        boutonValider.style.display  = 'inline-block';
        boutonAnnuler.style.display  = 'inline-block';
        afficherMessage('', '');
    }

    function desactiverModeEdition() {
        champs.forEach(function (champ) {
            const texte = document.getElementById('valeur-' + champ);
            const input = document.getElementById('input-' + champ);
            if (texte && input) {
                texte.style.display = 'block';
                input.style.display = 'none';
            }
        });
        boutonModifier.style.display = 'inline-block';
        boutonValider.style.display  = 'none';
        boutonAnnuler.style.display  = 'none';
    }

    function afficherMessage(texte, type) {
        if (!messageRetour) return;
        messageRetour.textContent   = texte;
        messageRetour.className     = 'message-retour ' + type;
        messageRetour.style.display = texte ? 'block' : 'none';
    }

    function validerChamps() {
        const nom       = document.getElementById('input-nom')?.value.trim()       ?? '';
        const prenom    = document.getElementById('input-prenom')?.value.trim()    ?? '';
        const email     = document.getElementById('input-email')?.value.trim()     ?? '';
        const telephone = document.getElementById('input-telephone')?.value.trim() ?? '';

        if (!nom || !prenom || !email || !telephone) {
            afficherMessage('Tous les champs sont obligatoires.', 'erreur');
            return null;
        }
        const regexEmail = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!regexEmail.test(email)) {
            afficherMessage('Adresse email invalide.', 'erreur');
            return null;
        }
        const regexTel = /^(\d[\s.-]?){9}\d$/;
        if (!regexTel.test(telephone.replace(/\s/g, ''))) {
            afficherMessage('Numéro de téléphone invalide (10 chiffres attendus).', 'erreur');
            return null;
        }
        return { nom, prenom, email, telephone };
    }

    async function envoyerModifications(donnees) {
        boutonValider.disabled = true;
        boutonValider.textContent = 'Enregistrement…';
        try {
            const reponse  = await fetch('api_modifier_profil.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(donnees)
            });
            const resultat = await reponse.json();
            if (resultat.succes) {
                champs.forEach(function (champ) {
                    const texte = document.getElementById('valeur-' + champ);
                    if (texte && resultat.user[champ] !== undefined) {
                        texte.textContent = resultat.user[champ];
                    }
                });
                desactiverModeEdition();
                afficherMessage('Profil mis à jour avec succès.', 'succes');
            } else {
                afficherMessage('❌ ' + resultat.message, 'erreur');
            }
        } catch (erreur) {
            afficherMessage('Erreur réseau. Veuillez réessayer.', 'erreur');
        } finally {
            boutonValider.disabled = false;
            boutonValider.textContent = 'Valider';
        }
    }

    if (boutonModifier) boutonModifier.addEventListener('click', activerModeEdition);
    if (boutonAnnuler)  boutonAnnuler.addEventListener('click', function () {
        desactiverModeEdition();
        afficherMessage('', '');
    });
    if (boutonValider)  boutonValider.addEventListener('click', function () {
        const donnees = validerChamps();
        if (donnees !== null) envoyerModifications(donnees);
    });
});