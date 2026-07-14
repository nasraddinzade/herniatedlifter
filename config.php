<?php
/**
 * Local secrets for Herniated Lifter.
 * This file is git-ignored on purpose — never commit it.
 *
 * The admin password is stored ONLY as a bcrypt hash below; the plain
 * password is not kept anywhere. admin.php checks it with password_verify().
 */

/*
 * Admin password hash (bcrypt / $2y$, cost 12).
 * To change the password later, run this on the server and paste the result:
 *   php -r "echo password_hash('NEW_PASSWORD', PASSWORD_DEFAULT), PHP_EOL;"
 */
define('ADMIN_PASSWORD_HASH', '$2y$12$5hC3LpCAvOQrVejdYQLDC.CeWcaeR77JQQt5bABnyiNwEe5clFLaK');

/*
 * Random salt used to hash visitor IP + User-Agent, so the raw IP is never
 * stored or recoverable.
 */
define('APP_SALT', '97e00e971d9c4cda902cd87f35b79c3898b8674268c8e8be');
