<?php declare(strict_types = 1);

namespace FastyBird\Core\Controllers\WebSockets;

use Nette;
use Override;

/**
 * Controller request
 */
final class Request implements IRequest
{

	/**
	 * Implement nette smart magic
	 */
	use Nette\SmartObject;

	/**
	 * @param string $name  fully qualified controller name (module:module:controller)
	 * @param array $params variables provided to the controller usually via URL
	 */
	public function __construct(private string $name, private array $params = [])
	{
	}

	#[Override]
	public function setControllerName(string $name): void
	{
		$this->name = $name;
	}

	#[Override]
	public function getControllerName(): string
	{
		return $this->name;
	}

	/**
	 * {@inheritDoc}
	 */
	#[Override]
	public function setParameters(array $params): void
	{
		$this->params = $params;
	}

	/**
	 * {@inheritDoc}
	 */
	#[Override]
	public function getParameters(): array
	{
		return $this->params;
	}

	#[Override]
	public function getParameter(string $key): mixed
	{
		return $this->params[$key] ?? null;
	}

}
