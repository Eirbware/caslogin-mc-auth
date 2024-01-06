<?php

use Doctrine\ORM\EntityRepository;

require_once '../auth_endpoint.php';
require_once '../utils.php';
if ($_SERVER['REQUEST_METHOD'] != "GET")
    die_with_http_code(405, "<h1>Method not allowed</h1>");
require_once '../bootstrap.php';
global $entityManager;
$loggedUserRep = $entityManager->getRepository(LoggedUser::class);
function get_user_with_uuid(string $uuid, EntityRepository $loggedUserRep)
{
    $user = $loggedUserRep->findOneBy(["uuid" => $uuid]);
    die_with_http_code_json(200, ["success" => true, "user" => $user]);
}

if (isset($_GET['uuid'])) {
    get_user_with_uuid($_GET['uuid'], $loggedUserRep);
} else {
    die_with_http_code_json(200, ["success" => true, "users" => $entityManager->getRepository(CasUser::class)->findAll()]);
}