<?php
require_once '../utils.php';
require_once '../CasLoginPDO.php';
require_once '../Requests.php';

function check_banned_exists(CasLoginPDO $pdo): void
{
	$smt = $pdo->prepare(Requests::SEARCH_CAS_USER_BY_LOGIN);
	$smt->bindValue(":loginSearch", $_POST['banned']);
	$smt->execute();
	if($smt->rowCount() === 0)
		die_with_http_code_json(400, ["success" => false, "error" => "BANNED_NOT_AN_USER"]);
	$smt->closeCursor();
}
function check_banner_exists(CasLoginPDO $pdo): void
{
	$smt = $pdo->prepare(Requests::SEARCH_CAS_USER_BY_LOGIN);
	$smt->bindValue(":loginSearch", $_POST['banner']);
	$smt->execute();
	if($smt->rowCount() === 0)
		die_with_http_code_json(400, ["success" => false, "error" => "BANNER_NOT_AN_USER"]);
	$smt->closeCursor();
}

function check_expires_correct_timestamp(): void
{
	if(!array_key_exists("expires", $_POST))
		return;
	if((string)(int)$_POST['expires'] != $_POST['expires'])
		die_with_http_code_json(400, ["success" => false, "error" => "EXPIRES_NOT_A_TIMESTAMP"]);
}

function ban_user(CasLoginPDO $pdo): void
{
	$smt = $pdo->prepare(Requests::BAN_USER);
	$smt->bindValue(":banned", $_POST["banned"]);
	$smt->bindValue(":banner", $_POST["banner"]);
	$smt->bindValue(":reason", array_key_exists("reason", $_POST) ? $_POST['reason'] : null);
	$smt->bindValue(":expires", array_key_exists("expires", $_POST) ? date("Y-m-d H:i:s", $_POST['expires']) : null);
	$smt->execute();
}

if($_SERVER['REQUEST_METHOD'] != "POST")
	die_with_http_code(405, "<h1>Bad Method</h1>");

header("Accept: application/json");

$json = file_get_contents('php://input');
$_POST = json_decode($json, true);

if(!array_has_all_keys($_POST, "banner", "banned"))
	die_with_http_code(400, "<h1>Bad Request</h1>");

$pdo = new CasLoginPDO();
check_banned_exists($pdo);
check_banner_exists($pdo);
check_expires_correct_timestamp();

ban_user($pdo);
die_with_http_code_json(200, ["success" => true]);

