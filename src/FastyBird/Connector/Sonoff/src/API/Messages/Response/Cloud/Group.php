<?php declare(strict_types = 1);

namespace FastyBird\Connector\Sonoff\API\Messages\Response\Cloud;

use FastyBird\Connector\Sonoff\API;
use Nette\Utils;
use Orisai\ObjectMapper;
use function array_map;

/**
 * User home group entity
 */
final readonly class Group implements API\Messages\Message
{

	/**
	 * @param array<string> $denyFeatures
	 */
	public function __construct(
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		private string $id,
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		private string $name,
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		private string $mainDeviceId,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\ObjectValue(),
			new ObjectMapper\Rules\NullValue(),
		])]
		private API\Messages\Uiid\Uuid|null $state = null,
		#[ObjectMapper\Rules\ArrayOf(
			new ObjectMapper\Rules\StringValue(notEmpty: true),
		)]
		private array $denyFeatures = [],
	)
	{
	}

	public function getId(): string
	{
		return $this->id;
	}

	public function getName(): string
	{
		return $this->name;
	}

	public function getMainDeviceId(): string
	{
		return $this->mainDeviceId;
	}

	public function getState(): API\Messages\Uiid\Uuid|null
	{
		return $this->state;
	}

	/**
	 * @return array<string>
	 */
	public function getDenyFeatures(): array
	{
		return array_map(static fn (string $item): string => Utils\Strings::lower($item), $this->denyFeatures);
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return [
			'id' => $this->getId(),
			'name' => $this->getName(),
			'main_device_id' => $this->getMainDeviceId(),
			'state' => $this->getState()?->toArray(),
			'deny_features' => $this->getDenyFeatures(),
		];
	}

}
