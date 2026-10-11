<?php declare(strict_types = 1);

namespace FastyBird\Module\Accounts\Helpers;

use DateMalformedStringException;
use Exception;
use Nette;
use Nette\Utils;
use Psr\Clock\ClockInterface;
use function base64_decode;
use function base64_encode;
use function count;
use function explode;

/**
 * Verification hash helper
 */
final readonly class SecurityHash
{

	use Nette\SmartObject;

	private const string SEPARATOR = '##';

	public function __construct(private ClockInterface $clock)
	{
	}

	/**
	 * @throws DateMalformedStringException
	 */
	public function createKey(string $interval = '+ 1 hour'): string
	{
		$now = $this->clock->now();

		$datetime = $now->modify($interval);

		return base64_encode(Utils\Random::generate(12) . self::SEPARATOR . $datetime->getTimestamp());
	}

	/**
	 * @throws Exception
	 */
	public function isValid(string $key): bool
	{
		$encoded = base64_decode($key, true);

		if ($encoded === false) {
			return false;
		}

		$pieces = explode(self::SEPARATOR, $encoded);

		if (count($pieces) === 2) {
			[, $timestamp] = $pieces;

			$datetime = Utils\DateTime::from($timestamp);

			if ($datetime >= $this->clock->now()) {
				return true;
			}
		}

		return false;
	}

}
