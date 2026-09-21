<?php declare(strict_types = 1);

/**
 * PresenterResponse.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:Core!
 * @subpackage     Events
 * @since          1.0.0
 *
 * @date           01.07.24
 */

namespace FastyBird\Core\Events;

use Nette\Application;
use Symfony\Contracts\EventDispatcher;

/**
 * Nette presenter (native MVC) response event
 *
 * @package        FastyBird:Core!
 * @subpackage     Events
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class PresenterResponse extends EventDispatcher\Event
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
