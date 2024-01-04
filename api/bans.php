<?php

use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use JetBrains\PhpStorm\NoReturn;
use repositories\BanRepository;

require_once '../auth_endpoint.php';
require_once '../utils.php';
require_once '../Errors.php';
require_once '../src/Ban.php';


function get_user_or_die(EntityRepository $userRepo, string $login): CasUser
{
    $user = $userRepo->findOneBy(["login" => $login]) or die_with_http_code_json(400, ["success" => false, "error" => Errors::BANNED_NOT_AN_USER]);
    return $user;
}

function check_expires_correct_timestamp(): void
{
    if (!array_key_exists("expires", $_POST))
        return;
    if ((string)(int)$_POST['expires'] != $_POST['expires'])
        die_with_http_code_json(400, ["success" => false, "error" => Errors::EXPIRES_NOT_A_TIMESTAMP]);
}

function ban_user(EntityManager $entityManager, CasUser $banned, ?CasUser $banner): void
{
    check_user_not_banned_or_die($entityManager, $banned);
    $ban = new Ban($banned,
        $banner,
        array_key_exists("reason", $_POST) ? $_POST['reason'] : null,
        array_key_exists("expires", $_POST) ? datetime_from_timestamp((int)$_POST["expires"]) : null);
    $entityManager->persist($ban);
    $entityManager->flush();
}

function check_user_not_banned_or_die(EntityManager $entityManager, CasUser $banned): void
{
    /** @var BanRepository $banRepo */
    $banRepo = $entityManager->getRepository(Ban::class);

    if(count($banRepo->getCurrentBanOfUser($banned)) > 0){
        die_with_http_code_json(400, ["success" => false, "error" => Errors::USER_ALREADY_BANNED]);
    }
}

#[NoReturn] function handle_ban_user(EntityManager $entityManager): void
{
    $json = file_get_contents('php://input');
    $_POST = json_decode($json, true);

    if (!array_has_all_keys($_POST, "banned"))
        die_with_http_code_json(400, ["success" => false, "error" => Errors::NOT_ENOUGH_KEYS]);

    check_expires_correct_timestamp();
    $banner = array_key_exists("banner", $_POST)
        ? get_user_or_die($entityManager->getRepository(CasUser::class), $_POST['banner'])
        : null;
    $banned = get_user_or_die($entityManager->getRepository(CasUser::class), $_POST['banned']);

    ban_user($entityManager, $banned, $banner);
    die_with_http_code_json(200, ["success" => true]);
}

#[NoReturn] function handle_get_bans(BanRepository $banRepo): void
{
    if (array_key_exists("expired", $_GET)) {
        $val = filter_var($_GET["expired"], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        if ($_GET["expired"] === null) {
            die_with_http_code_json(400, ["success" => false, "error" => Errors::INVALID_PARAMETERS]);
        }
        if ($_GET["expired"]) {
            die_with_expired_bans($banRepo);
        }
        die_with_not_expired_bans($banRepo);
    }
    die_with_http_code_json(200, ["success" => true, "bans" => $banRepo->findAll()]);
}

#[NoReturn] function die_with_not_expired_bans(BanRepository $banRepo): void
{

    die_with_http_code_json(200, ["success" => true, "bans" => $banRepo->getAllCurrentBans()]);
}

#[NoReturn] function die_with_expired_bans(BanRepository $banRepo): void
{
    $criteria = Criteria::create()
        ->where(Criteria::expr()->not(Criteria::expr()->isNull('expires')))
        ->andWhere(Criteria::expr()->lte('expires', new DateTime()));
    $query = $banRepo->createQueryBuilder("b")->addCriteria($criteria)->getQuery();

    die_with_http_code_json(200, ["success" => true, "bans" => $banRepo->getAllExpiredBans()]);
}

header("Accept: application/json");

if ($_SERVER['REQUEST_METHOD'] != "POST" && $_SERVER['REQUEST_METHOD'] != "GET")
    die_with_http_code(405, "<h1>Bad Method</h1>");
require_once '../bootstrap.php';
global $entityManager;
if ($_SERVER['REQUEST_METHOD'] == "POST") {
    handle_ban_user($entityManager);
} else {
    handle_get_bans($entityManager->getRepository(Ban::class));
}

