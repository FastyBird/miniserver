<?php declare(strict_types = 1);

/**
 * HttpSpecializedException.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.ipublikuj.eu
 * @author         Adam Kadlec <adam.kadlec@ipublikuj.eu>
 * @package        iPublikuj:SlimRouter!
 * @subpackage     Exceptions
 * @since          0.1.0
 *
 * @date           15.03.20
 */

namespace FastyBird\Library\SlimRouter\Exceptions;

use Psr\Http\Message\ServerRequestInterface;
use Throwable;

abstract class HttpSpecialized extends Http
{

	public function __construct(
		ServerRequestInterface $request,
		string|null $message = null,
		Throwable|null $previous = null,
	)
	{
		if ($message !== null) {
			$this->message = $message;
		}

		parent::__construct($request, $this->message, $this->code, $previous);
	}

}
