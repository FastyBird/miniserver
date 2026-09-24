<?php declare(strict_types = 1);

/**
 * Mapped.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:DevicesModule!
 * @subpackage     Entities
 * @since          1.0.0
 *
 * @date           02.04.22
 */

namespace FastyBird\Module\Devices\Entities\Devices\Properties;

use DateTimeInterface;
use Doctrine\ORM\Mapping as ORM;
use FastyBird\Core\Exceptions as ApplicationExceptions;
use FastyBird\Core\Values\Exceptions as ValuesExceptions;
use FastyBird\Core\Values\Types\Payloads;
use FastyBird\Core\Values\Utilities;
use FastyBird\Module\Devices\Entities;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\Types;
use Ramsey\Uuid;
use TypeError;
use ValueError;
use function array_merge;
use function assert;
use function sprintf;

#[ORM\Entity]
class Mapped extends Property
{

	public const TYPE = Types\PropertyType::MAPPED->value;

	public function __construct(
		Entities\Devices\Device $device,
		Entities\Devices\Properties\Property $parent,
		string $identifier,
		Uuid\UuidInterface|null $id = null,
	)
	{
		parent::__construct($device, $identifier, $id);

		$this->parent = $parent;
	}

	public static function getType(): string
	{
		return self::TYPE;
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 */
	public function getParent(): Dynamic|Variable
	{
		if ($this->parent === null) {
			throw new DevicesExceptions\InvalidState('Mapped property can\'t be without parent property');
		}

		assert($this->parent instanceof Dynamic || $this->parent instanceof Variable);

		return $this->parent;
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 */
	public function getChildren(): array
	{
		throw new DevicesExceptions\InvalidState(
			sprintf('Reading children is not allowed for property type: %s', static::getType()),
		);
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 */
	public function setChildren(array $children): void
	{
		throw new DevicesExceptions\InvalidState(
			sprintf('Assigning children is not allowed for property type: %s', static::getType()),
		);
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 */
	public function addChild(Property $child): void
	{
		throw new DevicesExceptions\InvalidState(
			sprintf('Adding child is not allowed for property type: %s', static::getType()),
		);
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 */
	public function removeChild(Property $child): void
	{
		throw new DevicesExceptions\InvalidState(
			sprintf('Removing child is not allowed for property type: %s', static::getType()),
		);
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 */
	public function setSettable(bool $settable): void
	{
		if (!$this->getParent() instanceof Dynamic) {
			throw new DevicesExceptions\InvalidState('Settable flag is allowed only for dynamic parent properties');
		}

		parent::setSettable($settable);
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 */
	public function isSettable(): bool
	{
		if (!$this->getParent() instanceof Dynamic) {
			throw new DevicesExceptions\InvalidState('Settable flag is allowed only for dynamic parent properties');
		}

		return parent::isSettable();
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 */
	public function setQueryable(bool $queryable): void
	{
		if (!$this->getParent() instanceof Dynamic) {
			throw new DevicesExceptions\InvalidState('Queryable flag is allowed only for dynamic parent properties');
		}

		parent::setQueryable($queryable);
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 */
	public function isQueryable(): bool
	{
		if (!$this->getParent() instanceof Dynamic) {
			throw new DevicesExceptions\InvalidState('Queryable flag is allowed only for dynamic parent properties');
		}

		return parent::isQueryable();
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	public function getDefault(): bool|float|int|string|DateTimeInterface|Payloads\Payload|null
	{
		try {
			return Utilities\Value::normalizeValue(
				Utilities\Value::transformDataType(
					Utilities\Value::flattenValue($this->getParent()->getDefault()),
					$this->getDataType(),
				),
				$this->getDataType(),
				$this->getFormat(),
			);
		} catch (ValuesExceptions\InvalidValue) {
			return null;
		}
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 */
	public function setDefault(
		bool|float|int|string|DateTimeInterface|Payloads\Payload|null $default,
	): void
	{
		throw new DevicesExceptions\InvalidState('Default value setter is allowed only for parent');
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	public function getValue(): bool|float|int|string|DateTimeInterface|Payloads\Payload|null
	{
		if (!$this->getParent() instanceof Variable) {
			throw new DevicesExceptions\InvalidState('Reading value is allowed only for variable parent properties');
		}

		try {
			return Utilities\Value::normalizeValue(
				Utilities\Value::transformDataType(
					Utilities\Value::flattenValue($this->getParent()->getValue()),
					$this->getDataType(),
				),
				$this->getDataType(),
				$this->getFormat(),
			);
		} catch (ValuesExceptions\InvalidValue) {
			return null;
		}
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 */
	public function setValue(bool|float|int|string|DateTimeInterface|Payloads\Payload|null $value): void
	{
		if (!$this->getParent() instanceof Variable) {
			throw new DevicesExceptions\InvalidState('Setting value is allowed only for variable parent properties');
		}

		throw new DevicesExceptions\InvalidState('Value setter is allowed only for parent');
	}

	/**
	 * {@inheritDoc}
	 *
	 * @throws DevicesExceptions\InvalidState
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 * @throws Uuid\Exception\InvalidArgumentException
	 */
	public function toArray(): array
	{
		if ($this->getParent() instanceof Entities\Devices\Properties\Variable) {
			return array_merge(parent::toArray(), [
				'parent' => $this->getParent()->getId()->toString(),

				'default' => Utilities\Value::flattenValue($this->getDefault()),
				'value' => Utilities\Value::flattenValue($this->getValue()),
			]);
		}

		return array_merge(parent::toArray(), [
			'parent' => $this->getParent()->getId()->toString(),

			'default' => Utilities\Value::flattenValue($this->getDefault()),
			'settable' => $this->isSettable(),
			'queryable' => $this->isQueryable(),
		]);
	}

}
