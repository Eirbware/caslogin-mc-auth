<?php

use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'CASUSERS')]
class CasUser implements JsonSerializable
{
	#[ORM\Id]
	#[ORM\Column(type: "string")]
	private string $login;

	#[ORM\Column(type: "string")]
	private string $ecole;

	/** @var Collection<string, Role> */
	#[ORM\JoinTable(name: 'USER_ROLES')]
	#[ORM\JoinColumn(name: 'login', referencedColumnName: 'login')]
	#[Orm\InverseJoinColumn(name: 'role', referencedColumnName: 'id')]
	#[Orm\ManyToMany(targetEntity: Role::class)]
	private Collection $roles;

	public function __construct($json){
		$res = $json["serviceResponse"]["authenticationSuccess"];
		$this->login = $res["user"];
		$this->ecole = $res["attributes"]["ecole"];
		$this->roles = new ArrayCollection();
	}

	public function getLogin(): string
	{
		return $this->login;
	}

	public function getEcole(): string
	{
		return $this->ecole;
	}

	public function jsonSerialize(): array
	{
		return [
			"login" => $this->login,
			"ecole" => $this->ecole,
			"roles" => $this->getRoles()
		];
	}

	public function getRoles(): array
	{
		return $this->roles->toArray();
	}
}