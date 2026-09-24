<?php declare(strict_types = 1);

/**
 * StoreCloudDevice.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:TuyaConnector!
 * @subpackage     Queue
 * @since          1.0.0
 *
 * @date           27.08.22
 */

namespace FastyBird\Connector\Tuya\Queue\Messages;

use FastyBird\Core\Persistence\Rules;
use Orisai\ObjectMapper;
use Ramsey\Uuid;
use function array_map;

/**
 * Discovered cloud device message
 *
 * @package        FastyBird:TuyaConnector!
 * @subpackage     Queue
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final readonly class StoreCloudDevice implements Message
{

	/**
	 * @param array<CloudDeviceDataPoint> $dataPoints
	 */
	public function __construct(
		#[Rules\UuidValue()]
		private Uuid\UuidInterface $connector,
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		private string $id,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\StringValue(notEmpty: true),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		#[ObjectMapper\Modifiers\FieldName('ip_address')]
		private string|null $ipAddress,
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		#[ObjectMapper\Modifiers\FieldName('local_key')]
		private string $localKey,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\StringValue(notEmpty: true),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		private string|null $name,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\StringValue(notEmpty: true),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		private string|null $model,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\StringValue(notEmpty: true),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		private string|null $icon,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\StringValue(notEmpty: true),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		private string|null $category,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\StringValue(notEmpty: true),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		#[ObjectMapper\Modifiers\FieldName('product_id')]
		private string|null $productId,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\StringValue(notEmpty: true),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		#[ObjectMapper\Modifiers\FieldName('product_name')]
		private string|null $productName,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\StringValue(notEmpty: true),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		private string|null $latitude,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\StringValue(notEmpty: true),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		private string|null $longitude,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\StringValue(notEmpty: true),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		private string|null $sn,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\StringValue(notEmpty: true),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		private string|null $mac,
		#[ObjectMapper\Rules\ArrayOf(
			new ObjectMapper\Rules\MappedObjectValue(CloudDeviceDataPoint::class),
		)]
		#[ObjectMapper\Modifiers\FieldName('data_points')]
		private array $dataPoints,
	)
	{
	}

	public function getConnector(): Uuid\UuidInterface
	{
		return $this->connector;
	}

	public function getId(): string
	{
		return $this->id;
	}

	public function getLocalKey(): string
	{
		return $this->localKey;
	}

	public function getIpAddress(): string|null
	{
		return $this->ipAddress;
	}

	public function getName(): string|null
	{
		return $this->name;
	}

	public function getModel(): string|null
	{
		return $this->model;
	}

	public function getIcon(): string|null
	{
		return $this->icon;
	}

	public function getCategory(): string|null
	{
		return $this->category;
	}

	public function getLatitude(): string|null
	{
		return $this->latitude;
	}

	public function getLongitude(): string|null
	{
		return $this->longitude;
	}

	public function getProductId(): string|null
	{
		return $this->productId;
	}

	public function getProductName(): string|null
	{
		return $this->productName;
	}

	public function getSn(): string|null
	{
		return $this->sn;
	}

	public function getMac(): string|null
	{
		return $this->mac;
	}

	/**
	 * @return array<CloudDeviceDataPoint>
	 */
	public function getDataPoints(): array
	{
		return $this->dataPoints;
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return [
			'connector' => $this->getConnector()->toString(),
			'id' => $this->getId(),
			'local_key' => $this->getLocalKey(),
			'ip_address' => $this->getIpAddress(),
			'name' => $this->getName(),
			'model' => $this->getModel(),
			'icon' => $this->getIcon(),
			'category' => $this->getCategory(),
			'lat' => $this->getLatitude(),
			'lon' => $this->getLongitude(),
			'product_id' => $this->getProductId(),
			'product_name' => $this->getProductName(),
			'sn' => $this->getSn(),
			'mac' => $this->getMac(),
			'data_points' => array_map(
				static fn (CloudDeviceDataPoint $item): array => $item->toArray(),
				$this->getDataPoints(),
			),
		];
	}

}
