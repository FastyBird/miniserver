<?php declare(strict_types = 1);

namespace FastyBird\Core\Tests\Fixtures\Dummy;

use FastyBird\Core\Documents\Application as Documents;

#[Documents\Mapping\Document]
#[Documents\Mapping\DiscriminatorEntry(name: self::TYPE)]
class DummyTwoDocument extends DummyDocument
{

	public const TYPE = 'two';

}
