<?php declare(strict_types = 1);

namespace FastyBird\Connector\Shelly\API\Messages\Response\Gen2;

use FastyBird\Connector\Shelly\API;
use Orisai\ObjectMapper;
use Override;

/**
 * Generation 2 device information message
 */
final readonly class GetDeviceInformation implements API\Messages\Message
{

	public function __construct(
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		private string $id,
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		private string $mac,
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		private string $model,
		#[ObjectMapper\Rules\IntValue(unsigned: true)]
		private int $gen,
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		#[ObjectMapper\Modifiers\FieldName('fw_id')]
		private string $fwId,
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		private string $ver,
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		private string $app,
		#[ObjectMapper\Rules\BoolValue()]
		#[ObjectMapper\Modifiers\FieldName('auth_en')]
		private bool $authEn,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\StringValue(notEmpty: true),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		#[ObjectMapper\Modifiers\FieldName('auth_domain')]
		private string|null $authDomain,
	)
	{
	}

	public function getId(): string
	{
		return $this->id;
	}

	public function getMacAddress(): string
	{
		return $this->mac;
	}

	public function getModel(): string
	{
		return $this->model;
	}

	public function getGeneration(): int
	{
		return $this->gen;
	}

	public function getFirmware(): string
	{
		return $this->fwId;
	}

	public function getVersion(): string
	{
		return $this->ver;
	}

	public function getApplication(): string
	{
		return $this->app;
	}

	public function hasAuthentication(): bool
	{
		return $this->authEn;
	}

	public function getAuthenticationDomain(): string|null
	{
		return $this->authDomain;
	}

	/**
	 * {@inheritDoc}
	 */
	#[Override]
	public function toArray(): array
	{
		return [
			'id' => $this->getId(),
			'mac_address' => $this->getMacAddress(),
			'model' => $this->getModel(),
			'generation' => $this->getGeneration(),
			'firmware' => $this->getFirmware(),
			'version' => $this->getVersion(),
			'application' => $this->getApplication(),
			'authentication' => $this->hasAuthentication(),
			'authentication_domain' => $this->getAuthenticationDomain(),
		];
	}

}
