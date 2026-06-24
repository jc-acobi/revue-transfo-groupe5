# Migrations

Un fichier par évolution de la base, nommé avec un horodatage :
`AAAAMMJJ_HHMM_description.sql` — par exemple `20260616_1432_creer_messages.sql`.

Règles :
- On **ajoute** un fichier ; on ne **modifie jamais** un fichier déjà appliqué.
- Une feature qui touche la base = un fichier (plusieurs instructions SQL possibles).
- Les fichiers sont appliqués automatiquement, **une seule fois par environnement**,
  au chargement des pages (via `db()`).
