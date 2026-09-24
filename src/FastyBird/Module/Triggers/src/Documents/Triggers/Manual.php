<?php declare(strict_types = 1);

/**
 * Manual.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:TriggersModule!
 * @subpackage     Documents
 * @since          1.0.0
 *
 * @date           02.06.22
 */

namespace FastyBird\Module\Triggers\Documents\Triggers;

use FastyBird\Core\Documents;
use FastyBird\Module\Triggers\Entities;

/**
 * Manual  trigger document
 *
 * @package        FastyBird:TriggersModule!
 * @subpackage     Documents
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
#[Documents\Mapping\Document(entity: Entities\Triggers\Manual::class)]
#[Documents\Mapping\DiscriminatorEntry(name: Entities\Triggers\Manual::TYPE)]
final class Manual extends Trigger
{

}
