<?php declare(strict_types = 1);

/**
 * HttpException.php
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

use Exception;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;

class Http extends Exception
{

	protected string $title = '';

	protected string $description = '';

	public function __construct(
		protected ServerRequestInterface $request,
		string $message = '',
		int $code = 0,
		Throwable|null $previous = null,
	)
	{
		parent::__construct($message, $code, $previous);
	}

	public function setTitle(string $title): void
	{
		$this->title = $title;
	}

	public function getRequest(): ServerRequestInterface
	{
		return $this->request;
	}

	public function getTitle(): string
	{
		return $this->title;
	}

	public function setDescription(string $description): void
	{
		$this->description = $description;
	}

	public function getDescription(): string
	{
		return $this->description;
	}

}
