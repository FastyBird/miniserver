<?php declare(strict_types = 1);

/**
 * ExchangeError.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:Exchange!
 * @subpackage     Events
 * @since          1.0.0
 *
 * @date           20.01.24
 */

namespace FastyBird\Core\Events;

use Symfony\Contracts\EventDispatcher;
use Throwable;

/**
 * Exchange service occurred and error
 *
 * @package        FastyBird:Exchange!
 * @subpackage     Events
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class ExchangeError extends EventDispatcher\Event
{

	public function __construct(private readonly Throwable|null $ex = null)
	{
	}

	public function getException(): Throwable|null
	{
		return $this->ex;
	}

}
