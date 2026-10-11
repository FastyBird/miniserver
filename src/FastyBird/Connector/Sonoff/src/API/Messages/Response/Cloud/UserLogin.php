<?php declare(strict_types = 1);

namespace FastyBird\Connector\Sonoff\API\Messages\Response\Cloud;

use FastyBird\Connector\Sonoff\API;
use FastyBird\Connector\Sonoff\Types;
use Orisai\ObjectMapper;
use Override;

/**
 * User logged in entity
 */
final readonly class UserLogin implements API\Messages\Message
{

	public function __construct(
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		#[ObjectMapper\Modifiers\FieldName('at')]
		private string $accessToken,
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		#[ObjectMapper\Modifiers\FieldName('rt')]
		private string $refreshToken,
		#[ObjectMapper\Rules\BackedEnumValue(class: Types\Region::class)]
		private Types\Region $region,
		#[ObjectMapper\Rules\MappedObjectValue(User::class)]
		private User $user,
	)
	{
	}

	public function getAccessToken(): string
	{
		return $this->accessToken;
	}

	public function getRefreshToken(): string
	{
		return $this->refreshToken;
	}

	public function getRegion(): Types\Region
	{
		return $this->region;
	}

	public function getUser(): User
	{
		return $this->user;
	}

	/**
	 * {@inheritDoc}
	 */
	#[Override]
	public function toArray(): array
	{
		return [
			'access_token' => $this->getAccessToken(),
			'refresh_token' => $this->getRefreshToken(),
			'region' => $this->getRegion()->value,
			'user' => $this->getUser()->toArray(),
		];
	}

}
