<?php declare(strict_types = 1);

namespace FastyBird\Library\WebSockets\Application\Controller;

use FastyBird\Library\WebSockets\Application;
use FastyBird\Library\WebSockets\Application\Responses;

/**
 * WebSockets controller interface
 *
 * @package        iPublikuj:WebSockets!
 * @subpackage     Application
 *
 * @author         Adam Kadlec <adam.kadlec@ipublikuj.eu>
 */
interface IController
{

	public function run(Application\Request $request): Responses\IResponse;

	public function getName(): string;

}
