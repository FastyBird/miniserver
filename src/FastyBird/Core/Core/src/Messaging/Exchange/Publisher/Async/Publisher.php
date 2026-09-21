<?php declare(strict_types = 1);

/**
 * Publisher.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:Exchange!
 * @subpackage     Publishers
 * @since          1.0.0
 *
 * @date           19.12.20
 */

namespace FastyBird\Core\Messaging\Exchange\Publisher\Async;

use FastyBird\Core\Documents as ApplicationDocuments;
use FastyBird\Core\Types\Metadata as MetadataTypes;
use React\Promise;

/**
 * Exchange asynchronous publisher interface
 *
 * @package        FastyBird:Exchange!
 * @subpackage     Publishers
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
interface Publisher
{

	/**
	 * @return Promise\PromiseInterface<bool>
	 */
	public function publish(
		MetadataTypes\Sources\Source $source,
		string $routingKey,
		ApplicationDocuments\Document|null $entity,
	): Promise\PromiseInterface;

}
