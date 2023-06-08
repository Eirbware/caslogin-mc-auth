<?php
require_once '../env.php';
require_once '../utils.php';
if($_SERVER['REQUEST_METHOD'] != "GET"){
    http_response_code(405);
    echo '<h1>Method not allowed</h1>';
    die();
}

if(!array_key_exists("uuid", $_GET)){
    http_response_code(400);
    echo '<h1>Bad Request</h1>';
    die();
}

if(!array_key_exists('ticket', $_GET)){
    redirect_cas();
}else{
    login_success();
}

function redirect_cas(): void
{
    http_response_code(302);
    $casUrl = get_env("cas_auth") . "?service=" . get_current_request_url();
    header("Location: " . $casUrl);
}

function login_success(): void
{
    $validationCode = create_auth_file();
    echo "Your validation code is <b>$validationCode</b>";
    echo "<br/>You can keep the validation code and close the tab now.";
}

function create_auth_file(): string
{
    $casTok = $_GET["ticket"];
    $validationCode = sprintf("%06d", mt_rand(1, 999999));
    $fp = fopen("authCodes/" . $_GET["uuid"], "w");
    fwrite($fp, "$validationCode\n$casTok");
    fclose($fp);
    return $validationCode;
}

