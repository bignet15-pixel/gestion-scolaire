// @ts-nocheck


import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.start();

document.addEventListener('DOMContentLoaded', function () {
    const noteInputs = document.querySelectorAll('.js-note-input');

    function calculerAppreciation(valeur, bareme) {
        if (!valeur && valeur !== 0) {
            return 'Non saisie';
        }

        if (bareme <= 0) {
            return 'Barème invalide';
        }

        const pourcentage = (valeur / bareme) * 100;

        if (pourcentage >= 80) {
            return 'Très bien';
        }

        if (pourcentage >= 70) {
            return 'Bien';
        }

        if (pourcentage >= 60) {
            return 'Assez bien';
        }

        if (pourcentage >= 50) {
            return 'Passable';
        }

        if (pourcentage >= 35) {
            return 'Insuffisant';
        }

        return 'Très insuffisant';
    }

    function classeBadge(appreciation) {
        if (appreciation === 'Très bien') {
            return 'badge badge-success';
        }

        if (appreciation === 'Bien' || appreciation === 'Assez bien') {
            return 'badge badge-primary-soft';
        }

        if (appreciation === 'Passable') {
            return 'badge badge-warning';
        }

        if (appreciation === 'Insuffisant' || appreciation === 'Très insuffisant') {
            return 'badge badge-danger';
        }

        return 'badge';
    }

    function mettreAJourAppreciation(input) {
        const valeurTexte = input.value;
        const bareme = parseFloat(input.dataset.bareme);
        const targetId = input.dataset.target;
        const badge = document.getElementById(targetId);

        if (!badge) {
            return;
        }

        if (valeurTexte === '') {
            badge.textContent = 'Non saisie';
            badge.className = 'badge';
            return;
        }

        const valeur = parseFloat(valeurTexte);

        if (Number.isNaN(valeur)) {
            badge.textContent = 'Valeur invalide';
            badge.className = 'badge badge-danger';
            return;
        }

        if (valeur < 0 || valeur > bareme) {
            badge.textContent = 'Hors barème';
            badge.className = 'badge badge-danger';
            return;
        }

        const appreciation = calculerAppreciation(valeur, bareme);

        badge.textContent = appreciation;
        badge.className = classeBadge(appreciation);
    }

    noteInputs.forEach(function (input) {
        mettreAJourAppreciation(input);

        input.addEventListener('input', function () {
            mettreAJourAppreciation(input);
        });
    });
});


document.addEventListener('DOMContentLoaded', function () {
    const imageInputs = document.querySelectorAll('.js-image-preview-input');

    imageInputs.forEach(function (input) {
        input.addEventListener('change', function () {
            const file = input.files[0];
            const previewId = input.dataset.preview;
            const preview = document.getElementById(previewId);

            if (!preview) {
                return;
            }

            if (!file) {
                preview.hidden = true;
                preview.src = '';
                return;
            }

            if (!file.type.startsWith('image/')) {
                alert('Veuillez choisir une image valide.');
                input.value = '';
                preview.hidden = true;
                preview.src = '';
                return;
            }

            const reader = new FileReader();

            reader.onload = function (event) {
                preview.src = event.target.result;
                preview.hidden = false;

                const placeholder = preview
                    .closest('.image-preview-box')
                    ?.querySelector('.image-preview-placeholder');

                if (placeholder) {
                    placeholder.style.display = 'none';
                }
            };

            reader.readAsDataURL(file);
        });
    });
});



