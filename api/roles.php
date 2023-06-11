<?php
require_once '../auth_endpoint.php';
require_once '../utils.php';
require_once '../Role.php';
require_once '../Requests.php';
require_once '../Errors.php';
require_once '../CasLoginPDO.php';

function handle_add_roles(CasLoginPDO $pdo): void
{
	check_user_has_not_role($pdo);
	$smt = $pdo->prepare(Requests::ADD_ROLE_TO_USER);
	$smt->bindValue(":user", $_POST["user"]);
	$smt->bindValue(":role", $_POST["role"]);
	$smt->execute();
}

function handle_del_roles(CasLoginPDO $pdo): void
{
	check_user_has_role($pdo);
	$smt = $pdo->prepare(Requests::REMOVE_ROLE_FROM_USER);
	$smt->bindValue(":user", $_POST["user"]);
	$smt->bindValue(":role", $_POST["role"]);
	$smt->execute();
}

function check_user_has_role(CasLoginPDO $pdo)
{
	$smt = $pdo->prepare(Requests::SEARCH_USER_BY_ROLE_AND_LOGIN);
	$smt->bindValue(":loginSearch", $_POST["user"]);
	$smt->bindValue(":roleSearch", $_POST["role"]);
	$smt->execute();
	if($smt->rowCount() === 0)
		die_with_http_code_json(400, ["succes" => false, "error" => Errors::USER_DOES_NOT_HAVE_ROLE]);
}

function check_user_has_not_role(CasLoginPDO $pdo)
{
	$smt = $pdo->prepare(Requests::SEARCH_USER_BY_ROLE_AND_LOGIN);
	$smt->bindValue(":loginSearch", $_POST["user"]);
	$smt->bindValue(":roleSearch", $_POST["role"]);
	$smt->execute();
	if($smt->rowCount() > 0)
		die_with_http_code_json(400, ["succes" => false, "error" => Errors::USER_HAS_ROLE]);
}

function check_role_exist(CasLoginPDO $pdo): void
{
	$smt = $pdo->prepare(Requests::SEARCH_ROLE_BY_ID);
	$smt->bindValue(":idSearch", $_POST["role"]);
	$smt->execute();
	if($smt->rowCount() === 0)
		die_with_http_code_json(400, ["success" => false, "error" => Errors::ROLE_NOT_IN_DATABASE]);
}

function check_user_exists(CasLoginPDO $pdo): void
{
	$smt = $pdo->prepare(Requests::SEARCH_CAS_USER_BY_LOGIN);
	$smt->bindValue(":loginSearch", $_POST["user"]);
	$smt->execute();
	if($smt->rowCount() === 0)
		die_with_http_code_json(400, ["success" => false, "error" => Errors::USER_NOT_IN_DATABASE]);
}

if($_SERVER['REQUEST_METHOD'] != "POST" && $_SERVER['REQUEST_METHOD'] != "DELETE")
	die_with_http_code(405, "<h1>Method not allowed</h1>");

$json = file_get_contents('php://input');
$_POST = json_decode($json, true);

if(!array_has_all_keys($_POST, "user", "role"))
	die_with_http_code_json(400, ["success" => false, "error" => Errors::NOT_ENOUGH_KEYS]);

$pdo = new CasLoginPDO();
check_user_exists($pdo);
check_role_exist($pdo);

if($_SERVER['REQUEST_METHOD'] == "POST")
	handle_add_roles($pdo);
else
	handle_del_roles($pdo);
die_with_http_code_json(200, ["success" => true]);