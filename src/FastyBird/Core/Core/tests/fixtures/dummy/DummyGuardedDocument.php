<?php declare(strict_types = 1);

namespace FastyBird\Core\Tests\Fixtures\Dummy;

use CuyZ\Valinor\Mapper\Tree\Message\MessageBuilder;
use FastyBird\Core\Documents;
use Override;
use RuntimeException;

/**
 * A Valinor-mapped document whose constructor refuses a value, the one way a migrated class refuses something its types
 * cannot express, and whose constructor fails, which is a defect
 */
final readonly class DummyGuardedDocument implements Documents\Document
{

	public const string REFUSE = 'refuse';

	public const string FAIL = 'fail';

	/**
	 * @throws RuntimeException
	 */
	public function __construct(private string $kind)
	{
		if ($kind === self::REFUSE) {
			throw MessageBuilder::newError('The kind is refused.')->build();
		}

		if ($kind === self::FAIL) {
			throw new RuntimeException('The constructor failed');
		}
	}

	#[Override]
	public function toArray(): array
	{
		return ['kind' => $this->kind];
	}

}
