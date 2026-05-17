document.addEventListener('DOMContentLoaded', function () {

    // Afficher/cacher le mot de passe
    const toggleMdp = document.getElementById('toggle-mdp-connexion');
    const inputMdp = document.getElementById('password');
    if (toggleMdp && inputMdp) {
        toggleMdp.addEventListener('click', function () {
            const visible = inputMdp.type === 'text';
            inputMdp.type = visible ? 'password' : 'text';
            toggleMdp.textContent = visible ? '👁️' : '🙈';
        });
    }

    //Compteur de caractères mot de passe 
    const compteur = document.getElementById('compteur-mdp-connexion');
    if (inputMdp && compteur) {
        inputMdp.addEventListener('input', function () {
            compteur.textContent = inputMdp.value.length + ' / 64 caractères';
        });
    }

    //Validation côté client avant envoi 
    const form = document.getElementById('form-connexion');
    if (!form){
        return;
    }

    form.addEventListener('submit', function (e) {
        let valide = true;
        const email = document.getElementById('email');
        const password = document.getElementById('password');
        const errEmail = document.getElementById('erreur-email');
        const errPassword = document.getElementById('erreur-password');

        // Réinitialiser
        [errEmail, errPassword].forEach(function (el) {
            if (el) { 
                el.textContent = ''; 
                el.style.display = 'none'; 
            }
        });
        [email, password].forEach(function (el) {
            if (el){
                el.classList.remove('champ-invalide');
            }
        });

        // Email
        if (!email.value.trim() || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.value.trim())) {
            errEmail.textContent = 'Veuillez saisir une adresse email valide.';
            errEmail.style.display = 'block';
            email.classList.add('champ-invalide');
            valide = false;
        }

        // Mot de passe
        if (!password.value.trim()) {
            errPassword.textContent = 'Le mot de passe est obligatoire.';
            errPassword.style.display = 'block';
            password.classList.add('champ-invalide');
            valide = false;
        }

        if (!valide) e.preventDefault();
    });
});