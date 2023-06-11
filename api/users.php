<?php
require_once '../utils.php';
require_once '../CasLoginPDO.php';
require_once '../LoggedUser.php';
require_once '../Requests.php';
if($_SERVER['REQUEST_METHOD'] != "GET")
	die_with_http_code(405, "<h1>Method not allowed</h1>");

$pdo = new CasLoginPDO();
$smt = $pdo->prepare(Requests::GET_LOGGED_NOT_BANNED);
$smt->execute();
$users = [];
foreach ($smt as $row) {
	$usr = new LoggedUser();
	$usr->login = $row['user'];
	$usr->uuid = $row['uuid'];
	if (!array_key_exists($usr->login, $users)) {
		$users[$usr->login] = $usr;
	}
	if (strlen($row['role']) > 0)
		$users[$usr->login]->roles[] = Role::from($row['role']);
}

die_with_http_code_json(200, ["success" => true, "users" => array_values($users)]);
