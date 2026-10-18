<?php declare(strict_types = 1);

namespace FastyBird\Module\Devices\Tests\Fixtures\Dummy;

use CuyZ\Valinor\Mapper\Configurator;
use DateTimeInterface;
use FastyBird\Core\Documents as CoreDocuments;
use FastyBird\Core\Values\Types\Payloads;
use FastyBird\Module\Devices\Documents as DevicesDocuments;
use Override;
use Ramsey\Uuid;

/**
 * DummyParityOrisaiDocument's three fields as Valinor reads them: the format, the value transformer and the value of a
 * state, with the types and the converters the Devices documents give them
 */
final readonly class DummyParityValinorDocument implements CoreDocuments\Document
{

	/**
	 * @param string|array<int, array<int, array<int, bool|float|int|string|null>|bool|float|int|string|null>|bool|float|int|string|null>|null $format
	 */
	public function __construct(
		#[DevicesDocuments\Mapping\MapFormat]
		private string|array|null $format = null,
		#[DevicesDocuments\Mapping\MapValueTransformer]
		#[Configurator\MapFromKey('value_transformer')]
		private Uuid\UuidInterface|string|null $valueTransformer = null,
		#[DevicesDocuments\Mapping\MapStateValue]
		private bool|float|int|string|DateTimeInterface|Payloads\Payload|null $value = null,
	)
	{
	}

	#[Override]
	public function toArray(): array
	{
		return ['format' => $this->format, 'value_transformer' => $this->valueTransformer, 'value' => $this->value];
	}

}
