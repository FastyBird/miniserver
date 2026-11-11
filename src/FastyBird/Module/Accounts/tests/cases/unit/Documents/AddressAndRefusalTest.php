<?php declare(strict_types = 1);

namespace FastyBird\Module\Accounts\Tests\Cases\Unit\Documents;

use FastyBird\Core\Documents as CoreDocuments;
use FastyBird\Module\Accounts\Documents as AccountsDocuments;
use Orisai\ObjectMapper;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use function microtime;
use function str_ends_with;
use function str_repeat;
use function str_replace;

/**
 * The pattern of an e-mail address is written out on the address of an Email and on the e-mail of an Account (and on the
 * notification of the Triggers module), once on each: a top level domain is 2 to 63 letters on every copy, and a copy with
 * another bound is told here and not by the pins of the whole documents. And a document is refused, not thrown at, when the
 * value that is refused is not valid UTF-8 and Valinor has to print it (#836)
 */
final class AddressAndRefusalTest extends TestCase
{

	private const string ID = '176984ad-7cf7-465d-9e53-71668a74a688';

	private const string ACCOUNT = 'f42a8b4c-d5c8-4242-8ff0-6e5f867dcfb1';

	/**
	 * @param class-string<CoreDocuments\Document> $class
	 * @param array<string, mixed> $data
	 */
	#[DataProvider('addresses')]
	public function testATopLevelDomainIsTwoToSixtyThreeLetters(
		string $class,
		array $data,
		string $field,
		string $top,
		bool $accepted,
	): void
	{
		$data[$field] = 'a@example.' . $top;

		try {
			$document = $this->mapper()->map($data, $class);

			self::assertTrue($accepted, $field . ' ' . $top);
			self::assertSame($data[$field], $document->toArray()[$field]);
		} catch (CoreDocuments\Exceptions\MappingFailed $ex) {
			self::assertFalse($accepted, $field . ' ' . $top);
			self::assertStringStartsWith($field . ': Value ', $ex->getMessage());
			self::assertStringEndsWith('does not match the pattern.', $ex->getMessage());
		}
	}

	/**
	 * A NUL byte in a date is a refusal of the field, with the formats it is read in: createFromFormat() throws a ValueError
	 * for it, which orisai let through (#836 turned it into a refusal of the whole document) and Valinor's date constructor
	 * of Mapper refuses at the field
	 */
	#[DataProvider('dates')]
	public function testANulByteInADateIsRefusedAtTheField(string $field): void
	{
		try {
			$this->mapper()->map(
				[
					'id' => self::ID,
					'first_name' => 'Jane',
					'last_name' => 'Doe',
					'language' => 'en',
					'state' => 'active',
					$field => "2020-04-01T12:00:00+00:00\0",
				],
				AccountsDocuments\Accounts\Account::class,
			);

			self::fail('the mapper accepted the data');
		} catch (CoreDocuments\Exceptions\MappingFailed $ex) {
			self::assertStringStartsWith($field . ': Value ', $ex->getMessage());
			self::assertStringEndsWith(
				'does not match any of the following formats: `Y-m-d\TH:i:sP`.',
				$ex->getMessage(),
			);
		}
	}

	/**
	 * @return array<string, array{string}>
	 */
	public static function dates(): array
	{
		return ['registered' => ['registered'], 'last_visit' => ['last_visit']];
	}

	/**
	 * A name of many dots is refused at once: the pattern took quadratic time for it, a second and more for a few hundred
	 * thousand dots, where the refusal is a millisecond
	 *
	 * @param class-string<CoreDocuments\Document> $class
	 * @param array<string, mixed> $data
	 */
	#[DataProvider('floods')]
	public function testAFloodOfDotsIsRefusedAtOnce(string $class, array $data, string $field): void
	{
		$data[$field] = 'a@' . str_repeat('.', 200_000) . '!';
		$start = microtime(true);

		try {
			$this->mapper()->map($data, $class);

			self::fail('the mapper accepted the data');
		} catch (CoreDocuments\Exceptions\MappingFailed $ex) {
			self::assertStringStartsWith($field . ': ', $ex->getMessage());
		}

		self::assertLessThan(1.0, microtime(true) - $start);
	}

	/**
	 * @param class-string<CoreDocuments\Document> $class
	 * @param array<string, mixed> $data
	 */
	#[DataProvider('unprintable')]
	public function testAValueWhichIsNotValidUtf8IsRefused(string $class, array $data): void
	{
		try {
			$this->mapper()->map($data, $class);

			self::fail('the mapper accepted the data');
		} catch (CoreDocuments\Exceptions\MappingFailed $ex) {
			self::assertSame('*root*: A key or a value is not valid UTF-8.', $ex->getMessage());
		}
	}

	/**
	 * @return array<string, array{class-string<CoreDocuments\Document>, array<string, mixed>, string, string, bool}>
	 */
	public static function addresses(): array
	{
		$email = [
			AccountsDocuments\Emails\Email::class,
			['id' => self::ID, 'account' => self::ACCOUNT, 'address' => 'x'],
			'address',
		];
		$account = [
			AccountsDocuments\Accounts\Account::class,
			[
				'id' => self::ID,
				'first_name' => 'Jane',
				'last_name' => 'Doe',
				'language' => 'en',
				'state' => 'active',
				'email' => 'x',
			],
			'email',
		];
		$cases = [];

		foreach (['the address of an e-mail' => $email, 'the e-mail of an account' => $account] as $name => [$class, $data, $field]) {
			$cases[$name . ', 2 letters'] = [$class, $data, $field, 'cz', true];
			$cases[$name . ', 1 letter'] = [$class, $data, $field, 'c', false];
			$cases[$name . ', 63 letters'] = [$class, $data, $field, str_repeat('x', 63), true];
			$cases[$name . ', 64 letters'] = [$class, $data, $field, str_repeat('x', 64), false];
		}

		return $cases;
	}

	/**
	 * @return array<string, array{class-string<CoreDocuments\Document>, array<string, mixed>, string}>
	 */
	public static function floods(): array
	{
		$cases = [];

		foreach (self::addresses() as $name => [$class, $data, $field]) {
			if (str_ends_with($name, ', 2 letters')) {
				$cases[str_replace(', 2 letters', '', $name)] = [$class, $data, $field];
			}
		}

		return $cases;
	}

	/**
	 * @return array<string, array{class-string<CoreDocuments\Document>, array<string, mixed>}>
	 */
	public static function unprintable(): array
	{
		return [
			'an id of a role' => [AccountsDocuments\Roles\Role::class, ['id' => "\xff", 'name' => 'administrator']],
			'an id of an identity' => [
				AccountsDocuments\Identities\Identity::class,
				['id' => "\xff", 'account' => self::ACCOUNT, 'state' => 'active', 'uid' => 'x'],
			],
			'an address of an e-mail' => [
				AccountsDocuments\Emails\Email::class,
				['id' => self::ID, 'account' => self::ACCOUNT, 'address' => "a@example.\xff"],
			],
			'the state of an account' => [
				AccountsDocuments\Accounts\Account::class,
				[
					'id' => self::ID,
					'first_name' => 'Jane',
					'last_name' => 'Doe',
					'language' => 'en',
					'state' => "\xff",
				],
			],
		];
	}

	private function mapper(): CoreDocuments\Mapping\Mapper
	{
		return new CoreDocuments\Mapping\Mapper($this->createMock(ObjectMapper\Processing\Processor::class));
	}

}
