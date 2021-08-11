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
use FastyBird\DevicesModule\States as DevicesModuleStates;
use FastyBird\RedisDbStoragePlugin\States as RedisDbStoragePluginStates;

/**
 * Property state
 *
 * @package        FastyBird:MiniServer!
 * @subpackage     States
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class Property extends RedisDbStoragePluginStates\State implements IProperty, DevicesModuleStates\IProperty
{

	/** @var string|null */
	private ?string $actual = null;

	/** @var string|null */
	private ?string $expected = null;

	/** @var bool */
	private bool $pending = false;

	/** @var string|null */
	private ?string $created = null;

	/** @var string|null */
	private ?string $updated = null;

	/**
	 * {@inheritDoc}
	 */
	public function getCreated(): ?DateTimeInterface
	{
		return $this->created !== null ? new DateTimeImmutable($this->created) : null;
	}

	/**
	 * {@inheritDoc}
	 */
	public function setCreated(?string $created): void
	{
		$this->created = $created;
	}

	/**
	 * {@inheritDoc}
	 */
	public function getUpdated(): ?DateTimeInterface
	{
		return $this->updated !== null ? new DateTimeImmutable($this->updated) : null;
	}

	/**
	 * {@inheritDoc}
	 */
	public function setUpdated(?string $updated): void
	{
		$this->updated = $updated;
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
			0          => 'id',
			'actual'   => null,
			'expected' => null,
			'pending'  => false,
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
		];
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return array_merge([
			'actual'   => $this->getActualValue(),
			'expected' => $this->getExpectedValue(),
			'pending'  => $this->isPending(),
		], parent::toArray());
	}

}
