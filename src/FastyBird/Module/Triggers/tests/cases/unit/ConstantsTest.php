<?php declare(strict_types = 1);

namespace FastyBird\Module\Triggers\Tests\Cases\Unit;

use FastyBird\Module\Triggers;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use function is_string;
use function preg_match;
use function strtolower;

/**
 * Every exchange routing key the module declares is well-formed. Publishers, consumers and the
 * document routing map all use the constant, so a malformed key never fails a lookup: it only
 * stops matching what other parties (the frontend, a broker binding) expect on the wire.
 */
final class ConstantsTest extends TestCase
{

	private const SUBJECT_PATTERN = '[a-z][a-zA-Z]*(\.[a-z][a-zA-Z]*)*';

	private const DOCUMENT_ROUTING_KEY_PATTERN = '/^fb\.exchange\.module\.document\.(reported|created|updated|deleted)\.'
		. self::SUBJECT_PATTERN . '$/';

	private const ACTION_ROUTING_KEY_PATTERN = '/^fb\.exchange\.action\.' . self::SUBJECT_PATTERN . '$/';

	private const DOCUMENT_CONSTANT_PATTERN = '/^MESSAGE_BUS_\w+_DOCUMENT_(REPORTED|CREATED|UPDATED|DELETED)_ROUTING_KEY$/';

	private const ACTION_CONSTANT_PATTERN = '/^MESSAGE_BUS_\w+_ACTION_ROUTING_KEY$/';

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

		foreach (self::routingKeyConstants() as $name => $value) {
			if (preg_match(self::DOCUMENT_CONSTANT_PATTERN, $name, $matches) === 1) {
				$keys[$name] = [strtolower($matches[1]), $value];
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

		foreach (self::routingKeyConstants() as $name => $value) {
			if (preg_match(self::ACTION_CONSTANT_PATTERN, $name) === 1) {
				$keys[$name] = [$value];
			}
		}

		return $keys;
	}

	/**
	 * @return array<string, string>
	 */
	private static function routingKeyConstants(): array
	{
		$constants = [];

		foreach ((new ReflectionClass(Triggers\Constants::class))->getConstants() as $name => $value) {
			if (is_string($value)) {
				$constants[$name] = $value;
			}
		}

		return $constants;
	}

}
