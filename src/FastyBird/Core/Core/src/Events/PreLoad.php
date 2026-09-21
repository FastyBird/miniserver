<?php declare(strict_types = 1);

/**
 * PreLoad.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:Application!
 * @subpackage     Events
 * @since          1.0.0
 *
 * @date           09.08.24
 */

namespace FastyBird\Core\Events;

use FastyBird\Core\Documents;
use Symfony\Contracts\EventDispatcher;

/**
 * Event triggered before document is created
 *
 * @template T of Documents\Document
 *
 * @package        FastyBird:Application!
 * @subpackage     Events
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class PreLoad extends EventDispatcher\Event
{

	/**
	 * @param array<mixed> $data
	 * @param class-string<T> $class
	 */
	public function __construct(
		private array $data,
		private readonly string $class,
	)
	{
	}

	/**
	 * @return array<mixed>
	 */
	public function getData(): array
	{
		return $this->data;
	}

	/**
	 * @param array<mixed> $data
	 */
	public function setData(array $data): void
	{
		$this->data = $data;
	}

	/**
	 * @return class-string<T>
	 */
	public function getClass(): string
	{
		return $this->class;
	}

}
