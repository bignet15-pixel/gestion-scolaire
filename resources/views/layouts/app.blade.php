{{-- Vue Blade : resources/views/layouts/app.blade.php --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Bangre Zaaka</title>

    {{-- Chargement des fichiers CSS et JavaScript. --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body>
    {{-- Preparation des donnees de la vue. --}}
    @php
        $active = function ($routes) {
            $routes = (array) $routes;

            return request()->routeIs(...$routes) ? ' active' : '';
        };

        $notificationsNonLues = auth()->check()
            ? auth()->user()->notificationsUtilisateur()->nonLues()->count()
            : 0;
    @endphp
    <div class="app-shell">
        <aside class="sidebar" id="appSidebar">
            <div class="sidebar-brand">
                <div class="brand-logo">
                    BZ
                </div>

                <div class="brand-text">
                    <div class="brand-title">
                        {{ config('ecole.nom') }}
                    </div>

                    <div class="brand-subtitle">
                        Cycle primaire
                    </div>
                </div>

                <button type="button" class="sidebar-close" data-sidebar-close aria-label="Fermer le menu">
                    ×
                </button>
            </div>

            <nav class="sidebar-nav">
                <a href="{{ route('dashboard') }}" class="sidebar-link{{ $active('dashboard') }}">
                    Tableau de bord
                </a>

                {{-- Contenu reserve aux utilisateurs connectes. --}}
                @auth
                    {{-- Condition : auth()->user()->estGestionnaire(). --}}
                    @if (auth()->user()->estGestionnaire())
                        <div class="sidebar-section">Administration</div>

                        <a href="{{ route('annee-scolaires.index') }}" class="sidebar-link{{ $active('annee-scolaires.*') }}">
                            Années scolaires
                        </a>

                        <a href="{{ route('trimestres.index') }}" class="sidebar-link{{ $active('trimestres.*') }}">
                            Trimestres
                        </a>

                        <a href="{{ route('classes.index') }}" class="sidebar-link{{ $active('classes.*') }}">
                            Classes
                        </a>

                        <a href="{{ route('enseignants.index') }}" class="sidebar-link{{ $active('enseignants.*') }}">
                            Enseignants
                        </a>

                        <a href="{{ route('parents.index') }}" class="sidebar-link{{ $active('parents.*') }}">
                            Parents
                        </a>

                        <a href="{{ route('matieres.index') }}" class="sidebar-link{{ $active('matieres.*') }}">
                            Matières
                        </a>

                        <a href="{{ route('affectations.index') }}" class="sidebar-link{{ $active('affectations.*') }}">
                            Affectations
                        </a>

                        <div class="sidebar-section">Communication</div>

                        <a href="{{ route('annonces.index') }}" class="sidebar-link{{ $active('annonces.*') }}">
                            Annonces
                        </a>

                        <a href="{{ route('annonces-ecole.index') }}" class="sidebar-link{{ $active('annonces-ecole.*') }}">
                            Annonces reçues
                        </a>

                        <a href="{{ route('notifications.index') }}" class="sidebar-link{{ $active('notifications.*') }}">
                            Notifications
                            @if ($notificationsNonLues > 0)
                                <span class="sidebar-badge">{{ $notificationsNonLues }}</span>
                            @endif
                        </a>

                        <div class="sidebar-section">Élèves & finances</div>

                        <a href="{{ route('eleves.index') }}" class="sidebar-link{{ $active('eleves.*') }}">
                            Élèves
                        </a>

                        <a href="{{ route('inscriptions.index') }}" class="sidebar-link{{ $active('inscriptions.*') }}">
                            Inscriptions
                        </a>

                        <a href="{{ route('paiements.index') }}" class="sidebar-link{{ $active('paiements.*') }}">
                            Paiements
                        </a>

                        <a href="{{ route('impayes.index') }}" class="sidebar-link{{ $active('impayes.*') }}">
                            Impayés
                        </a>

                        <div class="sidebar-section">Pédagogie</div>

                        <a href="{{ route('emplois-du-temps.index') }}" class="sidebar-link{{ $active('emplois-du-temps.index') }}">
                            Emploi du temps
                        </a>

                        <a href="{{ route('emplois-du-temps.semaine-classe') }}" class="sidebar-link{{ $active('emplois-du-temps.semaine-classe') }}">
                            Planning classe
                        </a>

                        <a href="{{ route('emplois-du-temps.semaine-enseignant') }}" class="sidebar-link{{ $active('emplois-du-temps.semaine-enseignant') }}">
                            Planning enseignant
                        </a>

                        <a href="{{ route('evaluations.index') }}" class="sidebar-link{{ $active('evaluations.*') }}">
                            Évaluations
                        </a>

                        <a href="{{ route('resultats.index') }}" class="sidebar-link{{ $active('resultats.*') }}">
                            Résultats
                        </a>

                        <div class="sidebar-section">Assiduité & discipline</div>

                        <a href="{{ route('absences-retards.index') }}" class="sidebar-link{{ $active('absences-retards.*') }}">
                            Absences et retards
                        </a>

                        <a href="{{ route('sanctions.index') }}" class="sidebar-link{{ $active('sanctions.*') }}">
                            Sanctions
                        </a>

                        <a href="{{ route('sanctions-appliquees.index') }}" class="sidebar-link{{ $active('sanctions-appliquees.*') }}">
                            Sanctions appliquées
                        </a>

                        <div class="sidebar-section">Demandes parentales</div>

                        <a href="{{ route('gestionnaire.justifications-parent.index') }}" class="sidebar-link{{ $active('gestionnaire.justifications-parent.*') }}">
                            Justifications parents
                        </a>

                        <a href="{{ route('gestionnaire.paiements-declares.index') }}" class="sidebar-link{{ $active('gestionnaire.paiements-declares.*') }}">
                            Paiements déclarés
                        </a>

                        <a href="{{ route('gestionnaire.demandes-reinscription.index') }}" class="sidebar-link{{ $active('gestionnaire.demandes-reinscription.*') }}">
                            Réinscriptions
                        </a>
                    @endif

                    {{-- Condition : auth()->user()->estEnseignant(). --}}
                    @if (auth()->user()->estEnseignant())
                        <div class="sidebar-section">Espace enseignant</div>

                        <a href="{{ route('enseignant.classes.index') }}" class="sidebar-link{{ $active('enseignant.classes.*') }}">
                            Mes classes
                        </a>

                        <a href="{{ route('evaluations.index') }}" class="sidebar-link{{ $active('evaluations.*') }}">
                            Mes évaluations
                        </a>

                        <a href="{{ route('emplois-du-temps.semaine-enseignant') }}" class="sidebar-link{{ $active('emplois-du-temps.semaine-enseignant') }}">
                            Mon emploi du temps
                        </a>

                        <a href="{{ route('emplois-du-temps.semaine-classe') }}" class="sidebar-link{{ $active('emplois-du-temps.semaine-classe') }}">
                            Planning classe
                        </a>

                        <a href="{{ route('resultats.index') }}" class="sidebar-link{{ $active('resultats.*') }}">
                            Moyennes / Classements
                        </a>

                        <a href="{{ route('annonces-ecole.index') }}" class="sidebar-link{{ $active('annonces-ecole.*') }}">
                            Annonces
                        </a>

                        <a href="{{ route('notifications.index') }}" class="sidebar-link{{ $active('notifications.*') }}">
                            Notifications
                            @if ($notificationsNonLues > 0)
                                <span class="sidebar-badge">{{ $notificationsNonLues }}</span>
                            @endif
                        </a>

                        <a href="{{ route('absences-retards.index') }}" class="sidebar-link{{ $active('absences-retards.*') }}">
                            Absences et retards
                        </a>

                        <a href="{{ route('sanctions-appliquees.index') }}" class="sidebar-link{{ $active('sanctions-appliquees.*') }}">
                            Sanctions appliquées
                        </a>                    @endif

                    {{-- Condition : auth()->user()->estParent(). --}}
                    @if (auth()->user()->estParent())
                        <div class="sidebar-section">Espace parent</div>

                        <a href="{{ route('dashboard') }}" class="sidebar-link{{ $active('dashboard') }}">
                            Mes enfants
                        </a>

                        <a href="{{ route('parent.paiements-declares.index') }}" class="sidebar-link{{ $active('parent.paiements-declares.*') }}">
                            Paiements déclarés
                        </a>

                        <a href="{{ route('annonces-ecole.index') }}" class="sidebar-link{{ $active('annonces-ecole.*') }}">
                            Annonces
                        </a>

                        <a href="{{ route('notifications.index') }}" class="sidebar-link{{ $active('notifications.*') }}">
                            Notifications
                            @if ($notificationsNonLues > 0)
                                <span class="sidebar-badge">{{ $notificationsNonLues }}</span>
                            @endif
                        </a>

                        <a href="{{ route('profile.edit') }}" class="sidebar-link{{ $active('profile.edit') }}">
                            Mon compte
                        </a>
                    @endif
                @endauth
            </nav>
        </aside>

        <div class="sidebar-overlay" data-sidebar-close hidden></div>

        <div class="main-area">
            <header class="topbar">
                <div class="topbar-title-block">
                    <button type="button" class="mobile-menu-toggle" data-sidebar-open aria-controls="appSidebar" aria-expanded="false">
                        <span>☰</span>
                        Menu
                    </button>

                    <div>
                    <div class="page-kicker">Bangre Zaaka</div>
                    <h1 class="page-title">
                        {{-- Contenu reserve aux utilisateurs connectes. --}}
                        @auth
                            @if (auth()->user()->estGestionnaire())
                                Espace gestionnaire
                            @elseif (auth()->user()->estEnseignant())
                                Espace enseignant
                            @elseif (auth()->user()->estParent())
                                Espace parent
                            @endif
                        {{-- Sinon, affichage de l alternative prevue. --}}
                        @else
                        Bangre Zaaka
                        @endauth
                    </h1>
                    </div>
                </div>

                {{-- Contenu reserve aux utilisateurs connectes. --}}
                @auth
                    <div class="user-box">
                        <div class="user-info">
                            <strong>{{ auth()->user()->name }}</strong>
                            <span>{{ auth()->user()->role }}</span>
                        </div>

                        <a href="{{ route('profile.edit') }}" class="btn topbar-icon-btn" aria-label="Mon compte" title="Mon compte">
                            <span class="topbar-action-icon" aria-hidden="true">👤</span>
                            <span class="topbar-action-text">Mon compte</span>
                        </a>

                        <form method="POST" action="{{ route('logout') }}">
                            {{-- Jeton de securite du formulaire. --}}
                            @csrf

                            <button type="submit" class="btn btn-danger topbar-icon-btn" aria-label="Déconnexion" title="Déconnexion">
                                <span class="topbar-action-icon" aria-hidden="true">⏻</span>
                                <span class="topbar-action-text">Déconnexion</span>
                            </button>
                        </form>
                    </div>
                @endauth
            </header>

            <main class="content">
                {{ $slot }}
            </main>
        </div>
    </div>
    <div id="confirmModal" class="confirm-modal" hidden>
    <div class="confirm-modal-backdrop" data-confirm-cancel></div>

    <div class="confirm-modal-dialog">
        <div class="confirm-modal-icon">
            !
        </div>

        <h2 id="confirmModalTitle">
            Confirmation
        </h2>

        <p id="confirmModalMessage">
            Voulez-vous vraiment effectuer cette action ?
        </p>

        <div class="confirm-modal-actions">
            <button type="button" class="btn" data-confirm-cancel>
                Annuler
            </button>

            <button type="button" class="btn btn-danger" id="confirmModalButton">
                Confirmer
            </button>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        let pendingForm = null;

        const modal = document.getElementById('confirmModal');
        const modalTitle = document.getElementById('confirmModalTitle');
        const modalMessage = document.getElementById('confirmModalMessage');
        const confirmButton = document.getElementById('confirmModalButton');
        const cancelElements = document.querySelectorAll('[data-confirm-cancel]');
        const sidebar = document.getElementById('appSidebar');
        const sidebarOverlay = document.querySelector('[data-sidebar-close].sidebar-overlay');
        const sidebarOpenButtons = document.querySelectorAll('[data-sidebar-open]');
        const sidebarCloseButtons = document.querySelectorAll('[data-sidebar-close]');

        function openSidebar() {
            if (!sidebar) {
                return;
            }

            sidebar.classList.add('is-open');
            document.body.classList.add('sidebar-open');

            if (sidebarOverlay) {
                sidebarOverlay.hidden = false;
                sidebarOverlay.classList.add('is-open');
            }

            sidebarOpenButtons.forEach(function (button) {
                button.setAttribute('aria-expanded', 'true');
            });
        }

        function closeSidebar() {
            if (!sidebar) {
                return;
            }

            sidebar.classList.remove('is-open');
            document.body.classList.remove('sidebar-open');

            if (sidebarOverlay) {
                sidebarOverlay.classList.remove('is-open');
                sidebarOverlay.hidden = true;
            }

            sidebarOpenButtons.forEach(function (button) {
                button.setAttribute('aria-expanded', 'false');
            });
        }

        sidebarOpenButtons.forEach(function (button) {
            button.addEventListener('click', openSidebar);
        });

        sidebarCloseButtons.forEach(function (button) {
            button.addEventListener('click', closeSidebar);
        });

        document.querySelectorAll('.sidebar-link').forEach(function (link) {
            link.addEventListener('click', function () {
                if (window.matchMedia('(max-width: 1024px)').matches) {
                    closeSidebar();
                }
            });
        });

        function prepareResponsiveTables() {
            const isSmallScreen = window.innerWidth <= 900;

            document.querySelectorAll('main table').forEach(function (table) {
                let wrapper = table.closest('.responsive-table-shell') || table.closest('.table-responsive');

                if (!wrapper) {
                    wrapper = document.createElement('div');
                    wrapper.className = 'responsive-table-shell';
                    table.parentNode.insertBefore(wrapper, table);
                    wrapper.appendChild(table);
                }

                wrapper.classList.add('responsive-table-shell');
                wrapper.setAttribute('tabindex', '0');
                wrapper.setAttribute('aria-label', 'Tableau défilable horizontalement');

                table.classList.remove('responsive-card-table');
                table.classList.add('responsive-scroll-table');

                table.querySelectorAll('td[data-label]').forEach(function (cell) {
                    cell.removeAttribute('data-label');
                });

                wrapper.querySelectorAll('.table-scroll-hint').forEach(function (hint) {
                    hint.remove();
                });

                if (isSmallScreen) {
                    wrapper.style.display = 'block';
                    wrapper.style.width = '100%';
                    wrapper.style.maxWidth = '100%';
                    wrapper.style.overflowX = 'auto';
                    wrapper.style.overflowY = 'hidden';
                    wrapper.style.webkitOverflowScrolling = 'touch';
                    wrapper.style.touchAction = 'pan-x';
                    wrapper.style.border = '1px solid #e5e7eb';
                    wrapper.style.borderRadius = '14px';
                    wrapper.style.background = '#ffffff';

                    table.style.display = 'table';
                    table.style.width = 'max-content';
                    table.style.minWidth = window.innerWidth <= 430 ? '900px' : '980px';
                    table.style.maxWidth = 'none';
                    table.style.tableLayout = 'auto';
                    table.style.whiteSpace = 'nowrap';

                    table.querySelectorAll('thead, tbody, tr, th, td').forEach(function (el) {
                        el.style.whiteSpace = 'nowrap';
                    });

                    const hint = document.createElement('div');
                    hint.className = 'table-scroll-hint';
                    hint.textContent = 'Faire glisser horizontalement pour voir la suite →';
                    wrapper.appendChild(hint);
                } else {
                    wrapper.style.display = '';
                    wrapper.style.width = '100%';
                    wrapper.style.maxWidth = '100%';
                    wrapper.style.overflowX = 'visible';
                    wrapper.style.overflowY = '';
                    wrapper.style.webkitOverflowScrolling = '';
                    wrapper.style.touchAction = '';
                    wrapper.style.border = '';
                    wrapper.style.borderRadius = '';
                    wrapper.style.background = '';

                    table.style.display = '';
                    table.style.width = '100%';
                    table.style.minWidth = '';
                    table.style.maxWidth = '100%';
                    table.style.tableLayout = '';
                    table.style.whiteSpace = '';

                    table.querySelectorAll('thead, tbody, tr, th, td').forEach(function (el) {
                        el.style.whiteSpace = '';
                    });
                }

                if (!wrapper.dataset.dragScrollReady) {
                    let isDown = false;
                    let startX = 0;
                    let startScrollLeft = 0;

                    wrapper.addEventListener('pointerdown', function (event) {
                        if (window.innerWidth > 900) {
                            return;
                        }

                        isDown = true;
                        startX = event.clientX;
                        startScrollLeft = wrapper.scrollLeft;
                        wrapper.classList.add('is-dragging-table');
                    });

                    wrapper.addEventListener('pointermove', function (event) {
                        if (!isDown) {
                            return;
                        }

                        const deltaX = event.clientX - startX;
                        wrapper.scrollLeft = startScrollLeft - deltaX;
                    });

                    ['pointerup', 'pointercancel', 'pointerleave'].forEach(function (eventName) {
                        wrapper.addEventListener(eventName, function () {
                            isDown = false;
                            wrapper.classList.remove('is-dragging-table');
                        });
                    });

                    wrapper.dataset.dragScrollReady = '1';
                }
            });
        }

        prepareResponsiveTables();
        window.addEventListener('resize', prepareResponsiveTables);

        function openModal(form) {
            pendingForm = form;

            modalTitle.textContent = form.dataset.confirmTitle || 'Confirmation';
            modalMessage.textContent = form.dataset.confirm || 'Voulez-vous vraiment effectuer cette action ?';
            confirmButton.textContent = form.dataset.confirmButton || 'Confirmer';

            modal.hidden = false;
            document.body.classList.add('modal-open');
        }

        function closeModal() {
            modal.hidden = true;
            document.body.classList.remove('modal-open');
            pendingForm = null;
        }

        document.addEventListener('submit', function (event) {
            const form = event.target;

            if (!form.matches('form[data-confirm]')) {
                return;
            }

            event.preventDefault();
            openModal(form);
        });

        confirmButton.addEventListener('click', function () {
            if (!pendingForm) {
                return;
            }

            const formToSubmit = pendingForm;
            closeModal();

            formToSubmit.submit();
        });

        cancelElements.forEach(function (element) {
            element.addEventListener('click', closeModal);
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && !modal.hidden) {
                closeModal();
            }

            if (event.key === 'Escape' && sidebar && sidebar.classList.contains('is-open')) {
                closeSidebar();
            }
        });
    });
</script>

</body>
</html>
