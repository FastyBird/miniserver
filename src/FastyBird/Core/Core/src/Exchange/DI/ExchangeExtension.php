<?php declare(strict_types = 1);

namespace FastyBird\Core\Exchange\DI;

use FastyBird\Core\DI\CoreExtension;
use FastyBird\Core\Documents;
use FastyBird\Core\Exchange\Consumers;
use FastyBird\Core\Exchange\Publisher;
use FastyBird\Core\Exchange\Publisher\Async;
use Nette\DI;
use Override;
use function assert;
use function is_bool;
use function is_string;

/**
 * The exchange: the consumer and publisher proxies and the routing document factory
 *
 * A child of the composite FastyBird\Core\DI\CoreExtension, which owns and runs it; it is never
 * registered with the compiler itself. It runs under the composite's name, so its services are
 * fbCore.exchange.*. It has no configuration. In beforeCompile() it registers every consumer
 * and publisher service with its proxy, reading the CoreExtension::CONSUMER_STATE and
 * CoreExtension::CONSUMER_ROUTING_KEY tags.
 */
final class ExchangeExtension extends DI\CompilerExtension
{

	#[Override]
	public function loadConfiguration(): void
	{
		$builder = $this->getContainerBuilder();

		$builder->addDefinition($this->prefix('exchange.consumer'), new DI\Definitions\ServiceDefinition())
			->setType(Consumers\Container::class);

		$builder->addDefinition($this->prefix('exchange.publisher'), new DI\Definitions\ServiceDefinition())
			->setType(Publisher\Container::class);

		$builder->addDefinition($this->prefix('exchange.publisher.async'), new DI\Definitions\ServiceDefinition())
			->setType(Async\Container::class);

		$builder->addDefinition($this->prefix('exchange.entityFactory'), new DI\Definitions\ServiceDefinition())
			->setType(Documents\RoutingDocumentFactory::class);
	}

	/**
	 * @throws DI\MissingServiceException
	 * @throws DI\NotAllowedDuringResolvingException
	 */
	#[Override]
	public function beforeCompile(): void
	{
		parent::beforeCompile();

		$builder = $this->getContainerBuilder();

		$consumerProxyServiceName = $builder->getByType(Consumers\Container::class);

		if ($consumerProxyServiceName !== null) {
			$consumerProxyService = $builder->getDefinition($consumerProxyServiceName);
			assert($consumerProxyService instanceof DI\Definitions\ServiceDefinition);

			foreach ($builder->findByType(Consumers\Consumer::class) as $consumerService) {
				if (
					$consumerService->getType() !== Consumers\Container::class
					&& ($consumerService->getAutowired() === true || !is_bool($consumerService->getAutowired()))
				) {
					$consumerService->setAutowired(false);
					$consumerStatus = $consumerService->getTag(CoreExtension::CONSUMER_STATE);
					assert(is_bool($consumerStatus) || $consumerStatus === null);
					$consumerRoutingKey = $consumerService->getTag(CoreExtension::CONSUMER_ROUTING_KEY);
					assert(is_string($consumerRoutingKey) || $consumerRoutingKey === null);

					$consumerProxyService->addSetup('?->register(?, ?, ?)', [
						'@self',
						$consumerService,
						$consumerRoutingKey ?? null,
						$consumerStatus ?? true,
					]);
				}
			}
		}

		$publisherProxyServiceName = $builder->getByType(Publisher\Container::class);

		if ($publisherProxyServiceName !== null) {
			$publisherProxyService = $builder->getDefinition($publisherProxyServiceName);
			assert($publisherProxyService instanceof DI\Definitions\ServiceDefinition);

			foreach ($builder->findByType(Publisher\MessagePublisher::class) as $publisherService) {
				if (
					$publisherService->getType() !== Publisher\Container::class
					&& ($publisherService->getAutowired() === true || !is_bool($publisherService->getAutowired()))
				) {
					$publisherService->setAutowired(false);
					$publisherProxyService->addSetup('?->register(?)', ['@self', $publisherService]);
				}
			}
		}

		$asyncPublisherProxyServiceName = $builder->getByType(
			Async\Container::class,
		);

		if ($asyncPublisherProxyServiceName !== null) {
			$asyncPublisherProxyService = $builder->getDefinition($asyncPublisherProxyServiceName);
			assert($asyncPublisherProxyService instanceof DI\Definitions\ServiceDefinition);

			foreach ($builder->findByType(
				Async\MessagePublisher::class,
			) as $publisherService) {
				if (
					$publisherService->getType() !== Async\Container::class
					&& ($publisherService->getAutowired() === true || !is_bool($publisherService->getAutowired()))
				) {
					$publisherService->setAutowired(false);
					$asyncPublisherProxyService->addSetup('?->register(?)', ['@self', $publisherService]);
				}
			}
		}
	}

}
