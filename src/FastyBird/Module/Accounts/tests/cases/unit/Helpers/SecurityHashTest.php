<?php declare(strict_types = 1);

namespace FastyBird\Module\Accounts\Tests\Cases\Unit\Helpers;

use DateTimeImmutable;
use Exception;
use FastyBird\Module\Accounts\Helpers;
use PHPUnit\Framework\TestCase;
use Psr\Clock\ClockInterface;

final class SecurityHashTest extends TestCase
{

	/**
	 * @throws Exception
	 */
	public function testPassword(): void
	{
		$systemClock = $this->createMock(ClockInterface::class);
		$systemClock
			->method('now')
			->willReturn(new DateTimeImmutable('2020-04-01T12:00:00+00:00'));

		$hashHelper = new Helpers\SecurityHash($systemClock);

		$hash = $hashHelper->createKey();

		self::assertTrue($hashHelper->isValid($hash));

		$systemClock = $this->createMock(ClockInterface::class);
		$systemClock
			->method('now')
			->willReturn(new DateTimeImmutable('2021-04-01T12:00:00+00:00'));

		$hashHelper = new Helpers\SecurityHash($systemClock);

		self::assertFalse($hashHelper->isValid($hash));

		$systemClock = $this->createMock(ClockInterface::class);
		$systemClock
			->method('now')
			->willReturn(new DateTimeImmutable('2020-04-01T12:59:00+00:00'));

		$hashHelper = new Helpers\SecurityHash($systemClock);

		self::assertTrue($hashHelper->isValid($hash));

		$systemClock = $this->createMock(ClockInterface::class);
		$systemClock
			->method('now')
			->willReturn(new DateTimeImmutable('2020-04-01T13:01:00+00:00'));

		$hashHelper = new Helpers\SecurityHash($systemClock);

		self::assertFalse($hashHelper->isValid($hash));
	}

}
