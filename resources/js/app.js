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
