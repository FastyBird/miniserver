<?php declare(strict_types = 1);

namespace FastyBird\Core\Tests\Fixtures\Dummy;

use CuyZ\Valinor\Mapper\Tree\Message\MessageBuilder;
use FastyBird\Core\Documents;
use Override;
use RuntimeException;
use ValueError;

/**
 * A Valinor-mapped document whose constructor refuses a value, the one way a migrated class refuses something its types
 * cannot express, and whose constructor fails, which is a defect, with a RuntimeException or with a ValueError
 */
final readonly class DummyGuardedDocument implements Documents\Document
{

	public const string REFUSE = 'refuse';

	public const string FAIL = 'fail';

	public const string FAIL_VALUE = 'fail-value';

	public const string BROKEN_MESSAGE = 'broken-message';

	/**
	 * @throws RuntimeException
	 * @throws ValueError
	 */
	public function __construct(private string $kind)
	{
		if ($kind === self::REFUSE) {
			throw MessageBuilder::newError('The kind is refused.')->build();
		}

		if ($kind === self::FAIL) {
			throw new RuntimeException('The constructor failed');
		}

		if ($kind === self::BROKEN_MESSAGE) {
			// An ICU body that does not parse: Valinor fails printing it, with the exception it also throws for an input that
			// is not valid UTF-8
			throw MessageBuilder::newError('The kind is {broken')->build();
		}

		if ($kind === self::FAIL_VALUE) {
			throw new ValueError('The constructor failed with a value error');
		}
	}

	#[Override]
	public function toArray(): array
	{
		return ['kind' => $this->kind];
	}

}
