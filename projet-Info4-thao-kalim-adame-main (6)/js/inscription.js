document.addEventListener('DOMContentLoaded', function () {

    // Afficher/cacher le mot de passe
    const toggleMdp = document.getElementById('toggle-mdp-inscription');
    const inputMdp = document.getElementById('password');
    if (toggleMdp && inputMdp) {
        toggleMdp.addEventListener('click', function () {
            const visible = inputMdp.type === 'text';
            inputMdp.type = visible ? 'password' : 'text';
            toggleMdp.textContent = visible ? '👁️' : '🙈';
        });
    }

    //Compteur de caractères 
    const compteur = document.getElementById('compteur-mdp-inscription');
    if (inputMdp && compteur) {
        inputMdp.addEventListener('input', function () {
            const len = inputMdp.value.length;
            compteur.textContent = len + ' / 64 caractères';
            compteur.style.color = len >= 60 ? '#a45742' : '';
        });
    }

    //Validation en temps réel 
    function afficherErreur(id, texte) {
        const el = document.getElementById(id);
        const champ = document.getElementById(id.replace('erreur-', ''));
        if (el) {
             el.textContent = texte; 
             el.style.display = texte ? 'block' : 'none'; 
        }
        if (champ){
            champ.classList.toggle('champ-invalide', texte !== '');
        }
    }

    document.getElementById('nom')?.addEventListener('blur', function () {
        afficherErreur('erreur-nom', this.value.trim() === '' ? 'Le nom est obligatoire.' : '');
    });
    document.getElementById('prenom')?.addEventListener('blur', function () {
        afficherErreur('erreur-prenom', this.value.trim() === '' ? 'Le prénom est obligatoire.' : '');
    });
    document.getElementById('email')?.addEventListener('blur', function () {
        const ok = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(this.value.trim());
        afficherErreur('erreur-email', !ok ? 'Adresse email invalide.' : '');
    });
    document.getElementById('telephone')?.addEventListener('blur', function () {
        const ok = /^\d{10}$/.test(this.value.replace(/[\s.\-]/g, ''));
        afficherErreur('erreur-telephone', !ok ? 'Téléphone invalide (10 chiffres).' : '');
    });
    document.getElementById('password')?.addEventListener('blur', function () {
        afficherErreur('erreur-password', this.value.length < 6 ? 'Mot de passe trop court (6 caractères min).' : '');
    });

    //Validation complète avant envoi
    const form = document.getElementById('form-inscription');
    if (!form){
        return;
    }

    form.addEventListener('submit', function (e) {
        let valide = true;

        const nom = document.getElementById('nom');
        const prenom = document.getElementById('prenom');
        const email = document.getElementById('email');
        const telephone = document.getElementById('telephone');
        const password = document.getElementById('password');

        if (!nom.value.trim()){ 
            afficherErreur('erreur-nom','Le nom est obligatoire.');         
            valide = false; 
        }
        if (!prenom.value.trim()){ 
            afficherErreur('erreur-prenom','Le prénom est obligatoire.');      
            valide = false; 
        }
        if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.value.trim())){
             afficherErreur('erreur-email','Email invalide.');
            valide = false; 
        }
        if (!/^\d{10}$/.test(telephone.value.replace(/[\s.\-]/g, ''))){
            afficherErreur('erreur-telephone','Téléphone invalide (10 chiffres).'); 
            valide = false; 
        }
        if (password.value.length < 6){
            afficherErreur('erreur-password','Mot de passe trop court.');
            valide = false; 
        }

        if (!valide){
            e.preventDefault();
        }
    });
});