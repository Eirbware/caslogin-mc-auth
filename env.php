<?php
$env = parse_ini_file("env.ini", TRUE);

function get_env(string $key){
    global $env;
    return $env[$key];
}