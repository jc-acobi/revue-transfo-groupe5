# Atelier vibe coding — gabarit (guide organisateur)

> Ce dépôt est le **gabarit générique**. Il ne se déploie jamais lui-même. On en
> fabrique un dépôt par groupe (`revue-transfo-groupe1` … `groupe6`).
> Ce fichier est destiné à **l'organisateur**, pas aux participants.

## Principe

Chaque **groupe** dispose de son propre dépôt. Un groupe = 4 personnes = 2 binômes.
Chaque binôme travaille de son côté sans gêner l'autre, puis tout le monde se rejoint
sur une intégration commune, et enfin la version finale est publiée sur le portail interne.

## Les 4 environnements (par groupe)

| Espace de travail | Branche | Dossier sur le serveur | Adresse |
|---|---|---|---|
| Binôme 1 | `binome1` | `/var/www/workshop/<groupe>/binome1` | `interne.acobi.fr/workshop/<groupe>/binome1/` |
| Binôme 2 | `binome2` | `/var/www/workshop/<groupe>/binome2` | `interne.acobi.fr/workshop/<groupe>/binome2/` |
| Intégration du groupe | `dev` | `/var/www/workshop/<groupe>/dev` | `interne.acobi.fr/workshop/<groupe>/dev/` |
| **Portail (version finale)** | `main` | `/var/www/apps/<groupe>` | `interne.acobi.fr/apps/<groupe>/` |

Le flux : `binome1` / `binome2` → `dev` → `main`. Seule la branche `main` est visible sur le portail.

## Intégration au portail

Le portail (`interne.acobi.fr`) scanne automatiquement `/var/www/apps/*/manifest.json`.
Dès que la version finale est publiée dans `/var/www/apps/<groupe>` (avec un `manifest.json`
où `visible: true`), une carte apparaît sur le portail. **Aucune modification du portail n'est nécessaire.**

## Prérequis serveur — UNE SEULE FOIS (pas par groupe)

Sur le VPS, avec l'utilisateur de déploiement (`VPS_USER`, ici `ubuntu`) :

1. Créer le dossier des espaces de travail et lui en donner la propriété
   (`/var/www/apps` existe déjà et appartient à `VPS_USER`) :

   ```bash
   sudo mkdir -p /var/www/workshop
   sudo chown <VPS_USER>: /var/www/workshop
   ```

2. Router les URL vers ces dossiers, qui sont à côté de la racine du portail
   (`/var/www/portail`), via deux liens symboliques :

   ```bash
   ln -s /var/www/apps     /var/www/portail/apps
   ln -s /var/www/workshop /var/www/portail/workshop
   ```

   Ces liens pointent vers les dossiers entiers → ils couvrent **tous** les groupes,
   présents et futurs. C'est tout : les déploiements créent ensuite eux-mêmes les
   sous-dossiers de chaque groupe.

## Créer le dépôt d'un groupe

1. Sur GitHub, marquer ce dépôt comme *template repository* (Settings → « Template repository »).
2. Cliquer **« Use this template »** pour créer `revue-transfo-groupe2` (puis `…3`, etc.).
   Le dépôt doit être **public** (voir plus bas).
3. Ajouter les secrets du dépôt : `VPS_HOST`, `VPS_USER`, `VPS_PASSWORD`
   (Settings → Secrets and variables → Actions). Ils ne sont pas copiés depuis le gabarit.
4. Cloner le nouveau dépôt en local, puis l'instancier :

   ```powershell
   ./nouveau-groupe.ps1 -Numero 2 -Pousser
   ```

   Les 4 déploiements partent et **créent automatiquement** les dossiers serveur du groupe.
   **Aucune manipulation serveur par groupe.**

## Remettre un groupe à neuf

Entre deux sessions de test, ou avant l'atelier, pour repartir d'une page vierge :

```powershell
./reset-groupe.ps1 -Pousser
```

Le script réaligne les 4 branches sur le repère `etat-initial` (posé à l'instanciation)
et republie. **Tout le travail effectué après l'état initial est effacé.**

## Déploiement auto-réparant

Sur le VPS, chaque workflow : si le dossier cible n'est pas encore un clone Git, il le
crée (`git clone`) ; sinon il le met à jour (`git fetch` + `git reset --hard`). L'URL du
dépôt est déduite de `github.repository`. Le garde-fou `if: github.repository != 'jc-acobi/revue-transfo'`
empêche le gabarit de se déployer.

## Dépôts publics

Les dépôts de groupe sont **publics** afin que le VPS puisse les cloner sans identifiants
GitHub (le portail l'est aussi). Le code ne contient aucun secret ; ceux-ci vivent dans
GitHub → Actions secrets.

## Repères remplacés à l'instanciation

| Repère | Devient (ex. groupe 2) | Présent dans |
|---|---|---|
| `__GROUPE__` | `groupe2` | `CLAUDE.md`, workflows |
| `__GROUPE_LABEL__` | `Groupe 2` | `manifest.json`, `index.php` |

## En cas de souci

- **Run rouge `connection reset by peer`** : aléa SSH (les 4 déploiements frappent le VPS
  en même temps). Relancer le run suffit.
- **403 sur `/apps/...`** : Apache bloque le suivi des liens symboliques → activer
  `Options +FollowSymLinks`, ou remplacer les liens par des `Alias` dans le vhost.
