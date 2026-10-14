<?php declare(strict_types = 1);

namespace FastyBird\Module\Triggers\Tests\Cases\Unit\Types;

use FastyBird\Module\Triggers\Types;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use function preg_match;
use function strtolower;

/**
 * Every exchange routing key the module declares is well-formed. Publishers, consumers and the
 * document routing map all use the case, so a malformed key never fails a lookup: it only
 * stops matching what other parties (the frontend, a broker binding) expect on the wire.
 */
final class RoutingKeyTest extends TestCase
{

	private const string SUBJECT_PATTERN = '[a-z][a-zA-Z]*(\.[a-z][a-zA-Z]*)*';

	private const string DOCUMENT_ROUTING_KEY_PATTERN = '/^fb\.exchange\.module\.document\.(reported|created|updated|deleted)\.'
		. self::SUBJECT_PATTERN . '$/';

	private const string ACTION_ROUTING_KEY_PATTERN = '/^fb\.exchange\.action\.' . self::SUBJECT_PATTERN . '$/';

	private const string DOCUMENT_CASE_PATTERN = '/^\w+_DOCUMENT_(REPORTED|CREATED|UPDATED|DELETED)$/';

	private const string ACTION_CASE_PATTERN = '/^\w+_ACTION$/';

	#[DataProvider('documentRoutingKeys')]
	public function testDocumentRoutingKeyIsWellFormed(string $action, string $routingKey): void
	{
		self::assertMatchesRegularExpression(self::DOCUMENT_ROUTING_KEY_PATTERN, $routingKey);
		self::assertStringStartsWith('fb.exchange.module.document.' . $action . '.', $routingKey);
	}

	#[DataProvider('actionRoutingKeys')]
	public function testActionRoutingKeyIsWellFormed(string $routingKey): void
	{
		self::assertMatchesRegularExpression(self::ACTION_ROUTING_KEY_PATTERN, $routingKey);
	}

	/**
	 * @return array<string, array{string, string}>
	 */
	public static function documentRoutingKeys(): array
	{
		$keys = [];

		foreach (Types\RoutingKey::cases() as $case) {
			if (preg_match(self::DOCUMENT_CASE_PATTERN, $case->name, $matches) === 1) {
				$keys[$case->name] = [strtolower($matches[1]), $case->value];
			}
		}

		return $keys;
	}

	/**
	 * @return array<string, array{string}>
	 */
	public static function actionRoutingKeys(): array
	{
		$keys = [];

		foreach (Types\RoutingKey::cases() as $case) {
			if (preg_match(self::ACTION_CASE_PATTERN, $case->name) === 1) {
				$keys[$case->name] = [$case->value];
			}
		}

		return $keys;
	}

}
