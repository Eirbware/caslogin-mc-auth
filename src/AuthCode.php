<?php

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: "AUTHCODES")]
class AuthCode
{
	#[ORM\Id]
	#[ORM\GeneratedValue]
	#[ORM\Column(type: 'integer')]
	private int $id;

	#[ORM\Column(type: "string")]
	private string $uuid;

	#[ORM\Column(type: "string")]
	private string $code;

	#[ORM\Column(type: "string")]
	private string $token;

	#[ORM\Column(type: "datetime")]
	private \DateTime $created;

	public function __construct(string $uuid, string $code, string $token)
	{
		$this->uuid = $uuid;
		$this->code = $code;
		$this->token = $token;
		$this->created = new DateTime('now');
	}

	/**
	 * @return int
	 */
	public function getId(): int
	{
		return $this->id;
	}

	/**
	 * @return string
	 */
	public function getUuid(): string
	{
		return $this->uuid;
	}

	/**
	 * @return string
	 */
	public function getCode(): string
	{
		return $this->code;
	}

	/**
	 * @return string
	 */
	public function getToken(): string
	{
		return $this->token;
	}

	/**
	 * @return DateTime
	 */
	public function getCreated(): DateTime
	{
		return $this->created;
	}


}