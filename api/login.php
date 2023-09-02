<?php

use Doctrine\ORM\EntityManager;
use JetBrains\PhpStorm\NoReturn;

require_once '../env.php';
require_once '../utils.php';
require_once '../Errors.php';
if ($_SERVER['REQUEST_METHOD'] != "GET") {
	http_response_code(405);
	echo '<h1>Method not allowed</h1>';
	die();
}

if (!array_key_exists("uuid", $_GET)) {
	http_response_code(400);
	echo '<h1>Bad Request</h1>';
	die();
}

if (!array_key_exists('ticket', $_GET)) {
	redirect_cas();
} else {
	require_once '../bootstrap.php';
	global $entityManager;
	login_success($entityManager, $_GET["ticket"], $_GET["uuid"]);
}

function redirect_cas(): void
{
	http_response_code(302);
	$casUrl = get_env("cas_auth") . "?service=" . get_current_request_url();
	header("Location: " . $casUrl);
}

function validate_cas_token(string $casToken, string $uuid): mixed
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
    return $res;
}

function get_or_create_cas_user(EntityManager $entityManager, mixed $res): CasUser
{
    $userRepo = $entityManager->getRepository(CasUser::class);
    $user = $userRepo->find($res["serviceResponse"]["authenticationSuccess"]["user"]);
    if ($user === null) {
        $user = new CasUser($res);
        $entityManager->persist($user);
        $entityManager->flush();
    }
    return $user;
}

function check_if_player_banned(EntityManager $entityManager, CasUser $casUser): void
{
    $banRepo = $entityManager->getRepository(Ban::class);
    $ban = $banRepo->findOneBy(["banned" => $casUser->getLogin()]);
    if ($ban !== null)
        die_with_http_code_json(400, ["success" => false, "error" => Errors::USER_BANNED, "ban" => $ban]);
}

function login_player(EntityManager $entityManager, string $uuid, CasUser $casUser): LoggedUser
{
    $logged = new LoggedUser($casUser, $uuid);
    $entityManager->persist($logged);
    $entityManager->flush();
    return $logged;
}

#[NoReturn] function login_success(EntityManager $entityManager, string $ticket, string $uuid): void
{
    $res = validate_cas_token($ticket, $uuid);
    $casUser = get_or_create_cas_user($entityManager, $res);
    check_if_player_banned($entityManager, $casUser);
//    check_if_player_logged_in($entityManager, $casUser);
    login_player($entityManager, $uuid, $casUser);
    die_with_http_code(200, "Successfully logged in. Please wait");
}

function create_auth_code(EntityManager $entityManager): string
{
	$oldAuth = $entityManager->getRepository(CSRFToken::class)->findOneBy(["uuid" => $_GET['uuid']]);
	if($oldAuth !== null){
		$entityManager->remove($oldAuth);
		$entityManager->flush();
	}
	$casTok = $_GET["ticket"];
	$validationCode = sprintf("%06d", mt_rand(1, 999999));
	$auth = new CSRFToken($_GET['uuid'], $validationCode, $casTok);
	$entityManager->persist($auth);
	$entityManager->flush();
	return $validationCode;
}

