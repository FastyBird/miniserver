<?php declare(strict_types = 1);

/**
 * TriggerCondition.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:MiniServer!
 * @subpackage     States
 * @since          0.1.0
 *
 * @date           12.01.22
 */

namespace FastyBird\MiniServer\States;

use DateTimeImmutable;
use DateTimeInterface;
use FastyBird\RedisDbStoragePlugin\States as RedisDbStoragePluginStates;

/**
 * Trigger condition state
 *
 * @package        FastyBird:MiniServer!
 * @subpackage     States
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class TriggerCondition extends RedisDbStoragePluginStates\State implements ITriggerCondition
{

	/** @var bool */
	private bool $validationResult = false;

	/** @var string|null */
	private ?string $createdAt = null;

	/** @var string|null */
	private ?string $updatedAt = null;

	/**
	 * {@inheritDoc}
	 */
	public function isFulfilled(): bool
	{
		return $this->validationResult;
	}

	/**
	 * {@inheritDoc}
	 */
	public function setFulfilled(bool $result): void
	{
		$this->validationResult = $result;
	}

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
	public static function getCreateFields(): array
	{
		return [
			0                  => 'id',
			'validationResult' => false,
			'createdAt'        => null,
			'updatedAt'        => null,
		];
	}

	/**
	 * {@inheritDoc}
	 */
	public static function getUpdateFields(): array
	{
		return [
			'validationResult',
			'updatedAt',
		];
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return array_merge([
			'validation_result' => $this->isFulfilled(),
			'created_at'        => $this->getCreatedAt() !== null ? $this->getCreatedAt()->format(DateTimeInterface::ATOM) : null,
			'updated_at'        => $this->getUpdatedAt() !== null ? $this->getUpdatedAt()->format(DateTimeInterface::ATOM) : null,
		], parent::toArray());
	}

}
