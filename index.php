<?php
require_once 'CasLoginPDO.php';
require_once 'Requests.php';
require_once 'User.php';
require_once 'Role.php';

$pdo = new CasLoginPDO();
$smt = $pdo->prepare(Requests::SELECT_USERS_WITH_ROLES->value);
$smt->execute();
$users = [];
echo '<pre>';
foreach($smt as $row){
    print_r($row);
    $usr = new User();
    $usr->login = $row['login'];
    $usr->uuid = $row['uuid'];
    if(!array_key_exists($usr->login, $users)){
        $users[$usr->login] = $usr;
    }
    if(strlen($row['role']) > 0)
        $users[$usr->login]->roles[] = Role::from($row['role']);
}
echo '</pre>';

echo '<pre>';
foreach ($users as $user) {
    print_r($user);
}
echo '</pre>';

echo '<pre>' . json_encode(array_values($users)) . '</pre>';