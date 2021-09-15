<?php declare(strict_types = 1);

/**
 * TriggerItem.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:MiniServer!
 * @subpackage     States
 * @since          0.1.0
 *
 * @date           15.09.21
 */

namespace FastyBird\MiniServer\States;

use DateTimeImmutable;
use DateTimeInterface;
use FastyBird\RedisDbStoragePlugin\States as RedisDbStoragePluginStates;

/**
 * Trigger item state
 *
 * @package        FastyBird:MiniServer!
 * @subpackage     States
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class TriggerItem extends RedisDbStoragePluginStates\State implements ITriggerItem
{

	/** @var bool */
	private bool $validationResult = false;

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
	public function getValidationResult(): bool
	{
		return $this->validationResult;
	}

	/**
	 * {@inheritDoc}
	 */
	public function setValidationResult(bool $result): void
	{
		$this->validationResult = $result;
	}

	/**
	 * {@inheritDoc}
	 */
	public static function getCreateFields(): array
	{
		return [
			0                   => 'id',
			'validation_result' => false,
			'created_at'        => null,
			'updated_at'        => null,
		];
	}

	/**
	 * {@inheritDoc}
	 */
	public static function getUpdateFields(): array
	{
		return [
			'validation_result',
			'updated_at',
		];
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return array_merge([
			'validation_result' => $this->getValidationResult(),
			'created_at'        => $this->getCreated() !== null ? $this->getCreated()->format(DateTimeInterface::ATOM) : null,
			'updated_at'        => $this->getUpdated() !== null ? $this->getUpdated()->format(DateTimeInterface::ATOM) : null,
		], parent::toArray());
	}

}
