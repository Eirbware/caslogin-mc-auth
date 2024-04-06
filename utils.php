<?php

//https://stackoverflow.com/a/40582472
use JetBrains\PhpStorm\NoReturn;

/**
 * Get header Authorization
 * */
function get_authorization_headers(): ?string
{
	$headers = null;
	if (isset($_SERVER['Authorization'])) {
		$headers = trim($_SERVER["Authorization"]);
	} else if (isset($_SERVER['HTTP_AUTHORIZATION'])) { //Nginx or fast CGI
		$headers = trim($_SERVER["HTTP_AUTHORIZATION"]);
	} elseif (function_exists('apache_request_headers')) {
		$requestHeaders = apache_request_headers();
		// Server-side fix for bug in old Android versions (a nice side effect of this fix means we don't care about capitalization for Authorization)
		$requestHeaders = array_combine(array_map('ucwords', array_keys($requestHeaders)), array_values($requestHeaders));
		//print_r($requestHeaders);
		if (isset($requestHeaders['Authorization'])) {
			$headers = trim($requestHeaders['Authorization']);
		}
	}
	return $headers;
}

/**
 * get access token from header
 * */
function get_bearer_token(): ?string
{
	$headers = get_authorization_headers();
	// HEADER: Get the access token from the header
	if (!empty($headers)) {
		if (preg_match('/Bearer\s(\S+)/', $headers, $matches)) {
			return $matches[1];
		}
	}
	return null;
}

function array_has_all_keys(array $array, ...$keys): bool
{
	foreach ($keys as $key) {
		if (!array_key_exists($key, $array))
			return false;
	}
	return true;
}


#[NoReturn] function die_with_http_code(int $response_code, string $reason = ""): void
{
	http_response_code($response_code);
	die($reason);
}

#[NoReturn] function die_with_http_code_json(int $response_code, mixed $jsonObj): void
{
	http_response_code($response_code);
	header("content-type: application/json");
	die(json_encode($jsonObj));
}

#[NoReturn] function throw_error(Errors $error, array $additionalArgs = []): void{
    die_with_http_code_json(400, array_merge(["success" => false, "error" => $error], $additionalArgs));
}

function get_protocol(): string
{
	return ($_SERVER["HTTPS"] ? "https://" : "http://");
}

function get_current_request_url(): string
{
	return urlencode(get_protocol() . $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"]);
}

function datetime_from_timestamp(int $timestamp): DateTime{
	$dt = new DateTime('now');
	$dt->setTimestamp($timestamp);
	return $dt;
}