# Guide de test - API parent mobile

Ce guide permet de tester rapidement l'API parent avec `curl` avant de connecter l'application Android Kotlin.

## 1. Lancer Laravel

```bash
cd ~/Documents/gestion-scolaire
php artisan serve --host=0.0.0.0 --port=8000
```

Pour tester depuis la meme machine :

```text
http://127.0.0.1:8000
```

Pour tester depuis un telephone Android physique, utiliser l'IP de la machine Laravel :

```text
http://ADRESSE_IP_DU_PC:8000
```

## 2. Connexion

```bash
curl -X POST http://127.0.0.1:8000/api/parent/login \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{"email":"exemple@gmail.com","password":"password","device_name":"android-test"}'
```

Copier le token recu :

```bash
TOKEN="COLLER_LE_TOKEN_ICI"
```

## 3. Profil

```bash
curl http://127.0.0.1:8000/api/parent/me \
  -H "Accept: application/json" \
  -H "Authorization: Bearer $TOKEN"
```

## 4. Liste des enfants

```bash
curl http://127.0.0.1:8000/api/parent/enfants \
  -H "Accept: application/json" \
  -H "Authorization: Bearer $TOKEN"
```

Noter l'identifiant d'un enfant. Exemple :

```text
ID_ENFANT=4
```

## 5. Dashboard enfant

```bash
curl http://127.0.0.1:8000/api/parent/enfants/4/dashboard \
  -H "Accept: application/json" \
  -H "Authorization: Bearer $TOKEN"
```

## 6. Notes

```bash
curl http://127.0.0.1:8000/api/parent/enfants/4/notes \
  -H "Accept: application/json" \
  -H "Authorization: Bearer $TOKEN"
```

Avec filtre trimestre :

```bash
curl "http://127.0.0.1:8000/api/parent/enfants/4/notes?trimestre_id=3" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer $TOKEN"
```

## 7. Resultats

```bash
curl http://127.0.0.1:8000/api/parent/enfants/4/resultats \
  -H "Accept: application/json" \
  -H "Authorization: Bearer $TOKEN"
```

## 8. Bulletins

Liste :

```bash
curl http://127.0.0.1:8000/api/parent/enfants/4/bulletins \
  -H "Accept: application/json" \
  -H "Authorization: Bearer $TOKEN"
```

Telechargement PDF trimestriel :

```bash
curl -L http://127.0.0.1:8000/api/parent/enfants/4/bulletins/3/telecharger \
  -H "Authorization: Bearer $TOKEN" \
  --output bulletin-trimestre-3.pdf
```

## 9. Paiements et recus

Paiements :

```bash
curl http://127.0.0.1:8000/api/parent/enfants/4/paiements \
  -H "Accept: application/json" \
  -H "Authorization: Bearer $TOKEN"
```

Recu PDF :

```bash
curl -L http://127.0.0.1:8000/api/parent/paiements/13/recu \
  -H "Authorization: Bearer $TOKEN" \
  --output recu-13.pdf
```

## 10. Paiements declares

Liste :

```bash
curl http://127.0.0.1:8000/api/parent/enfants/4/paiements-declares \
  -H "Accept: application/json" \
  -H "Authorization: Bearer $TOKEN"
```

Declaration simple :

```bash
curl -X POST http://127.0.0.1:8000/api/parent/enfants/4/paiements-declares \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -d '{"montant":25000,"mode_paiement":"mobile_money","reference_transaction":"OM-123456"}'
```

## 11. Absences et retards

```bash
curl http://127.0.0.1:8000/api/parent/enfants/4/absences-retards \
  -H "Accept: application/json" \
  -H "Authorization: Bearer $TOKEN"
```

Justification :

```bash
curl -X POST http://127.0.0.1:8000/api/parent/absences-retards/ID_ABSENCE_RETARD/justifier \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -d '{"motif":"Maladie"}'
```

## 12. Sanctions

```bash
curl http://127.0.0.1:8000/api/parent/enfants/4/sanctions \
  -H "Accept: application/json" \
  -H "Authorization: Bearer $TOKEN"
```

## 13. Reinscription

Etat :

```bash
curl http://127.0.0.1:8000/api/parent/enfants/4/reinscription \
  -H "Accept: application/json" \
  -H "Authorization: Bearer $TOKEN"
```

Demande :

```bash
curl -X POST http://127.0.0.1:8000/api/parent/enfants/4/reinscription \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -d '{"type_demande":"passage","message":"Demande de reinscription."}'
```

## 14. Annonces et notifications

```bash
curl http://127.0.0.1:8000/api/parent/annonces \
  -H "Accept: application/json" \
  -H "Authorization: Bearer $TOKEN"
```

```bash
curl http://127.0.0.1:8000/api/parent/notifications \
  -H "Accept: application/json" \
  -H "Authorization: Bearer $TOKEN"
```

Marquer une notification comme lue :

```bash
curl -X PATCH http://127.0.0.1:8000/api/parent/notifications/ID_NOTIFICATION/lue \
  -H "Accept: application/json" \
  -H "Authorization: Bearer $TOKEN"
```

## 15. Deconnexion

```bash
curl -X POST http://127.0.0.1:8000/api/parent/logout \
  -H "Accept: application/json" \
  -H "Authorization: Bearer $TOKEN"
```

Tester que l'ancien token est invalide :

```bash
curl http://127.0.0.1:8000/api/parent/me \
  -H "Accept: application/json" \
  -H "Authorization: Bearer $TOKEN"
```

Reponse attendue :

```json
{
  "message": "Unauthenticated."
}
```
