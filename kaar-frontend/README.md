# Kaar — Frontend React

Frontend de la plateforme de réservation de cars (voyageur, administrateur de compagnie, administrateur).

## Démarrage
```bash
npm install
cp .env.example .env     # VITE_API_URL = URL de l'API CodeIgniter
npm run dev              # http://localhost:5173
```
Activez le CORS côté CodeIgniter pour `http://localhost:5173` (en-têtes `Authorization`, `Content-Type`).

## Parcours
| Profil | Accès |
|---|---|
| Visiteur | `/`, `/trajets`, `/trajets/:id`, `/compagnies` : consultation libre. La connexion n'est demandée qu'au clic sur **Réserver** (popup). |
| Voyageur | Réservation → places → paiement simulé → billet. `/mes-billets` |
| Admin compagnie (`company_admin`) | `/compagnie` (redirigé après connexion) |
| Admin plateforme (`admin`) | `/admin` (redirigé après connexion) |

## Points à valider côté backend (non décrits dans openapi.yaml)
1. **Plan des places** : `seat_ids` est obligatoire pour réserver, mais aucune route ne liste les places.
   Le front essaie `GET /api/trips/{id}/seats`, puis `trip.seats`, sinon génère des places numérotées 1..N
   (⚠ à n'utiliser qu'en test : les identifiants ne correspondront pas à votre base).
   Format lu : `{ id, seat_number, status | is_available }`.
2. **Billets** : champs lus avec repli (`ticket_number`, `seat_number`, `reservation_id`, `departure_city`…). Adaptez `normalizeTicket()` dans `TicketCard.jsx`.
3. **Dashboards** : l'API ne renvoie que des compteurs (pas de séries temporelles) ; les graphiques montrent donc des répartitions par statut.
4. Les chiffres du bandeau d'accueil (+24 000, 35, 120+, 4,8/5) sont statiques, comme dans la maquette.
