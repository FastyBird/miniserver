<?php declare(strict_types = 1);

/**
 * PostLoad.php
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
 * Event triggered after document is created
 *
 * @template T of Documents\Document
 *
 * @package        FastyBird:Application!
 * @subpackage     Events
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class PostLoad extends EventDispatcher\Event
{

	/**
	 * @param T $document
	 */
	public function __construct(private readonly Documents\Document $document)
	{
	}

	/**
	 * @return T
	 */
	public function getDocument(): Documents\Document
	{
		return $this->document;
	}

}
