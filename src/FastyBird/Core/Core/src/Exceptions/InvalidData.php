<?php declare(strict_types = 1);

/**
 * InvalidData.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:Tools!
 * @subpackage     Exceptions
 * @since          1.0.0
 *
 * @date           24.06.20
 */

namespace FastyBird\Core\Exceptions;

use RuntimeException;
use Throwable;
use function implode;

class InvalidData extends RuntimeException implements Exception
{

	/**
	 * @param array<string> $messages
	 */
	public function __construct(private readonly array $messages, int $code = 0, Throwable|null $previous = null)
	{
		$message = implode(' ', $messages);

		parent::__construct($message, $code, $previous);
	}

	/**
	 * @return array<string>
	 */
	public function getMessages(): array
	{
		return $this->messages;
	}

}
