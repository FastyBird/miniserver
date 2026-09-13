<?php declare(strict_types = 1);

/**
 * Response.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:SimpleAuth!
 * @subpackage     Events
 * @since          1.0.0
 *
 * @date           01.07.24
 */

namespace FastyBird\Core\SimpleAuth\Events;

use Nette\Application;
use Symfony\Contracts\EventDispatcher;

/**
 * Application response event
 *
 * @package        FastyBird:SimpleAuth!
 * @subpackage     Events
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class Response extends EventDispatcher\Event
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
