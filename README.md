# Gestion Scolaire — Cycle primaire

## Membres du groupe

* Kiedega Abdoul-Samando
* Ouedraogo Daouda

## Description

Application web Laravel de gestion d’une école primaire.

Le projet permet de gérer les années scolaires, les trimestres, les classes, les enseignants, les matières, les élèves, les inscriptions, les paiements, les absences, les sanctions, les évaluations, les notes, les résultats, les bulletins PDF, les annonces et les notifications.

Le projet expose également une API REST utilisée par une application mobile Android native destinée aux parents d’élèves.

L’école utilisée dans le projet est configurée dans :

````text
, les paiements, les absences, les sanctions, les évaluations, les notes, les résultats, les bulletins PDF, les annonces et les notifications.

Le projet expose également une API REST utilisée par une application mobile Android native destinée aux parents d’élèves.

L’école utilisée dans le projet est configurée dans :

```text
config/ecole.php
````

École configurée :

```text
Bangre Zaaka
Objectif Courage Concentration Réussite
```

---

## Rôles disponibles

Le système contient trois espaces principaux.

### Gestionnaire

Le gestionnaire administre l’ensemble de l’école.

Il peut :

* gérer les années scolaires et les trimestres ;
* gérer les classes ;
* gérer les enseignants ;
* gérer les matières ;
* affecter les enseignants aux classes et aux matières ;
* gérer les élèves ;
* associer un élève à un parent ;
* inscrire les élèves ;
* traiter les demandes de première inscription et de réinscription ;
* gérer les paiements ;
* valider ou refuser les paiements déclarés par les parents ;
* générer les reçus PDF ;
* gérer les absences et les retards ;
* traiter les justifications envoyées par les parents ;
* configurer et appliquer les sanctions ;
* créer les annonces de l’école ;
* consulter les notifications ;
* consulter les notes, les résultats, les moyennes et les classements ;
* générer les bulletins PDF.

### Enseignant

L’enseignant intervient uniquement sur ses classes et matières affectées.

Il peut :

* consulter ses classes ;
* consulter ses matières ;
* consulter son emploi du temps ;
* créer des évaluations ;
* saisir les notes ;
* modifier les notes autorisées ;
* consulter les résultats des classes où il intervient.

### Parent

Le parent peut suivre la scolarité de ses enfants depuis le web ou l’application mobile.

Il peut :

* consulter ses enfants ;
* consulter le dossier scolaire d’un enfant ;
* consulter les notes ;
* consulter les résultats ;
* télécharger les bulletins PDF ;
* suivre les paiements ;
* déclarer un paiement avec preuve ;
* consulter les reçus PDF ;
* consulter les absences et retards ;
* justifier une absence ou un retard avec pièce jointe ;
* consulter les sanctions ;
* demander une première inscription ;
* demander une réinscription ;
* consulter les annonces ;
* consulter les notifications ;
* modifier son profil ;
* modifier son mot de passe.

---

## Fonctionnement métier

* Une année scolaire contient trois trimestres.
* Une classe appartient à une année scolaire.
* Une inscription relie un élève à une classe pour une année scolaire.
* Un élève peut être enregistré sans être encore inscrit.
* Un élève enregistré peut être associé à un parent.
* Un parent peut demander une première inscription pour un enfant enregistré mais non encore inscrit.
* Un parent peut demander une réinscription pour un enfant déjà inscrit.
* Le gestionnaire valide ou refuse les demandes d’inscription et de réinscription.
* Une matière est un référentiel global.
* Le coefficient d’une matière dépend de son affectation à une classe.
* Une affectation relie une classe, une matière et un enseignant.
* Les évaluations s’appuient sur les affectations.
* Les notes sont liées à une évaluation et à une inscription.
* Les paiements sont liés aux inscriptions.
* Les paiements déclarés par les parents sont validés ou refusés par le gestionnaire.
* Les absences et retards peuvent être justifiés par les parents.
* Les sanctions peuvent influencer les résultats selon la configuration retenue.
* Les bulletins et reçus sont générés en PDF.

---

##

---

##

---

## Technologies utilisées

* PHP 8.x
* Laravel 13
* Laravel Breeze
* Laravel Sanctum
* Blade
* MySQL
* Vite
* CSS
* DomPDF
* Kotlin Android côté mobile parent
* Retrofit / OkHttp côté mobile
* Jetpack Compose côté mobile

---

## Installation backend

Installer les dépendances :

```bash
composer install
npm install
```

Créer le fichier d’environnement :

```bash
cp .env.example .env
php artisan key:generate
```

Configurer la base de données dans `.env` :

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=gestion_scolaire
DB_USERNAME=root
DB_PASSWORD=
```

