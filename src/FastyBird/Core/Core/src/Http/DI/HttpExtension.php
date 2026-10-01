<?php declare(strict_types = 1);

namespace FastyBird\Core\Http\DI;

use FastyBird\Core\Http;
use FastyBird\Core\Http\Commands;
use FastyBird\Core\Http\Middleware;
use FastyBird\Core\Http\Routing;
use FastyBird\Core\Http\Server;
use FastyBird\Core\Http\Subscribers;
use Nette\DI;
use Nette\Schema;
use Override;
use stdClass;
use function assert;

/**
 * The HTTP server: router, middlewares, the server application and its console command
 *
 * A child of the composite FastyBird\Core\DI\CoreExtension, which owns and runs it; it is never
 * registered with the compiler itself. It runs as fbCore.http and reads its fbCore > http
 * section, so its services are fbCore.http.*.
 */
final class HttpExtension extends DI\CompilerExtension
{

	#[Override]
	public function getConfigSchema(): Schema\Schema
	{
		return Schema\Expect::structure([
			'static' => Schema\Expect::structure([
				'publicRoot' => Schema\Expect::string()->nullable(),
				'enabled' => Schema\Expect::bool(false),
			]),
			'server' => Schema\Expect::structure([
				'address' => Schema\Expect::string('127.0.0.1'),
				'port' => Schema\Expect::int(8_000),
				'certificate' => Schema\Expect::string()->nullable(),
			]),
			'cors' => Schema\Expect::structure([
				'enabled' => Schema\Expect::bool(false),
				'allow' => Schema\Expect::structure([
					'origin' => Schema\Expect::string('*'),
					'methods' => Schema\Expect::arrayOf('string')->default([
						'GET',
						'POST',
						'PATCH',
						'DELETE',
						'OPTIONS',
					]),
					'credentials' => Schema\Expect::bool(true),
					'headers' => Schema\Expect::arrayOf('string')->default([
						'Content-Type',
						'Authorization',
						'X-Requested-With',
					]),
				]),
			]),
		]);
	}

	#[Override]
	public function loadConfiguration(): void
	{
		$builder = $this->getContainerBuilder();
		$configuration = $this->getConfig();
		assert($configuration instanceof stdClass);

		$builder->addDefinition(
			$this->prefix('routing.responseFactory'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Http\ServerResponseFactory::class);

		$builder->addDefinition($this->prefix('routing.router'), new DI\Definitions\ServiceDefinition())
			->setType(Routing\ServerRouter::class);

		$builder->addDefinition($this->prefix('commands.server'), new DI\Definitions\ServiceDefinition())
			->setType(Commands\HttpServer::class)
			->setArguments([
				'serverAddress' => $configuration->server->address,
				'serverPort' => $configuration->server->port,
				'serverCertificate' => $configuration->server->certificate,
			]);

		$builder->addDefinition($this->prefix('middlewares.cors'), new DI\Definitions\ServiceDefinition())
			->setType(Middleware\Cors::class)
			->setArguments([
				'enabled' => $configuration->cors->enabled,
				'allowOrigin' => $configuration->cors->allow->origin,
				'allowMethods' => $configuration->cors->allow->methods,
				'allowCredentials' => $configuration->cors->allow->credentials,
				'allowHeaders' => $configuration->cors->allow->headers,
			]);

		$builder->addDefinition(
			$this->prefix('middlewares.staticFiles'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Middleware\StaticFiles::class)
			->setArgument('publicRoot', $configuration->static->publicRoot)
			->setArgument('enabled', $configuration->static->enabled);

		$builder->addDefinition($this->prefix('middlewares.router'), new DI\Definitions\ServiceDefinition())
			->setType(Middleware\Router::class);

		$builder->addDefinition($this->prefix('application.classic'), new DI\Definitions\ServiceDefinition())
			->setType(Server\Application::class);

		$builder->addDefinition($this->prefix('server.factory'), new DI\Definitions\ServiceDefinition())
			->setType(Server\Factory::class);

		$builder->addDefinition($this->prefix('subscribers.server'), new DI\Definitions\ServiceDefinition())
			->setType(Subscribers\Server::class);
	}

}
