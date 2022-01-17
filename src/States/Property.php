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
	private ?string $actualValue = null;

	/** @var string|null */
	private ?string $expectedValue = null;

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
		var_dump($this->createdAt);
		var_dump((new DateTimeImmutable())->format(DateTimeInterface::ATOM));
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
		return $this->actualValue;
	}

	/**
	 * {@inheritDoc}
	 */
	public function setActual(?string $actual): void
	{
		$this->actualValue = $actual;
	}

	/**
	 * {@inheritDoc}
	 */
	public function setActualValue(?string $actual): void
	{
		$this->setActual($actual);
	}

	/**
	 * {@inheritDoc}
	 */
	public function getExpectedValue(): ?string
	{
		return $this->expectedValue;
	}

	/**
	 * {@inheritDoc}
	 */
	public function setExpected(?string $expected): void
	{
		$this->expectedValue = $expected;
	}

	/**
	 * {@inheritDoc}
	 */
	public function setExpectedValue(?string $expected): void
	{
		$this->setExpected($expected);
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
			0               => 'id',
			'actualValue'   => null,
			'expectedValue' => null,
			'pending'       => false,
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
			'updatedAt',
		];
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return array_merge([
			'actualValue'   => $this->getActualValue(),
			'expectedValue' => $this->getExpectedValue(),
			'pending'       => $this->isPending(),
			'createdAt'     => $this->getCreatedAt() !== null ? $this->getCreatedAt()->format(DateTimeInterface::ATOM) : null,
			'updatedAt'     => $this->getUpdatedAt() !== null ? $this->getUpdatedAt()->format(DateTimeInterface::ATOM) : null,
		], parent::toArray());
	}

}
