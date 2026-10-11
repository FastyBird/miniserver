<?php declare(strict_types = 1);

namespace FastyBird\Connector\Tuya\API\Messages\Response;

use FastyBird\Connector\Tuya\API;
use Orisai\ObjectMapper;
use Override;

/**
 * OpenAPI user device child message
 */
final readonly class UserDeviceChild implements API\Messages\Message
{

	public function __construct(
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		private string $id,
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		#[ObjectMapper\Modifiers\FieldName('node_id')]
		private string $nodeId,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\StringValue(notEmpty: true),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		private string|null $name,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\StringValue(notEmpty: true),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		private string|null $icon,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\StringValue(notEmpty: true),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		#[ObjectMapper\Modifiers\FieldName('product_id')]
		private string|null $productId,
		#[ObjectMapper\Rules\BoolValue()]
		private bool $online = false,
	)
	{
	}

	public function getId(): string
	{
		return $this->id;
	}

	public function getNodeId(): string
	{
		return $this->nodeId;
	}

	public function getName(): string|null
	{
		return $this->name !== '' ? $this->name : null;
	}

	public function getProductId(): string|null
	{
		return $this->productId !== '' ? $this->productId : null;
	}

	public function getIcon(): string|null
	{
		return $this->icon !== '' ? $this->icon : null;
	}

	public function isOnline(): bool
	{
		return $this->online;
	}

	/**
	 * {@inheritDoc}
	 */
	#[Override]
	public function toArray(): array
	{
		return [
			'id' => $this->getId(),
			'node_id' => $this->getNodeId(),
			'name' => $this->getName(),
			'product_id' => $this->getProductId(),
			'icon' => $this->getIcon(),
			'online' => $this->isOnline(),
		];
	}

}
