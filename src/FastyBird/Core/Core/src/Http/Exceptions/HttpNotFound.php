<?php declare(strict_types = 1);

namespace FastyBird\Core\Http\Exceptions;

final class HttpNotFound extends HttpSpecialized
{

	protected $code = 404;

	protected $message = 'Not found.';

	public protected(set) string $title = '404 Not Found';

	public protected(set) string $description = 'The requested resource could not be found. Please verify the URI and try again.';

}
