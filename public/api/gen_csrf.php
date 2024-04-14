<?php

use Doctrine\ORM\EntityManager;
use Errors;

require_once '../../private/auth_endpoint.php';
require_once '../../private/env.php';
require_once '../../private/utils.php';
require_once '../../private/Errors.php';

if ($_SERVER["REQUEST_METHOD"] != "POST") {
    die_with_http_code(405, "<h1>Method not allowed</h1>");
}

$json = file_get_contents('php://input');
$_POST = json_decode($json, true);

if (!array_key_exists("uuid", $_POST)) {
    throw_error(Errors::NOT_ENOUGH_KEYS);
}

require_once '../../private/bootstrap.php';
global $entityManager;
$csrfRepo = $entityManager->getRepository(CSRFToken::class);
$oldCsrf = $csrfRepo->findOneBy(["uuid" => $_POST["uuid"]]);
if ($oldCsrf !== null) {
    $entityManager->remove($oldCsrf);
    $entityManager->flush();
}

generate_csrf($entityManager, $_POST["uuid"]);

function generate_csrf(EntityManager $entityManager, string $uuid, int $maxRetries = 3, int $retryCount = 0, Exception $causingEx = null): void{
    if($retryCount > $maxRetries)
        die_with_http_code_json(500, ["success" => false, "error" => Errors::COULD_NOT_GENERATE_CSRF, "exception" => $causingEx]);
    try {
        $newCsrf = new CSRFToken($uuid, get_env("csrf_expiry"));
        $entityManager->persist($newCsrf);
        $entityManager->flush();
        die_with_http_code_json(200, ["success" => true, "token" => $newCsrf->getToken()]);
    }catch(\Doctrine\DBAL\Exception\UniqueConstraintViolationException $ex){
        resetEntityManager();
        global $entityManager;
        generate_csrf($entityManager, $uuid, $maxRetries, $retryCount+1, $ex);
    }
}


