<?php declare(strict_types = 1);

/**
 * Consumer.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:Exchange!
 * @subpackage     Consumers
 * @since          1.0.0
 *
 * @date           19.12.20
 */

namespace FastyBird\Core\Messaging\Exchange\Consumers;

use FastyBird\Core\Documents\Application as ApplicationDocuments;
use FastyBird\Library\Metadata\Types as MetadataTypes;

/**
 * Exchange consumer interface
 *
 * @package        FastyBird:Exchange!
 * @subpackage     Consumers
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
interface Consumer
{

	public function consume(
		MetadataTypes\Sources\Source $source,
		string $routingKey,
		ApplicationDocuments\Document|null $document,
	): void;

}
