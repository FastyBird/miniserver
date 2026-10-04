<?php declare(strict_types = 1);

/**
 * SecurityHash.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:AccountsModule!
 * @subpackage     Helpers
 * @since          1.0.0
 *
 * @date           30.03.20
 */

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
 *
 * @package        FastyBird:AccountsModule!
 * @subpackage     Helpers
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class SecurityHash
{

	use Nette\SmartObject;

	private const SEPARATOR = '##';

	public function __construct(private readonly ClockInterface $clock)
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
