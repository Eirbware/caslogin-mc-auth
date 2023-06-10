<?php

enum Requests: string
{
    const SELECT_USERS = 'SELECT * FROM CASUSERS';
    const SELECT_LOGGED = 'SELECT * FROM LOGGED';
    const SELECT_ROLES = 'SELECT * FROM ROLES';
    const SELECT_USERS_WITH_ROLES = "SELECT u.*, r.id as 'role' from CASUSERS u left outer join USER_ROLES ur on (u.login = ur.login) left outer join ROLES r on (ur.role = r.id)";
    const SEARCH_CAS_USER_BY_LOGIN = "SELECT * from CASUSERS WHERE login LIKE :loginSearch";
    const CREATE_CAS_USER = "INSERT INTO CASUSERS VALUES (:login)";
    const SEARCH_LOGGED_USER_WITH_ROLES_BY_LOGIN = "SELECT u.*, r.id as 'role' from LOGGED u left outer join USER_ROLES ur on (u.user = ur.login) left outer join ROLES r on (ur.role = r.id) WHERE u.user LIKE :loginSearch";
    const GET_LOGGED_BY_UUID = "SELECT * FROM LOGGED WHERE uuid = :uuid";
    const LOG_USER = "INSERT INTO LOGGED VALUES (:user, :uuid)";
    const SEARCH_NOT_EXPIRED_BAN_BY_USER = "SELECT b.banned, b.banner, b.reason, b.timestamp, b.expires FROM CASUSERS u INNER JOIN BANS b ON (u.login = b.banned) WHERE b.banned LIKE :userSearch AND (b.expires IS NULL OR b.expires > NOW()) ORDER BY b.timestamp DESC";
	const BAN_USER = "INSERT INTO BANS (banned, banner, reason, timestamp, expires) VALUES (:banned, :banner, :reason, NOW(), :expires)";
}

