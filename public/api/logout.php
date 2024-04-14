<?php

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use JetBrains\PhpStorm\NoReturn;
use Errors;

require_once '../../private/auth_endpoint.php';
require_once '../../private/utils.php';
require_once '../../private/Errors.php';

#[NoReturn] function handle_logout(EntityManager $entityManager, string $user): void
{
	$casUser = get_cas_user_or_die($entityManager->getRepository(CasUser::class), $user);
	$loggedUser = get_logged_user_or_die($entityManager->getRepository(LoggedUser::class), $casUser);
	$entityManager->remove($loggedUser);
	$entityManager->flush();
	die_with_http_code_json(200, ["success" => true]);
}

function get_cas_user_or_die(EntityRepository $casUserRepo, string $user): CasUser
{
	$casUser = $casUserRepo->find($user);
	if ($casUser === null)
		throw_error(Errors::USER_NOT_IN_DATABASE);
	return $casUser;
}

function get_logged_user_or_die(EntityRepository $loggedUserRepo, CasUser $casUser): LoggedUser
{
	$loggedUser = $loggedUserRepo->findOneBy(["user" => $casUser]);
	if ($loggedUser === null)
		throw_error(Errors::USER_NOT_LOGGED_IN);
	return $loggedUser;
}

if ($_SERVER["REQUEST_METHOD"] != "POST")
	die_with_http_code(405, "<h1>Method not allowed</h1>");

$json = file_get_contents('php://input');
$_POST = json_decode($json, true);

if (!array_key_exists("user", $_POST))
	throw_error(Errors::NOT_ENOUGH_KEYS);

require_once '../../private/bootstrap.php';
global $entityManager;

handle_logout($entityManager, $_POST["user"]);