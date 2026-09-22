<?php declare(strict_types = 1);

namespace FastyBird\Core\Exceptions;

use function implode;

final class HttpMethodNotAllowed extends HttpSpecialized
{

	/** @var array<string> */
	protected array $allowedMethods = [];

	protected $code = 405;

	protected $message = 'Method not allowed.';

	protected string $title = '405 Method Not Allowed';

	protected string $description = 'The request method is not supported for the requested resource.';

	/**
	 * @return array<string>
	 */
	public function getAllowedMethods(): array
	{
		return $this->allowedMethods;
	}

	/**
	 * @param array<string> $methods
	 */
	public function setAllowedMethods(array $methods): self
	{
		$this->allowedMethods = $methods;
		$this->message = 'Method not allowed. Must be one of: ' . implode(', ', $methods);

		return $this;
	}

}