document.addEventListener('DOMContentLoaded', function () {
    const searchInputs = document.querySelectorAll('.js-table-search');

    searchInputs.forEach(function (input) {
        input.addEventListener('input', function () {
            const targetId = input.dataset.target;
            const table = document.getElementById(targetId);

            if (!table) {
                return;
            }

            const searchValue = input.value.toLowerCase().trim();
            const rows = table.querySelectorAll('tbody tr');

            rows.forEach(function (row) {
                const rowText = row.textContent.toLowerCase();

                if (rowText.includes(searchValue)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
    });
});


document.addEventListener('DOMContentLoaded', function () {
    const forms = document.querySelectorAll('.js-inscription-form');

    forms.forEach(function (form) {
        const optionsUrl = form.dataset.optionsUrl;
        const inscriptionId = form.dataset.inscriptionId || '';
        const anneeSelect = form.querySelector('.js-inscription-annee');
        const classeSelect = form.querySelector('.js-inscription-classe');
        const eleveSelect = form.querySelector('.js-inscription-eleve');
        const submitButton = form.querySelector('.js-inscription-submit');
        const message = form.querySelector('.js-inscription-options-message');
        const fraisInput = form.querySelector('.js-inscription-frais');

        if (!optionsUrl || !anneeSelect || !classeSelect || !eleveSelect) {
            return;
        }

        function formatMontant(montant) {
            const valeur = Number(montant);

            if (Number.isNaN(valeur)) {
                return '';
            }

            return new Intl.NumberFormat('fr-FR').format(valeur) + ' FCFA';
        }

        function setSelectOptions(select, options, selectedId, emptyLabel) {
            select.innerHTML = '';

            if (!options.length) {
                const option = document.createElement('option');
                option.value = '';
                option.textContent = emptyLabel;
                select.appendChild(option);
                select.disabled = true;
                return '';
            }

            select.disabled = false;

            options.forEach(function (item) {
                const option = document.createElement('option');
                option.value = item.id;
                option.textContent = item.label;

                if (item.frais_scolarite !== undefined) {
                    option.dataset.frais = item.frais_scolarite;
                }

                select.appendChild(option);
            });

            const hasSelectedOption = selectedId
                && Array.from(select.options).some(function (option) {
                    return String(option.value) === String(selectedId);
                });

            select.value = hasSelectedOption ? selectedId : select.options[0].value;

            return select.value;
        }

        function refreshSubmitState() {
            if (!submitButton) {
                return;
            }

            submitButton.disabled = !classeSelect.value || !eleveSelect.value;
        }

        function refreshFraisPlaceholder() {
            if (!fraisInput) {
                return;
            }

            const selectedOption = classeSelect.options[classeSelect.selectedIndex];
            const montant = selectedOption?.dataset?.frais;

            if (montant !== undefined) {
                fraisInput.placeholder = 'Frais de la classe : ' + formatMontant(montant);
            }
        }

        function setLoading(loading) {
            anneeSelect.disabled = loading;
            classeSelect.disabled = loading;
            eleveSelect.disabled = loading;

            if (submitButton) {
                submitButton.disabled = loading;
            }

            if (message && loading) {
                message.textContent = 'Chargement des classes et des élèves admissibles...';
            }
        }

        async function chargerOptions(classeId, eleveId) {
            const url = new URL(optionsUrl, window.location.href);

            if (anneeSelect.value) {
                url.searchParams.set('annee_scolaire_id', anneeSelect.value);
            }

            if (classeId) {
                url.searchParams.set('classe_id', classeId);
            }

            if (inscriptionId) {
                url.searchParams.set('inscription_id', inscriptionId);
            }

            setLoading(true);

            try {
                const response = await fetch(url.toString(), {
                    headers: {
                        Accept: 'application/json',
                    },
                });

                if (!response.ok) {
                    throw new Error('Réponse invalide');
                }

                const data = await response.json();
                const selectedClasseId = setSelectOptions(
                    classeSelect,
                    data.classes || [],
                    data.selected_classe_id,
                    'Aucune classe disponible pour cette année'
                );

                const selectedEleveId = setSelectOptions(
                    eleveSelect,
                    data.eleves || [],
                    eleveId,
                    'Aucun élève admissible pour cette classe'
                );

                if (message) {
                    if (!selectedClasseId) {
                        message.textContent = 'Aucune classe disponible pour cette année scolaire.';
                    } else if (!selectedEleveId) {
                        message.textContent = 'Aucun élève ne respecte les conditions d’inscription pour cette classe.';
                    } else {
                        message.textContent = 'Les élèves déjà inscrits, avec impayés ou non admissibles au niveau choisi ne sont pas affichés.';
                    }
                }

                refreshFraisPlaceholder();
                refreshSubmitState();
            } catch (error) {
                if (message) {
                    message.textContent = 'Impossible de charger les options d’inscription. Réessayez.';
                }

                refreshSubmitState();
            } finally {
                anneeSelect.disabled = false;
                classeSelect.disabled = !classeSelect.options.length || !classeSelect.value;
                eleveSelect.disabled = !eleveSelect.options.length || !eleveSelect.value;
                refreshSubmitState();
            }
        }

        refreshFraisPlaceholder();
        refreshSubmitState();

        anneeSelect.addEventListener('change', function () {
            chargerOptions('', '');
        });

        classeSelect.addEventListener('change', function () {
            chargerOptions(classeSelect.value, eleveSelect.value);
        });
    });
});

document.addEventListener('DOMContentLoaded', function () {
    const forms = document.querySelectorAll('.js-eleve-parcours-filter');

    forms.forEach(function (form) {
        const anneeSelect = form.querySelector('.js-eleve-parcours-annee');
        const classeSelect = form.querySelector('.js-eleve-parcours-classe');

        if (!anneeSelect || !classeSelect) {
            return;
        }

        function filtrerClasses() {
            const selectedAnnee = anneeSelect.value;

            Array.from(classeSelect.options).forEach(function (option) {
                if (!option.value) {
                    option.hidden = false;
                    option.disabled = false;
                    return;
                }

                const visible = !selectedAnnee || option.dataset.annee === selectedAnnee;

                option.hidden = !visible;
                option.disabled = !visible;
            });

            const optionSelectionnee = classeSelect.selectedOptions[0];

            if (optionSelectionnee && optionSelectionnee.disabled) {
                classeSelect.value = '';
            }
        }

        filtrerClasses();

        anneeSelect.addEventListener('change', filtrerClasses);
    });
});

document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.js-assiduite-type').forEach(function (typeSelect) {
        const form = typeSelect.closest('form');
        const dateDebut = form?.querySelector('.js-assiduite-date-debut');
        const dateFin = form?.querySelector('.js-assiduite-date-fin');
        const heureDebut = form?.querySelector('.js-assiduite-heure-debut');
        const heureArrivee = form?.querySelector('.js-assiduite-heure-arrivee');
        const duree = form?.querySelector('.js-assiduite-duree');
        let dateDebutPrecedente = dateDebut?.value || '';

        if (!form || !dateDebut || !dateFin) {
            return;
        }

        function afficherChamps(selector, visible) {
            form.querySelectorAll(selector).forEach(function (champ) {
                champ.hidden = !visible;
                champ.querySelectorAll('input, select, textarea').forEach(function (controle) {
                    controle.disabled = !visible;
                });
            });
        }

        function calculerDuree() {
            if (typeSelect.value !== 'retard' || !heureDebut?.value || !heureArrivee?.value || !duree) {
                return;
            }

            const [heureDepart, minuteDepart] = heureDebut.value.split(':').map(Number);
            const [heureArriveeValeur, minuteArrivee] = heureArrivee.value.split(':').map(Number);
            const debutEnMinutes = heureDepart * 60 + minuteDepart;
            const arriveeEnMinutes = heureArriveeValeur * 60 + minuteArrivee;

            if (arriveeEnMinutes > debutEnMinutes) {
                duree.value = arriveeEnMinutes - debutEnMinutes;
            }
        }

        function actualiserType() {
            const retard = typeSelect.value === 'retard';

            if (retard) {
                dateFin.value = dateDebut.value;
            } else if (!dateFin.value) {
                dateFin.value = dateDebut.value;
            }

            afficherChamps('[data-assiduite-absence-only]', !retard);
            afficherChamps('[data-assiduite-retard-only]', retard);

            if (!duree?.value) {
                calculerDuree();
            }
        }

        typeSelect.addEventListener('change', actualiserType);
        dateDebut.addEventListener('change', function () {
            if (typeSelect.value === 'retard' || !dateFin.value || dateFin.value === dateDebutPrecedente) {
                dateFin.value = dateDebut.value;
            }

            dateDebutPrecedente = dateDebut.value;
        });
        heureDebut?.addEventListener('change', calculerDuree);
        heureArrivee?.addEventListener('change', calculerDuree);

        actualiserType();
    });
});

document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.js-sanction-mode').forEach(function (modeSelect) {
        const form = modeSelect.closest('form');
        const categorieSelect = form?.querySelector('.js-sanction-categorie');
        const effetSelect = form?.querySelector('.js-sanction-effet');
        const valeurEffet = form?.querySelector('.js-sanction-valeur');

        if (!form || !categorieSelect || !effetSelect) {
            return;
        }

        function afficherChamps(selector, visible, obligatoires) {
            form.querySelectorAll(selector).forEach(function (champ) {
                champ.hidden = !visible;
                champ.querySelectorAll('input, select, textarea').forEach(function (controle) {
                    controle.disabled = !visible;
                    controle.required = visible && obligatoires;
                });
            });
        }

        function actualiserMode() {
            if (categorieSelect.value === 'conduite' && modeSelect.value === 'automatique') {
                modeSelect.value = 'manuel';
            }

            const declenchementParSeuil = categorieSelect.value !== 'conduite'
                && ['automatique', 'mixte'].includes(modeSelect.value);
            afficherChamps('[data-sanction-automatique]', declenchementParSeuil, declenchementParSeuil);
        }

        function actualiserEffet() {
            const pointsEnMoins = effetSelect.value === 'points_en_moins';
            afficherChamps('[data-sanction-valeur]', pointsEnMoins, pointsEnMoins);

            if (!pointsEnMoins && valeurEffet) {
                valeurEffet.value = '';
            }
        }

        categorieSelect.addEventListener('change', actualiserMode);
        modeSelect.addEventListener('change', actualiserMode);
        effetSelect.addEventListener('change', actualiserEffet);

        actualiserMode();
        actualiserEffet();
    });
});

document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.js-sanction-appliquee-form').forEach(function (form) {
        const sanctionSelect = form.querySelector('.js-sanction-appliquee-select');
        const trimestreSelect = form.querySelector('.js-sanction-trimestre');
        const trimestreLabel = form.querySelector('.js-sanction-trimestre-label');

        if (!sanctionSelect || !trimestreSelect) {
            return;
        }

        function actualiserTrimestre() {
            const option = sanctionSelect.options[sanctionSelect.selectedIndex];
            const obligatoire = option?.dataset.effet === 'points_en_moins';

            trimestreSelect.required = obligatoire;

            if (trimestreLabel) {
                trimestreLabel.textContent = obligatoire ? 'Trimestre (obligatoire)' : 'Trimestre';
            }
        }

        sanctionSelect.addEventListener('change', actualiserTrimestre);
        actualiserTrimestre();
    });
});
