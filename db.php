<?php
require_once __DIR__ . '/migrate.php';

/**
 * Connexion aux bases de données du groupe.
 *
 * - Les identifiants viennent d'un fichier serveur (/var/www/config/db.php), jamais du dépôt.
 *   Deux formats acceptés :
 *     nouveau : ['host'=>…, 'groupes'=>['groupe1'=>['user'=>…,'pass'=>…], …]]  (un utilisateur par groupe)
 *     ancien  : ['host'=>…, 'user'=>…, 'pass'=>…]                              (un utilisateur partagé)
 * - La base dépend de l'environnement, déduit du chemin :
 *     /var/www/apps/groupeN              -> groupeN_prod
 *     /var/www/workshop/groupeN/dev      -> groupeN_dev
 *     /var/www/workshop/groupeN/binome1  -> groupeN_binome1
 *     /var/www/workshop/groupeN/binome2  -> groupeN_binome2
 *   Elle est créée automatiquement si elle n'existe pas encore.
 *
 * Utilisation dans une page :
 *   require_once __DIR__ . '/db.php';
 *   $bdd = db();
 *   $lignes = $bdd->query("SELECT * FROM ma_table")->fetchAll();
 */

/** Identifiants (user, pass) pour un groupe donné, selon le format du fichier de config. */
function db_identifiants(array $cfg, string $groupe): array
{
    if (isset($cfg['groupes'][$groupe])) {
        return [$cfg['groupes'][$groupe]['user'], $cfg['groupes'][$groupe]['pass']];
    }
    return [$cfg['user'], $cfg['pass']]; // ancien format (compatibilité)
}

/** Groupe courant (ex. "groupe1"), déduit de l'emplacement du fichier. */
function db_groupe(): string
{
    if (!preg_match('/groupe\d+/', __DIR__, $g)) {
        throw new RuntimeException("Impossible de déterminer le groupe.");
    }
    return $g[0];
}

/** Environnement courant (prod, dev, binome1, binome2), déduit du chemin. */
function db_environnement(): string
{
    if (preg_match('#[\\\\/]apps[\\\\/]#', __DIR__)) {
        return 'prod';
    }
    if (preg_match('#[\\\\/](binome1|binome2|dev)[\\\\/]?$#', __DIR__, $e)) {
        return $e[1];
    }
    return 'dev';
}

/** Ouvre une connexion à la base `<groupe>_<env>` (création + USE) avec les identifiants du groupe. */
function db_connexion(string $env): PDO
{
    $configPath = '/var/www/config/db.php';
    if (!is_file($configPath)) {
        throw new RuntimeException("Configuration de la base introuvable sur le serveur.");
    }
    $cfg = require $configPath;
    $groupe = db_groupe();
    [$user, $pass] = db_identifiants($cfg, $groupe);
    $schema = $groupe . '_' . $env;

    $pdo = new PDO(
        "mysql:host={$cfg['host']};charset=utf8mb4",
        $user,
        $pass,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$schema}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE `{$schema}`");
    return $pdo;
}

/**
 * Connexion à la base de l'environnement COURANT.
 * Crée la base au besoin et applique les migrations en attente.
 */
function db(): PDO
{
    static $pdo = null;
    if ($pdo !== null) {
        return $pdo;
    }
    $pdo = db_connexion(db_environnement());
    migrate($pdo, __DIR__ . '/migrations');
    return $pdo;
}

/**
 * Connexion à une AUTRE base d'environnement DU MÊME GROUPE (prod, dev, binome1, binome2).
 * Sert aux opérations entre environnements (copie de données…).
 * Ne peut ni sortir du groupe courant ni viser un environnement inconnu.
 */
function db_env(string $env): PDO
{
    $env = strtolower(trim($env));
    if (!in_array($env, ['prod', 'dev', 'binome1', 'binome2'], true)) {
        throw new InvalidArgumentException("Environnement inconnu : {$env}");
    }
    return db_connexion($env);
}
