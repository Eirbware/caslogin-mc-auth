<?php
require_once '../auth_endpoint.php';
require_once '../utils.php';
require_once '../Requests.php';
if($_SERVER['REQUEST_METHOD'] != "GET")
	die_with_http_code(405, "<h1>Method not allowed</h1>");
require_once '../bootstrap.php';
global $entityManager;
die_with_http_code_json(200, ["success" => true, "users" => $entityManager->getRepository(LoggedUser::class)->findAll()]);
