document.addEventListener('DOMContentLoaded', function () {

    const boutonModifier = document.getElementById('btn-modifier-profil');
    const boutonValider = document.getElementById('btn-valider-profil');
    const boutonAnnuler = document.getElementById('btn-annuler-profil');
    const messageRetour = document.getElementById('message-retour-profil');
    const champs = ['nom', 'prenom', 'email', 'telephone'];

    // Afficher/cacher le mot de passe 
    const toggleMdp = document.getElementById('toggle-mdp-profil');
    const inputMdp = document.getElementById('affichage-mdp');
    if (toggleMdp && inputMdp) {
        toggleMdp.addEventListener('click', function () {
            const visible = inputMdp.type === 'text';
            inputMdp.type = visible ? 'password' : 'text';
            toggleMdp.textContent = visible ? '👁️' : '🙈';
        });
    }

    // Passer en mode édition avec animation
    function activerModeEdition() {
        champs.forEach(function (champ) {
            const texte = document.getElementById('valeur-' + champ);
            const input = document.getElementById('input-' + champ);
            if (!texte || !input){
                return;
            }
            input.value = texte.textContent.trim();

            // Animation : fondu croisé
            texte.style.opacity = '0';
            texte.style.transform = 'translateY(-4px)';
            setTimeout(function () {
                texte.style.display = 'none';
                input.style.display = 'block';
                input.style.opacity = '0';
                input.style.transform = 'translateY(4px)';
                requestAnimationFrame(function () {
                    input.style.transition = 'opacity 0.2s, transform 0.2s';
                    input.style.opacity = '1';
                    input.style.transform = 'translateY(0)';
                });
            }, 150);
        });

        boutonModifier.style.display = 'none';
        boutonValider.style.display = 'inline-block';
        boutonAnnuler.style.display = 'inline-block';
        afficherMessage('', '');

        // Focus sur le premier champ après l'animation
        setTimeout(function () {
            document.getElementById('input-nom')?.focus();
        }, 200);
    }

    // Revenir en mode lecture 
    function desactiverModeEdition() {
        champs.forEach(function (champ) {
            const texte = document.getElementById('valeur-' + champ);
            const input = document.getElementById('input-' + champ);
            if (!texte || !input){
                return;
            }
            input.style.opacity = '0';
            setTimeout(function () {
                input.style.display = 'none';
                texte.style.display = 'flex';
                texte.style.opacity = '0';
                texte.style.transform = 'translateY(4px)';
                requestAnimationFrame(function () {
                    texte.style.transition = 'opacity 0.2s, transform 0.2s';
                    texte.style.opacity = '1';
                    texte.style.transform = 'translateY(0)';
                });
            }, 150);
        });

        boutonModifier.style.display = 'inline-block';
        boutonValider.style.display = 'none';
        boutonAnnuler.style.display = 'none';
    }

    // Afficher un message 
    function afficherMessage(texte, type) {
        if (!messageRetour){
            return;
        }
        messageRetour.textContent   = texte;
        messageRetour.className     = 'message-retour ' + type;
        messageRetour.style.display = texte ? 'block' : 'none';
        if (texte) {
            messageRetour.style.opacity = '0';
            requestAnimationFrame(function () {
                messageRetour.style.transition = 'opacity 0.3s';
                messageRetour.style.opacity = '1';
            });
        }
    }

    function validerChamps() {
        const nom = document.getElementById('input-nom')?.value.trim() ?? '';
        const prenom = document.getElementById('input-prenom')?.value.trim() ?? '';
        const email = document.getElementById('input-email')?.value.trim() ?? '';
        const telephone = document.getElementById('input-telephone')?.value.trim() ?? '';

        if (!nom || !prenom || !email || !telephone) {
            afficherMessage('❌ Tous les champs sont obligatoires.', 'erreur');
            return null;
        }
        if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
            afficherMessage('❌ Adresse email invalide.', 'erreur');
            return null;
        }
        if (!/^\d{10}$/.test(telephone.replace(/[\s.\-]/g, ''))) {
            afficherMessage('❌ Numéro de téléphone invalide (10 chiffres attendus).', 'erreur');
            return null;
        }
        return { nom, prenom, email, telephone };
    }

    async function envoyerModifications(donnees) {
        boutonValider.disabled = true;
        boutonValider.textContent = '⏳ Enregistrement…';
        try {
            const reponse  = await fetch('api_modifier_profil.php', {
                method:  'POST',
                headers: { 'Content-Type': 'application/json' },
                body:    JSON.stringify(donnees)
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
                afficherMessage('✅ Profil mis à jour avec succès.', 'succes');
            } else {
                afficherMessage('❌ ' + resultat.message, 'erreur');
            }
        } catch (e) {
            afficherMessage('❌ Erreur réseau. Veuillez réessayer.', 'erreur');
        } finally {
            boutonValider.disabled = false;
            boutonValider.textContent = '✅ Valider';
        }
    }

    if (boutonModifier){
        boutonModifier.addEventListener('click', activerModeEdition);
    }
    if (boutonAnnuler) {
        boutonAnnuler.addEventListener('click', function () {
            desactiverModeEdition();
            afficherMessage('', '');
        });
    }
    if (boutonValider)  boutonValider.addEventListener('click', function () {
        const donnees = validerChamps();
        if (donnees) envoyerModifications(donnees);
    });
});