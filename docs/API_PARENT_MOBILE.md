# API parent mobile - Laravel

Cette documentation decrit l'API REST exposee par le projet Laravel pour l'application mobile parent Android native en Kotlin.

## Base URL

En developpement local :

```text
http://127.0.0.1:8000/api/parent
```

Sur un telephone physique, `127.0.0.1` designe le telephone lui-meme. Il faut utiliser l'adresse IP de la machine qui lance Laravel, par exemple :

```text
http://192.168.1.20:8000/api/parent
```

Lancer Laravel en reseau local :

```bash
php artisan serve --host=0.0.0.0 --port=8000
```

## Format commun

Les routes retournent du JSON.

Reponse de succes :

```json
{
  "success": true,
  "message": "Operation reussie.",
  "data": {}
}
```

Erreur de validation :

```json
{
  "message": "The email field is required.",
  "errors": {
    "email": ["The email field is required."]
  }
}
```

Erreur d'authentification :

```json
{
  "message": "Unauthenticated."
}
```

## Authentification

L'API utilise Laravel Sanctum avec token Bearer.

Apres connexion, l'application Android doit stocker le token et l'envoyer dans toutes les routes protegees :

```http
Authorization: Bearer TOKEN_ICI
Accept: application/json
```

### Connexion

```http
POST /login
```

Body :

```json
{
  "email": "exemple@gmail.com",
  "password": "password",
  "device_name": "android-parent"
}
```

Reponse :

```json
{
  "success": true,
  "message": "Connexion reussie.",
  "data": {
    "token_type": "Bearer",
    "token": "1|xxxxxxxx",
    "user": {
      "id": 9,
      "nom": "nom",
      "prenom": "prenom",
      "email": "exemple@gmail.com",
      "role": "parent"
    }
  }
}
```

### Deconnexion

```http
POST /logout
Authorization: Bearer TOKEN_ICI
```

### Profil parent

```http
GET /me
PUT /me
PUT /password
```

`PUT /me` permet de modifier le profil parent. `PUT /password` permet de modifier le mot de passe du parent connecte.

### Mot de passe oublie OTP

```http
POST /password/forgot
POST /password/verify-otp
POST /password/reset
```

## Enfants du parent

### Liste des enfants

```http
GET /enfants
```

Retourne uniquement les enfants lies au parent connecte.

### Dashboard enfant

```http
GET /enfants/{eleve}/dashboard
```

Retourne un resume complet : informations enfant, inscription active, classe, annee, trimestre, situation financiere, dernieres notes, paiements, absences, sanctions.

## Notes

```http
GET /enfants/{eleve}/notes
```

Filtres possibles :

```text
?annee_scolaire_id=2
?trimestre_id=3
?matiere_id=1
```

## Resultats

```http
GET /enfants/{eleve}/resultats
```

Retourne les resultats trimestriels disponibles pour l'enfant : moyenne, rang, appreciation, trimestre et disponibilite du bulletin.

## Bulletins PDF

### Liste des bulletins

```http
GET /enfants/{eleve}/bulletins
```

### Telecharger un bulletin trimestriel

```http
GET /enfants/{eleve}/bulletins/{trimestre}/telecharger
```

### Telecharger le bulletin annuel

```http
GET /enfants/{eleve}/bulletins/annuel/telecharger
```

Ces routes retournent un fichier PDF. Elles sont protegees par le token parent.

## Paiements et recus

### Paiements officiels

```http
GET /enfants/{eleve}/paiements
```

Retourne la situation financiere et la liste des paiements valides.

### Recu PDF

```http
GET /paiements/{paiement}/recu
```

Retourne le recu PDF du paiement, seulement si le paiement appartient a un enfant du parent connecte.

## Paiements declares

### Liste

```http
GET /enfants/{eleve}/paiements-declares
```

### Declaration d'un paiement

```http
POST /enfants/{eleve}/paiements-declares
```

Body JSON minimal :

```json
{
  "montant": 25000,
  "mode_paiement": "mobile_money",
  "reference_transaction": "OM-123456"
}
```

Si une preuve de paiement est envoyee, utiliser `multipart/form-data`.

### Preuve de paiement

```http
GET /paiements-declares/{paiementDeclare}/preuve
```

## Absences et retards

### Liste

```http
GET /enfants/{eleve}/absences-retards
```

### Justifier une absence ou un retard

```http
POST /absences-retards/{absenceRetard}/justifier
```

Body minimal :

```json
{
  "motif": "Maladie"
}
```

Si une piece justificative est envoyee, utiliser `multipart/form-data`.

### Piece justificative

```http
GET /justifications/{justification}/piece
```

## Sanctions

```http
GET /enfants/{eleve}/sanctions
```

Retourne les sanctions appliquees a l'enfant, avec statut, motif, mesure, dates et retrait de points si disponible.

## Reinscription

### Etat de la demande

```http
GET /enfants/{eleve}/reinscription
```

### Envoyer une demande

```http
POST /enfants/{eleve}/reinscription
```

Body exemple :

```json
{
  "type_demande": "passage",
  "message": "Demande de reinscription pour l'annee suivante."
}
```

## Annonces

```http
GET /annonces
GET /annonces/{annonce}
```

Retourne les annonces visibles par le parent selon la cible de l'annonce : tous, parents ou classe de l'enfant.

## Notifications

```http
GET /notifications
GET /notifications/non-lues
GET /notifications/{notification}
PATCH /notifications/{notification}/lue
PATCH /notifications/tout-lu
```

Les notifications sont propres au parent connecte.

## Regle de securite principale

Chaque route verifie que le parent connecte a le droit d'acceder a la ressource :

```text
parent connecte -> enfant lie au parent -> ressource de cet enfant
```

Un parent ne doit jamais pouvoir consulter l'enfant, le bulletin, le recu, la notification ou la piece justificative d'un autre parent.
