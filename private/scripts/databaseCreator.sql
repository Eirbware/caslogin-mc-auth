SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS BANS;
DROP TABLE IF EXISTS CASUSERS;
DROP TABLE IF EXISTS CSRFTOKENS;
DROP TABLE IF EXISTS LOGGED;
DROP TABLE IF EXISTS ROLES;
DROP TABLE IF EXISTS USER_ROLES;
SET FOREIGN_KEY_CHECKS = 1;

create table CASUSERS
(
    login varchar(64) not null
        primary key,
    ecole varchar(64) not null,
    diplome char(6) not null
);

create table BANS
(
    id        int auto_increment
        primary key,
    banned    varchar(64)  not null,
    banner    varchar(64)  null,
    reason    varchar(128) null,
    timestamp datetime     not null,
    expires   datetime     null,
    constraint BANS_ibfk_1
        foreign key (banned) references CASUSERS (login),
    constraint BANS_ibfk_2
        foreign key (banner) references CASUSERS (login)
);

create index banned
    on BANS (banned);

create index banner
    on BANS (banner);

create table CSRFTOKENS
(
    id      int auto_increment
        primary key,
    token   char(10) charset latin1 not null,
    uuid    char(36) charset latin1 not null,
    expires datetime                not null,
    constraint csrf
        unique (token),
    constraint uuid
        unique (uuid)
);

create table LOGGED
(
    id    int auto_increment
        primary key,
    login varchar(64) null,
    uuid  char(36) null,
    constraint login
        unique (login),
    constraint uuid
        unique (uuid),
    constraint LOGGED_ibfk_1
        foreign key (login) references CASUSERS (login),
    constraint USER_LEN_CHECK
        check (length(`uuid`) = 36)
);

create table ROLES
(
    id varchar(32) not null
        primary key
);

INSERT INTO ROLES VALUES ('ADMINISTRATOR'), ('MODERATOR');

create table USER_ROLES
(
    login varchar(64) not null,
    role  varchar(32) not null,
    primary key (login, role),
    constraint USER_ROLES_ibfk_1
        foreign key (login) references CASUSERS (login),
    constraint USER_ROLES_ibfk_2
        foreign key (role) references ROLES (id)
);

create index role
    on USER_ROLES (role);

