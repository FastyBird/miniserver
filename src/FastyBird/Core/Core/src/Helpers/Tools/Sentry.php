<?php declare(strict_types = 1);

/**
 * Sentry.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:Tools!
 * @subpackage     Helpers
 * @since          1.0.0
 *
 * @date           07.04.23
 */

namespace FastyBird\Core\Helpers\Tools;

use Nette;
use Sentry\ClientInterface;

/**
 * Sentry connection helpers
 *
 * @package        FastyBird:Tools!
 * @subpackage     Helpers
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class Sentry
{

	use Nette\SmartObject;

	public function __construct(
		private readonly ClientInterface|null $client = null,
	)
	{
	}

	public function clear(): void
	{
		$this->client?->flush();
	}

}
