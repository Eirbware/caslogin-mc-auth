<?php

class Ban implements JsonSerializable
{
	public string $bannedUser;
	public string $banner;
	public ?string $reason;
	public DateTime $timestamp;
	public ?DateTime $expires;

	public function jsonSerialize(): array
	{
		$arr = (array)$this;
		$arr['timestamp'] = $this->timestamp->getTimestamp();
		$arr['expires'] = $this->expires?->getTimestamp();
		return $arr;
	}
}