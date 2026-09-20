<?php declare(strict_types = 1);

namespace FastyBird\Module\Accounts\Tests\Cases\Unit\Helpers;

use DateTimeImmutable;
use Exception;
use FastyBird\Core\Services\DateTimeFactory;
use FastyBird\Module\Accounts\Helpers;
use PHPUnit\Framework\TestCase;

final class SecurityHashTest extends TestCase
{

	/**
	 * @throws Exception
	 */
	public function testPassword(): void
	{
		$systemClock = $this->createMock(DateTimeFactory\SystemClock::class);
		$systemClock
			->method('getNow')
			->willReturn(new DateTimeImmutable('2020-04-01T12:00:00+00:00'));

		$hashHelper = new Helpers\SecurityHash($systemClock);

		$hash = $hashHelper->createKey();

		self::assertTrue($hashHelper->isValid($hash));

		$systemClock = $this->createMock(DateTimeFactory\SystemClock::class);
		$systemClock
			->method('getNow')
			->willReturn(new DateTimeImmutable('2021-04-01T12:00:00+00:00'));

		$hashHelper = new Helpers\SecurityHash($systemClock);

		self::assertFalse($hashHelper->isValid($hash));

		$systemClock = $this->createMock(DateTimeFactory\SystemClock::class);
		$systemClock
			->method('getNow')
			->willReturn(new DateTimeImmutable('2020-04-01T12:59:00+00:00'));

		$hashHelper = new Helpers\SecurityHash($systemClock);

		self::assertTrue($hashHelper->isValid($hash));

		$systemClock = $this->createMock(DateTimeFactory\SystemClock::class);
		$systemClock
			->method('getNow')
			->willReturn(new DateTimeImmutable('2020-04-01T13:01:00+00:00'));

		$hashHelper = new Helpers\SecurityHash($systemClock);

		self::assertFalse($hashHelper->isValid($hash));
	}

}
