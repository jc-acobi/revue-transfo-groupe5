<?php
/**
 * Applique les migrations SQL pas encore exécutées sur la base courante.
 *
 * Chaque fichier `migrations/*.sql` est joué une seule fois ; le suivi est gardé
 * dans la table `schema_migrations`. Les fichiers sont nommés avec un horodatage
 * (ex. 20260616_1432_creer_messages.sql) et joués dans l'ordre alphabétique du
 * nom (donc l'ordre chronologique).
 *
 * Règle : pour faire évoluer la base, on AJOUTE un nouveau fichier — on ne modifie
 * jamais un fichier déjà appliqué.
 */
function migrate(PDO $bdd, string $dossier): void
{
    $bdd->exec("CREATE TABLE IF NOT EXISTS schema_migrations (
        fichier VARCHAR(255) PRIMARY KEY,
        applique_le TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $deja = array_flip(
        $bdd->query("SELECT fichier FROM schema_migrations")->fetchAll(PDO::FETCH_COLUMN)
    );

    $fichiers = glob($dossier . '/*.sql') ?: [];
    sort($fichiers); // ordre chronologique grâce aux noms horodatés

    foreach ($fichiers as $chemin) {
        $nom = basename($chemin);
        if (isset($deja[$nom])) {
            continue;
        }
        $sql = file_get_contents($chemin);
        if (trim($sql) !== '') {
            $bdd->exec($sql); // un fichier peut contenir plusieurs instructions
        }
        $bdd->prepare("INSERT INTO schema_migrations (fichier) VALUES (?)")->execute([$nom]);
    }
}
