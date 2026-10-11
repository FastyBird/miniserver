<?php declare(strict_types = 1);

namespace FastyBird\Connector\Tuya\API\Messages\Response;

use FastyBird\Connector\Tuya\API;
use Orisai\ObjectMapper;
use Override;
use function array_map;

/**
 * OpenAPI user device detail message
 */
final readonly class UserDevice implements API\Messages\Message
{

	/**
	 * @param array<UserDeviceDataPointState> $status
	 */
	public function __construct(
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		private string $id,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\StringValue(notEmpty: true),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		private string|null $name,
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		private string $uid,
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		#[ObjectMapper\Modifiers\FieldName('local_key')]
		private string $localKey,
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
		#[ObjectMapper\Rules\BoolValue()]
		private bool $sub,
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		private string $uuid,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\StringValue(notEmpty: true),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		#[ObjectMapper\Modifiers\FieldName('owner_id')]
		private string|null $ownerId,
		#[ObjectMapper\Rules\BoolValue()]
		private bool $online,
		#[ObjectMapper\Rules\ArrayOf(
			new ObjectMapper\Rules\MappedObjectValue(UserDeviceDataPointState::class),
		)]
		private array $status,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\IntValue(unsigned: true),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		#[ObjectMapper\Modifiers\FieldName('active_time')]
		private int|null $activeTime,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\IntValue(unsigned: true),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		#[ObjectMapper\Modifiers\FieldName('create_time')]
		private int|null $createTime,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\IntValue(unsigned: true),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		#[ObjectMapper\Modifiers\FieldName('update_time')]
		private int|null $updateTime,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\IntValue(unsigned: true),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		#[ObjectMapper\Modifiers\FieldName('biz_type')]
		private int|null $bizType,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\StringValue(notEmpty: true),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		private string|null $icon,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\StringValue(notEmpty: true),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		private string|null $ip,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\StringValue(notEmpty: true),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		#[ObjectMapper\Modifiers\FieldName('time_zone')]
		private string|null $timeZone,
	)
	{
	}

	public function getId(): string
	{
		return $this->id;
	}

	public function getName(): string|null
	{
		return $this->name !== '' ? $this->name : null;
	}

	public function getUid(): string
	{
		return $this->uid;
	}

	public function getLocalKey(): string
	{
		return $this->localKey;
	}

	public function getCategory(): string|null
	{
		return $this->category !== '' ? $this->category : null;
	}

	public function getProductId(): string|null
	{
		return $this->productId !== '' ? $this->productId : null;
	}

	public function getProductName(): string|null
	{
		return $this->productName !== '' ? $this->productName : null;
	}

	public function getSub(): bool
	{
		return $this->sub;
	}

	public function getUuid(): string
	{
		return $this->uuid;
	}

	public function getOwnerId(): string|null
	{
		return $this->ownerId !== '' ? $this->ownerId : null;
	}

	public function isOnline(): bool
	{
		return $this->online;
	}

	/**
	 * @return array<UserDeviceDataPointState>
	 */
	public function getStatus(): array
	{
		return $this->status;
	}

	public function getActiveTime(): int|null
	{
		return $this->activeTime;
	}

	public function getCreateTime(): int|null
	{
		return $this->createTime;
	}

	public function getUpdateTime(): int|null
	{
		return $this->updateTime;
	}

	public function getBizType(): int|null
	{
		return $this->bizType;
	}

	public function getIcon(): string|null
	{
		return $this->icon !== '' ? $this->icon : null;
	}

	public function getIp(): string|null
	{
		return $this->ip !== '' ? $this->ip : null;
	}

	public function getTimeZone(): string|null
	{
		return $this->timeZone !== '' ? $this->timeZone : null;
	}

	/**
	 * {@inheritDoc}
	 */
	#[Override]
	public function toArray(): array
	{
		return [
			'id' => $this->getId(),
			'name' => $this->getName(),
			'uid' => $this->getUid(),
			'local_key' => $this->getLocalKey(),
			'category' => $this->getCategory(),
			'product_id' => $this->getProductId(),
			'product_name' => $this->getProductName(),
			'sub' => $this->getSub(),
			'uuid' => $this->getUuid(),
			'owner_id' => $this->getOwnerId(),
			'online' => $this->isOnline(),
			'status' => array_map(
				static fn (UserDeviceDataPointState $item): array => $item->toArray(),
				$this->getStatus(),
			),
			'active_time' => $this->getActiveTime(),
			'create_time' => $this->getCreateTime(),
			'update_time' => $this->getUpdateTime(),
			'biz_type' => $this->getBizType(),
			'icon' => $this->getIcon(),
			'ip' => $this->getIp(),
			'time_zone' => $this->getTimeZone(),
		];
	}

}
