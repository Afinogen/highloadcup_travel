<?php
/**
 * Created by PhpStorm.
 * User: afinogen
 * Date: 11.08.17
 * Time: 16:30
 */

namespace Travel;

define('TEST_TIMESTAMP', 1503333691);

require_once __DIR__.'/load.php';

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

$routes = explode('/', $uri);
$entity = $routes[1] ?? 0;
$id = $routes[2] ?? 0;
$action = $routes[3] ?? 0;

$className = __NAMESPACE__.'\\'.ucfirst($entity);
if (!class_exists($className)) {
    header('HTTP/1.0 404 Not Found');
    die();
}

$db = new \PDO(
    'mysql:dbname=travel;host=localhost;port=3306', 'root', null, [
        \PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES \'UTF8\'',
        \PDO::ATTR_PERSISTENT         => true
    ]
);

/** @var \Travel\AbstractEntity $class */
$class = new $className($db);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_SERVER['Content-Type'])) {
        $type = trim(explode(';', $_SERVER['Content-Type'])[0]);
        if ($type !== 'application/json') {
            header('HTTP/1.0 400 Bad Values');
            die();
        }
    }
    $inputJSON = file_get_contents('php://input');
    $input = json_decode($inputJSON, true);
    if ($input && $class->checkFields($input, $id !== 'new')) {

        $itemId = (int)$id;
        if ($itemId > 0 && $class->hasItem($itemId)) {

            $class->update($input, $itemId);

            header('Content-Type: application/json; charset=utf-8');
//            header('Content-Length: 2');
            echo '{}';
            die();
        }

        if ($id === 'new') {
            $class->insert($input);
            header('Content-Type: application/json; charset=utf-8');
//            header('Content-Length: 2');
            echo '{}';
            die();
        }

        header('HTTP/1.0 404 Not Found');
        die();
    }

    header('HTTP/1.0 400 Bad Values');
    die();
}

if ((int)$id > 0) {
    if (!$action) {
        $res = $class->findById($id);
        if ($res) {
            $val = json_encode($class->hydrate($res));

            header('Content-Type: application/json; charset=utf-8');
//            header('Content-Length: '.strlen($val));

            echo $val;
            die();
        }

        header('HTTP/1.0 404 Not Found');
        die();
    }

    $res = $class->hasItem($id);
    if (!$res) {
        header('HTTP/1.0 404 Not Found');
        die();
    }
    $filter = [];
    if (!empty($_GET)) {
        $filter = $class->getFilter($_GET);
        if (!$filter) {
            header('HTTP/1.0 400 Bad Values');
            die();
        }
    }

    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([$action => $class->{$action}($id, $filter)]);
    die();
}

header('HTTP/1.0 404 Not Found');
die();
