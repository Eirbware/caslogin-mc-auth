<?php
require_once '../auth_endpoint.php';
require_once '../LoggedUser.php';
require_once '../CasLoginPDO.php';
require_once '../Requests.php';

header('content-type:application/json');
function get_users(): void
{
    $pdo = new CasLoginPDO();
    $smt = $pdo->prepare(Requests::SELECT_USERS_WITH_ROLES);
    $smt->execute();

    $users = [];
    foreach($smt as $row){
        $usr = new LoggedUser();
        $usr->login = $row['login'];
        $usr->uuid = $row['uuid'];
        if(!array_key_exists($usr->login, $users)){
            $users[$usr->login] = $usr;
        }
        if(strlen($row['role']) > 0)
            $users[$usr->login]->roles[] = Role::from($row['role']);
    }

    echo json_encode($users);
}

function post_user(): void
{

}

function del_user(): void
{

}

switch($_SERVER['REQUEST_METHOD']){
    case 'GET':
        get_users();
        break;
    case 'POST':
        post_user();
        break;
    case 'DELETE':
        del_user();
        break;
    default:
        header('content-type:text/html; charset=UTF-8');

        http_response_code(405);
        echo '<h1>405 Method not Allowed</h1>';
        break;
}
