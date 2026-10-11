<?php declare(strict_types = 1);

namespace FastyBird\Connector\Shelly\ValueObjects;

use Orisai\ObjectMapper;
use function strtolower;

/**
 * mDNS search result
 */
final readonly class MdnsResult implements ObjectMapper\MappedObject
{

	/**
	 * @param array<string, string|null> $data
	 */
	public function __construct(
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		private string $address,
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		private string $name,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\StringValue(notEmpty: true),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		private string|null $domain,
		#[ObjectMapper\Rules\ArrayOf(
			new ObjectMapper\Rules\MixedValue(),
			new ObjectMapper\Rules\StringValue(notEmpty: true),
		)]
		private array $data,
	)
	{
	}

	public function getAddress(): string
	{
		return $this->address;
	}

	public function getName(): string
	{
		return $this->name;
	}

	public function getDomain(): string|null
	{
		return $this->domain;
	}

	/**
	 * @return array<string, string|null>
	 */
	public function getData(): array
	{
		return $this->data;
	}

	/**
	 * @return array<string, string|array<string, string|int|float|null>|null>
	 */
	public function __serialize(): array
	{
		return [
			'address' => $this->getAddress(),
			'name' => strtolower($this->getName()),
			'domain' => $this->getDomain(),
			'data' => $this->getData(),
		];
	}

}
