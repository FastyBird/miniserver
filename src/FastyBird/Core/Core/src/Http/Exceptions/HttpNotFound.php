<?php declare(strict_types = 1);

namespace FastyBird\Core\Http\Exceptions;

final class HttpNotFound extends HttpSpecialized
{

	protected $code = 404;

	protected $message = 'Not found.';

	protected string $title = '404 Not Found';

	protected string $description = 'The requested resource could not be found. Please verify the URI and try again.';

}
