<?php declare(strict_types = 1);

namespace FastyBird\Connector\Shelly\API\Messages\Response\Gen1;

use FastyBird\Connector\Shelly\API;
use Orisai\ObjectMapper;

/**
 * Generation 1 device information message
 */
final readonly class GetDeviceInformation implements API\Messages\Message
{

	public function __construct(
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		private string $type,
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		private string $mac,
		#[ObjectMapper\Rules\BoolValue(castBoolLike: true)]
		private bool $auth,
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		private string $fw,
		#[ObjectMapper\Rules\IntValue(unsigned: true)]
		private int $longid,
	)
	{
	}

	public function getModel(): string
	{
		return $this->type;
	}

	public function getMacAddress(): string
	{
		return $this->mac;
	}

	public function hasAuthentication(): bool
	{
		return $this->auth;
	}

	public function getFirmware(): string
	{
		return $this->fw;
	}

	public function getLongIdentifier(): int
	{
		return $this->longid;
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return [
			'model' => $this->getModel(),
			'mac_address' => $this->getMacAddress(),
			'has_authentication' => $this->hasAuthentication(),
			'firmware' => $this->getFirmware(),
			'long_identifier' => $this->getLongIdentifier(),
		];
	}

}
