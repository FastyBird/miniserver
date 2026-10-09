<?php declare(strict_types = 1);

namespace FastyBird\Plugin\RedisDb\Tests\Fixtures\Dummy;

use FastyBird\Core\Documents;
use Orisai\ObjectMapper;

// Routed so that the exchange handler tests can have the real routing document factory build it
#[Documents\Mapping\Document]
#[Documents\Mapping\RoutingMap(['fb.exchange.module.document.testing.routing.key'])]
final readonly class DummyDocument implements Documents\Document
{

	public function __construct(
		#[ObjectMapper\Rules\StringValue()]
		private string $attribute,
		#[ObjectMapper\Rules\IntValue()]
		private int $value,
	)
	{
	}

	public function getAttribute(): string
	{
		return $this->attribute;
	}

	public function getValue(): int
	{
		return $this->value;
	}

	public function toArray(): array
	{
		return [
			'attribute' => $this->getAttribute(),
			'value' => $this->getValue(),
		];
	}

}
