<?php

use Doctrine\ORM\Mapping as ORM;
use repositories\BanRepository;

#[ORM\Entity(repositoryClass: BanRepository::class)]
#[ORM\Table(name: 'BANS')]
class Ban implements \JsonSerializable
{
    #[Orm\Id]
    #[Orm\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private int $id;
    #[Orm\ManyToOne(targetEntity: CasUser::class)]
    #[ORM\JoinColumn(name: 'banned', referencedColumnName: 'login')]
    private CasUser $banned;
    #[ORM\ManyToOne(targetEntity: CasUser::class)]
    #[ORM\JoinColumn(name: 'banner', referencedColumnName: 'login')]
    private ?CasUser $banner;
    #[ORM\Column(type: "string", nullable: true)]
    private ?string $reason;
    #[ORM\Column(type: "datetime")]
    private \DateTime $timestamp;
    #[ORM\Column(type: "datetime", nullable: true)]
    private ?\DateTime $expires;

    public function __construct(CasUser $banned, ?CasUser $banner, ?string $reason, ?DateTime $expires)
    {
        $this->banned = $banned;
        $this->banner = $banner;
        $this->reason = $reason;
        $this->timestamp = new \DateTime('now');
        $this->expires = $expires;
    }

    public function pardon(): void
    {
        $this->expires = new DateTime();
    }

    public function jsonSerialize(): array
    {
        return [
            "banned" => $this->banned,
            "banner" => $this->banner,
            "reason" => $this->reason,
            "timestamp" => $this->timestamp->getTimestamp(),
            "expires" => $this->expires?->getTimestamp()
        ];
    }
}