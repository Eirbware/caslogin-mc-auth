<?php
// bootstrap.php
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMSetup;

require_once "vendor/autoload.php";
require_once 'env.php';
// Create a simple "default" Doctrine ORM configuration for Attributes
$config = ORMSetup::createAttributeMetadataConfiguration(
	paths: array(__DIR__."/src"),
);

$databaseParameters = get_env("database");

// configuring the database connection
$connection = DriverManager::getConnection([
	'dbname' => $databaseParameters["dbname"],
	'user' => $databaseParameters["username"],
	'password' => $databaseParameters["password"],
	'host' => $databaseParameters["host"],
	'driver' => 'pdo_mysql',
], $config);

// obtaining the entity manager
$entityManager = new EntityManager($connection, $config);