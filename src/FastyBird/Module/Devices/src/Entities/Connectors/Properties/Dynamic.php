<?php declare(strict_types = 1);

namespace FastyBird\Module\Devices\Entities\Connectors\Properties;

use DateTimeInterface;
use Doctrine\ORM\Mapping as ORM;
use FastyBird\Core\Values\Types\Payloads;
use FastyBird\Module\Devices\Exceptions;
use FastyBird\Module\Devices\Types;
use Override;
use function sprintf;

#[ORM\Entity]
class Dynamic extends Property
{

	public const string TYPE = Types\PropertyType::DYNAMIC->value;

	#[Override]
	public static function getType(): string
	{
		return self::TYPE;
	}

	/**
	 * @throws Exceptions\InvalidState
	 */
	#[Override]
	public function getValue(): bool|float|int|string|DateTimeInterface|Payloads\Payload|null
	{
		throw new Exceptions\InvalidState(
			sprintf('Reading value is not allowed for property type: %s', static::getType()),
		);
	}

	/**
	 * @throws Exceptions\InvalidState
	 */
	#[Override]
	public function setValue(bool|float|int|string|DateTimeInterface|Payloads\Payload|null $value): void
	{
		throw new Exceptions\InvalidState(
			sprintf(
				'Writing value is not allowed for property type: %s',
				static::getType(),
			),
		);
	}

}
