<?php

require_once 'Role.php';

class LoggedUser implements JsonSerializable
{
    //TODO Create CASUser class to make it truly transparent to the database but that's a pain...
    public string $login;
    public string $uuid;
    /* @var Role[] */
    public array $roles;
    public function __construct()
    {
        $this->roles = [];
    }

    public function __toString(): string
    {
        return "[" . implode(',', $this->roles) . "]";
    }

    public function jsonSerialize(): array
    {
        return [
            "login" => $this->login,
            "uuid" => $this->uuid,
            "roles" => array_map(function ($r) {return $r->name;}, $this->roles)
        ];
    }
}