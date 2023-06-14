<?php

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'LOGGED')]
class LoggedUser implements \JsonSerializable
{
	#[ORM\Id]
    #[ORM\GeneratedValue]
	#[ORM\Column(type: 'integer')]
	private int $id;
	#[ORM\OneToOne(targetEntity: CasUser::class)]
	#[Orm\JoinColumn(name: 'login', referencedColumnName: 'login')]
	private CasUser $user;
	#[Orm\Column(type: 'string')]
	private string $uuid;

    public function __construct(CasUser $user, string $uuid)
    {
        $this->user = $user;
        $this->uuid = $uuid;
    }

    public function jsonSerialize(): array
	{
		return [
			"user" => $this->user,
			"uuid" => $this->uuid
		];
	}

	/**
	 * @return CasUser
	 */
	public function getUser(): CasUser
	{
		return $this->user;
	}

	/**
	 * @return string
	 */
	public function getUuid(): string
	{
		return $this->uuid;
	}
}