# Plan mobile Android Kotlin - Espace parent

L'application mobile sera une application Android native en Kotlin qui consomme l'API REST Laravel.

## Objectif

Donner au parent un espace mobile pour suivre ses enfants : notes, resultats, bulletins, paiements, absences, sanctions, annonces, notifications et demandes.

## Architecture recommandee

Architecture simple et propre :

```text
app/src/main/java/.../
├── core/
│   ├── network/
│   │   ├── ApiClient.kt
│   │   ├── AuthInterceptor.kt
│   │   └── ApiResponse.kt
│   ├── storage/
│   │   └── TokenStorage.kt
│   └── utils/
├── data/
│   ├── remote/
│   │   ├── AuthApi.kt
│   │   ├── ParentApi.kt
│   │   └── dto/
│   └── repository/
├── ui/
│   ├── auth/
│   ├── home/
│   ├── children/
│   ├── notes/
│   ├── payments/
│   ├── attendance/
│   ├── bulletins/
│   ├── announcements/
│   └── notifications/
└── MainActivity.kt
```

## Bibliotheques Android utiles

- Retrofit : appels HTTP vers Laravel.
- OkHttp : client HTTP et ajout automatique du token Bearer.
- Kotlin Coroutines : appels asynchrones.
- DataStore : stockage local du token.
- ViewModel : conservation de l'etat des ecrans.
- Navigation : navigation entre les ecrans.

L'interface peut etre faite avec Jetpack Compose ou XML. Pour un nouveau projet Kotlin, Compose est conseille si l'equipe est a l'aise.

## Configuration reseau

En developpement :

- Emulateur Android : utiliser souvent `http://10.0.2.2:8000/api/parent` pour joindre le serveur Laravel de la machine hote.
- Telephone physique : utiliser l'adresse IP du PC, par exemple `http://192.168.1.20:8000/api/parent`.

Laravel doit etre lance avec :

```bash
php artisan serve --host=0.0.0.0 --port=8000
```

Sur Android, si l'API locale est en HTTP non chiffre, il faudra autoriser le trafic clair pendant le developpement. En production, utiliser HTTPS.

## Ecrans a realiser

### 1. Authentification

- Connexion parent.
- Mot de passe oublie par OTP.
- Verification OTP.
- Nouveau mot de passe.
- Deconnexion.

Routes :

```text
POST /login
POST /password/forgot
POST /password/verify-otp
POST /password/reset
POST /logout
```

### 2. Accueil parent

- Informations du parent connecte.
- Liste des enfants.
- Notifications non lues.

Routes :

```text
GET /me
GET /enfants
GET /notifications/non-lues
```

### 3. Dashboard enfant

- Photo et identite.
- Classe active.
- Situation financiere.
- Dernieres notes.
- Derniers paiements.
- Absences et sanctions recentes.

Route :

```text
GET /enfants/{eleve}/dashboard
```

### 4. Notes et resultats

- Liste des notes.
- Filtres par trimestre et matiere.
- Resultats trimestriels.

Routes :

```text
GET /enfants/{eleve}/notes
GET /enfants/{eleve}/resultats
```

### 5. Bulletins

- Liste des bulletins disponibles.
- Ouverture ou telechargement PDF.

Routes :

```text
GET /enfants/{eleve}/bulletins
GET /enfants/{eleve}/bulletins/{trimestre}/telecharger
GET /enfants/{eleve}/bulletins/annuel/telecharger
```

### 6. Paiements

- Situation financiere.
- Historique des paiements officiels.
- Recus PDF.
- Paiements declares.
- Declaration d'un paiement.

Routes :

```text
GET /enfants/{eleve}/paiements
GET /paiements/{paiement}/recu
GET /enfants/{eleve}/paiements-declares
POST /enfants/{eleve}/paiements-declares
GET /paiements-declares/{paiementDeclare}/preuve
```

### 7. Absences, retards et sanctions

- Liste des absences et retards.
- Envoi d'une justification.
- Liste des sanctions.

Routes :

```text
GET /enfants/{eleve}/absences-retards
POST /absences-retards/{absenceRetard}/justifier
GET /justifications/{justification}/piece
GET /enfants/{eleve}/sanctions
```

### 8. Reinscription

- Etat de la demande.
- Envoi d'une demande de reinscription.

Routes :

```text
GET /enfants/{eleve}/reinscription
POST /enfants/{eleve}/reinscription
```

### 9. Annonces et notifications

- Liste des annonces.
- Detail annonce.
- Liste des notifications.
- Marquer une notification comme lue.
- Tout marquer comme lu.

Routes :

```text
GET /annonces
GET /annonces/{annonce}
GET /notifications
GET /notifications/non-lues
GET /notifications/{notification}
PATCH /notifications/{notification}/lue
PATCH /notifications/tout-lu
```

## Ordre de developpement mobile conseille

1. Creer le projet Android Kotlin.
2. Configurer Retrofit, OkHttp et DataStore.
3. Faire l'ecran login.
4. Stocker le token Bearer.
5. Faire l'accueil parent avec liste des enfants.
6. Faire le dashboard enfant.
7. Ajouter les details : notes, resultats, paiements, absences, sanctions.
8. Ajouter bulletins et recus PDF.
9. Ajouter annonces et notifications.
10. Ajouter les actions : paiement declare, justification, reinscription.

## Regles importantes

- Ne jamais coder le token en dur.
- Toujours envoyer `Accept: application/json`.
- Toujours envoyer `Authorization: Bearer TOKEN` apres login.
- Gerer le cas `Unauthenticated.` en renvoyant le parent vers la connexion.
- Gerer les erreurs de validation Laravel pour afficher les messages sous les champs.
- En production, utiliser HTTPS.
