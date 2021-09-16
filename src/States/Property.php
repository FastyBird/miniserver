<?php declare(strict_types = 1);

/**
 * Property.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:MiniServer!
 * @subpackage     States
 * @since          0.1.0
 *
 * @date           03.03.20
 */

namespace FastyBird\MiniServer\States;

use DateTimeImmutable;
use DateTimeInterface;
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

	/** @var string|null */
	private ?string $actual = null;

	/** @var string|null */
	private ?string $expected = null;

	/** @var bool */
	private bool $pending = false;

	/** @var string|null */
	private ?string $createdAt = null;

	/** @var string|null */
	private ?string $updatedAt = null;

	/**
	 * {@inheritDoc}
	 */
	public function getCreatedAt(): ?DateTimeInterface
	{
		return $this->createdAt !== null ? new DateTimeImmutable($this->createdAt) : null;
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
	public function getActualValue(): ?string
	{
		return $this->actual;
	}

	/**
	 * {@inheritDoc}
	 */
	public function setActualValue(?string $actual): void
	{
		$this->actual = $actual;
	}

	/**
	 * {@inheritDoc}
	 */
	public function setActual(?string $actual): void
	{
		$this->setActualValue($actual);
	}

	/**
	 * {@inheritDoc}
	 */
	public function getExpectedValue(): ?string
	{
		return $this->expected;
	}

	/**
	 * {@inheritDoc}
	 */
	public function setExpectedValue(?string $expected): void
	{
		$this->expected = $expected;
	}

	/**
	 * {@inheritDoc}
	 */
	public function setExpected(?string $expected): void
	{
		$this->setExpectedValue($expected);
	}

	/**
	 * {@inheritDoc}
	 */
	public function isPending(): bool
	{
		return $this->pending;
	}

	/**
	 * {@inheritDoc}
	 */
	public function setPending(bool $pending): void
	{
		$this->pending = $pending;
	}

	/**
	 * {@inheritDoc}
	 */
	public static function getCreateFields(): array
	{
		return [
			0           => 'id',
			'actual'    => null,
			'expected'  => null,
			'pending'   => false,
			'createdAt' => null,
			'updatedAt' => null,
		];
	}

	/**
	 * {@inheritDoc}
	 */
	public static function getUpdateFields(): array
	{
		return [
			'actual',
			'expected',
			'pending',
			'updatedAt',
		];
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return array_merge([
			'actual'     => $this->getActualValue(),
			'expected'   => $this->getExpectedValue(),
			'pending'    => $this->isPending(),
			'created_at' => $this->getCreatedAt() !== null ? $this->getCreatedAt()->format(DateTimeInterface::ATOM) : null,
			'updated_at' => $this->getUpdatedAt() !== null ? $this->getUpdatedAt()->format(DateTimeInterface::ATOM) : null,
		], parent::toArray());
	}

}