Lancer les migrations et les données de démonstration :

```bash
php artisan migrate:fresh --seed
php artisan storage:link
```

---

## Lancement du backend

Pour un accès seulement depuis le PC :

```bash
php artisan serve --host=127.0.0.1 --port=8000
```

Pour permettre aux téléphones du même réseau d’accéder à l’API :

```bash
php artisan serve --host=0.0.0.0 --port=8000
```

Exemple d’URL réseau utilisée pendant le développement :

```text
http://10.17.132.51:8000
```

---

## Lancement du frontend Laravel

Dans un autre terminal :

```bash
npm run dev
```

---

## Emails et queue Laravel

Les emails du projet sont envoyés via les queues Laravel afin d’éviter de ralentir les requêtes web et API.

Configuration recommandée dans `.env` :

```env
QUEUE_CONNECTION=database
```

Lancer le worker dans un terminal séparé :

```bash
php artisan queue:work --queue=emails,default --tries=3 --timeout=90
```

Exemples d’emails mis en queue :

* OTP de mot de passe oublié ;
* notifications importantes ;
* annonces envoyées aux parents ;
* alertes liées aux notes, absences, sanctions, paiements ou réinscriptions.

---

## Comptes de démonstration

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

Les comptes parents dépendent des données présentes dans le seeder ou des parents créés depuis l’interface gestionnaire.

---

## API parent mobile

Le projet expose une API REST pour l’application mobile parent Android native en Kotlin.

Base URL locale PC :

```text
http://127.0.0.1:8000/api/parent/
```

Base URL réseau utilisée pour un téléphone sur le même Wi-Fi :

```text
http://10.17.132.51:8000/api/parent/
```

Authentification :

```text
Laravel Sanctum avec token Bearer
```

---

## Application mobile parent

L’application mobile Android permet au parent de :

* se connecter ;
* récupérer son compte ;
* consulter ses enfants ;
* filtrer par année scolaire ;
* consulter le dossier d’un enfant ;
* consulter les notes ;
* consulter les résultats ;
* télécharger les bulletins PDF ;
* consulter les paiements ;
* déclarer un paiement avec preuve ;
* renseigner le numéro de transfert ;
* renseigner la référence de transaction ;
* consulter les absences et retards ;
* justifier une absence ou un retard avec pièce jointe ;
* consulter les sanctions ;
* demander une première inscription ;
* demander une réinscription ;
* consulter les annonces ;
* consulter les notifications ;
* modifier son profil ;
* modifier son mot de passe.

---

## Documentation utile

```text
docs/API_PARENT_MOBILE.md
docs/GUIDE_TEST_API_PARENT.md
docs/PLAN_MOBILE_KOTLIN.md
docs/PRESENTATION_BACKEND.md
```

---

##

---

##

---

## Commandes utiles

Nettoyer les caches Laravel :

```bash
php artisan optimize:clear
```

Recharger l’autoload Composer :

```bash
composer dump-autoload
```

Voir les routes parent mobile :

```bash
php artisan route:list --path=api/parent
```

Voir les migrations :

```bash
php artisan migrate:status
```

Lancer la queue :

```bash
php artisan queue:work --queue=emails,default --tries=3 --timeout=90
```

---

##
