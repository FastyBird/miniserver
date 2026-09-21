<?php declare(strict_types = 1);

/**
 * ServerRequestError.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Exceptions
 * @since          1.0.0
 *
 * @date           12.07.23
 */

namespace FastyBird\Connector\NsPanel\Exceptions;

use FastyBird\Connector\NsPanel\Types;
use FastyBird\Core\Exceptions as SlimRouterExceptions;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;

class ServerRequestError extends SlimRouterExceptions\Http implements Exception
{

	public function __construct(
		ServerRequestInterface $request,
		private readonly Types\ServerStatus $error,
		string $message = '',
		int $code = 0,
		Throwable|null $previous = null,
	)
	{
		parent::__construct($request, $message, $code, $previous);
	}

	public function getError(): Types\ServerStatus
	{
		return $this->error;
	}

}
