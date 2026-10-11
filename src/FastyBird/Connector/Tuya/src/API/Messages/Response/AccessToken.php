<?php declare(strict_types = 1);

namespace FastyBird\Connector\Tuya\API\Messages\Response;

use DateTimeInterface;
use FastyBird\Connector\Tuya\API;
use Orisai\ObjectMapper;
use function intval;

/**
 * OpenAPI access token message
 */
final readonly class AccessToken implements API\Messages\Message
{

	public function __construct(
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		private string $uid,
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		#[ObjectMapper\Modifiers\FieldName('access_token')]
		private string $accessToken,
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		#[ObjectMapper\Modifiers\FieldName('refresh_token')]
		private string $refreshToken,
		#[ObjectMapper\Rules\IntValue(unsigned: true)]
		#[ObjectMapper\Modifiers\FieldName('expire_time')]
		private int $expireTime,
	)
	{
	}

	public function getUid(): string
	{
		return $this->uid;
	}

	public function getAccessToken(): string
	{
		return $this->accessToken;
	}

	public function getRefreshToken(): string
	{
		return $this->refreshToken;
	}

	public function getExpireTime(): int
	{
		return $this->expireTime;
	}

	public function isExpired(DateTimeInterface $now): bool
	{
		return !(($this->getExpireTime() - 60 * 1_000) > intval($now->format('Uv')));
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return [
			'uid' => $this->getUid(),
			'access_token' => $this->getAccessToken(),
			'refresh_token' => $this->getRefreshToken(),
			'expire_time' => $this->getExpireTime(),
		];
	}

}
