<?php declare(strict_types = 1);

/**
 * LoadClassMetadata.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:Application!
 * @subpackage     Events
 * @since          1.0.0
 *
 * @date           12.02.24
 */

namespace FastyBird\Core\Events\Application;

use FastyBird\Core\Documents;
use Symfony\Contracts\EventDispatcher;

/**
 * Event triggered when document metadata are loaded
 *
 * @template T of Documents\Document
 *
 * @package        FastyBird:Application!
 * @subpackage     Events
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class LoadClassMetadata extends EventDispatcher\Event
{

	/**
	 * @param Documents\Mapping\ClassMetadata<T> $classMetadata
	 */
	public function __construct(
		private readonly Documents\Mapping\ClassMetadata $classMetadata,
	)
	{
	}

	/**
	 * @return Documents\Mapping\ClassMetadata<T>
	 */
	public function getClassMetadata(): Documents\Mapping\ClassMetadata
	{
		return $this->classMetadata;
	}

}
