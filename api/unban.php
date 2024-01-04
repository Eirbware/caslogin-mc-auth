<?php

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\Persistence\ObjectRepository;
use JetBrains\PhpStorm\NoReturn;
use repositories\BanRepository;

require_once '../auth_endpoint.php';
require_once '../utils.php';
require_once '../Errors.php';
require_once '../src/Ban.php';

function get_cas_user_or_die(EntityRepository $userRepo, string $login): CasUser
{
    $user = $userRepo->find($login) or throw_error(Errors::USER_NOT_IN_DATABASE);
    return $user;
}

#[NoReturn] function handle_unban_user(EntityManager $entityManager): void
{
    $user = get_cas_user_or_die($entityManager->getRepository(CasUser::class), $_POST["user"]);
    /** @var BanRepository $banRepository */
    $banRepository = $entityManager->getRepository(Ban::class);
    $ban = $banRepository->getCurrentBanOfUser($user);
    if($ban === null){
        throw_error(Errors::USER_NOT_BANNED);
    }
    $ban->pardon();
    $entityManager->flush();
    die_with_http_code_json(200, ["success" => true]);
}

if ($_SERVER['REQUEST_METHOD'] != "POST")
    die_with_http_code(405, "<h1>Bad Method</h1>");
$_POST = json_decode(file_get_contents('php://input'), true);
if (!array_key_exists("user", $_POST))
    die_with_http_code_json(400, ["success" => false, "error" => Errors::INVALID_PARAMETERS]);
require_once '../bootstrap.php';
global $entityManager;
handle_unban_user($entityManager);