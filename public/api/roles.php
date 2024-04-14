<?php

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use private\Errors;

require_once '../../private/auth_endpoint.php';
require_once '../../private/utils.php';
require_once '../../private/Errors.php';

function handle_add_roles(EntityManager $entityManager, CasUser $user, Role $role): void
{
	if ($user->getRoles()->contains($role))
		throw_error(Errors::USER_HAS_ROLE);
	$user->getRoles()->add($role);
	$entityManager->flush();
}

function handle_del_roles(EntityManager $entityManager, CasUser $user, Role $role): void
{
	if (!$user->getRoles()->contains($role))
		throw_error(Errors::USER_DOES_NOT_HAVE_ROLE);
	$user->getRoles()->removeElement($role);
	$entityManager->flush();
}

function get_role_or_die(EntityRepository $roleRepo): Role
{

	$role = $roleRepo->findOneBy(["id" => $_POST["role"]]) or throw_error(Errors::ROLE_NOT_IN_DATABASE);
	return $role;
}

function get_user_or_die(EntityRepository $userRepo): CasUser
{
	$user = $userRepo->findOneBy(["login" => $_POST["user"]]) or throw_error(Errors::USER_NOT_IN_DATABASE);
	return $user;
}

if ($_SERVER['REQUEST_METHOD'] != "POST" && $_SERVER['REQUEST_METHOD'] != "DELETE")
	die_with_http_code(405, "<h1>Method not allowed</h1>");

$json = file_get_contents('php://input');
$_POST = json_decode($json, true);

if (!array_has_all_keys($_POST, "user", "role"))
	throw_error(Errors::NOT_ENOUGH_KEYS);

require_once '../../private/bootstrap.php';
global $entityManager;
$user = get_user_or_die($entityManager->getRepository(CasUser::class));
$role = get_role_or_die($entityManager->getRepository(Role::class));

if ($_SERVER['REQUEST_METHOD'] == "POST")
	handle_add_roles($entityManager, $user, $role);
else
	handle_del_roles($entityManager, $user, $role);
die_with_http_code_json(200, ["success" => true]);