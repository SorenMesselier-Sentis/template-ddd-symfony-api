# Tester les e-mails (Twig + Mailpit)

Ce guide décrit le flux complet pour déclencher et visualiser les e-mails en local.

## Prérequis

- Stack Docker démarrée : `make up`
- Base initialisée : `make db-fresh` (charge les fixtures, dont un admin)
- Variables mail dans `.env.local` (copie de `.env` si besoin) :

```bash
MAILER_DSN=smtp://mailpit:1025
MAILER_FROM=noreply@example.com
```

> **Note :** depuis le conteneur PHP, l’hôte SMTP est `mailpit`, pas `localhost`.

## Architecture du flux

```
POST /api/v1/users  ou  DELETE /api/v1/users/{id}
        │
        ▼
CommandHandler → événement domaine → table outbox (même transaction)
        │
        ▼
make outbox-relay          # publie vers RabbitMQ
        │
        ▼
make consume               # consomme la queue events.user
        │
        ▼
SendWelcomeEmailOnUserCreated  /  SendAccountDeletionEmailOnUserDeleted
        │
        ▼
Twig (templates/email/…) → NotificationSender → Symfony Mailer → Mailpit
```

## Démarrage des workers

Ouvrir **3 terminaux** :

```bash
# Terminal 1 — consumer RabbitMQ (laisser tourner)
make consume

# Terminal 2 — relais outbox (à relancer après chaque action API, ou en boucle)
make outbox-relay

# Terminal 3 — commandes curl / tests manuels
```

Pour automatiser le relais outbox en continu :

```bash
watch -n 2 make outbox-relay
```

## Compte admin (fixtures)

| Champ    | Valeur                 |
|----------|------------------------|
| Email    | `john.doe@example.com` |
| Password | `secret1234`           |
| Rôles    | `ROLE_ADMIN`, `ROLE_USER` |

## 1. E-mail de bienvenue (UserCreated)

### Connexion

```bash
TOKEN=$(curl -s -X POST http://localhost:8080/api/v1/auth/login \
  -H 'Content-Type: application/json' \
  -d '{"email":"john.doe@example.com","password":"secret1234"}' \
  | php -r 'echo json_decode(file_get_contents("php://stdin"))->data->access_token;')

echo "$TOKEN"
```

### Création d’un utilisateur

```bash
curl -s -X POST http://localhost:8080/api/v1/users \
  -H "Authorization: Bearer $TOKEN" \
  -H 'Content-Type: application/json' \
  -d '{
    "firstName": "Alice",
    "lastName": "Martin",
    "email": "alice.martin@example.com",
    "password": "secret1234"
  }'
```

### Relais + vérification

```bash
make outbox-relay   # terminal 2
```

Ouvrir Mailpit : http://localhost:8025 (ou `make mail`)

Vous devez voir un e-mail **Welcome to the platform!** envoyé à `alice.martin@example.com`.

Templates modifiables :

- `templates/email/user/welcome.subject.twig`
- `templates/email/user/welcome.txt.twig`
- `templates/email/user/welcome.html.twig`

## 2. E-mail de suppression de compte (UserDeleted)

### Récupérer l’ID d’un utilisateur

```bash
USER_ID=$(curl -s "http://localhost:8080/api/v1/users?email=alice.martin@example.com" \
  -H "Authorization: Bearer $TOKEN" \
  | php -r '$d=json_decode(file_get_contents("php://stdin")); echo $d->data[0]->id;')

echo "$USER_ID"
```

### Supprimer l’utilisateur

```bash
curl -s -X DELETE "http://localhost:8080/api/v1/users/$USER_ID" \
  -H "Authorization: Bearer $TOKEN" \
  -w "\nHTTP %{http_code}\n"
```

Réponse attendue : `HTTP 204`.

### Relais + vérification

```bash
make outbox-relay
```

Dans Mailpit : e-mail **Your account has been deleted** pour `alice.martin@example.com`.

Templates :

- `templates/email/user/account_deletion.*.twig`

## Dépannage

| Symptomôme | Cause probable | Action |
|------------|----------------|--------|
| Pas d’e-mail dans Mailpit | Outbox non relayée | `make outbox-relay` |
| Pas d’e-mail dans Mailpit | Consumer arrêté | `make consume` |
| Erreur SMTP | Mauvais `MAILER_DSN` | `smtp://mailpit:1025` dans `.env.local` |
| `401` sur POST /users | Token manquant ou non admin | Se connecter avec `john.doe@example.com` |
| Message en failed | Voir la dead letter | `make messenger-failed-show` |

Logs PHP :

```bash
make logs-php
```

Statistiques Messenger :

```bash
make messenger-stats
```

## Modifier le contenu sans redéployer

1. Éditer les fichiers sous `templates/email/`
2. Vider le cache si nécessaire : `make clear`
3. Rejouer l’action API + `make outbox-relay`

Les handlers ne contiennent plus de `sprintf` : seul Twig porte le contenu.
