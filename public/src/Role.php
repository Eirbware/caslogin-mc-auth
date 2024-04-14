<?php

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'ROLES')]
class Role implements \JsonSerializable
{
	#[ORM\Id]
	#[ORM\Column(type: "string")]
	private string $id;


	public function getId(): string
	{
		return $this->id;
	}

	public function jsonSerialize(): string
	{
		return $this->id;
	}
}