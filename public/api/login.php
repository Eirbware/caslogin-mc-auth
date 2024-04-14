<?php

use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManager;
use JetBrains\PhpStorm\NoReturn;
use Errors;

require_once '../../private/env.php';
require_once '../../private/utils.php';
require_once '../../private/Errors.php';
if ($_SERVER['REQUEST_METHOD'] != "GET") {
    http_response_code(405);
    echo '<h1>Method not allowed</h1>';
    die();
}

if (!array_key_exists("token", $_GET)) {
    http_response_code(400);
    echo '<h1>Bad Request</h1>';
    die();
}

if (!array_key_exists('ticket', $_GET)) {
    $MD5_LEN = 32;
    $tokenLength = 10;
    $csrf = substr(md5(microtime()), rand(0, $MD5_LEN - $tokenLength - 1), $tokenLength);
    session_start();
    $_SESSION["csrf"] = $csrf;
    ?>
    <div style="display: flex; flex-direction: column; align-items: center">
        <h1 style="font-size: xxx-large">Are you sure you want to log in to the minecraft server?</h1>
        <div style="margin-top: 10em; display: flex; justify-content: space-between; width: 30%;">
            <a href="<?= get_env("cas_auth") . "?service=" . rawurlencode(get_current_request_url() . "&csrf=" . $csrf); ?>"
               style="font-size: xxx-large;">Yes</a>
            <a href="https://haveibeenpwned.com" style="font-size: xxx-large">No</a>
        </div>
    </div>

    <?php
} else {
    session_start();
    if (!isset($_GET["csrf"])
        || !isset($_SESSION["csrf"])
        || strcmp($_GET["csrf"], $_SESSION["csrf"]) != 0){
        die_with_http_code(403, "<h1>Someone tried to hack you.</h1>");
    }
    require_once '../../private/bootstrap.php';
    global $entityManager;
    login_success($entityManager, $_GET["ticket"], $_GET["token"]);
}

function validate_cas_token(string $casToken, string $token): mixed
{
    // Dirty but needed to not change code between prod and dev lol...)
    if (!str_contains(get_env("cas_auth"), "cas.bordeaux-inp.fr")) {
        $servUrl = rawurlencode(remove_single_get_param(get_current_request_url(), "ticket"));
        $serviceUrl = get_env("cas_auth") . "?service=" . base64_encode($servUrl);
    } else
        $serviceUrl = remove_single_get_param(get_current_request_url(), "ticket");
    $validationUrl = get_env("cas_validate") . "?ticket=$casToken&service=$serviceUrl&format=json";
    $ch = curl_init($validationUrl);

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, 0);

    $resStr = curl_exec($ch);
    curl_close($ch);
    $res = json_decode($resStr, true);
    if (array_key_exists("authenticationFailure", $res["serviceResponse"])) {
        throw_error(Errors::INVALID_TOKEN, ["debug" => $res]);
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
        throw_error(Errors::USER_BANNED, ["ban" => $ban]);
}

function login_player(EntityManager $entityManager, string $uuid, CasUser $casUser): LoggedUser
{
    $logged = new LoggedUser($casUser, $uuid);
    $entityManager->persist($logged);
    $entityManager->flush();
    return $logged;
}

#[NoReturn] function login_success(EntityManager $entityManager, string $ticket, string $token): void
{

    $res = validate_cas_token($ticket, $token);
    $casUser = get_or_create_cas_user($entityManager, $res);
    check_if_player_banned($entityManager, $casUser);
//    check_if_player_logged_in($entityManager, $casUser);
    $uuid = get_uuid_from_token_or_die($entityManager, $token);
    check_already_logged_user($entityManager, $casUser);
    check_already_logged_uuid($entityManager, $uuid);
    login_player($entityManager, $uuid, $casUser);
    die_with_http_code(200, "Successfully logged in. Please close this tab and return to the game");
}

function check_already_logged_uuid(EntityManager $entityManager, string $uuid)
{
    if ($entityManager->getRepository(LoggedUser::class)->findOneBy(["uuid" => $uuid]))
        die_with_http_code(400, "<h1>You are already logged in!</h1>");
}

function check_already_logged_user(EntityManager $entityManager, CasUser $casUser)
{
    if ($entityManager->getRepository(LoggedUser::class)->findOneBy(["user" => $casUser]))
        die_with_http_code(400, "<h1><emph>" . $casUser->getLogin() . "</emph> is already logged in!</h1>");
}

function get_uuid_from_token_or_die(EntityManager $entityManager, string $token): string
{
    $csrfRepo = $entityManager->getRepository(CSRFToken::class);
    $criteria = new Criteria();
    $criteria
        ->where(Criteria::expr()->eq("token", $token))
        ->andWhere(Criteria::expr()->gt("expires", new DateTime('now')));
    $csrfTok = $csrfRepo->matching($criteria)->first();
    if (!$csrfTok)
        die_with_http_code(400, "<h1>Invalid or expired CSRF token!</h1>");
    $uuid = $csrfTok->getUuid();
    $entityManager->remove($csrfTok);
    $entityManager->flush();
    return $uuid;
}

//function create_auth_code(EntityManager $entityManager): string
//{
//    $oldAuth = $entityManager->getRepository(CSRFToken::class)->findOneBy(["uuid" => $_GET['uuid']]);
//    if ($oldAuth !== null) {
//        $entityManager->remove($oldAuth);
//        $entityManager->flush();
//    }
//    $casTok = $_GET["ticket"];
//    $validationCode = sprintf("%06d", mt_rand(1, 999999));
//    $auth = new CSRFToken($_GET['uuid'], $validationCode, $casTok);
//    $entityManager->persist($auth);
//    $entityManager->flush();
//    return $validationCode;
//}

