# Gestion Scolaire - Cycle primaire

==============================
Membre du Groupe
-------------------
Kiedega Abdoul-samando
Ouedraogo Daouda



Application web Laravel de gestion d'une ecole primaire. Elle couvre les
annees scolaires, trimestres, classes, enseignants, matieres, affectations,
eleves, inscriptions, paiements, impayes, emplois du temps, evaluations, notes,
moyennes et classements.

L'ecole utilisee dans le projet est configuree dans `config/ecole.php`.

## Roles

Deux espaces sont disponibles.

**Gestionnaire**

- gere les annees scolaires, les trimestres, les classes, les enseignants et les matieres ;
- affecte les enseignants aux couples classe / matiere avec un coefficient propre a la classe ;
- gere les eleves, les inscriptions, les paiements et les impayes ;
- choisit le chef de classe parmi les eleves actifs de la classe ;
- cree les evaluations de type composition ou test ;
- consulte les notes, les resultats, les moyennes et les classements ;
- genere les recus PDF des paiements.

**Enseignant**

- consulte ses classes, ses matieres et ses emplois du temps ;
- cree des evaluations pour ses affectations ;
- saisit et modifie les notes des eleves ;
- consulte les resultats des classes ou il intervient.

## Fonctionnement metier

- Une classe appartient a une annee scolaire.
- Une inscription relie un eleve a une classe pour une annee scolaire.
- Une matiere est un referentiel global, mais son coefficient est defini dans l'affectation classe / matiere.
- Une affectation relie une classe, une matiere et un enseignant.
- Les emplois du temps et les evaluations s'appuient sur les affectations.
- Les paiements sont lies aux inscriptions.
- Les notes sont liees a une evaluation et a une inscription.
- Un eleve ne peut pas etre inscrit dans l'annee suivante s'il garde des impayes sur son ancienne inscription.
- Quand une annee scolaire est fermee, les actions pedagogiques de cette annee sont bloquees en modification.
- Quand un trimestre est ferme, les evaluations et les notes de ce trimestre sont bloquees en modification.
- Les paiements restent enregistrables et modifiables meme si l'annee scolaire est fermee.

## Calcul des moyennes

Les moyennes sont calculees sur 20 avec les coefficients des matieres affectees
a la classe.

Pour une periode :

```text
total des points = somme(note ramenee sur 20 x coefficient de la matiere)
moyenne = total des points / total des coefficients de la classe
```

Le total des coefficients vient de toutes les matieres affectees a la classe.
Donc une matiere sans note compte comme 0 point dans la moyenne de la periode.

La moyenne annuelle est calculee avec les trois moyennes trimestrielles quand
les trois trimestres ont des resultats calculables.

## Technologies

- PHP 8.3
- Laravel 13
- Laravel Breeze
- Blade
- MySQL
- Vite
- CSS
- DomPDF

## Installation

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
```

Configurer ensuite la base de donnees dans `.env` :

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=gestion_scolaire
DB_USERNAME=root
DB_PASSWORD=
```

Puis lancer les migrations et les donnees de demonstration :

```bash
php artisan migrate:fresh --seed
php artisan storage:link
```

## Lancement

Dans un terminal :

```bash
npm run dev
```

Dans un autre terminal :

```bash
php artisan serve
```

Application :

```text
http://127.0.0.1:8000
```

## Comptes de test

Tous les comptes de demonstration utilisent le mot de passe :

```text
password
```

Compte gestionnaire :

```text
gestionnaire@example.com
```

Comptes enseignants :

```text
enseignant1@example.com
enseignant2@example.com
enseignant3@example.com
enseignant4@example.com
enseignant5@example.com
enseignant6@example.com
enseignant7@example.com
```

## Donnees de demonstration

Le seeder principal se trouve dans `database/seeders/DatabaseSeeder.php`.

Il cree notamment :

- l'annee scolaire active `2025-2026` ;
- les trois trimestres de cette annee ;
- sept enseignants ;
- les classes `CP1 A`, `CP2 A`, `CE1 A`, `CE2 A`, `CM1 A` et `CM2 A` ;
- sept matieres : Francais, Mathematiques, Sciences, Histoire-Geographie, Lecture, Ecriture et Dessin ;
- dix eleves inscrits en `CP1 A` ;
- les affectations des sept matieres de `CP1 A` ;
- une composition par matiere pour chaque trimestre : `composition-1-E`, `composition-2-E` et `composition-3-E` ;
- des notes pour les compositions de `CP1 A` ;
- des paiements complets et partiels pour tester les impayes.



## API parent mobile

Le projet expose une API REST pour l'application mobile parent Android native en Kotlin.

Base URL locale :

```text
http://127.0.0.1:8000/api/parent
```

Authentification : Laravel Sanctum avec token Bearer.

Fonctionnalites principales :

- connexion, deconnexion, profil parent, changement de mot de passe ;
- mot de passe oublie par OTP email ;
- liste des enfants du parent ;
- dashboard detaille de chaque enfant ;
- notes, resultats, bulletins PDF, paiements et recus PDF ;
- paiements declares, preuves de paiement ;
- absences, retards, justifications et pieces jointes ;
- sanctions, reinscription, annonces et notifications.

Documentation API :

```text
docs/API_PARENT_MOBILE.md
docs/GUIDE_TEST_API_PARENT.md
docs/PLAN_MOBILE_KOTLIN.md
```

Routes :

```bash
php artisan route:list --path=api/parent
```

## Structure utile

```text
app/Http/Controllers/   Controleurs metier
app/Models/             Modeles Eloquent
database/migrations/    Structure de la base de donnees
database/seeders/       Donnees de demonstration
resources/views/        Pages Blade
resources/css/app.css   Styles de l'application
routes/web.php          Routes principales
config/ecole.php        Informations de l'ecole
```

## Acces et securite

Les routes sont protegees par authentification et par role.

Le gestionnaire a acces a l'administration complete. L'enseignant est limite aux
classes, matieres, evaluations, notes et resultats lies a ses affectations.

Les fichiers sensibles et generes ne doivent pas etre envoyes sur GitHub :
`.env`, `vendor/`, `node_modules/`, `public/build/`, `public/storage/` et les
images uploades dans `storage/app/public/` sont ignores par `.gitignore`.

## Ecole configuree

```text
Bangre Zaaka
Objectif Courage Concentration Reussite
```
