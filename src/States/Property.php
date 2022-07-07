<?php declare(strict_types = 1);

/**
 * Property.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:MiniServer!
 * @subpackage     States
 * @since          0.2.0
 *
 * @date           03.03.20
 */

namespace FastyBird\MiniServer\States;

use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use Exception;
use FastyBird\RedisDbStoragePlugin\States as RedisDbStoragePluginStates;

/**
 * Property state
 *
 * @package        FastyBird:MiniServer!
 * @subpackage     States
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class Property extends RedisDbStoragePluginStates\State implements IProperty
{

	/** @var bool|float|int|string|null */
	private float|bool|int|string|null $actualValue = null;

	/** @var bool|float|int|string|null */
	private float|bool|int|string|null $expectedValue = null;

	/** @var bool */
	private bool|string|null $pending = null;

	/** @var bool */
	private bool|null $valid = null;

	/** @var string|null */
	private ?string $createdAt = null;

	/** @var string|null */
	private ?string $updatedAt = null;

	/**
	 * {@inheritDoc}
	 *
	 * @throws Exception
	 */
	public function getCreatedAt(): ?DateTimeInterface
	{
		return $this->createdAt !== null ? new DateTime($this->createdAt) : null;
	}

	/**
	 * {@inheritDoc}
	 */
	public function setCreatedAt(?string $createdAt): void
	{
		$this->createdAt = $createdAt;
	}

	/**
	 * {@inheritDoc}
	 *
	 * @throws Exception
	 */
	public function getUpdatedAt(): ?DateTimeInterface
	{
		return $this->updatedAt !== null ? new DateTimeImmutable($this->updatedAt) : null;
	}

	/**
	 * {@inheritDoc}
	 */
	public function setUpdatedAt(?string $updatedAt): void
	{
		$this->updatedAt = $updatedAt;
	}

	/**
	 * {@inheritDoc}
	 */
	public function getActualValue(): float|bool|int|string|null
	{
		return $this->actualValue;
	}

	/**
	 * {@inheritDoc}
	 */
	public function setActualValue(float|bool|int|string|null $actual): void
	{
		$this->actualValue = $actual;
	}

	/**
	 * {@inheritDoc}
	 */
	public function getExpectedValue(): float|bool|int|string|null
	{
		return $this->expectedValue;
	}

	/**
	 * {@inheritDoc}
	 */
	public function setExpectedValue(float|bool|int|string|null $expected): void
	{
		$this->expectedValue = $expected;
	}

	/**
	 * {@inheritDoc}
	 */
	public function isPending(): bool
	{
		return $this->valid !== null ? is_bool($this->valid) ? $this->valid : true : false;
	}

	/**
	 * {@inheritDoc}
	 */
	public function getPending(): bool|string|null
	{
		return $this->pending;
	}

	/**
	 * {@inheritDoc}
	 */
	public function setPending(bool|string|null $pending): void
	{
		$this->pending = $pending;
	}

	/**
	 * {@inheritDoc}
	 */
	public function isValid(): bool
	{
		return $this->valid ?? false;
	}

	/**
	 * {@inheritDoc}
	 */
	public function setValid(bool $valid): void
	{
		$this->valid = $valid;
	}

	/**
	 * {@inheritDoc}
	 */
	public static function getCreateFields(): array
	{
		return [
			0               => 'id',
			'actualValue'   => null,
			'expectedValue' => null,
			'pending'       => false,
			'valid'         => false,
			'createdAt'     => null,
			'updatedAt'     => null,
		];
	}

	/**
	 * {@inheritDoc}
	 */
	public static function getUpdateFields(): array
	{
		return [
			'actualValue',
			'expectedValue',
			'pending',
			'valid',
			'updatedAt',
		];
	}

	/**
	 * {@inheritDoc}
	 *
	 * @throws Exception
	 */
	public function toArray(): array
	{
		return array_merge([
			'actual_value'   => $this->getActualValue(),
			'expected_Value' => $this->getExpectedValue(),
			'pending'        => $this->isPending(),
			'valid'          => $this->isValid(),
			'created_at'     => $this->getCreatedAt() !== null ? $this->getCreatedAt()->format(DateTimeInterface::ATOM) : null,
			'updated_at'     => $this->getUpdatedAt() !== null ? $this->getUpdatedAt()->format(DateTimeInterface::ATOM) : null,
		], parent::toArray());
	}

}
