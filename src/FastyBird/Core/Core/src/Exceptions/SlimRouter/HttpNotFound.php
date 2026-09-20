<?php declare(strict_types = 1);

/**
 * HttpNotFoundException.php
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

namespace FastyBird\Core\Exceptions\SlimRouter;

class HttpNotFound extends HttpSpecialized
{

	protected $code = 404;

	protected $message = 'Not found.';

	protected string $title = '404 Not Found';

	protected string $description = 'The requested resource could not be found. Please verify the URI and try again.';

}
