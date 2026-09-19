<?php declare(strict_types = 1);

namespace FastyBird\Library\WebSockets\Application;

use Nette;

/**
 * Controller request
 *
 * @package        iPublikuj:WebSockets!
 * @subpackage     Application
 *
 * @author         Adam Kadlec <adam.kadlec@ipublikuj.eu>
 *
 * @property string $controllerName
 * @property array $parameters
 */
class Request implements IRequest
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

	public function setControllerName(string $name): void
	{
		$this->name = $name;
	}

	public function getControllerName(): string
	{
		return $this->name;
	}

	/**
	 * {@inheritDoc}
	 */
	public function setParameters(array $params): void
	{
		$this->params = $params;
	}

	/**
	 * {@inheritDoc}
	 */
	public function getParameters(): array
	{
		return $this->params;
	}

	public function getParameter(string $key): mixed
	{
		return $this->params[$key] ?? null;
	}

}
