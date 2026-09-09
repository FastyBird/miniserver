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
use FastyBird\Core\Tools\Exceptions as ToolsExceptions;
use FastyBird\Core\Tools\Utilities as ToolsUtilities;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Devices\Entities;
use FastyBird\Module\Devices\Exceptions;
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
	 * @throws Exceptions\InvalidState
	 */
	public function getParent(): Dynamic|Variable
	{
		if ($this->parent === null) {
			throw new Exceptions\InvalidState('Mapped property can\'t be without parent property');
		}

		assert($this->parent instanceof Dynamic || $this->parent instanceof Variable);

		return $this->parent;
	}

	/**
	 * @throws Exceptions\InvalidState
	 */
	public function getChildren(): array
	{
		throw new Exceptions\InvalidState(
			sprintf('Reading children is not allowed for property type: %s', static::getType()),
		);
	}

	/**
	 * @throws Exceptions\InvalidState
	 */
	public function setChildren(array $children): void
	{
		throw new Exceptions\InvalidState(
			sprintf('Assigning children is not allowed for property type: %s', static::getType()),
		);
	}

	/**
	 * @throws Exceptions\InvalidState
	 */
	public function addChild(Property $child): void
	{
		throw new Exceptions\InvalidState(
			sprintf('Adding child is not allowed for property type: %s', static::getType()),
		);
	}

	/**
	 * @throws Exceptions\InvalidState
	 */
	public function removeChild(Property $child): void
	{
		throw new Exceptions\InvalidState(
			sprintf('Removing child is not allowed for property type: %s', static::getType()),
		);
	}

	/**
	 * @throws Exceptions\InvalidState
	 */
	public function setSettable(bool $settable): void
	{
		if (!$this->getParent() instanceof Dynamic) {
			throw new Exceptions\InvalidState('Settable flag is allowed only for dynamic parent properties');
		}

		parent::setSettable($settable);
	}

	/**
	 * @throws Exceptions\InvalidState
	 */
	public function isSettable(): bool
	{
		if (!$this->getParent() instanceof Dynamic) {
			throw new Exceptions\InvalidState('Settable flag is allowed only for dynamic parent properties');
		}

		return parent::isSettable();
	}

	/**
	 * @throws Exceptions\InvalidState
	 */
	public function setQueryable(bool $queryable): void
	{
		if (!$this->getParent() instanceof Dynamic) {
			throw new Exceptions\InvalidState('Queryable flag is allowed only for dynamic parent properties');
		}

		parent::setQueryable($queryable);
	}

	/**
	 * @throws Exceptions\InvalidState
	 */
	public function isQueryable(): bool
	{
		if (!$this->getParent() instanceof Dynamic) {
			throw new Exceptions\InvalidState('Queryable flag is allowed only for dynamic parent properties');
		}

		return parent::isQueryable();
	}

	/**
	 * @throws Exceptions\InvalidState
	 * @throws ToolsExceptions\InvalidArgument
	 * @throws ToolsExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	public function getDefault(): bool|float|int|string|DateTimeInterface|MetadataTypes\Payloads\Payload|null
	{
		try {
			return ToolsUtilities\Value::normalizeValue(
				ToolsUtilities\Value::transformDataType(
					ToolsUtilities\Value::flattenValue($this->getParent()->getDefault()),
					$this->getDataType(),
				),
				$this->getDataType(),
				$this->getFormat(),
			);
		} catch (ToolsExceptions\InvalidValue) {
			return null;
		}
	}

	/**
	 * @throws Exceptions\InvalidState
	 */
	public function setDefault(
		bool|float|int|string|DateTimeInterface|MetadataTypes\Payloads\Payload|null $default,
	): void
	{
		throw new Exceptions\InvalidState('Default value setter is allowed only for parent');
	}

	/**
	 * @throws Exceptions\InvalidState
	 * @throws ToolsExceptions\InvalidArgument
	 * @throws ToolsExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	public function getValue(): bool|float|int|string|DateTimeInterface|MetadataTypes\Payloads\Payload|null
	{
		if (!$this->getParent() instanceof Variable) {
			throw new Exceptions\InvalidState('Reading value is allowed only for variable parent properties');
		}

		try {
			return ToolsUtilities\Value::normalizeValue(
				ToolsUtilities\Value::transformDataType(
					ToolsUtilities\Value::flattenValue($this->getParent()->getValue()),
					$this->getDataType(),
				),
				$this->getDataType(),
				$this->getFormat(),
			);
		} catch (ToolsExceptions\InvalidValue) {
			return null;
		}
	}

	/**
	 * @throws Exceptions\InvalidState
	 */
	public function setValue(bool|float|int|string|DateTimeInterface|MetadataTypes\Payloads\Payload|null $value): void
	{
		if (!$this->getParent() instanceof Variable) {
			throw new Exceptions\InvalidState('Setting value is allowed only for variable parent properties');
		}

		throw new Exceptions\InvalidState('Value setter is allowed only for parent');
	}

	/**
	 * {@inheritDoc}
	 *
	 * @throws Exceptions\InvalidState
	 * @throws ToolsExceptions\InvalidArgument
	 * @throws ToolsExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 * @throws Uuid\Exception\InvalidArgumentException
	 */
	public function toArray(): array
	{
		if ($this->getParent() instanceof Entities\Devices\Properties\Variable) {
			return array_merge(parent::toArray(), [
				'parent' => $this->getParent()->getId()->toString(),

				'default' => ToolsUtilities\Value::flattenValue($this->getDefault()),
				'value' => ToolsUtilities\Value::flattenValue($this->getValue()),
			]);
		}

		return array_merge(parent::toArray(), [
			'parent' => $this->getParent()->getId()->toString(),

			'default' => ToolsUtilities\Value::flattenValue($this->getDefault()),
			'settable' => $this->isSettable(),
			'queryable' => $this->isQueryable(),
		]);
	}

}
