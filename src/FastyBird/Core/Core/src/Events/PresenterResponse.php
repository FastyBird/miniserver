<?php declare(strict_types = 1);

namespace FastyBird\Core\Events;

use Nette\Application;
use Symfony\Contracts\EventDispatcher;

/**
 * Nette presenter (native MVC) response event
 */
final class PresenterResponse extends EventDispatcher\Event
{

	public function __construct(
		private readonly Application\Application $application,
		private readonly Application\Response $response,
	)
	{
	}

	public function getApplication(): Application\Application
	{
		return $this->application;
	}

	public function getResponse(): Application\Response
	{
		return $this->response;
	}

}
