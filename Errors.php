<?php

enum Errors implements JsonSerializable
{
	case BANNED_NOT_AN_USER;
	case BANNER_NOT_AN_USER;

	case EXPIRES_NOT_A_TIMESTAMP;

	case USER_DOES_NOT_HAVE_ROLE;

	case USER_HAS_ROLE;

	case ROLE_NOT_IN_DATABASE;

	case USER_NOT_IN_DATABASE;

	case NOT_ENOUGH_KEYS;

	case NO_AUTH_CODE_FOR_UUID;

	case AUTH_CODE_EXPIRED;

	case INVALID_AUTH_CODE;

	case INVALID_TOKEN;

	case USER_BANNED;

	public function jsonSerialize(): string
	{
		return $this->name;
	}
}
