<?php declare(strict_types = 1);

/**
 * PresenterRequest.php
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
 * Nette presenter (native MVC) request event
 *
 * @package        FastyBird:Core!
 * @subpackage     Events
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class PresenterRequest extends EventDispatcher\Event
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
