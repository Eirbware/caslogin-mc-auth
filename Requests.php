<?php

enum Requests: string
{
    case SELECT_USERS = 'SELECT * FROM CASUSERS';
    case SELECT_LOGGED = 'SELECT * FROM LOGGED';
    case SELECT_ROLES = 'SELECT * FROM ROLES';
    case SELECT_USERS_WITH_ROLES = "SELECT u.*, r.id as 'role' from CASUSERS u left outer join USER_ROLES ur on (u.login = ur.login) left outer join ROLES r on (ur.role = r.id);";
}
