<?php declare(strict_types = 1);

namespace FastyBird\Connector\Sonoff\Tests\Cases\Unit\Helpers;

use FastyBird\Connector\Sonoff\Helpers;
use PHPUnit\Framework\TestCase;
use function assert;
use function base64_encode;
use function is_string;
use function restore_error_handler;
use function set_error_handler;

final class TransformerTest extends TestCase
{

	private const string KEY = 'device-key';

	private const string VECTOR = '0123456789abcdef';

	public function testMessageDecryptsWithTheKeyAndTheVectorItWasEncryptedWith(): void
	{
		self::assertSame(
			'{"switch":"on"}',
			Helpers\Transformer::decryptMessage(
				$this->encrypt('{"switch":"on"}'),
				self::KEY,
				base64_encode(self::VECTOR),
			),
		);
	}

	/**
	 * The vector comes with the message, from whoever sent it. OpenSSL would cut a longer one to its first sixteen
	 * bytes and pad a shorter one, each with a warning; a vector that is not sixteen bytes makes the message one that
	 * cannot be decrypted, and nothing is raised
	 */
	public function testMessageWithAVectorThatIsNotSixteenBytesDoesNotDecrypt(): void
	{
		$message = $this->encrypt('{"switch":"on"}');

		$warnings = [];

		set_error_handler(static function (int $level, string $text) use (&$warnings): bool {
			$warnings[] = $text;

			return true;
		});

		try {
			foreach (
				[
					'longer' => base64_encode(self::VECTOR . 'and-then-more'),
					'shorter' => base64_encode('01234567'),
					'none' => '',
					'not base64' => '!' . base64_encode(self::VECTOR),
				] as $case => $vector
			) {
				self::assertFalse(Helpers\Transformer::decryptMessage($message, self::KEY, $vector), $case);
			}
		} finally {
			restore_error_handler();
		}

		self::assertSame([], $warnings);
	}

	private function encrypt(string $message): string
	{
		$encrypted = Helpers\Transformer::encryptMessage($message, self::KEY, base64_encode(self::VECTOR));
		assert(is_string($encrypted));

		return $encrypted;
	}

}
