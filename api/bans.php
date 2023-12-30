<?php

use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\Expr\Comparison;
use Doctrine\Common\Collections\Expr\CompositeExpression;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use JetBrains\PhpStorm\NoReturn;

require_once '../auth_endpoint.php';
require_once '../utils.php';
require_once '../Errors.php';
require_once '../src/Ban.php';


function get_banned_or_die(EntityRepository $userRepo): CasUser
{
    $user = $userRepo->findOneBy(["login" => $_POST['banned']]) or die_with_http_code_json(400, ["success" => false, "error" => Errors::BANNED_NOT_AN_USER]);
    return $user;
}

function get_banner_or_die(EntityRepository $userRepo): CasUser
{
    $user = $userRepo->findOneBy(["login" => $_POST['banner']]) or die_with_http_code_json(400, ["success" => false, "error" => Errors::BANNER_NOT_AN_USER]);;
    return $user;
}

function check_expires_correct_timestamp(): void
{
    if (!array_key_exists("expires", $_POST))
        return;
    if ((string)(int)$_POST['expires'] != $_POST['expires'])
        die_with_http_code_json(400, ["success" => false, "error" => Errors::EXPIRES_NOT_A_TIMESTAMP]);
}

function ban_user(EntityManager $entityManager, CasUser $banned, CasUser $banner): void
{
    $ban = new Ban($banned,
        $banner,
        array_key_exists("reason", $_POST) ? $_POST['reason'] : null,
        array_key_exists("expires", $_POST) ? datetime_from_timestamp((int)$_POST["expires"]) : null);
    $entityManager->persist($ban);
    $entityManager->flush();
}

#[NoReturn] function handle_ban_user(EntityManager $entityManager): void
{
    $json = file_get_contents('php://input');
    $_POST = json_decode($json, true);

    if (!array_has_all_keys($_POST, "banned"))
        die_with_http_code(400, "<h1>Bad Request</h1>");

    check_expires_correct_timestamp();
    $banner = array_key_exists("banner", $_POST)
        ? get_banner_or_die($entityManager->getRepository(CasUser::class))
        : null;
    $banned = get_banned_or_die($entityManager->getRepository(CasUser::class));

    ban_user($entityManager, $banned, $banner);
    die_with_http_code_json(200, ["success" => true]);
}

#[NoReturn] function handle_get_bans(EntityRepository $banRepo): void
{
    if (array_key_exists("expired", $_GET)) {
        $val = filter_var($_GET["expired"], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        if($_GET["expired"] === null){
            die_with_http_code_json(400, ["success" => false, "error" => Errors::INVALID_PARAMETERS]);
        }
        if ($_GET["expired"]) {
            die_with_expired_bans($banRepo);
        }
        die_with_not_expired_bans($banRepo);
    }
    die_with_http_code_json(200, ["success" => true, "bans" => $banRepo->findAll()]);
}

#[NoReturn] function die_with_not_expired_bans(EntityRepository $banRepo): void
{
    $criteria = Criteria::create()
        ->where(Criteria::expr()->isNull('expires'))
        ->orWhere(new Comparison('expires', Comparison::GT, new DateTime()))
    ;
    $query = $banRepo->createQueryBuilder("b")->addCriteria($criteria)->getQuery();
    die_with_http_code_json(200, ["success" => true, "bans" => $query->getResult()]);
}

#[NoReturn] function die_with_expired_bans(EntityRepository $banRepo): void
{
    $criteria = Criteria::create()
        ->where(new CompositeExpression(CompositeExpression::TYPE_NOT, [Criteria::expr()->isNull('expires')]))
        ->andWhere(new Comparison('expires', Comparison::LTE, new DateTime()))
    ;
    $query = $banRepo->createQueryBuilder("b")->addCriteria($criteria)->getQuery();

    die_with_http_code_json(200, ["success" => true, "bans" => $query->getResult()]);
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

