<?php declare(strict_types = 1);

namespace FastyBird\Core\Presenters\Events;

use Nette\Application;
use Symfony\Contracts\EventDispatcher;

/**
 * Nette presenter (native MVC) request event
 */
final class PresenterRequest extends EventDispatcher\Event
{

	public function __construct(
		private readonly Application\Application $application,
		private readonly Application\Request $request,
	)
	{
	}

	public function getApplication(): Application\Application
	{
		return $this->application;
	}

	public function getRequest(): Application\Request
	{
		return $this->request;
	}

}
