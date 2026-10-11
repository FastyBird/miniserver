<?php declare(strict_types = 1);

namespace FastyBird\Module\Triggers\Documents\Triggers;

use FastyBird\Core\Documents;
use FastyBird\Module\Triggers\Entities;

/**
 * Manual  trigger document
 */
#[Documents\Mapping\Document(entity: Entities\Triggers\Manual::class)]
#[Documents\Mapping\DiscriminatorEntry(name: Entities\Triggers\Manual::TYPE)]
final class Manual extends Trigger
{

}
