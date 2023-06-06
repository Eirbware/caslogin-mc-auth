<?php
require_once 'utils.php';
require_once 'env.php';

if(get_bearer_token() != get_env('api_key')){
    http_response_code(401);
    echo '<h1>401 Unauthorized</h1>';
    die();
}