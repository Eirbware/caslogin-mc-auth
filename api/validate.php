<?php
require_once '../auth_endpoint.php';
require_once '../env.php';
require_once '../utils.php';
require_once '../CasLoginPDO.php';
require_once '../Requests.php';
require_once '../LoggedUser.php';
require_once '../Ban.php';

if ($_SERVER["REQUEST_METHOD"] != "GET") {
	http_response_code(405);
	die('<h1>Method Not Allowed</h1>');
}

if (!array_has_all_keys($_GET, "code", "uuid")) {
	http_response_code(400);
	die('<h1>Not enough parameters</h1>');
}

function validate_auth($uuid, $authCode): void
{
	if (!is_dir("authCodes"))
		mkdir('authCodes', 0700);
	$filepath = "authCodes/$uuid";
	$handle = fopen($filepath, 'r') or die_with_http_code_json(400, ["success" => false, "error" => "NO_AUTH_CODE_FOR_UUID"]);
	if (time() - filectime($filepath) >= get_env("auth_code_expiry")) {
		unlink($filepath);
		die_with_http_code_json(400, ["success" => false, "error" => "AUTH_CODE_EXPIRED"]);
	}

	if (feof($handle))
		die_with_http_code(500, "<h1>Internal Server Error</h1>");
	$actualAuthCode = trim(fgets($handle));
	if (feof($handle))
		die_with_http_code(500, "<h1>Internal Server Error</h1>");
	$casToken = trim(fgets($handle));
	fclose($handle);
	unlink($filepath);
	if ($authCode != $actualAuthCode)
		die_with_http_code_json(400, ["success" => false, "error" => "INVALID_AUTH_CODE"]);
	$user = validate_cas_token($casToken, $uuid);
	die_with_http_code_json(200, ["success" => true, "user" => $user]);
}

function validate_cas_token(string $casToken, string $uuid): LoggedUser
{
	// Dirty but needed to not change code between prod and dev lol...)
	if (!str_contains(get_env("cas_auth"), "cas.bordeaux-inp.fr")) {
		$servUrl = rawurlencode(get_protocol() . $_SERVER["HTTP_HOST"] . "/api/login.php?uuid=$uuid");
		$serviceUrl = get_env("cas_auth") . "?service=" . base64_encode($servUrl);
	} else
		$serviceUrl = get_current_request_url();
	$validationUrl = get_env("cas_validate") . "?ticket=$casToken&service=$serviceUrl&format=json";
	$ch = curl_init($validationUrl);

	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_HEADER, 0);

	$resStr = curl_exec($ch);
	curl_close($ch);
	$res = json_decode($resStr, true)["serviceResponse"];
	if (array_key_exists("authenticationFailure", $res)) {
		die_with_http_code_json(400, ["success" => false, "error" => "INVALID_TOKEN"]);
	}

	return log_user($res["authenticationSuccess"], $uuid);
}

function log_user(mixed $authenticationSuccess, string $uuid): LoggedUser
{
	$casUser = get_or_create_cas_user($authenticationSuccess["user"]);
	$pdo = new CasLoginPDO();
	check_if_player_banned($pdo, $casUser);
	return login_player($pdo, $uuid, $casUser);


}

function login_player(CasLoginPDO $pdo, string $uuid, string $casUser)
{
	$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_SILENT);
	$smt = $pdo->prepare(Requests::LOG_USER);
	$smt->bindValue(":user", $casUser);
	$smt->bindValue(":uuid", $uuid);
	$smt->execute();
	$smt = $pdo->prepare(Requests::SEARCH_LOGGED_USER_WITH_ROLES_BY_LOGIN);
	$smt->bindValue(":loginSearch", $casUser);
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
	return $users[$casUser];
}

function check_if_player_banned(CasLoginPDO $pdo, $casUser): void
{
	$smt = $pdo->prepare(Requests::SEARCH_NOT_EXPIRED_BAN_BY_USER);
	$smt->bindValue(":userSearch", $casUser);
	$smt->execute();
	if ($smt->rowCount() > 0) {
		$row = $smt->fetch();
		$ban = new Ban();
		$ban->bannedUser = $row['banned'];
		$ban->banner = $row['banner'];
		$ban->reason = $row['reason'];
		$ban->timestamp = new DateTime($row['timestamp']);
		$ban->expires = $row['expires'] === null ? null : new DateTime($row['expires']);

		die_with_http_code_json(400, ["success" => false, "error" => "USER_BANNED", "ban" => $ban]);
	}
}


function get_or_create_cas_user(string $user): string
{
	$pdo = new CasLoginPDO();
	$smt = $pdo->prepare(Requests::SEARCH_CAS_USER_BY_LOGIN);
	$smt->bindValue(":loginSearch", $user);
	$smt->execute();
	if ($smt->rowCount() > 0)
		return $user;
	$smt->closeCursor();
	$pdo->beginTransaction();
	$smt = $pdo->prepare(Requests::CREATE_CAS_USER);
	$smt->bindValue(":login", $user);
	if (!$smt->execute()) {
		$pdo->rollback();
		throw new RuntimeException("FATAL ERROR, CANNOT CREATE CAS USER");
	}
	$pdo->commit();
	return $user;
}

validate_auth($_GET['uuid'], $_GET['code']);
