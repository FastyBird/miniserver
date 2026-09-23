<?php declare(strict_types = 1);

namespace FastyBird\Core\Tests\Cases\Unit\Controllers\WebSockets;

use FastyBird\Core\Controllers\WebSockets\Request;
use PHPUnit\Framework\TestCase;

/**
 * Request::$controllerName and Request::$parameters used to be documented as SmartObject magic
 * properties. Nothing in the repository ever accessed them that way -- every consumer already
 * went through these accessor methods -- but removing the trait means there is no longer a
 * fallback if that ever changes silently, so the accessors themselves are what guarantee it.
 */
final class RequestTest extends TestCase
{

	public function testSetControllerNameChangesGetControllerName(): void
	{
		$request = new Request('module:module:controller');

		$request->setControllerName('other:other:controller');

		self::assertSame('other:other:controller', $request->getControllerName());
	}

	public function testSetParametersChangesGetParameters(): void
	{
		$request = new Request('module:module:controller', ['id' => '1']);

		$request->setParameters(['id' => '2', 'action' => 'default']);

		self::assertSame(['id' => '2', 'action' => 'default'], $request->getParameters());
	}

}
