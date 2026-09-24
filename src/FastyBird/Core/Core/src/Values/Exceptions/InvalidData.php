<?php declare(strict_types = 1);

namespace FastyBird\Core\Values\Exceptions;

use FastyBird\Core\Exceptions;
use RuntimeException;
use Throwable;
use function implode;

final class InvalidData extends RuntimeException implements Exceptions\Exception
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
