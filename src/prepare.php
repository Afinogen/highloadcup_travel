<?php
/**
 * Created by PhpStorm.
 * User: afinogen
 * Date: 13.08.17
 * Time: 0:20
 */

namespace Travel;

$timeStart = microtime(true);

require_once __DIR__.'/load.php';
try {
    echo 'Connect Db'.PHP_EOL;
    $db = new \PDO(
        'mysql:dbname=travel;host=localhost;port=3306', 'root', null, [
            \PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES \'UTF8\'',
            \PDO::ATTR_PERSISTENT         => true
        ]
    );
} catch (\Exception $e) {
    echo $e->getMessage();
    exit;
}

echo 'Open Zip'.PHP_EOL;
$zip = zip_open('/tmp/data/data.zip');

$timeStamp = time();

if (is_resource($zip)) {
    echo 'Open - ok'.PHP_EOL;

    echo 'Get options'.PHP_EOL;
    $zipO = new \ZipArchive();

    if ($zipO->open('/tmp/data/data.zip') === true) {
        if ($iconData = $zipO->getFromName('options.txt')) {
            $timeStamp = (int)trim(explode("\r\n", $iconData)[0]);
            $indexFile = file('/var/www/html/index.php');
            $indexFile[10] = 'define(\'TEST_TIMESTAMP\', '.$timeStamp.');'.PHP_EOL;
            file_put_contents('/var/www/html/index.php', implode('', $indexFile));
        }
    }
    define('TEST_TIMESTAMP', $timeStamp);

    do {
        $entry = zip_read($zip);
        if (!$entry) {
            break;
        }

        $name = explode('_', zip_entry_name($entry))[0];
        if ($name === 'options.txt') {
            continue;
        }
        echo 'Name entity - '.$name.PHP_EOL;
        // open entry
        zip_entry_open($zip, $entry, 'r');

        // read entry
        $entry_content = json_decode(zip_entry_read($entry, zip_entry_filesize($entry)), true);

        $rows = $entry_content[$name];
        $className = __NAMESPACE__.'\\'.ucfirst($name);
        /** @var \Travel\AbstractEntity $class */
        $class = new $className($db);
        $res = $class->multiInsert($rows);
        echo date('H:i:s').' Insert - '.$res.PHP_EOL;
    } while ($entry);
} else {
    echo 'Error open zip'.PHP_EOL;
}
echo 'End Prepare'.PHP_EOL;
echo 'Time - '.(microtime(true) - $timeStart).PHP_EOL;