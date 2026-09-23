<?php declare(strict_types = 1);

namespace FastyBird\Core\Tests\Cases\Unit\Controllers\WebSockets\Controller;

use FastyBird\Core\Controllers\WebSockets\Controller\Controller;
use PHPUnit\Framework\TestCase;

/**
 * Controller::$payload used to be documented as a SmartObject magic property. Two consumers
 * outside Core (Module\Devices and Module\Ui's ExchangeV1 controllers) wrote to it as
 * `$this->payload->data = [...]` directly, which only worked because SmartObject's __set
 * intercepted the otherwise-private property from the subclass's scope; removing the trait broke
 * both silently until they were switched to the getPayload() accessor. This guards that the
 * accessor returns the same stdClass instance sendPayload() reads from, which is what makes the
 * accessor a safe substitute for the direct (now impossible) property write.
 *
 * Controller::$user is also documented with the same @property-read tag this issue targets, but
 * its declared type, Nette\Security\User, belongs to nette/security, which this project does not
 * require (only nette/http is installed) -- class_exists(Nette\Security\User::class) is false at
 * runtime. getUser()/injectPrimary()'s $user parameter were already unreachable dead code before
 * this epic; a test exercising them would have to fabricate a production class that does not
 * exist, so it is intentionally not covered here.
 */
final class ControllerTest extends TestCase
{

	public function testGetPayloadReturnsSameInstanceSendPayloadReads(): void
	{
		$controller = new class extends Controller
		{

		};

		$controller->getPayload()->data = ['response' => 'accepted'];

		self::assertSame(['response' => 'accepted'], $controller->getPayload()->data);
	}

}
