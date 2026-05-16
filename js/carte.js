document.addEventListener('DOMContentLoaded', function () {

    const formulaireFiltres = document.querySelector('.filters-form');
    const grillePlats = document.getElementById('grille-plats');
    const grilleMenus = document.getElementById('grille-menus');
    const selectTri = document.getElementById('select-tri');
    const compteurResultats = document.getElementById('compteur-resultats');
    const indicateurCharge = document.getElementById('indicateur-chargement');

    function setChargement(actif) {
        if (indicateurCharge){
            indicateurCharge.style.display = actif ? 'block' : 'none';
        }
    }

    function mettreAJourCompteur(total) {
        if (compteurResultats){
            compteurResultats.textContent = total + ' résultat' + (total > 1 ? 's' : '');
        }
    }

    function echapper(texte) {
        const div = document.createElement('div');
        div.textContent = String(texte ?? '');
        return div.innerHTML;
    }

    //Construire HTML d'un plat 
    function construireCartePlat(plat) {
        const saveurs = (plat.informations?.saveurs    || []).join(', ');
        const allergenes = (plat.informations?.allergenes || []).join(', ');
        return `
            <article class="dish-card" data-prix="${plat.prix}" data-type="${(plat.type||'').toLowerCase()}">
                <div class="dish-head">
                    <h3>${echapper(plat.nom)}</h3>
                    <span class="price">${parseFloat(plat.prix).toFixed(2).replace('.', ',')} EUR</span>
                </div>
                <p class="type">Type : ${echapper(plat.type)}</p>
                <p class="desc">${echapper(plat.description)}</p>
                ${saveurs    ? `<p class="flavors"><strong>Saveurs :</strong> ${echapper(saveurs)}</p>`       : ''}
                ${allergenes ? `<p class="allergens"><strong>Allergènes :</strong> ${echapper(allergenes)}</p>` : ''}
                <a class="add-cart" href="carte.php?type=${encodeURIComponent(plat.type)}&id=${encodeURIComponent(plat.id)}">
                    Ajouter au panier
                </a>
            </article>`;
    }

    // Construire HTML d'un menu 
    function construireCarteMenu(menu) {
        const creneaux = (menu.creneaux_limites || []).join(', ');
        return `
            <article class="dish-card" data-prix="${menu.prix_total}" data-type="menu">
                <div class="dish-head">
                    <h3>${echapper(menu.nom)}</h3>
                    <span class="price">${parseFloat(menu.prix_total).toFixed(2).replace('.', ',')} EUR</span>
                </div>
                <p class="type">Type : Menu</p>
                <p class="desc">${echapper(menu.description)}</p>
                <p class="flavors">
                    <strong>Créneaux :</strong> ${echapper(creneaux)}<br>
                    <strong>Personnes min :</strong> ${echapper(String(menu.nb_personnes_min ?? 1))}
                </p>
                <a class="add-cart" href="carte.php?type=menu&id=${encodeURIComponent(menu.idm)}">
                    Ajouter au panier
                </a>
            </article>`;
    }

    //Transition animée sur une grille 
    function animerGrille(grille, nouveauContenu) {
        if (!grille){
            return;
        }
        grille.style.transition = 'opacity 0.2s';
        grille.style.opacity = '0';
        setTimeout(function () {
            grille.innerHTML = nouveauContenu;
            grille.style.opacity = '1';

            // Animation en cascade sur chaque carte
            grille.querySelectorAll('.dish-card').forEach(function (carte, i) {
                carte.style.opacity = '0';
                carte.style.transform = 'translateY(16px)';
                carte.style.transition = 'none';
                setTimeout(function () {
                    carte.style.transition = 'opacity 0.25s, transform 0.25s';
                    carte.style.opacity = '1';
                    carte.style.transform = 'translateY(0)';
                }, i * 40);
            });
        }, 200);
    }

    //Filtres actifs
    function obtenirFiltresActifs() {
        const saveurs = [], allergenes = [], types = [];
        if (!formulaireFiltres){
            return { saveurs, allergenes, types };
        }
        formulaireFiltres.querySelectorAll('input[name="saveurs"]:checked').forEach(cb    => saveurs.push(cb.value));
        formulaireFiltres.querySelectorAll('input[name="allergenes"]:checked').forEach(cb => allergenes.push(cb.value));
        formulaireFiltres.querySelectorAll('input[name="types"]:checked').forEach(cb      => types.push(cb.value));
        return { saveurs, allergenes, types };
    }

    function construireURL(filtres) {
        const params = new URLSearchParams();
        if (filtres.saveurs.length){
            params.set('saveurs',    filtres.saveurs.join(','));
        }
        if (filtres.allergenes.length){
            params.set('allergenes', filtres.allergenes.join(','));
        }
        if (filtres.types.length){
            params.set('types',      filtres.types.join(','));
        }
        return 'api_filtrer_plats.php?' + params.toString();
    }

    //Tri côté client 
    function trierArticles(grille, critere) {
        if (!grille){
            return;
        }
        const articles = Array.from(grille.querySelectorAll('.dish-card'));
        articles.sort(function (a, b) {
            const prixA = parseFloat(a.dataset.prix || '0');
            const prixB = parseFloat(b.dataset.prix || '0');
            if (critere === 'prix-asc'){
                return prixA - prixB;
            }
            if (critere === 'prix-desc'){
                return prixB - prixA;
            }
            return 0;
        });
        articles.forEach(function (a) { grille.appendChild(a); });
    }

    function appliquerTriActuel() {
        if (!selectTri){
            return;
        }
        trierArticles(grillePlats, selectTri.value);
        trierArticles(grilleMenus, selectTri.value);
    }

    // Requête asynchrone principale 
    async function appliquerFiltres() {
        setChargement(true);
        try {
            const reponse = await fetch(construireURL(obtenirFiltresActifs()));
            const resultat = await reponse.json();
            if (!resultat.succes){
                return;
            }

            const htmlPlats = resultat.plats.length === 0
                ? '<p class="etat-vide">Aucun plat ne correspond à votre sélection.</p>'
                : resultat.plats.map(construireCartePlat).join('');

            const htmlMenus = resultat.menus.length === 0
                ? '<p class="etat-vide">Aucun menu ne correspond à votre sélection.</p>'
                : resultat.menus.map(construireCarteMenu).join('');

            animerGrille(grillePlats, htmlPlats);
            animerGrille(grilleMenus, htmlMenus);

            mettreAJourCompteur(resultat.total);

            // Réappliquer tri après animation (délai pour laisser le DOM se reconstruire)
            setTimeout(appliquerTriActuel, 250);

        } catch (e) {
            console.error('Erreur filtres :', e);
        } finally {
            setChargement(false);
        }
    }
    let timerFiltres = null;
    if (formulaireFiltres) {
        formulaireFiltres.querySelectorAll('input[type="checkbox"]').forEach(function (cb) {
            cb.addEventListener('change', function () {
                clearTimeout(timerFiltres);
                timerFiltres = setTimeout(appliquerFiltres, 300);
            });
        });
        const boutonReset = formulaireFiltres.querySelector('[type="reset"]');
        if (boutonReset) {
            boutonReset.addEventListener('click', function () {
                setTimeout(appliquerFiltres, 50);
            });
        }
    }
    if (selectTri) selectTri.addEventListener('change', appliquerTriActuel);
});