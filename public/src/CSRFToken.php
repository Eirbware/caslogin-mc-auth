<?php

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: "CSRFTOKENS")]
class CSRFToken
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private int $id;

    #[ORM\Column(type: "string", unique: true)]
    private string $uuid;

    #[ORM\Column(type: "string", unique: true)]
    private string $token;

    #[ORM\Column(type: "datetime")]
    private DateTime $expires;

    public function __construct(string $uuid, int $expiry_seconds)
    {
        $this->uuid = $uuid;
        $MD5_LEN = 32;
        $tokenLength = 10;
        $this->token = substr(md5(microtime()), rand(0, $MD5_LEN-$tokenLength-1), $tokenLength);
        $this->expires = new DateTime('now');
        $this->expires->add(new DateInterval("PT" . $expiry_seconds . "S"));
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
    public function getToken(): string
    {
        return $this->token;
    }

    public function getExpires(): DateTime
    {
        return $this->expires;
    }


}