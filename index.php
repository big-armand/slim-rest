<?php
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

require 'vendor/autoload.php';

class MyDB extends SQLite3 {
  function __construct() {
    $this->open('friends.db');
  }
}

$db = new MyDB();
if(!$db) {
  echo $db->lastErrorMsg();
  exit();
}


$app = new \Slim\App;

$app->get('/hello/{name}', function (Request $request, Response $response, array $args) {
  $name = $args['name'];
  $response->getBody()->write("Hello, $name");

  return $response;
});
$app->get(
  '/friends',
  function (Request $request, Response $response, array $args) use ($db) {
    $sql = "select * from participant";
    $stmt = $db->prepare($sql);
    $ret = $stmt->execute();
    if (!$ret) {
      return $response->getBody()->write("ERROR with the database");
    }
    $friends = [];
    while ($friend = $ret->fetchArray(SQLITE3_ASSOC)) {
      $friends[] = $friend;
    }
    return $response->withJson($friends);
  }
);
$app->get(
  '/friends/{id}',
  function (Request $request, Response $response, array $args) use ($db) {
    $sql = "select * from participant where id = :id";
    $stmt = $db->prepare($sql);
    $stmt->bindValue('id', $args['id']);
    $ret = $stmt->execute();
    if (!$ret) {
      return $response->getBody()->write("ERROR with the database");
    }
    $friends = [];
    while ($friend = $ret->fetchArray(SQLITE3_ASSOC)) {
      $friends[] = $friend;
    }
    if (empty($friends)) {
      return $response->withStatus(404)->withHeader('Content-Type', 'text/html')->write('Error 404 : Page not found');
    }
    return $response->withJson($friends);
  }
);
$app->post(
    '/friends',
    function (Request $request, Response $response, array $args) use ($db) {
        $requestData = $request->getParsedBody();
        if (!isset($requestData['name']) || !isset($requestData['surname'])) {
            return $response->withStatus(400)->withJson(['error' => 'Name and surname are required.']);
        }
        $sql = "insert into 'participant' (name, surname) values (:name, :surname)";
        $stmt = $db->prepare($sql);
        $stmt->bindValue('name', $requestData['name']);
        $stmt->bindValue('surname', $requestData['surname']);
        $stmt->execute();
        $newUserId = $db->lastInsertRowID();
        return $response->withStatus(201)->withHeader('Location', "/friends/$newUserId");
    }
);
$app->put(
  '/friends/{id}',
  function (Request $request, Response $response, array $args) use ($db) {
    $requestData = $request->getParsedBody();
    $sql = "update 'participant' set name = :name, surname = :surname where id = :id";
    $stmt = $db->prepare($sql);
    $stmt->bindValue('name', $requestData['name']);
    $stmt->bindValue('surname', $requestData['surname']);
    $stmt->bindValue('id', $args['id']);
    $ret = $stmt->execute();

    $sql = "select * from participant where id = :id";
    $stmt = $db->prepare($sql);
    $stmt->bindValue('id', $args['id']);
    $ret = $stmt->execute();
    $friends = [];
    while ($friend = $ret->fetchArray(SQLITE3_ASSOC)) {
      $friends[] = $friend;
    }
    if (empty($friends)) {
      return $response->withStatus(404)->withHeader('Content-Type', 'text/html')->write('Error 404 : Page not found');
    }
    return $response->withStatus(201);
  }
);
$app->delete(
  '/friends/{id}',
  function (Request $request, Response $response, array $args) use ($db) {
    $requestData = $request->getParsedBody();

    $sql = "select * from participant where id = :id";
    $stmt = $db->prepare($sql);
    $stmt->bindValue('id', $args['id']);
    $ret = $stmt->execute();

    $friends = [];
    while ($friend = $ret->fetchArray(SQLITE3_ASSOC)) {
      $friends[] = $friend;
    }

    $sql = "delete from 'participant' where id = :id";
    $stmt = $db->prepare($sql);
    $stmt->bindValue('id', $args['id']);
    $ret = $stmt->execute();

    if (empty($friends)) {
      return $response->withStatus(404)->withHeader('Content-Type', 'text/html')->write('Error 404 : Page not found');
    }
    return $response->withStatus(204);
  }
);

$app->run();
