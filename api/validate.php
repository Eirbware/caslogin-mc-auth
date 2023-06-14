<?php

use Doctrine\ORM\EntityManager;
use JetBrains\PhpStorm\NoReturn;

require_once '../auth_endpoint.php';
require_once '../env.php';
require_once '../utils.php';
require_once '../Requests.php';
require_once '../Errors.php';

#[NoReturn] function validate_auth(EntityManager $entityManager, string $uuid, string $authCode): void
{
	if (!is_dir("authCodes"))
		mkdir('authCodes', 0700);
	$filepath = "authCodes/$uuid";
	$handle = fopen($filepath, 'r') or die_with_http_code_json(400, ["success" => false, "error" => Errors::NO_AUTH_CODE_FOR_UUID]);
	if (time() - filectime($filepath) >= get_env("auth_code_expiry")) {
		unlink($filepath);
		die_with_http_code_json(400, ["success" => false, "error" => Errors::AUTH_CODE_EXPIRED]);
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
		die_with_http_code_json(400, ["success" => false, "error" => Errors::INVALID_AUTH_CODE]);
	$user = validate_cas_token($entityManager, $casToken, $uuid);
	die_with_http_code_json(200, ["success" => true, "loggedUser" => $user]);
}

function validate_cas_token(EntityManager $entityManager, string $casToken, string $uuid): LoggedUser
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
	$res = json_decode($resStr, true);
	if (array_key_exists("authenticationFailure", $res["serviceResponse"])) {
		die_with_http_code_json(400, ["success" => false, "error" => Errors::INVALID_TOKEN]);
	}
	return log_user($entityManager, $res, $uuid);
}

function log_user(EntityManager $entityManager, mixed $res, string $uuid): LoggedUser
{
	$casUser = get_or_create_cas_user($entityManager, $res);
	check_if_player_banned($entityManager, $casUser);
    check_if_player_logged_in($entityManager, $casUser);
	return login_player($entityManager, $uuid, $casUser);


}

function check_if_player_logged_in(EntityManager $entityManager, CasUser $casUser): void
{
    $userRepo = $entityManager->getRepository(LoggedUser::class);
    if($userRepo->findOneBy(["user" => $casUser]))
        die_with_http_code_json(400, ["success" => false, "error" => Errors::USER_ALREADY_LOGGED_IN]);
}

function login_player(EntityManager $entityManager, string $uuid, CasUser $casUser): LoggedUser
{
    $logged = new LoggedUser($casUser, $uuid);
    $entityManager->persist($logged);
    $entityManager->flush();
	return $logged;
}

function check_if_player_banned(EntityManager $entityManager, CasUser $casUser): void
{
    $banRepo = $entityManager->getRepository(Ban::class);
    $ban = $banRepo->findOneBy(["banned" => $casUser->getLogin()]);
    if($ban !== null)
        die_with_http_code_json(400, ["success" => false, "error" => Errors::USER_BANNED, "ban" => $ban]);
}


function get_or_create_cas_user(EntityManager $entityManager, mixed $res): CasUser
{
    $userRepo = $entityManager->getRepository(CasUser::class);
    $user = $userRepo->find($res["serviceResponse"]["authenticationSuccess"]["user"]);
    if($user === null){
        $user = new CasUser($res);
        $entityManager->persist($user);
        $entityManager->flush();
    }
	return $user;
}
if ($_SERVER["REQUEST_METHOD"] != "GET") {
    http_response_code(405);
    die('<h1>Method Not Allowed</h1>');
}

if (!array_has_all_keys($_GET, "code", "uuid")) {
    die_with_http_code_json(400, ["success" => false, "error" => Errors::NOT_ENOUGH_KEYS]);
}
require_once '../bootstrap.php';
global $entityManager;
validate_auth($entityManager, $_GET['uuid'], $_GET['code']);
