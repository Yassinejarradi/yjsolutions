<?php

// Vérifie le PHP portable avant l'installation : version 8.4 ou plus et extensions chargées (voir php\README.txt).

$missing = array_values(array_filter(
    ['pdo_sqlite', 'sqlite3', 'mbstring', 'fileinfo', 'openssl', 'gd', 'zip', 'intl', 'curl', 'dom', 'xml', 'xmlwriter', 'ctype', 'tokenizer', 'iconv'],
    fn (string $extension) => ! extension_loaded($extension),
));

$ok = true;

if (version_compare(PHP_VERSION, '8.4.0', '<')) {
    echo '[ERREUR] PHP '.PHP_VERSION.' : il faut PHP 8.4 (voir php\README.txt).'.PHP_EOL;
    $ok = false;
}

if (PHP_ZTS) {
    echo '[ATTENTION] Ce PHP est la version « Thread Safe » : la version « Non Thread Safe » (NTS) est conseillée.'.PHP_EOL;
}

if ($missing !== []) {
    echo '[ERREUR] Extensions PHP non chargées : '.implode(', ', $missing).PHP_EOL;
    echo '         Vérifiez php\php.ini et le dossier php\ext (voir php\README.txt).'.PHP_EOL;
    $ok = false;
}

if ($ok) {
    echo 'PHP '.PHP_VERSION.' (64 bits, '.(PHP_ZTS ? 'TS' : 'NTS').') : toutes les extensions sont chargées.'.PHP_EOL;
}

exit($ok ? 0 : 1);
