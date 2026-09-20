<?php declare(strict_types = 1);

/**
 * SyncDevicesEventPayload.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     API
 * @since          1.0.0
 *
 * @date           09.07.23
 */

namespace FastyBird\Connector\NsPanel\API\Messages\Request;

use FastyBird\Connector\NsPanel\API;
use FastyBird\Connector\NsPanel\Types;
use FastyBird\Core\Persistence\Application\Rules as ApplicationObjectMapper;
use Orisai\ObjectMapper;
use Ramsey\Uuid;
use stdClass;
use function array_map;
use function is_array;

/**
 * Synchronise third-party devices with NS Panel event payload endpoint request definition
 *
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     API
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final readonly class SyncDevicesEventPayloadEndpoint implements API\Messages\Message
{

	/**
	 * @param array<API\Messages\Capability> $capabilities
	 * @param array<string, string|array<string, string>> $tags
	 */
	public function __construct(
		#[ApplicationObjectMapper\UuidValue()]
		#[ObjectMapper\Modifiers\FieldName('third_serial_number')]
		private Uuid\UuidInterface $thirdSerialNumber,
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		private string $name,
		#[ObjectMapper\Rules\BackedEnumValue(class: Types\Category::class)]
		#[ObjectMapper\Modifiers\FieldName('display_category')]
		private Types\Category $displayCategory,
		#[ObjectMapper\Rules\ArrayOf(
			new ObjectMapper\Rules\MappedObjectValue(API\Messages\Capability::class),
		)]
		private array $capabilities,
		#[ObjectMapper\Rules\MappedObjectValue(API\Messages\State::class)]
		private API\Messages\State $state,
		#[ObjectMapper\Rules\ArrayOf(
			item: new ObjectMapper\Rules\AnyOf([
				new ObjectMapper\Rules\StringValue(),
				new ObjectMapper\Rules\ArrayOf(
					item: new ObjectMapper\Rules\StringValue(),
					key: new ObjectMapper\Rules\AnyOf([
						new ObjectMapper\Rules\StringValue(),
						new ObjectMapper\Rules\IntValue(),
					]),
				),
			]),
			key: new ObjectMapper\Rules\StringValue(),
		)]
		private array $tags,
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		private string $manufacturer,
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		private string $model,
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		#[ObjectMapper\Modifiers\FieldName('firmware_version')]
		private string $firmwareVersion,
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		#[ObjectMapper\Modifiers\FieldName('service_address')]
		private string $serviceAddress,
		#[ObjectMapper\Rules\BoolValue()]
		private bool $online = false,
	)
	{
	}

	public function getThirdSerialNumber(): Uuid\UuidInterface
	{
		return $this->thirdSerialNumber;
	}

	public function getName(): string
	{
		return $this->name;
	}

	public function getDisplayCategory(): Types\Category
	{
		return $this->displayCategory;
	}

	/**
	 * @return array<API\Messages\Capability>
	 */
	public function getCapabilities(): array
	{
		return $this->capabilities;
	}

	/**
	 * @return array<API\Messages\States\State>
	 */
	public function getState(): array
	{
		return $this->state->getStates();
	}

	/**
	 * @return array<string, string|array<string, string>>
	 */
	public function getTags(): array
	{
		return $this->tags;
	}

	public function getManufacturer(): string
	{
		return $this->manufacturer;
	}

	public function getModel(): string
	{
		return $this->model;
	}

	public function getFirmwareVersion(): string
	{
		return $this->firmwareVersion;
	}

	public function getServiceAddress(): string
	{
		return $this->serviceAddress;
	}

	public function isOnline(): bool
	{
		return $this->online;
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return [
			'third_serial_number' => $this->getThirdSerialNumber()->toString(),
			'name' => $this->getName(),
			'display_category' => $this->getDisplayCategory()->value,
			'capabilities' => array_map(
				static fn (API\Messages\Capability $capability): array => $capability->toArray(),
				$this->getCapabilities(),
			),
			'state' => $this->state->toArray(),
			'tags' => $this->getTags(),
			'manufacturer' => $this->getManufacturer(),
			'model' => $this->getModel(),
			'firmware_version' => $this->getFirmwareVersion(),
			'service_address' => $this->getServiceAddress(),
			'online' => $this->isOnline(),
		];
	}

	public function toJson(): object
	{
		$tags = new stdClass();

		foreach ($this->getTags() as $name => $value) {
			if (is_array($value)) {
				$tags->{$name} = new stdClass();

				foreach ($value as $subName => $subValue) {
					$tags->{$name}->{$subName} = $subValue;
				}
			} else {
				$tags->{$name} = $value;
			}
		}

		$json = new stdClass();
		$json->third_serial_number = $this->getThirdSerialNumber();
		$json->name = $this->getName();
		$json->display_category = $this->getDisplayCategory()->value;
		$json->capabilities = array_map(
			static fn (API\Messages\Capability $capability): object => $capability->toJson(),
			$this->getCapabilities(),
		);
		$json->state = $this->state->toJson();
		$json->tags = $tags;
		$json->manufacturer = $this->getManufacturer();
		$json->model = $this->getModel();
		$json->firmware_version = $this->getFirmwareVersion();
		$json->service_address = $this->getServiceAddress();
		$json->online = $this->isOnline();

		return $json;
	}

}
