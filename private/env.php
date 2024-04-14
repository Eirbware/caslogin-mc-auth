<?php
$env = parse_ini_file("env.ini", TRUE, INI_SCANNER_TYPED);

function get_env(string $key)
{
	global $env;
	return $env[$key];
}