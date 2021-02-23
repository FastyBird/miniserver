<?php
// source: /Users/akadlec/Development/FastyBird/other/miniserver-app/vendor/fastybird/bootstrap/src/Boot/../../config/common.neon
// source: /Users/akadlec/Development/FastyBird/other/miniserver-app/vendor/fastybird/bootstrap/src/Boot/../../config/defaults.neon
// source: /Users/akadlec/Development/FastyBird/other/miniserver-app/vendor/fastybird/bootstrap/src/Boot/../../../../../config/common.neon
// source: /Users/akadlec/Development/FastyBird/other/miniserver-app/vendor/fastybird/bootstrap/src/Boot/../../../../../config/defaults.neon
// source: array

/** @noinspection PhpParamsInspection,PhpMethodMayBeStaticInspection */

declare(strict_types=1);

class Container_9769c2aee7 extends Nette\DI\Container
{
	protected $tags = [
		'middleware' => [
			'02' => ['priority' => 10],
			'fbAuthModule.middleware.urlFormat' => ['priority' => 150],
			'fbJsonApi.middlewares.jsonapi' => ['priority' => 100],
		],
		'nette.inject' => [
			'fbAuthModule.controllers.account' => true,
			'fbAuthModule.controllers.accountEmails' => true,
			'fbAuthModule.controllers.accountIdentities' => true,
			'fbAuthModule.controllers.accounts' => true,
			'fbAuthModule.controllers.emails' => true,
			'fbAuthModule.controllers.identities' => true,
			'fbAuthModule.controllers.public' => true,
			'fbAuthModule.controllers.roleChildren' => true,
			'fbAuthModule.controllers.roles' => true,
			'fbAuthModule.controllers.session' => true,
			'fbDevicesModule.controllers.channelConfiguration' => true,
			'fbDevicesModule.controllers.channelProperites' => true,
			'fbDevicesModule.controllers.channels' => true,
			'fbDevicesModule.controllers.connectors' => true,
			'fbDevicesModule.controllers.deviceChildren' => true,
			'fbDevicesModule.controllers.deviceConfiguration' => true,
			'fbDevicesModule.controllers.deviceConnector' => true,
			'fbDevicesModule.controllers.deviceProperties' => true,
			'fbDevicesModule.controllers.devices' => true,
			'fbTriggersModule.controllers.actions' => true,
			'fbTriggersModule.controllers.conditions' => true,
			'fbTriggersModule.controllers.notifications' => true,
			'fbTriggersModule.controllers.triggers' => true,
			'fbUiModule.controllers.dashboards' => true,
			'fbUiModule.controllers.dataSources' => true,
			'fbUiModule.controllers.display' => true,
			'fbUiModule.controllers.groups' => true,
			'fbUiModule.controllers.widgets' => true,
		],
		'nettrine.orm.mapping.driver' => ['nettrineOrm.mappingDriver' => true],
		'nettrine.orm.annotation.driver' => ['nettrineOrmAnnotations.annotationDriver' => true],
	];

	protected $types = ['container' => 'Nette\DI\Container'];

	protected $aliases = [
		'httpRequest' => 'http.request',
		'httpResponse' => 'http.response',
		'nette.httpRequestFactory' => 'http.requestFactory',
		'session' => 'session.session',
	];

	protected $wiring = [
		'Nette\DI\Container' => [['container']],
		'Nette\Http\RequestFactory' => [['http.requestFactory']],
		'Nette\Http\IRequest' => [['http.request']],
		'Nette\Http\Request' => [['http.request']],
		'Nette\Http\IResponse' => [['http.response']],
		'Nette\Http\Response' => [['http.response']],
		'Nette\Http\Session' => [['session.session']],
		'Tracy\ILogger' => [
			0 => ['contributteMonolog.psrToTracyLazyAdapter'],
			2 => ['tracy.logger', 'contributteMonolog.psrToTracyAdapter'],
		],
		'Tracy\BlueScreen' => [['tracy.blueScreen']],
		'Tracy\Bar' => [['tracy.bar']],
		'Monolog\Handler\AbstractProcessingHandler' => [
			2 => ['contributteMonolog.logger.default.handler.0', 'contributteMonolog.logger.default.handler.1'],
		],
		'Monolog\Handler\AbstractHandler' => [
			2 => ['contributteMonolog.logger.default.handler.0', 'contributteMonolog.logger.default.handler.1'],
		],
		'Monolog\Handler\HandlerInterface' => [
			2 => ['contributteMonolog.logger.default.handler.0', 'contributteMonolog.logger.default.handler.1'],
		],
		'Monolog\ResettableInterface' => [
			0 => ['contributteMonolog.logger.default'],
			2 => ['contributteMonolog.logger.default.handler.0', 'contributteMonolog.logger.default.handler.1'],
		],
		'Monolog\Handler\StreamHandler' => [2 => ['contributteMonolog.logger.default.handler.0']],
		'Mangoweb\MonologTracyHandler\TracyHandler' => [2 => ['contributteMonolog.logger.default.handler.1']],
		'Monolog\Processor\MemoryProcessor' => [2 => ['contributteMonolog.logger.default.processor.0']],
		'Monolog\Processor\ProcessorInterface' => [2 => ['contributteMonolog.logger.default.processor.0']],
		'Monolog\Processor\MemoryPeakUsageProcessor' => [2 => ['contributteMonolog.logger.default.processor.0']],
		'Mangoweb\MonologTracyHandler\TracyProcessor' => [2 => ['contributteMonolog.logger.default.processor.1']],
		'Psr\Log\LoggerInterface' => [['contributteMonolog.logger.default']],
		'Monolog\Logger' => [['contributteMonolog.logger.default']],
		'Tracy\Bridges\Psr\PsrToTracyLoggerAdapter' => [2 => ['contributteMonolog.psrToTracyAdapter']],
		'Contributte\Monolog\Tracy\LazyTracyLogger' => [['contributteMonolog.psrToTracyLazyAdapter']],
		'Contributte\Translation\LocaleResolver' => [['contributteTranslation.localeResolver']],
		'Contributte\Translation\FallbackResolver' => [['contributteTranslation.fallbackResolver']],
		'Symfony\Component\Config\ConfigCacheFactoryInterface' => [['contributteTranslation.configCacheFactory']],
		'Symfony\Component\Config\ConfigCacheFactory' => [['contributteTranslation.configCacheFactory']],
		'Symfony\Component\Translation\Translator' => [['contributteTranslation.translator']],
		'Symfony\Contracts\Translation\TranslatorInterface' => [['contributteTranslation.translator']],
		'Symfony\Component\Translation\TranslatorBagInterface' => [1 => ['contributteTranslation.translator']],
		'Symfony\Contracts\Translation\LocaleAwareInterface' => [1 => ['contributteTranslation.translator']],
		'Nette\Localization\ITranslator' => [['contributteTranslation.translator']],
		'Contributte\Translation\Translator' => [['contributteTranslation.translator']],
		'Symfony\Component\Translation\Loader\ArrayLoader' => [['contributteTranslation.loaderNeon']],
		'Symfony\Component\Translation\Loader\LoaderInterface' => [['contributteTranslation.loaderNeon']],
		'Contributte\Translation\Loaders\Neon' => [['contributteTranslation.loaderNeon']],
		'Tracy\IBarPanel' => [['contributteTranslation.tracyPanel']],
		'Contributte\Translation\Tracy\Panel' => [['contributteTranslation.tracyPanel']],
		'Symfony\Contracts\EventDispatcher\EventDispatcherInterface' => [['contributteEvents.dispatcher']],
		'Psr\EventDispatcher\EventDispatcherInterface' => [['contributteEvents.dispatcher']],
		'Symfony\Component\EventDispatcher\EventDispatcherInterface' => [['contributteEvents.dispatcher']],
		'Doctrine\Common\Annotations\Reader' => [
			0 => ['nettrineAnnotations.reader'],
			2 => ['nettrineAnnotations.delegatedReader'],
		],
		'Doctrine\Common\Annotations\AnnotationReader' => [2 => ['nettrineAnnotations.delegatedReader']],
		'Doctrine\Common\Cache\Cache' => [['nettrineCache.driver']],
		'Doctrine\DBAL\Logging\SQLLogger' => [1 => ['nettrineDbal.logger']],
		'Doctrine\DBAL\Logging\LoggerChain' => [['nettrineDbal.logger']],
		'Doctrine\DBAL\Configuration' => [0 => ['nettrineOrm.configuration'], 2 => ['nettrineDbal.configuration']],
		'Doctrine\Common\EventManager' => [['nettrineDbal.eventManager']],
		'Nettrine\DBAL\Events\ContainerAwareEventManager' => [['nettrineDbal.eventManager']],
		'Nettrine\DBAL\ConnectionFactory' => [['nettrineDbal.connectionFactory']],
		'Doctrine\DBAL\Driver\Connection' => [['nettrineDbal.connection']],
		'Doctrine\DBAL\Connection' => [['nettrineDbal.connection']],
		'Doctrine\ORM\Configuration' => [['nettrineOrm.configuration']],
		'Doctrine\ORM\Mapping\EntityListenerResolver' => [['nettrineOrm.entityListenerResolver']],
		'Nettrine\ORM\Mapping\ContainerEntityListenerResolver' => [['nettrineOrm.entityListenerResolver']],
		'Doctrine\ORM\Decorator\EntityManagerDecorator' => [['nettrineOrm.entityManagerDecorator']],
		'Doctrine\Persistence\ObjectManagerDecorator' => [['nettrineOrm.entityManagerDecorator']],
		'Doctrine\ORM\EntityManagerInterface' => [['nettrineOrm.entityManagerDecorator']],
		'Doctrine\Persistence\ObjectManager' => [['nettrineOrm.entityManagerDecorator']],
		'Nettrine\ORM\EntityManagerDecorator' => [['nettrineOrm.entityManagerDecorator']],
		'Doctrine\Persistence\AbstractManagerRegistry' => [['nettrineOrm.managerRegistry']],
		'Doctrine\Persistence\ConnectionRegistry' => [['nettrineOrm.managerRegistry']],
		'Doctrine\Persistence\ManagerRegistry' => [['nettrineOrm.managerRegistry']],
		'Nettrine\ORM\ManagerRegistry' => [['nettrineOrm.managerRegistry']],
		'Doctrine\Persistence\Mapping\Driver\MappingDriver' => [
			0 => ['nettrineOrm.mappingDriver'],
			2 => [1 => 'nettrineOrmAnnotations.annotationDriver'],
		],
		'Doctrine\Persistence\Mapping\Driver\MappingDriverChain' => [['nettrineOrm.mappingDriver']],
		'Doctrine\Persistence\Mapping\Driver\AnnotationDriver' => [2 => ['nettrineOrmAnnotations.annotationDriver']],
		'Doctrine\ORM\Mapping\Driver\AnnotationDriver' => [2 => ['nettrineOrmAnnotations.annotationDriver']],
		'Doctrine\ORM\Cache\RegionsConfiguration' => [2 => ['nettrineOrmCache.regions']],
		'Doctrine\ORM\Cache\CacheFactory' => [2 => ['nettrineOrmCache.cacheFactory']],
		'Doctrine\ORM\Cache\DefaultCacheFactory' => [2 => ['nettrineOrmCache.cacheFactory']],
		'Doctrine\ORM\Cache\CacheConfiguration' => [2 => ['nettrineOrmCache.cacheConfiguration']],
		'FastyBird\ApplicationExchange\Publisher\IPublisher' => [
			0 => ['fbApplicationExchange.publisher'],
			2 => [1 => '012'],
		],
		'FastyBird\ApplicationExchange\Publisher\PublisherProxy' => [['fbApplicationExchange.publisher']],
		'FastyBird\DateTimeFactory\DateTimeFactory' => [['01']],
		'FastyBird\SimpleAuth\Auth' => [['fbSimpleAuth.auth']],
		'FastyBird\SimpleAuth\Security\TokenBuilder' => [['fbSimpleAuth.token.builder']],
		'FastyBird\SimpleAuth\Security\TokenReader' => [['fbSimpleAuth.token.reader']],
		'FastyBird\SimpleAuth\Security\TokenValidator' => [['fbSimpleAuth.token.validator']],
		'FastyBird\SimpleAuth\Security\IUserStorage' => [['fbSimpleAuth.security.userStorage']],
		'FastyBird\SimpleAuth\Security\UserStorage' => [['fbSimpleAuth.security.userStorage']],
		'Psr\Http\Server\MiddlewareInterface' => [
			[
				'fbSimpleAuth.middleware.access',
				'fbSimpleAuth.middleware.user',
				'02',
				'fbJsonApi.middlewares.jsonapi',
				'fbAuthModule.middleware.access',
				'fbAuthModule.middleware.urlFormat',
				'fbDevicesModule.middleware.access',
				'fbTriggersModule.middleware.access',
				'fbUiModule.middleware.access',
			],
		],
		'FastyBird\SimpleAuth\Middleware\AccessMiddleware' => [['fbSimpleAuth.middleware.access']],
		'FastyBird\SimpleAuth\Middleware\UserMiddleware' => [['fbSimpleAuth.middleware.user']],
		'FastyBird\SimpleAuth\Mapping\Driver\Owner' => [['fbSimpleAuth.doctrine.driver']],
		'Doctrine\Common\EventSubscriber' => [
			[
				'fbSimpleAuth.doctrine.subscriber',
				'fbAuthModule.subscribers.accountEntity',
				'fbAuthModule.subscribers.emailEntity',
				'fbDevicesModule.subscribers.entities',
				'fbTriggersModule.subscribers.actionEntity',
				'fbTriggersModule.subscribers.conditionEntity',
				'fbTriggersModule.subscribers.notificationEntity',
				'fbTriggersModule.subscribers.entities',
				'ipubDoctrinePhone.subscriber',
				'ipubDoctrineConsistence.subscriber',
				'ipubDoctrineTimestampable.subscriber',
				'ipubDoctrineDynamicDiscriminatorMap.subscriber',
				'010',
				'011',
			],
		],
		'FastyBird\SimpleAuth\Subscribers\UserSubscriber' => [['fbSimpleAuth.doctrine.subscriber']],
		'FastyBird\SimpleAuth\Models\Tokens\ITokenRepository' => [['fbSimpleAuth.doctrine.tokenRepository']],
		'FastyBird\SimpleAuth\Models\Tokens\TokenRepository' => [['fbSimpleAuth.doctrine.tokenRepository']],
		'FastyBird\SimpleAuth\Models\Tokens\ITokensManager' => [['fbSimpleAuth.doctrine.tokensManager']],
		'FastyBird\SimpleAuth\Models\Tokens\TokensManager' => [['fbSimpleAuth.doctrine.tokensManager']],
		'FastyBird\Database\Middleware\PagingMiddleware' => [['02']],
		'Symfony\Component\EventDispatcher\EventSubscriberInterface' => [
			['fbDatabase.subscribers.databaseCheck', 'fbDatabase.subscribers.exchange'],
		],
		'FastyBird\Database\Subscribers\DatabaseCheckSubscriber' => [['fbDatabase.subscribers.databaseCheck']],
		'FastyBird\Database\Subscribers\ExchangeSubscriber' => [['fbDatabase.subscribers.exchange']],
		'FastyBird\Database\Helpers\Database' => [['fbDatabase.helpers.database']],
		'FastyBird\JsonApi\Middleware\JsonApiMiddleware' => [['fbJsonApi.middlewares.jsonapi']],
		'Neomerx\JsonApi\Schema\SchemaContainer' => [['fbJsonApi.schemas.container']],
		'Neomerx\JsonApi\Contracts\Schema\SchemaContainerInterface' => [['fbJsonApi.schemas.container']],
		'FastyBird\JsonApi\JsonApi\JsonApiSchemaContainer' => [['fbJsonApi.schemas.container']],
		'FastyBird\ModulesMetadata\Schemas\IValidator' => [['03']],
		'FastyBird\ModulesMetadata\Schemas\Validator' => [['03']],
		'FastyBird\ModulesMetadata\Loaders\IMetadataLoader' => [['04']],
		'FastyBird\ModulesMetadata\Loaders\MetadataLoader' => [['04']],
		'FastyBird\ModulesMetadata\Loaders\ISchemaLoader' => [['05']],
		'FastyBird\ModulesMetadata\Loaders\SchemaLoader' => [['05']],
		'Psr\Http\Message\ResponseFactoryInterface' => [['fbWebServer.routing.responseFactory']],
		'FastyBird\WebServer\Http\ResponseFactory' => [['fbWebServer.routing.responseFactory']],
		'IPub\SlimRouter\Routing\Router' => [['fbWebServer.routing.router']],
		'IteratorAggregate' => [2 => ['fbWebServer.routing.router']],
		'Traversable' => [2 => ['fbWebServer.routing.router']],
		'IPub\SlimRouter\Routing\IRouter' => [['fbWebServer.routing.router']],
		'FastyBird\WebServer\Router\Router' => [['fbWebServer.routing.router']],
		'React\EventLoop\LoopInterface' => [['react.eventLoop']],
		'Evenement\EventEmitter' => [['react.socketServer']],
		'Evenement\EventEmitterInterface' => [['react.socketServer']],
		'React\Socket\ServerInterface' => [['react.socketServer']],
		'React\Socket\Server' => [['react.socketServer']],
		'Symfony\Component\Console\Command\Command' => [
			[
				'fbWebServer.command.server',
				'fbAuthModule.commands.create',
				'fbAuthModule.commands.initialize',
				'fbDevicesModule.commands.create',
				'fbDevicesModule.commands.initialize',
				'fbTriggersModule.commands.initialize',
				'fbUiModule.commands.initialize',
				'06',
			],
		],
		'FastyBird\WebServer\Commands\HttpServerCommand' => [['fbWebServer.command.server']],
		'FastyBird\RedisDbStoragePlugin\Connections\IConnection' => [['fbRedisDbStoragePlugin.connection']],
		'FastyBird\RedisDbStoragePlugin\Connections\Connection' => [['fbRedisDbStoragePlugin.connection']],
		'FastyBird\RedisDbStoragePlugin\Client\IClient' => [['fbRedisDbStoragePlugin.client']],
		'FastyBird\RedisDbStoragePlugin\Client\Client' => [['fbRedisDbStoragePlugin.client']],
		'FastyBird\RedisDbStoragePlugin\Models\StatesManagerFactory' => [
			['fbRedisDbStoragePlugin.model.statesManagerFactory'],
		],
		'FastyBird\RedisDbStoragePlugin\Models\StateRepositoryFactory' => [
			['fbRedisDbStoragePlugin.model.stateRepositoryFactory'],
		],
		'FastyBird\AuthModule\Middleware\AccessMiddleware' => [['fbAuthModule.middleware.access']],
		'FastyBird\AuthModule\Middleware\UrlFormatMiddleware' => [['fbAuthModule.middleware.urlFormat']],
		'FastyBird\WebServer\Router\IRoutes' => [
			[
				'fbAuthModule.router.routes',
				'fbDevicesModule.router.routes',
				'fbTriggersModule.router.routes',
				'fbUiModule.router.routes',
			],
		],
		'FastyBird\AuthModule\Router\Routes' => [['fbAuthModule.router.routes']],
		'FastyBird\AuthModule\Commands\Accounts\CreateCommand' => [['fbAuthModule.commands.create']],
		'FastyBird\AuthModule\Commands\InitializeCommand' => [['fbAuthModule.commands.initialize']],
		'FastyBird\AuthModule\Models\Accounts\IAccountRepository' => [['fbAuthModule.models.accountRepository']],
		'FastyBird\AuthModule\Models\Accounts\AccountRepository' => [['fbAuthModule.models.accountRepository']],
		'FastyBird\AuthModule\Models\Emails\IEmailRepository' => [['fbAuthModule.models.emailRepository']],
		'FastyBird\AuthModule\Models\Emails\EmailRepository' => [['fbAuthModule.models.emailRepository']],
		'FastyBird\AuthModule\Models\Identities\IIdentityRepository' => [['fbAuthModule.models.identityRepository']],
		'FastyBird\AuthModule\Models\Identities\IdentityRepository' => [['fbAuthModule.models.identityRepository']],
		'FastyBird\AuthModule\Models\Roles\IRoleRepository' => [['fbAuthModule.models.roleRepository']],
		'FastyBird\AuthModule\Models\Roles\RoleRepository' => [['fbAuthModule.models.roleRepository']],
		'FastyBird\AuthModule\Models\Accounts\IAccountsManager' => [['fbAuthModule.models.accountsManager']],
		'FastyBird\AuthModule\Models\Accounts\AccountsManager' => [['fbAuthModule.models.accountsManager']],
		'FastyBird\AuthModule\Models\Emails\IEmailsManager' => [['fbAuthModule.models.emailsManager']],
		'FastyBird\AuthModule\Models\Emails\EmailsManager' => [['fbAuthModule.models.emailsManager']],
		'FastyBird\AuthModule\Models\Identities\IIdentitiesManager' => [['fbAuthModule.models.identitiesManager']],
		'FastyBird\AuthModule\Models\Identities\IdentitiesManager' => [['fbAuthModule.models.identitiesManager']],
		'FastyBird\AuthModule\Models\Roles\IRolesManager' => [['fbAuthModule.models.rolesManager']],
		'FastyBird\AuthModule\Models\Roles\RolesManager' => [['fbAuthModule.models.rolesManager']],
		'FastyBird\AuthModule\Subscribers\AccountEntitySubscriber' => [['fbAuthModule.subscribers.accountEntity']],
		'FastyBird\AuthModule\Subscribers\EmailEntitySubscriber' => [['fbAuthModule.subscribers.emailEntity']],
		'FastyBird\AuthModule\Controllers\BaseV1Controller' => [
			[
				'fbAuthModule.controllers.session',
				'fbAuthModule.controllers.account',
				'fbAuthModule.controllers.accountEmails',
				'fbAuthModule.controllers.accountIdentities',
				'fbAuthModule.controllers.accounts',
				'fbAuthModule.controllers.emails',
				'fbAuthModule.controllers.identities',
				'fbAuthModule.controllers.roles',
				'fbAuthModule.controllers.roleChildren',
				'fbAuthModule.controllers.public',
			],
		],
		'FastyBird\AuthModule\Controllers\SessionV1Controller' => [['fbAuthModule.controllers.session']],
		'FastyBird\AuthModule\Controllers\AccountV1Controller' => [['fbAuthModule.controllers.account']],
		'FastyBird\AuthModule\Controllers\AccountEmailsV1Controller' => [['fbAuthModule.controllers.accountEmails']],
		'FastyBird\AuthModule\Controllers\AccountIdentitiesV1Controller' => [
			['fbAuthModule.controllers.accountIdentities'],
		],
		'FastyBird\AuthModule\Controllers\AccountsV1Controller' => [['fbAuthModule.controllers.accounts']],
		'FastyBird\AuthModule\Controllers\EmailsV1Controller' => [['fbAuthModule.controllers.emails']],
		'FastyBird\AuthModule\Controllers\IdentitiesV1Controller' => [['fbAuthModule.controllers.identities']],
		'FastyBird\AuthModule\Controllers\RolesV1Controller' => [['fbAuthModule.controllers.roles']],
		'FastyBird\AuthModule\Controllers\RoleChildrenV1Controller' => [['fbAuthModule.controllers.roleChildren']],
		'FastyBird\AuthModule\Controllers\PublicV1Controller' => [['fbAuthModule.controllers.public']],
		'FastyBird\JsonApi\Schemas\JsonApiSchema' => [
			[
				'fbAuthModule.schemas.account',
				'fbAuthModule.schemas.email',
				'fbAuthModule.schemas.accountIdentity',
				'fbAuthModule.schemas.role',
				'fbAuthModule.schemas.session',
				'fbDevicesModule.schemas.device',
				'fbDevicesModule.schemas.device.properties',
				'fbDevicesModule.schemas.device.connector',
				'fbDevicesModule.schemas.device.configuration',
				'fbDevicesModule.schemas.channel',
				'fbDevicesModule.schemas.channel.property',
				'fbDevicesModule.schemas.configuration',
				'fbDevicesModule.schemas.connector.fbBus',
				'fbDevicesModule.schemas.connector.fbMqttV1',
				'fbTriggersModule.schemas.triggers.automatic',
				'fbTriggersModule.schemas.triggers.manual',
				'fbTriggersModule.schemas.actions.channelProperty',
				'fbTriggersModule.schemas.conditions.channelProperty',
				'fbTriggersModule.schemas.conditions.deviceProperty',
				'fbTriggersModule.schemas.conditions.date',
				'fbTriggersModule.schemas.conditions.time',
				'fbTriggersModule.schemas.notifications.email',
				'fbTriggersModule.schemas.notifications.sms',
				'fbUiModule.schemas.dashboard',
				'fbUiModule.schemas.group',
				'fbUiModule.schemas.widgets.analogActuator',
				'fbUiModule.schemas.widgets.analogSensor',
				'fbUiModule.schemas.widgets.digitalActuator',
				'fbUiModule.schemas.widgets.digitalSensor',
				'fbUiModule.schemas.dataSources.channelProperty',
				'fbUiModule.schemas.display.analogValue',
				'fbUiModule.schemas.display.button',
				'fbUiModule.schemas.display.chartGraph',
				'fbUiModule.schemas.display.digitalValue',
				'fbUiModule.schemas.display.gauge',
				'fbUiModule.schemas.display.groupedButton',
				'fbUiModule.schemas.display.slider',
			],
		],
		'Neomerx\JsonApi\Contracts\Schema\SchemaInterface' => [
			[
				'fbAuthModule.schemas.account',
				'fbAuthModule.schemas.email',
				'fbAuthModule.schemas.accountIdentity',
				'fbAuthModule.schemas.role',
				'fbAuthModule.schemas.session',
				'fbDevicesModule.schemas.device',
				'fbDevicesModule.schemas.device.properties',
				'fbDevicesModule.schemas.device.connector',
				'fbDevicesModule.schemas.device.configuration',
				'fbDevicesModule.schemas.channel',
				'fbDevicesModule.schemas.channel.property',
				'fbDevicesModule.schemas.configuration',
				'fbDevicesModule.schemas.connector.fbBus',
				'fbDevicesModule.schemas.connector.fbMqttV1',
				'fbTriggersModule.schemas.triggers.automatic',
				'fbTriggersModule.schemas.triggers.manual',
				'fbTriggersModule.schemas.actions.channelProperty',
				'fbTriggersModule.schemas.conditions.channelProperty',
				'fbTriggersModule.schemas.conditions.deviceProperty',
				'fbTriggersModule.schemas.conditions.date',
				'fbTriggersModule.schemas.conditions.time',
				'fbTriggersModule.schemas.notifications.email',
				'fbTriggersModule.schemas.notifications.sms',
				'fbUiModule.schemas.dashboard',
				'fbUiModule.schemas.group',
				'fbUiModule.schemas.widgets.analogActuator',
				'fbUiModule.schemas.widgets.analogSensor',
				'fbUiModule.schemas.widgets.digitalActuator',
				'fbUiModule.schemas.widgets.digitalSensor',
				'fbUiModule.schemas.dataSources.channelProperty',
				'fbUiModule.schemas.display.analogValue',
				'fbUiModule.schemas.display.button',
				'fbUiModule.schemas.display.chartGraph',
				'fbUiModule.schemas.display.digitalValue',
				'fbUiModule.schemas.display.gauge',
				'fbUiModule.schemas.display.groupedButton',
				'fbUiModule.schemas.display.slider',
			],
		],
		'FastyBird\JsonApi\Schemas\IJsonApiSchema' => [
			[
				'fbAuthModule.schemas.account',
				'fbAuthModule.schemas.email',
				'fbAuthModule.schemas.accountIdentity',
				'fbAuthModule.schemas.role',
				'fbAuthModule.schemas.session',
				'fbDevicesModule.schemas.device',
				'fbDevicesModule.schemas.device.properties',
				'fbDevicesModule.schemas.device.connector',
				'fbDevicesModule.schemas.device.configuration',
				'fbDevicesModule.schemas.channel',
				'fbDevicesModule.schemas.channel.property',
				'fbDevicesModule.schemas.configuration',
				'fbDevicesModule.schemas.connector.fbBus',
				'fbDevicesModule.schemas.connector.fbMqttV1',
				'fbTriggersModule.schemas.triggers.automatic',
				'fbTriggersModule.schemas.triggers.manual',
				'fbTriggersModule.schemas.actions.channelProperty',
				'fbTriggersModule.schemas.conditions.channelProperty',
				'fbTriggersModule.schemas.conditions.deviceProperty',
				'fbTriggersModule.schemas.conditions.date',
				'fbTriggersModule.schemas.conditions.time',
				'fbTriggersModule.schemas.notifications.email',
				'fbTriggersModule.schemas.notifications.sms',
				'fbUiModule.schemas.dashboard',
				'fbUiModule.schemas.group',
				'fbUiModule.schemas.widgets.analogActuator',
				'fbUiModule.schemas.widgets.analogSensor',
				'fbUiModule.schemas.widgets.digitalActuator',
				'fbUiModule.schemas.widgets.digitalSensor',
				'fbUiModule.schemas.dataSources.channelProperty',
				'fbUiModule.schemas.display.analogValue',
				'fbUiModule.schemas.display.button',
				'fbUiModule.schemas.display.chartGraph',
				'fbUiModule.schemas.display.digitalValue',
				'fbUiModule.schemas.display.gauge',
				'fbUiModule.schemas.display.groupedButton',
				'fbUiModule.schemas.display.slider',
			],
		],
		'FastyBird\AuthModule\Schemas\Accounts\AccountSchema' => [['fbAuthModule.schemas.account']],
		'FastyBird\AuthModule\Schemas\Emails\EmailSchema' => [['fbAuthModule.schemas.email']],
		'FastyBird\AuthModule\Schemas\Identities\IdentitySchema' => [['fbAuthModule.schemas.accountIdentity']],
		'FastyBird\AuthModule\Schemas\Roles\RoleSchema' => [['fbAuthModule.schemas.role']],
		'FastyBird\AuthModule\Schemas\Sessions\SessionSchema' => [['fbAuthModule.schemas.session']],
		'FastyBird\JsonApi\Hydrators\Hydrator' => [
			[
				'fbAuthModule.hydrators.accounts.profile',
				'fbAuthModule.hydrators.accounts',
				'fbAuthModule.hydrators.emails.profile',
				'fbAuthModule.hydrators.emails.email',
				'fbAuthModule.hydrators.identities.profile',
				'fbAuthModule.hydrators.role',
				'fbDevicesModule.hydrators.device',
				'fbDevicesModule.hydrators.channel',
				'fbDevicesModule.hydrators.connectors',
				'fbTriggersModule.hydrators.triggers.automatic',
				'fbTriggersModule.hydrators.triggers.manual',
				'fbTriggersModule.hydrators.actions.channelProperty',
				'fbTriggersModule.hydrators.conditions.channelProperty',
				'fbTriggersModule.hydrators.conditions.deviceProperty',
				'fbTriggersModule.hydrators.conditions.time',
				'fbTriggersModule.hydrators.notifications.email',
				'fbTriggersModule.hydrators.notifications.sms',
				'fbUiModule.hydrators.dashboard',
				'fbUiModule.hydrators.group',
				'fbUiModule.hydrators.widgets.analogActuator',
				'fbUiModule.hydrators.widgets.analogSensor',
				'fbUiModule.hydrators.widgets.digitalActuator',
				'fbUiModule.hydrators.widgets.digitalSensor',
				'fbUiModule.hydrators.dataSources.channelProperty',
				'fbUiModule.hydrators.widgets.analogValue',
				'fbUiModule.hydrators.widgets.button',
				'fbUiModule.hydrators.widgets.chartGraph',
				'fbUiModule.hydrators.widgets.digitalValue',
				'fbUiModule.hydrators.widgets.gauge',
				'fbUiModule.hydrators.widgets.groupedButton',
				'fbUiModule.hydrators.widgets.slider',
			],
		],
		'FastyBird\AuthModule\Hydrators\Accounts\ProfileAccountHydrator' => [['fbAuthModule.hydrators.accounts.profile']],
		'FastyBird\AuthModule\Hydrators\Accounts\AccountHydrator' => [['fbAuthModule.hydrators.accounts']],
		'FastyBird\AuthModule\Hydrators\Emails\ProfileEmailHydrator' => [['fbAuthModule.hydrators.emails.profile']],
		'FastyBird\AuthModule\Hydrators\Emails\EmailHydrator' => [['fbAuthModule.hydrators.emails.email']],
		'FastyBird\AuthModule\Hydrators\Identities\IdentityHydrator' => [['fbAuthModule.hydrators.identities.profile']],
		'FastyBird\AuthModule\Hydrators\Roles\RoleHydrator' => [['fbAuthModule.hydrators.role']],
		'FastyBird\AuthModule\Helpers\SecurityHash' => [['fbAuthModule.security.hash']],
		'FastyBird\SimpleAuth\Security\IIdentityFactory' => [['fbAuthModule.security.identityFactory']],
		'FastyBird\AuthModule\Security\IdentityFactory' => [['fbAuthModule.security.identityFactory']],
		'FastyBird\SimpleAuth\Security\IAuthenticator' => [['fbAuthModule.security.authenticator']],
		'FastyBird\AuthModule\Security\Authenticator' => [['fbAuthModule.security.authenticator']],
		'FastyBird\SimpleAuth\Security\User' => [['security.user']],
		'FastyBird\AuthModule\Security\User' => [['security.user']],
		'FastyBird\DevicesModule\Middleware\AccessMiddleware' => [['fbDevicesModule.middleware.access']],
		'FastyBird\DevicesModule\Router\Routes' => [['fbDevicesModule.router.routes']],
		'FastyBird\DevicesModule\Commands\Devices\CreateCommand' => [['fbDevicesModule.commands.create']],
		'FastyBird\DevicesModule\Commands\InitializeCommand' => [['fbDevicesModule.commands.initialize']],
		'FastyBird\DevicesModule\Models\Devices\IDeviceRepository' => [['fbDevicesModule.models.deviceRepository']],
		'FastyBird\DevicesModule\Models\Devices\DeviceRepository' => [['fbDevicesModule.models.deviceRepository']],
		'FastyBird\DevicesModule\Models\Devices\Properties\IPropertyRepository' => [
			['fbDevicesModule.models.devicePropertyRepository'],
		],
		'FastyBird\DevicesModule\Models\Devices\Properties\PropertyRepository' => [
			['fbDevicesModule.models.devicePropertyRepository'],
		],
		'FastyBird\DevicesModule\Models\Devices\Configuration\IRowRepository' => [
			['fbDevicesModule.models.deviceConfigurationRepository'],
		],
		'FastyBird\DevicesModule\Models\Devices\Configuration\RowRepository' => [
			['fbDevicesModule.models.deviceConfigurationRepository'],
		],
		'FastyBird\DevicesModule\Models\Channels\IChannelRepository' => [['fbDevicesModule.models.channelRepository']],
		'FastyBird\DevicesModule\Models\Channels\ChannelRepository' => [['fbDevicesModule.models.channelRepository']],
		'FastyBird\DevicesModule\Models\Channels\Properties\IPropertyRepository' => [
			['fbDevicesModule.models.channelPropertyRepository'],
		],
		'FastyBird\DevicesModule\Models\Channels\Properties\PropertyRepository' => [
			['fbDevicesModule.models.channelPropertyRepository'],
		],
		'FastyBird\DevicesModule\Models\Channels\Configuration\IRowRepository' => [
			['fbDevicesModule.models.channelConfigurationRepository'],
		],
		'FastyBird\DevicesModule\Models\Channels\Configuration\RowRepository' => [
			['fbDevicesModule.models.channelConfigurationRepository'],
		],
		'FastyBird\DevicesModule\Models\Devices\IDevicesManager' => [['fbDevicesModule.models.devicesManager']],
		'FastyBird\DevicesModule\Models\Devices\DevicesManager' => [['fbDevicesModule.models.devicesManager']],
		'FastyBird\DevicesModule\Models\Devices\Controls\IControlsManager' => [
			['fbDevicesModule.models.devicesControlsManager'],
		],
		'FastyBird\DevicesModule\Models\Devices\Controls\ControlsManager' => [
			['fbDevicesModule.models.devicesControlsManager'],
		],
		'FastyBird\DevicesModule\Models\Devices\Properties\IPropertiesManager' => [
			['fbDevicesModule.models.devicesPropertiesManager'],
		],
		'FastyBird\DevicesModule\Models\Devices\Properties\PropertiesManager' => [
			['fbDevicesModule.models.devicesPropertiesManager'],
		],
		'FastyBird\DevicesModule\Models\Devices\Configuration\IRowsManager' => [
			['fbDevicesModule.models.devicesConfigurationManager'],
		],
		'FastyBird\DevicesModule\Models\Devices\Configuration\RowsManager' => [
			['fbDevicesModule.models.devicesConfigurationManager'],
		],
		'FastyBird\DevicesModule\Models\Devices\Connectors\IConnectorsManager' => [
			['fbDevicesModule.models.devicesConnectorManager'],
		],
		'FastyBird\DevicesModule\Models\Devices\Connectors\ConnectorsManager' => [
			['fbDevicesModule.models.devicesConnectorManager'],
		],
		'FastyBird\DevicesModule\Models\Channels\IChannelsManager' => [['fbDevicesModule.models.channelsManager']],
		'FastyBird\DevicesModule\Models\Channels\ChannelsManager' => [['fbDevicesModule.models.channelsManager']],
		'FastyBird\DevicesModule\Models\Channels\Controls\IControlsManager' => [
			['fbDevicesModule.models.channelsControlsManager'],
		],
		'FastyBird\DevicesModule\Models\Channels\Controls\ControlsManager' => [
			['fbDevicesModule.models.channelsControlsManager'],
		],
		'FastyBird\DevicesModule\Models\Channels\Properties\IPropertiesManager' => [
			['fbDevicesModule.models.channelsPropertiesManager'],
		],
		'FastyBird\DevicesModule\Models\Channels\Properties\PropertiesManager' => [
			['fbDevicesModule.models.channelsPropertiesManager'],
		],
		'FastyBird\DevicesModule\Models\Channels\Configuration\IRowsManager' => [
			['fbDevicesModule.models.channelsConfigurationManager'],
		],
		'FastyBird\DevicesModule\Models\Channels\Configuration\RowsManager' => [
			['fbDevicesModule.models.channelsConfigurationManager'],
		],
		'FastyBird\DevicesModule\Subscribers\EntitiesSubscriber' => [['fbDevicesModule.subscribers.entities']],
		'FastyBird\ApplicationExchange\Consumer\IConsumer' => [
			[
				'fbDevicesModule.consumers.deviceProperty',
				'fbDevicesModule.consumers.channelProperty',
				'fbTriggersModule.consumers.device',
				'fbTriggersModule.consumers.deviceProperty',
				'fbTriggersModule.consumers.channel',
				'fbTriggersModule.consumers.channelProperty',
			],
		],
		'FastyBird\DevicesModule\Consumers\DevicePropertyMessageConsumer' => [['fbDevicesModule.consumers.deviceProperty']],
		'FastyBird\DevicesModule\Consumers\ChannelPropertyMessageConsumer' => [
			['fbDevicesModule.consumers.channelProperty'],
		],
		'FastyBird\DevicesModule\Controllers\BaseV1Controller' => [
			[
				'fbDevicesModule.controllers.devices',
				'fbDevicesModule.controllers.deviceChildren',
				'fbDevicesModule.controllers.deviceProperties',
				'fbDevicesModule.controllers.deviceConfiguration',
				'fbDevicesModule.controllers.deviceConnector',
				'fbDevicesModule.controllers.channels',
				'fbDevicesModule.controllers.channelProperites',
				'fbDevicesModule.controllers.channelConfiguration',
				'fbDevicesModule.controllers.connectors',
			],
		],
		'FastyBird\DevicesModule\Controllers\DevicesV1Controller' => [['fbDevicesModule.controllers.devices']],
		'FastyBird\DevicesModule\Controllers\DeviceChildrenV1Controller' => [
			['fbDevicesModule.controllers.deviceChildren'],
		],
		'FastyBird\DevicesModule\Controllers\DevicePropertiesV1Controller' => [
			['fbDevicesModule.controllers.deviceProperties'],
		],
		'FastyBird\DevicesModule\Controllers\DeviceConfigurationV1Controller' => [
			['fbDevicesModule.controllers.deviceConfiguration'],
		],
		'FastyBird\DevicesModule\Controllers\DeviceConnectorV1Controller' => [
			['fbDevicesModule.controllers.deviceConnector'],
		],
		'FastyBird\DevicesModule\Controllers\ChannelsV1Controller' => [['fbDevicesModule.controllers.channels']],
		'FastyBird\DevicesModule\Controllers\ChannelPropertiesV1Controller' => [
			['fbDevicesModule.controllers.channelProperites'],
		],
		'FastyBird\DevicesModule\Controllers\ChannelConfigurationV1Controller' => [
			['fbDevicesModule.controllers.channelConfiguration'],
		],
		'FastyBird\DevicesModule\Controllers\ConnectorsV1Controller' => [['fbDevicesModule.controllers.connectors']],
		'FastyBird\DevicesModule\Schemas\Devices\DeviceSchema' => [['fbDevicesModule.schemas.device']],
		'FastyBird\DevicesModule\Schemas\Devices\Properties\PropertySchema' => [
			['fbDevicesModule.schemas.device.properties'],
		],
		'FastyBird\DevicesModule\Schemas\Devices\Connectors\ConnectorSchema' => [
			['fbDevicesModule.schemas.device.connector'],
		],
		'FastyBird\DevicesModule\Schemas\Devices\Configuration\RowSchema' => [
			['fbDevicesModule.schemas.device.configuration'],
		],
		'FastyBird\DevicesModule\Schemas\Channels\ChannelSchema' => [['fbDevicesModule.schemas.channel']],
		'FastyBird\DevicesModule\Schemas\Channels\Properties\PropertySchema' => [
			['fbDevicesModule.schemas.channel.property'],
		],
		'FastyBird\DevicesModule\Schemas\Channels\Configuration\RowSchema' => [['fbDevicesModule.schemas.configuration']],
		'FastyBird\DevicesModule\Schemas\Connectors\ConnectorSchema' => [
			['fbDevicesModule.schemas.connector.fbBus', 'fbDevicesModule.schemas.connector.fbMqttV1'],
		],
		'FastyBird\DevicesModule\Schemas\Connectors\FbBusConnectorSchema' => [['fbDevicesModule.schemas.connector.fbBus']],
		'FastyBird\DevicesModule\Schemas\Connectors\FbMqttV1ConnectorSchema' => [
			['fbDevicesModule.schemas.connector.fbMqttV1'],
		],
		'FastyBird\DevicesModule\Hydrators\Devices\DeviceHydrator' => [['fbDevicesModule.hydrators.device']],
		'FastyBird\DevicesModule\Hydrators\Channels\ChannelHydrator' => [['fbDevicesModule.hydrators.channel']],
		'FastyBird\DevicesModule\Hydrators\Devices\Connectors\ConnectorHydrator' => [
			['fbDevicesModule.hydrators.connectors'],
		],
		'FastyBird\DevicesModule\Helpers\PropertyHelper' => [['fbDevicesModule.helpers.property']],
		'FastyBird\DevicesModule\Helpers\NumberHashHelper' => [['fbDevicesModule.helpers.hash']],
		'FastyBird\TriggersModule\Middleware\AccessMiddleware' => [['fbTriggersModule.middleware.access']],
		'FastyBird\TriggersModule\Router\Routes' => [['fbTriggersModule.router.routes']],
		'FastyBird\TriggersModule\Commands\InitializeCommand' => [['fbTriggersModule.commands.initialize']],
		'FastyBird\TriggersModule\Models\Triggers\ITriggerRepository' => [['fbTriggersModule.models.triggerRepository']],
		'FastyBird\TriggersModule\Models\Triggers\TriggerRepository' => [['fbTriggersModule.models.triggerRepository']],
		'FastyBird\TriggersModule\Models\Actions\IActionRepository' => [['fbTriggersModule.models.actionRepository']],
		'FastyBird\TriggersModule\Models\Actions\ActionRepository' => [['fbTriggersModule.models.actionRepository']],
		'FastyBird\TriggersModule\Models\Conditions\IConditionRepository' => [
			['fbTriggersModule.models.conditionRepository'],
		],
		'FastyBird\TriggersModule\Models\Conditions\ConditionRepository' => [
			['fbTriggersModule.models.conditionRepository'],
		],
		'FastyBird\TriggersModule\Models\Notifications\INotificationRepository' => [
			['fbTriggersModule.models.notificationRepository'],
		],
		'FastyBird\TriggersModule\Models\Notifications\NotificationRepository' => [
			['fbTriggersModule.models.notificationRepository'],
		],
		'FastyBird\TriggersModule\Models\Triggers\ITriggersManager' => [['fbTriggersModule.models.triggersManager']],
		'FastyBird\TriggersModule\Models\Triggers\TriggersManager' => [['fbTriggersModule.models.triggersManager']],
		'FastyBird\TriggersModule\Models\Actions\IActionsManager' => [['fbTriggersModule.models.actionsManager']],
		'FastyBird\TriggersModule\Models\Actions\ActionsManager' => [['fbTriggersModule.models.actionsManager']],
		'FastyBird\TriggersModule\Models\Conditions\IConditionsManager' => [['fbTriggersModule.models.conditionsManager']],
		'FastyBird\TriggersModule\Models\Conditions\ConditionsManager' => [['fbTriggersModule.models.conditionsManager']],
		'FastyBird\TriggersModule\Models\Notifications\INotificationsManager' => [
			['fbTriggersModule.models.notificationsManager'],
		],
		'FastyBird\TriggersModule\Models\Notifications\NotificationsManager' => [
			['fbTriggersModule.models.notificationsManager'],
		],
		'FastyBird\TriggersModule\Subscribers\ActionEntitySubscriber' => [['fbTriggersModule.subscribers.actionEntity']],
		'FastyBird\TriggersModule\Subscribers\ConditionEntitySubscriber' => [
			['fbTriggersModule.subscribers.conditionEntity'],
		],
		'FastyBird\TriggersModule\Subscribers\NotificationEntitySubscriber' => [
			['fbTriggersModule.subscribers.notificationEntity'],
		],
		'FastyBird\TriggersModule\Subscribers\EntitiesSubscriber' => [['fbTriggersModule.subscribers.entities']],
		'FastyBird\TriggersModule\Consumers\DeviceMessageConsumer' => [['fbTriggersModule.consumers.device']],
		'FastyBird\TriggersModule\Consumers\DevicePropertyMessageConsumer' => [
			['fbTriggersModule.consumers.deviceProperty'],
		],
		'FastyBird\TriggersModule\Consumers\ChannelMessageConsumer' => [['fbTriggersModule.consumers.channel']],
		'FastyBird\TriggersModule\Consumers\ChannelPropertyMessageConsumer' => [
			['fbTriggersModule.consumers.channelProperty'],
		],
		'FastyBird\TriggersModule\Controllers\BaseV1Controller' => [
			[
				'fbTriggersModule.controllers.triggers',
				'fbTriggersModule.controllers.actions',
				'fbTriggersModule.controllers.conditions',
				'fbTriggersModule.controllers.notifications',
			],
		],
		'FastyBird\TriggersModule\Controllers\TriggersV1Controller' => [['fbTriggersModule.controllers.triggers']],
		'FastyBird\TriggersModule\Controllers\ActionsV1Controller' => [['fbTriggersModule.controllers.actions']],
		'FastyBird\TriggersModule\Controllers\ConditionsV1Controller' => [['fbTriggersModule.controllers.conditions']],
		'FastyBird\TriggersModule\Controllers\NotificationsV1Controller' => [
			['fbTriggersModule.controllers.notifications'],
		],
		'FastyBird\TriggersModule\Schemas\Triggers\TriggerSchema' => [
			['fbTriggersModule.schemas.triggers.automatic', 'fbTriggersModule.schemas.triggers.manual'],
		],
		'FastyBird\TriggersModule\Schemas\Triggers\AutomaticTriggerSchema' => [
			['fbTriggersModule.schemas.triggers.automatic'],
		],
		'FastyBird\TriggersModule\Schemas\Triggers\ManualTriggerSchema' => [['fbTriggersModule.schemas.triggers.manual']],
		'FastyBird\TriggersModule\Schemas\Actions\ActionSchema' => [['fbTriggersModule.schemas.actions.channelProperty']],
		'FastyBird\TriggersModule\Schemas\Actions\ChannelPropertyActionSchema' => [
			['fbTriggersModule.schemas.actions.channelProperty'],
		],
		'FastyBird\TriggersModule\Schemas\Conditions\ConditionSchema' => [
			[
				'fbTriggersModule.schemas.conditions.channelProperty',
				'fbTriggersModule.schemas.conditions.deviceProperty',
				'fbTriggersModule.schemas.conditions.date',
				'fbTriggersModule.schemas.conditions.time',
			],
		],
		'FastyBird\TriggersModule\Schemas\Conditions\ChannelPropertyConditionSchema' => [
			['fbTriggersModule.schemas.conditions.channelProperty'],
		],
		'FastyBird\TriggersModule\Schemas\Conditions\DevicePropertyConditionSchema' => [
			['fbTriggersModule.schemas.conditions.deviceProperty'],
		],
		'FastyBird\TriggersModule\Schemas\Conditions\DateConditionSchema' => [['fbTriggersModule.schemas.conditions.date']],
		'FastyBird\TriggersModule\Schemas\Conditions\TimeConditionSchema' => [['fbTriggersModule.schemas.conditions.time']],
		'FastyBird\TriggersModule\Schemas\Notifications\NotificationSchema' => [
			['fbTriggersModule.schemas.notifications.email', 'fbTriggersModule.schemas.notifications.sms'],
		],
		'FastyBird\TriggersModule\Schemas\Notifications\EmailNotificationSchema' => [
			['fbTriggersModule.schemas.notifications.email'],
		],
		'FastyBird\TriggersModule\Schemas\Notifications\SmsNotificationSchema' => [
			['fbTriggersModule.schemas.notifications.sms'],
		],
		'FastyBird\TriggersModule\Hydrators\Triggers\TriggerHydrator' => [
			['fbTriggersModule.hydrators.triggers.automatic', 'fbTriggersModule.hydrators.triggers.manual'],
		],
		'FastyBird\TriggersModule\Hydrators\Triggers\AutomaticTriggerHydrator' => [
			['fbTriggersModule.hydrators.triggers.automatic'],
		],
		'FastyBird\TriggersModule\Hydrators\Triggers\ManualTriggerHydrator' => [
			['fbTriggersModule.hydrators.triggers.manual'],
		],
		'FastyBird\TriggersModule\Hydrators\Actions\ActionHydrator' => [
			['fbTriggersModule.hydrators.actions.channelProperty'],
		],
		'FastyBird\TriggersModule\Hydrators\Actions\ChannelPropertyActionHydrator' => [
			['fbTriggersModule.hydrators.actions.channelProperty'],
		],
		'FastyBird\TriggersModule\Hydrators\Conditions\PropertyConditionHydrator' => [
			[
				'fbTriggersModule.hydrators.conditions.channelProperty',
				'fbTriggersModule.hydrators.conditions.deviceProperty',
			],
		],
		'FastyBird\TriggersModule\Hydrators\Conditions\ConditionHydrator' => [
			[
				'fbTriggersModule.hydrators.conditions.channelProperty',
				'fbTriggersModule.hydrators.conditions.deviceProperty',
				'fbTriggersModule.hydrators.conditions.time',
			],
		],
		'FastyBird\TriggersModule\Hydrators\Conditions\ChannelPropertyConditionHydrator' => [
			['fbTriggersModule.hydrators.conditions.channelProperty'],
		],
		'FastyBird\TriggersModule\Hydrators\Conditions\DevicePropertyConditionHydrator' => [
			['fbTriggersModule.hydrators.conditions.deviceProperty'],
		],
		'FastyBird\TriggersModule\Hydrators\Conditions\TimeConditionHydrator' => [
			['fbTriggersModule.hydrators.conditions.time'],
		],
		'FastyBird\TriggersModule\Hydrators\Notifications\NotificationHydrator' => [
			['fbTriggersModule.hydrators.notifications.email', 'fbTriggersModule.hydrators.notifications.sms'],
		],
		'FastyBird\TriggersModule\Hydrators\Notifications\EmailNotificationHydrator' => [
			['fbTriggersModule.hydrators.notifications.email'],
		],
		'FastyBird\TriggersModule\Hydrators\Notifications\SmsNotificationHydrator' => [
			['fbTriggersModule.hydrators.notifications.sms'],
		],
		'FastyBird\UIModule\Middleware\AccessMiddleware' => [['fbUiModule.middleware.access']],
		'FastyBird\UIModule\Router\Routes' => [['fbUiModule.router.routes']],
		'FastyBird\UIModule\Commands\InitializeCommand' => [['fbUiModule.commands.initialize']],
		'FastyBird\UIModule\Models\Dashboards\IDashboardRepository' => [['fbUiModule.models.dashboardRepository']],
		'FastyBird\UIModule\Models\Dashboards\DashboardRepository' => [['fbUiModule.models.dashboardRepository']],
		'FastyBird\UIModule\Models\Groups\IGroupRepository' => [['fbUiModule.models.groupRepository']],
		'FastyBird\UIModule\Models\Groups\GroupRepository' => [['fbUiModule.models.groupRepository']],
		'FastyBird\UIModule\Models\Widgets\IWidgetRepository' => [['fbUiModule.models.widgetRepository']],
		'FastyBird\UIModule\Models\Widgets\WidgetRepository' => [['fbUiModule.models.widgetRepository']],
		'FastyBird\UIModule\Models\Widgets\DataSources\IDataSourceRepository' => [
			['fbUiModule.models.dataSourceRepository'],
		],
		'FastyBird\UIModule\Models\Widgets\DataSources\DataSourceRepository' => [
			['fbUiModule.models.dataSourceRepository'],
		],
		'FastyBird\UIModule\Models\Dashboards\IDashboardsManager' => [['fbUiModule.models.dashboardsManager']],
		'FastyBird\UIModule\Models\Dashboards\DashboardsManager' => [['fbUiModule.models.dashboardsManager']],
		'FastyBird\UIModule\Models\Groups\IGroupsManager' => [['fbUiModule.models.groupsManager']],
		'FastyBird\UIModule\Models\Groups\GroupsManager' => [['fbUiModule.models.groupsManager']],
		'FastyBird\UIModule\Models\Widgets\IWidgetsManager' => [['fbUiModule.models.widgetsManager']],
		'FastyBird\UIModule\Models\Widgets\WidgetsManager' => [['fbUiModule.models.widgetsManager']],
		'FastyBird\UIModule\Models\Widgets\DataSources\IDataSourcesManager' => [['fbUiModule.models.dataSourcesManager']],
		'FastyBird\UIModule\Models\Widgets\DataSources\DataSourcesManager' => [['fbUiModule.models.dataSourcesManager']],
		'FastyBird\UIModule\Models\Widgets\Displays\IDisplaysManager' => [['fbUiModule.models.displaysManager']],
		'FastyBird\UIModule\Models\Widgets\Displays\DisplaysManager' => [['fbUiModule.models.displaysManager']],
		'FastyBird\UIModule\Controllers\BaseV1Controller' => [
			[
				'fbUiModule.controllers.dashboards',
				'fbUiModule.controllers.groups',
				'fbUiModule.controllers.widgets',
				'fbUiModule.controllers.dataSources',
				'fbUiModule.controllers.display',
			],
		],
		'FastyBird\UIModule\Controllers\DashboardsV1Controller' => [['fbUiModule.controllers.dashboards']],
		'FastyBird\UIModule\Controllers\GroupsV1Controller' => [['fbUiModule.controllers.groups']],
		'FastyBird\UIModule\Controllers\WidgetsV1Controller' => [['fbUiModule.controllers.widgets']],
		'FastyBird\UIModule\Controllers\DataSourcesV1Controller' => [['fbUiModule.controllers.dataSources']],
		'FastyBird\UIModule\Controllers\DisplayV1Controller' => [['fbUiModule.controllers.display']],
		'FastyBird\UIModule\Schemas\Dashboards\DashboardSchema' => [['fbUiModule.schemas.dashboard']],
		'FastyBird\UIModule\Schemas\Groups\GroupSchema' => [['fbUiModule.schemas.group']],
		'FastyBird\UIModule\Schemas\Widgets\WidgetSchema' => [
			[
				'fbUiModule.schemas.widgets.analogActuator',
				'fbUiModule.schemas.widgets.analogSensor',
				'fbUiModule.schemas.widgets.digitalActuator',
				'fbUiModule.schemas.widgets.digitalSensor',
			],
		],
		'FastyBird\UIModule\Schemas\Widgets\AnalogActuatorSchema' => [['fbUiModule.schemas.widgets.analogActuator']],
		'FastyBird\UIModule\Schemas\Widgets\AnalogSensorSchema' => [['fbUiModule.schemas.widgets.analogSensor']],
		'FastyBird\UIModule\Schemas\Widgets\DigitalActuatorSchema' => [['fbUiModule.schemas.widgets.digitalActuator']],
		'FastyBird\UIModule\Schemas\Widgets\DigitalSensorSchema' => [['fbUiModule.schemas.widgets.digitalSensor']],
		'FastyBird\UIModule\Schemas\Widgets\DataSources\DataSourceSchema' => [
			['fbUiModule.schemas.dataSources.channelProperty'],
		],
		'FastyBird\UIModule\Schemas\Widgets\DataSources\ChannelPropertyDataSourceSchema' => [
			['fbUiModule.schemas.dataSources.channelProperty'],
		],
		'FastyBird\UIModule\Schemas\Widgets\Display\DisplaySchema' => [
			[
				'fbUiModule.schemas.display.analogValue',
				'fbUiModule.schemas.display.button',
				'fbUiModule.schemas.display.chartGraph',
				'fbUiModule.schemas.display.digitalValue',
				'fbUiModule.schemas.display.gauge',
				'fbUiModule.schemas.display.groupedButton',
				'fbUiModule.schemas.display.slider',
			],
		],
		'FastyBird\UIModule\Schemas\Widgets\Display\AnalogValueSchema' => [['fbUiModule.schemas.display.analogValue']],
		'FastyBird\UIModule\Schemas\Widgets\Display\ButtonSchema' => [['fbUiModule.schemas.display.button']],
		'FastyBird\UIModule\Schemas\Widgets\Display\ChartGraphSchema' => [['fbUiModule.schemas.display.chartGraph']],
		'FastyBird\UIModule\Schemas\Widgets\Display\DigitalValueSchema' => [['fbUiModule.schemas.display.digitalValue']],
		'FastyBird\UIModule\Schemas\Widgets\Display\GaugeSchema' => [['fbUiModule.schemas.display.gauge']],
		'FastyBird\UIModule\Schemas\Widgets\Display\GroupedButtonSchema' => [['fbUiModule.schemas.display.groupedButton']],
		'FastyBird\UIModule\Schemas\Widgets\Display\SliderSchema' => [['fbUiModule.schemas.display.slider']],
		'FastyBird\UIModule\Hydrators\Dashboards\DashboardHydrator' => [['fbUiModule.hydrators.dashboard']],
		'FastyBird\UIModule\Hydrators\Groups\GroupHydrator' => [['fbUiModule.hydrators.group']],
		'FastyBird\UIModule\Hydrators\Widgets\WidgetHydrator' => [
			[
				'fbUiModule.hydrators.widgets.analogActuator',
				'fbUiModule.hydrators.widgets.analogSensor',
				'fbUiModule.hydrators.widgets.digitalActuator',
				'fbUiModule.hydrators.widgets.digitalSensor',
			],
		],
		'FastyBird\UIModule\Hydrators\Widgets\AnalogActuatorWidgetHydrator' => [
			['fbUiModule.hydrators.widgets.analogActuator'],
		],
		'FastyBird\UIModule\Hydrators\Widgets\AnalogSensorWidgetHydrator' => [
			['fbUiModule.hydrators.widgets.analogSensor'],
		],
		'FastyBird\UIModule\Hydrators\Widgets\DigitalActuatorWidgetHydrator' => [
			['fbUiModule.hydrators.widgets.digitalActuator'],
		],
		'FastyBird\UIModule\Hydrators\Widgets\DigitalSensorWidgetHydrator' => [
			['fbUiModule.hydrators.widgets.digitalSensor'],
		],
		'FastyBird\UIModule\Hydrators\Widgets\DataSources\DataSourceHydrator' => [
			['fbUiModule.hydrators.dataSources.channelProperty'],
		],
		'FastyBird\UIModule\Hydrators\Widgets\DataSources\ChannelPropertyDataSourceHydrator' => [
			['fbUiModule.hydrators.dataSources.channelProperty'],
		],
		'FastyBird\UIModule\Hydrators\Widgets\Displays\DisplayHydrator' => [
			[
				'fbUiModule.hydrators.widgets.analogValue',
				'fbUiModule.hydrators.widgets.button',
				'fbUiModule.hydrators.widgets.chartGraph',
				'fbUiModule.hydrators.widgets.digitalValue',
				'fbUiModule.hydrators.widgets.gauge',
				'fbUiModule.hydrators.widgets.groupedButton',
				'fbUiModule.hydrators.widgets.slider',
			],
		],
		'FastyBird\UIModule\Hydrators\Widgets\Displays\AnalogValueHydrator' => [
			['fbUiModule.hydrators.widgets.analogValue'],
		],
		'FastyBird\UIModule\Hydrators\Widgets\Displays\ButtonHydrator' => [['fbUiModule.hydrators.widgets.button']],
		'FastyBird\UIModule\Hydrators\Widgets\Displays\ChartGraphHydrator' => [['fbUiModule.hydrators.widgets.chartGraph']],
		'FastyBird\UIModule\Hydrators\Widgets\Displays\DigitalValueHydrator' => [
			['fbUiModule.hydrators.widgets.digitalValue'],
		],
		'FastyBird\UIModule\Hydrators\Widgets\Displays\GaugeHydrator' => [['fbUiModule.hydrators.widgets.gauge']],
		'FastyBird\UIModule\Hydrators\Widgets\Displays\GroupedButtonHydrator' => [
			['fbUiModule.hydrators.widgets.groupedButton'],
		],
		'FastyBird\UIModule\Hydrators\Widgets\Displays\SliderHydrator' => [['fbUiModule.hydrators.widgets.slider']],
		'libphonenumber\PhoneNumberUtil' => [['ipubPhone.libphone.utils']],
		'libphonenumber\geocoding\PhoneNumberOfflineGeocoder' => [['ipubPhone.libphone.geoCoder']],
		'libphonenumber\ShortNumberInfo' => [['ipubPhone.libphone.shortNumber']],
		'libphonenumber\PhoneNumberToCarrierMapper' => [['ipubPhone.libphone.mapper.carrier']],
		'libphonenumber\PhoneNumberToTimeZonesMapper' => [['ipubPhone.libphone.mapper.timezone']],
		'IPub\Phone\Phone' => [['ipubPhone.phone']],
		'IPub\DoctrinePhone\Events\PhoneObjectSubscriber' => [['ipubDoctrinePhone.subscriber']],
		'Consistence\Doctrine\Enum\EnumPostLoadEntityListener' => [['ipubDoctrineConsistence.subscriber']],
		'IPub\DoctrineConsistence\Events\EnumSubscriber' => [['ipubDoctrineConsistence.subscriber']],
		'IPub\DoctrineCrud\Mapping\IEntityMapper' => [2 => ['ipubDoctrineCrud.entity.mapper']],
		'IPub\DoctrineCrud\Mapping\EntityMapper' => [2 => ['ipubDoctrineCrud.entity.mapper']],
		'IPub\DoctrineCrud\Crud\Create\IEntityCreator' => [2 => ['ipubDoctrineCrud.entity.creator']],
		'IPub\DoctrineCrud\Crud\Update\IEntityUpdater' => [2 => ['ipubDoctrineCrud.entity.updater']],
		'IPub\DoctrineCrud\Crud\Delete\IEntityDeleter' => [2 => ['ipubDoctrineCrud.entity.deleter']],
		'IPub\DoctrineCrud\Crud\IEntityCrudFactory' => [['ipubDoctrineCrud.crud']],
		'IPub\DoctrineTimestampable\Configuration' => [['ipubDoctrineTimestampable.configuration']],
		'IPub\DoctrineTimestampable\Mapping\Driver\Timestampable' => [['ipubDoctrineTimestampable.driver']],
		'IPub\DoctrineTimestampable\Events\TimestampableSubscriber' => [['ipubDoctrineTimestampable.subscriber']],
		'IPub\DoctrineDynamicDiscriminatorMap\Events\DynamicDiscriminatorSubscriber' => [
			['ipubDoctrineDynamicDiscriminatorMap.subscriber'],
		],
		'FastyBird\MiniServer\Commands\InitializeCommand' => [['06']],
		'FastyBird\MiniServer\Application\IApplication' => [['07']],
		'FastyBird\MiniServer\Application\Application' => [['07']],
		'FastyBird\DevicesModule\Models\States\IPropertiesManager' => [['08']],
		'FastyBird\MiniServer\Models\PropertiesManager' => [['08']],
		'FastyBird\DevicesModule\Models\States\IPropertyRepository' => [['09']],
		'FastyBird\MiniServer\Models\PropertyRepository' => [['09']],
		'FastyBird\MiniServer\Subscribers\PropertyConditionSubscriber' => [['010']],
		'FastyBird\MiniServer\Subscribers\PropertyActionSubscriber' => [['011']],
		'FastyBird\MiniServer\Publisher\RedisPublisher' => [2 => ['012']],
	];


	public function __construct(array $params = [])
	{
		parent::__construct($params);
		$this->parameters += [
			'logger' => ['level' => 100],
			'database' => [
				'version' => 5.7,
				'host' => '127.0.0.1',
				'port' => 3306,
				'driver' => 'pdo_mysql',
				'memory' => false,
				'dbname' => 'miniserver_app',
				'username' => 'root',
				'password' => 'root',
			],
			'redis' => ['host' => '51.91.109.122', 'port' => 5432, 'username' => null, 'password' => null],
			'server' => ['address' => '0.0.0.0', 'port' => 8000],
			'security' => [
				'issuer' => 'com.fastybird.miniserver',
				'signature' => 'g3xHbkELpMD9LRqW4WmJkHL7kz2bdNYAQJyEuFVzR3k=',
			],
			'appDir' => '/Users/akadlec/Development/FastyBird/other/miniserver-app/vendor/fastybird/bootstrap/src/Boot/../../../../..',
			'wwwDir' => '/Users/akadlec/Development/FastyBird/other/miniserver-app/www',
			'vendorDir' => '/Users/akadlec/Development/FastyBird/other/miniserver-app/vendor',
			'debugMode' => true,
			'productionMode' => false,
			'consoleMode' => false,
			'tempDir' => '/Users/akadlec/Development/FastyBird/other/miniserver-app/vendor/fastybird/bootstrap/src/Boot/../../../../../var/temp',
			'logsDir' => '/Users/akadlec/Development/FastyBird/other/miniserver-app/vendor/fastybird/bootstrap/src/Boot/../../../../../var/logs',
		];
	}


	public function createService01(): FastyBird\DateTimeFactory\DateTimeFactory
	{
		return new FastyBird\DateTimeFactory\DateTimeFactory;
	}


	public function createService02(): FastyBird\Database\Middleware\PagingMiddleware
	{
		return new FastyBird\Database\Middleware\PagingMiddleware;
	}


	public function createService03(): FastyBird\ModulesMetadata\Schemas\Validator
	{
		return new FastyBird\ModulesMetadata\Schemas\Validator;
	}


	public function createService04(): FastyBird\ModulesMetadata\Loaders\MetadataLoader
	{
		return new FastyBird\ModulesMetadata\Loaders\MetadataLoader($this->getService('03'));
	}


	public function createService05(): FastyBird\ModulesMetadata\Loaders\SchemaLoader
	{
		return new FastyBird\ModulesMetadata\Loaders\SchemaLoader;
	}


	public function createService06(): FastyBird\MiniServer\Commands\InitializeCommand
	{
		return new FastyBird\MiniServer\Commands\InitializeCommand(
			$this->getService('fbAuthModule.models.accountRepository'),
			$this->getService('fbAuthModule.models.roleRepository'),
			$this->getService('fbAuthModule.models.rolesManager'),
			$this->getService('nettrineOrm.managerRegistry'),
			$this->getService('contributteMonolog.logger.default')
		);
	}


	public function createService07(): FastyBird\MiniServer\Application\Application
	{
		return new FastyBird\MiniServer\Application\Application($this->getService('fbWebServer.routing.router'));
	}


	public function createService08(): FastyBird\MiniServer\Models\PropertiesManager
	{
		return new FastyBird\MiniServer\Models\PropertiesManager(
			$this->getService('fbRedisDbStoragePlugin.model.statesManagerFactory'),
			$this->getService('01')
		);
	}


	public function createService09(): FastyBird\MiniServer\Models\PropertyRepository
	{
		return new FastyBird\MiniServer\Models\PropertyRepository($this->getService('fbRedisDbStoragePlugin.model.stateRepositoryFactory'));
	}


	public function createService010(): FastyBird\MiniServer\Subscribers\PropertyConditionSubscriber
	{
		return new FastyBird\MiniServer\Subscribers\PropertyConditionSubscriber;
	}


	public function createService011(): FastyBird\MiniServer\Subscribers\PropertyActionSubscriber
	{
		return new FastyBird\MiniServer\Subscribers\PropertyActionSubscriber;
	}


	public function createService012(): FastyBird\MiniServer\Publisher\RedisPublisher
	{
		return new FastyBird\MiniServer\Publisher\RedisPublisher(
			$this->getService('contributteMonolog.logger.default'),
			'51.91.109.122',
			5432
		);
	}


	public function createServiceContainer(): Container_9769c2aee7
	{
		return $this;
	}


	public function createServiceContributteEvents__dispatcher(): Symfony\Component\EventDispatcher\EventDispatcherInterface
	{
		$service = new Contributte\EventDispatcher\LazyEventDispatcher($this);
		$service->addSubscriberLazy('FastyBird\ApplicationEvents\Events\StartupEvent', 'fbDatabase.subscribers.databaseCheck');
		$service->addSubscriberLazy('FastyBird\ApplicationEvents\Events\RequestEvent', 'fbDatabase.subscribers.databaseCheck');
		$service->addSubscriberLazy('FastyBird\ApplicationEvents\Events\ResponseEvent', 'fbDatabase.subscribers.databaseCheck');
		$service->addSubscriberLazy(
			'FastyBird\ApplicationExchange\Events\MessageConsumedEvent',
			'fbDatabase.subscribers.exchange'
		);
		return $service;
	}


	public function createServiceContributteMonolog__logger__default(): Monolog\Logger
	{
		return new Monolog\Logger(
			'default',
			[
				$this->getService('contributteMonolog.logger.default.handler.0'),
				$this->getService('contributteMonolog.logger.default.handler.1'),
			],
			[
				$this->getService('contributteMonolog.logger.default.processor.0'),
				$this->getService('contributteMonolog.logger.default.processor.1'),
			]
		);
	}


	public function createServiceContributteMonolog__logger__default__handler__0(): Monolog\Handler\StreamHandler
	{
		return new Monolog\Handler\StreamHandler('php://stdout');
	}


	public function createServiceContributteMonolog__logger__default__handler__1(): Mangoweb\MonologTracyHandler\TracyHandler
	{
		return new Mangoweb\MonologTracyHandler\TracyHandler('/Users/akadlec/Development/FastyBird/other/miniserver-app/vendor/fastybird/bootstrap/src/Boot/../../../../../var/logs');
	}


	public function createServiceContributteMonolog__logger__default__processor__0(): Monolog\Processor\MemoryPeakUsageProcessor
	{
		return new Monolog\Processor\MemoryPeakUsageProcessor;
	}


	public function createServiceContributteMonolog__logger__default__processor__1(): Mangoweb\MonologTracyHandler\TracyProcessor
	{
		return new Mangoweb\MonologTracyHandler\TracyProcessor;
	}


	public function createServiceContributteMonolog__psrToTracyAdapter(): Tracy\Bridges\Psr\PsrToTracyLoggerAdapter
	{
		return new Tracy\Bridges\Psr\PsrToTracyLoggerAdapter($this->getService('contributteMonolog.logger.default'));
	}


	public function createServiceContributteMonolog__psrToTracyLazyAdapter(): Contributte\Monolog\Tracy\LazyTracyLogger
	{
		return new Contributte\Monolog\Tracy\LazyTracyLogger('contributteMonolog.psrToTracyAdapter', $this);
	}


	public function createServiceContributteTranslation__configCacheFactory(): Symfony\Component\Config\ConfigCacheFactory
	{
		return new Symfony\Component\Config\ConfigCacheFactory(true);
	}


	public function createServiceContributteTranslation__fallbackResolver(): Contributte\Translation\FallbackResolver
	{
		return new Contributte\Translation\FallbackResolver;
	}


	public function createServiceContributteTranslation__loaderNeon(): Contributte\Translation\Loaders\Neon
	{
		return new Contributte\Translation\Loaders\Neon;
	}


	public function createServiceContributteTranslation__localeResolver(): Contributte\Translation\LocaleResolver
	{
		return new Contributte\Translation\LocaleResolver($this);
	}


	public function createServiceContributteTranslation__tracyPanel(): Contributte\Translation\Tracy\Panel
	{
		$service = new Contributte\Translation\Tracy\Panel($this->getService('contributteTranslation.translator'));
		$service->addResource(
			'neon',
			'/Users/akadlec/Development/FastyBird/other/miniserver-app/vendor/fastybird/ui-module/src/DI/../Translations/ui-module.en_US.neon',
			'en_US',
			'ui-module'
		);
		$service->addResource(
			'neon',
			'/Users/akadlec/Development/FastyBird/other/miniserver-app/vendor/fastybird/triggers-module/src/DI/../Translations/triggers-module.en_US.neon',
			'en_US',
			'triggers-module'
		);
		$service->addResource(
			'neon',
			'/Users/akadlec/Development/FastyBird/other/miniserver-app/vendor/fastybird/devices-module/src/DI/../Translations/commands.en_US.neon',
			'en_US',
			'commands'
		);
		$service->addResource(
			'neon',
			'/Users/akadlec/Development/FastyBird/other/miniserver-app/vendor/fastybird/devices-module/src/DI/../Translations/devices-module.en_US.neon',
			'en_US',
			'devices-module'
		);
		$service->addResource(
			'neon',
			'/Users/akadlec/Development/FastyBird/other/miniserver-app/vendor/fastybird/auth-module/src/DI/../Translations/auth-module.en_US.neon',
			'en_US',
			'auth-module'
		);
		$service->addResource(
			'neon',
			'/Users/akadlec/Development/FastyBird/other/miniserver-app/vendor/fastybird/auth-module/src/DI/../Translations/commands.en_US.neon',
			'en_US',
			'commands'
		);
		$service->addResource(
			'neon',
			'/Users/akadlec/Development/FastyBird/other/miniserver-app/vendor/fastybird/json-api/src/DI/../Translations/jsonApi.en_US.neon',
			'en_US',
			'jsonApi'
		);
		return $service;
	}


	public function createServiceContributteTranslation__translator(): Contributte\Translation\Translator
	{
		$service = new Contributte\Translation\Translator(
			$this->getService('contributteTranslation.localeResolver'),
			$this->getService('contributteTranslation.fallbackResolver'),
			'en_US',
			'/Users/akadlec/Development/FastyBird/other/miniserver-app/vendor/fastybird/bootstrap/src/Boot/../../../../../var/temp/cache/translation',
			true
		);
		$service->setLocalesWhitelist(null);
		$service->setConfigCacheFactory($this->getService('contributteTranslation.configCacheFactory'));
		$service->setFallbackLocales(['en_US', 'en']);
		$service->addLoader('neon', $this->getService('contributteTranslation.loaderNeon'));
		$service->addResource(
			'neon',
			'/Users/akadlec/Development/FastyBird/other/miniserver-app/vendor/fastybird/ui-module/src/DI/../Translations/ui-module.en_US.neon',
			'en_US',
			'ui-module'
		);
		$service->addResource(
			'neon',
			'/Users/akadlec/Development/FastyBird/other/miniserver-app/vendor/fastybird/triggers-module/src/DI/../Translations/triggers-module.en_US.neon',
			'en_US',
			'triggers-module'
		);
		$service->addResource(
			'neon',
			'/Users/akadlec/Development/FastyBird/other/miniserver-app/vendor/fastybird/devices-module/src/DI/../Translations/commands.en_US.neon',
			'en_US',
			'commands'
		);
		$service->addResource(
			'neon',
			'/Users/akadlec/Development/FastyBird/other/miniserver-app/vendor/fastybird/devices-module/src/DI/../Translations/devices-module.en_US.neon',
			'en_US',
			'devices-module'
		);
		$service->addResource(
			'neon',
			'/Users/akadlec/Development/FastyBird/other/miniserver-app/vendor/fastybird/auth-module/src/DI/../Translations/auth-module.en_US.neon',
			'en_US',
			'auth-module'
		);
		$service->addResource(
			'neon',
			'/Users/akadlec/Development/FastyBird/other/miniserver-app/vendor/fastybird/auth-module/src/DI/../Translations/commands.en_US.neon',
			'en_US',
			'commands'
		);
		$service->addResource(
			'neon',
			'/Users/akadlec/Development/FastyBird/other/miniserver-app/vendor/fastybird/json-api/src/DI/../Translations/jsonApi.en_US.neon',
			'en_US',
			'jsonApi'
		);
		return $service;
	}


	public function createServiceFbApplicationExchange__publisher(): FastyBird\ApplicationExchange\Publisher\PublisherProxy
	{
		$service = new FastyBird\ApplicationExchange\Publisher\PublisherProxy($this->getService('contributteEvents.dispatcher'));
		$service->registerPublisher($this->getService('012'));
		return $service;
	}


	public function createServiceFbAuthModule__commands__create(): FastyBird\AuthModule\Commands\Accounts\CreateCommand
	{
		return new FastyBird\AuthModule\Commands\Accounts\CreateCommand(
			$this->getService('fbAuthModule.models.accountsManager'),
			$this->getService('fbAuthModule.models.emailRepository'),
			$this->getService('fbAuthModule.models.emailsManager'),
			$this->getService('fbAuthModule.models.identitiesManager'),
			$this->getService('fbAuthModule.models.roleRepository'),
			$this->getService('contributteTranslation.translator'),
			$this->getService('nettrineOrm.managerRegistry')
		);
	}


	public function createServiceFbAuthModule__commands__initialize(): FastyBird\AuthModule\Commands\InitializeCommand
	{
		return new FastyBird\AuthModule\Commands\InitializeCommand(
			$this->getService('fbAuthModule.models.accountRepository'),
			$this->getService('fbAuthModule.models.roleRepository'),
			$this->getService('fbAuthModule.models.rolesManager'),
			$this->getService('nettrineOrm.managerRegistry'),
			$this->getService('fbDatabase.helpers.database')
		);
	}


	public function createServiceFbAuthModule__controllers__account(): FastyBird\AuthModule\Controllers\AccountV1Controller
	{
		$service = new FastyBird\AuthModule\Controllers\AccountV1Controller(
			$this->getService('fbAuthModule.hydrators.accounts.profile'),
			$this->getService('fbAuthModule.models.accountsManager')
		);
		$service->injectDateFactory($this->getService('01'));
		$service->injectLogger($this->getService('contributteMonolog.logger.default'));
		$service->injectManagerRegistry($this->getService('nettrineOrm.managerRegistry'));
		$service->injectTranslator($this->getService('contributteTranslation.translator'));
		$service->injectUser($this->getService('security.user'));
		return $service;
	}


	public function createServiceFbAuthModule__controllers__accountEmails(): FastyBird\AuthModule\Controllers\AccountEmailsV1Controller
	{
		$service = new FastyBird\AuthModule\Controllers\AccountEmailsV1Controller(
			$this->getService('fbAuthModule.hydrators.emails.profile'),
			$this->getService('fbAuthModule.models.emailRepository'),
			$this->getService('fbAuthModule.models.emailsManager'),
			$this->getService('fbAuthModule.security.hash')
		);
		$service->injectDateFactory($this->getService('01'));
		$service->injectLogger($this->getService('contributteMonolog.logger.default'));
		$service->injectManagerRegistry($this->getService('nettrineOrm.managerRegistry'));
		$service->injectTranslator($this->getService('contributteTranslation.translator'));
		$service->injectUser($this->getService('security.user'));
		return $service;
	}


	public function createServiceFbAuthModule__controllers__accountIdentities(): FastyBird\AuthModule\Controllers\AccountIdentitiesV1Controller
	{
		$service = new FastyBird\AuthModule\Controllers\AccountIdentitiesV1Controller(
			$this->getService('fbAuthModule.models.identityRepository'),
			$this->getService('fbAuthModule.models.identitiesManager')
		);
		$service->injectDateFactory($this->getService('01'));
		$service->injectLogger($this->getService('contributteMonolog.logger.default'));
		$service->injectManagerRegistry($this->getService('nettrineOrm.managerRegistry'));
		$service->injectTranslator($this->getService('contributteTranslation.translator'));
		$service->injectUser($this->getService('security.user'));
		return $service;
	}


	public function createServiceFbAuthModule__controllers__accounts(): FastyBird\AuthModule\Controllers\AccountsV1Controller
	{
		$service = new FastyBird\AuthModule\Controllers\AccountsV1Controller(
			$this->getService('fbAuthModule.hydrators.accounts'),
			$this->getService('fbAuthModule.models.accountRepository'),
			$this->getService('fbAuthModule.models.accountsManager'),
			$this->getService('fbAuthModule.models.identitiesManager')
		);
		$service->injectDateFactory($this->getService('01'));
		$service->injectLogger($this->getService('contributteMonolog.logger.default'));
		$service->injectManagerRegistry($this->getService('nettrineOrm.managerRegistry'));
		$service->injectTranslator($this->getService('contributteTranslation.translator'));
		$service->injectUser($this->getService('security.user'));
		return $service;
	}


	public function createServiceFbAuthModule__controllers__emails(): FastyBird\AuthModule\Controllers\EmailsV1Controller
	{
		$service = new FastyBird\AuthModule\Controllers\EmailsV1Controller(
			$this->getService('fbAuthModule.hydrators.emails.email'),
			$this->getService('fbAuthModule.models.emailRepository'),
			$this->getService('fbAuthModule.models.emailsManager'),
			$this->getService('fbAuthModule.models.accountRepository'),
			$this->getService('fbAuthModule.security.hash')
		);
		$service->injectDateFactory($this->getService('01'));
		$service->injectLogger($this->getService('contributteMonolog.logger.default'));
		$service->injectManagerRegistry($this->getService('nettrineOrm.managerRegistry'));
		$service->injectTranslator($this->getService('contributteTranslation.translator'));
		$service->injectUser($this->getService('security.user'));
		return $service;
	}


	public function createServiceFbAuthModule__controllers__identities(): FastyBird\AuthModule\Controllers\IdentitiesV1Controller
	{
		$service = new FastyBird\AuthModule\Controllers\IdentitiesV1Controller(
			$this->getService('fbAuthModule.hydrators.identities.profile'),
			$this->getService('fbAuthModule.models.identityRepository'),
			$this->getService('fbAuthModule.models.identitiesManager'),
			$this->getService('fbAuthModule.models.accountRepository')
		);
		$service->injectDateFactory($this->getService('01'));
		$service->injectLogger($this->getService('contributteMonolog.logger.default'));
		$service->injectManagerRegistry($this->getService('nettrineOrm.managerRegistry'));
		$service->injectTranslator($this->getService('contributteTranslation.translator'));
		$service->injectUser($this->getService('security.user'));
		return $service;
	}


	public function createServiceFbAuthModule__controllers__public(): FastyBird\AuthModule\Controllers\PublicV1Controller
	{
		$service = new FastyBird\AuthModule\Controllers\PublicV1Controller(
			$this->getService('fbAuthModule.models.identityRepository'),
			$this->getService('fbAuthModule.models.accountsManager'),
			$this->getService('fbAuthModule.security.hash')
		);
		$service->injectDateFactory($this->getService('01'));
		$service->injectLogger($this->getService('contributteMonolog.logger.default'));
		$service->injectManagerRegistry($this->getService('nettrineOrm.managerRegistry'));
		$service->injectTranslator($this->getService('contributteTranslation.translator'));
		$service->injectUser($this->getService('security.user'));
		return $service;
	}


	public function createServiceFbAuthModule__controllers__roleChildren(): FastyBird\AuthModule\Controllers\RoleChildrenV1Controller
	{
		$service = new FastyBird\AuthModule\Controllers\RoleChildrenV1Controller($this->getService('fbAuthModule.models.roleRepository'));
		$service->injectDateFactory($this->getService('01'));
		$service->injectLogger($this->getService('contributteMonolog.logger.default'));
		$service->injectManagerRegistry($this->getService('nettrineOrm.managerRegistry'));
		$service->injectTranslator($this->getService('contributteTranslation.translator'));
		$service->injectUser($this->getService('security.user'));
		return $service;
	}


	public function createServiceFbAuthModule__controllers__roles(): FastyBird\AuthModule\Controllers\RolesV1Controller
	{
		$service = new FastyBird\AuthModule\Controllers\RolesV1Controller(
			$this->getService('fbAuthModule.models.roleRepository'),
			$this->getService('fbAuthModule.models.rolesManager'),
			$this->getService('fbAuthModule.hydrators.role')
		);
		$service->injectDateFactory($this->getService('01'));
		$service->injectLogger($this->getService('contributteMonolog.logger.default'));
		$service->injectManagerRegistry($this->getService('nettrineOrm.managerRegistry'));
		$service->injectTranslator($this->getService('contributteTranslation.translator'));
		$service->injectUser($this->getService('security.user'));
		return $service;
	}


	public function createServiceFbAuthModule__controllers__session(): FastyBird\AuthModule\Controllers\SessionV1Controller
	{
		$service = new FastyBird\AuthModule\Controllers\SessionV1Controller(
			$this->getService('fbSimpleAuth.doctrine.tokenRepository'),
			$this->getService('fbSimpleAuth.doctrine.tokensManager'),
			$this->getService('fbSimpleAuth.token.reader'),
			$this->getService('fbSimpleAuth.token.builder')
		);
		$service->injectDateFactory($this->getService('01'));
		$service->injectLogger($this->getService('contributteMonolog.logger.default'));
		$service->injectManagerRegistry($this->getService('nettrineOrm.managerRegistry'));
		$service->injectTranslator($this->getService('contributteTranslation.translator'));
		$service->injectUser($this->getService('security.user'));
		return $service;
	}


	public function createServiceFbAuthModule__hydrators__accounts(): FastyBird\AuthModule\Hydrators\Accounts\AccountHydrator
	{
		return new FastyBird\AuthModule\Hydrators\Accounts\AccountHydrator(
			$this->getService('nettrineOrm.managerRegistry'),
			$this->getService('contributteTranslation.translator')
		);
	}


	public function createServiceFbAuthModule__hydrators__accounts__profile(): FastyBird\AuthModule\Hydrators\Accounts\ProfileAccountHydrator
	{
		return new FastyBird\AuthModule\Hydrators\Accounts\ProfileAccountHydrator(
			$this->getService('nettrineOrm.managerRegistry'),
			$this->getService('contributteTranslation.translator')
		);
	}


	public function createServiceFbAuthModule__hydrators__emails__email(): FastyBird\AuthModule\Hydrators\Emails\EmailHydrator
	{
		return new FastyBird\AuthModule\Hydrators\Emails\EmailHydrator(
			$this->getService('nettrineOrm.managerRegistry'),
			$this->getService('contributteTranslation.translator')
		);
	}


	public function createServiceFbAuthModule__hydrators__emails__profile(): FastyBird\AuthModule\Hydrators\Emails\ProfileEmailHydrator
	{
		return new FastyBird\AuthModule\Hydrators\Emails\ProfileEmailHydrator(
			$this->getService('nettrineOrm.managerRegistry'),
			$this->getService('contributteTranslation.translator')
		);
	}


	public function createServiceFbAuthModule__hydrators__identities__profile(): FastyBird\AuthModule\Hydrators\Identities\IdentityHydrator
	{
		return new FastyBird\AuthModule\Hydrators\Identities\IdentityHydrator(
			$this->getService('nettrineOrm.managerRegistry'),
			$this->getService('contributteTranslation.translator')
		);
	}


	public function createServiceFbAuthModule__hydrators__role(): FastyBird\AuthModule\Hydrators\Roles\RoleHydrator
	{
		return new FastyBird\AuthModule\Hydrators\Roles\RoleHydrator(
			$this->getService('nettrineOrm.managerRegistry'),
			$this->getService('contributteTranslation.translator')
		);
	}


	public function createServiceFbAuthModule__middleware__access(): FastyBird\AuthModule\Middleware\AccessMiddleware
	{
		return new FastyBird\AuthModule\Middleware\AccessMiddleware($this->getService('contributteTranslation.translator'));
	}


	public function createServiceFbAuthModule__middleware__urlFormat(): FastyBird\AuthModule\Middleware\UrlFormatMiddleware
	{
		return new FastyBird\AuthModule\Middleware\UrlFormatMiddleware(
			$this->getService('security.user'),
			$this->getService('contributteTranslation.translator')
		);
	}


	public function createServiceFbAuthModule__models__accountRepository(): FastyBird\AuthModule\Models\Accounts\AccountRepository
	{
		return new FastyBird\AuthModule\Models\Accounts\AccountRepository($this->getService('nettrineOrm.managerRegistry'));
	}


	public function createServiceFbAuthModule__models__accountsManager(): FastyBird\AuthModule\Models\Accounts\AccountsManager
	{
		return new FastyBird\AuthModule\Models\Accounts\AccountsManager($this->getService('ipubDoctrineCrud.crud')->create('FastyBird\AuthModule\Entities\Accounts\Account'));
	}


	public function createServiceFbAuthModule__models__emailRepository(): FastyBird\AuthModule\Models\Emails\EmailRepository
	{
		return new FastyBird\AuthModule\Models\Emails\EmailRepository($this->getService('nettrineOrm.managerRegistry'));
	}


	public function createServiceFbAuthModule__models__emailsManager(): FastyBird\AuthModule\Models\Emails\EmailsManager
	{
		return new FastyBird\AuthModule\Models\Emails\EmailsManager($this->getService('ipubDoctrineCrud.crud')->create('FastyBird\AuthModule\Entities\Emails\Email'));
	}


	public function createServiceFbAuthModule__models__identitiesManager(): FastyBird\AuthModule\Models\Identities\IdentitiesManager
	{
		return new FastyBird\AuthModule\Models\Identities\IdentitiesManager($this->getService('ipubDoctrineCrud.crud')->create('FastyBird\AuthModule\Entities\Identities\Identity'));
	}


	public function createServiceFbAuthModule__models__identityRepository(): FastyBird\AuthModule\Models\Identities\IdentityRepository
	{
		return new FastyBird\AuthModule\Models\Identities\IdentityRepository($this->getService('nettrineOrm.managerRegistry'));
	}


	public function createServiceFbAuthModule__models__roleRepository(): FastyBird\AuthModule\Models\Roles\RoleRepository
	{
		return new FastyBird\AuthModule\Models\Roles\RoleRepository($this->getService('nettrineOrm.managerRegistry'));
	}


	public function createServiceFbAuthModule__models__rolesManager(): FastyBird\AuthModule\Models\Roles\RolesManager
	{
		return new FastyBird\AuthModule\Models\Roles\RolesManager($this->getService('ipubDoctrineCrud.crud')->create('FastyBird\AuthModule\Entities\Roles\Role'));
	}


	public function createServiceFbAuthModule__router__routes(): FastyBird\AuthModule\Router\Routes
	{
		return new FastyBird\AuthModule\Router\Routes(
			$this->getService('fbAuthModule.controllers.public'),
			$this->getService('fbAuthModule.controllers.session'),
			$this->getService('fbAuthModule.controllers.account'),
			$this->getService('fbAuthModule.controllers.accountEmails'),
			$this->getService('fbAuthModule.controllers.accountIdentities'),
			$this->getService('fbAuthModule.controllers.accounts'),
			$this->getService('fbAuthModule.controllers.emails'),
			$this->getService('fbAuthModule.controllers.identities'),
			$this->getService('fbAuthModule.controllers.roles'),
			$this->getService('fbAuthModule.controllers.roleChildren'),
			$this->getService('fbAuthModule.middleware.access'),
			$this->getService('fbSimpleAuth.middleware.access'),
			$this->getService('fbSimpleAuth.middleware.user')
		);
	}


	public function createServiceFbAuthModule__schemas__account(): FastyBird\AuthModule\Schemas\Accounts\AccountSchema
	{
		return new FastyBird\AuthModule\Schemas\Accounts\AccountSchema($this->getService('fbWebServer.routing.router'));
	}


	public function createServiceFbAuthModule__schemas__accountIdentity(): FastyBird\AuthModule\Schemas\Identities\IdentitySchema
	{
		return new FastyBird\AuthModule\Schemas\Identities\IdentitySchema($this->getService('fbWebServer.routing.router'));
	}


	public function createServiceFbAuthModule__schemas__email(): FastyBird\AuthModule\Schemas\Emails\EmailSchema
	{
		return new FastyBird\AuthModule\Schemas\Emails\EmailSchema($this->getService('fbWebServer.routing.router'));
	}


	public function createServiceFbAuthModule__schemas__role(): FastyBird\AuthModule\Schemas\Roles\RoleSchema
	{
		return new FastyBird\AuthModule\Schemas\Roles\RoleSchema(
			$this->getService('fbAuthModule.models.roleRepository'),
			$this->getService('fbWebServer.routing.router')
		);
	}


	public function createServiceFbAuthModule__schemas__session(): FastyBird\AuthModule\Schemas\Sessions\SessionSchema
	{
		return new FastyBird\AuthModule\Schemas\Sessions\SessionSchema($this->getService('fbWebServer.routing.router'));
	}


	public function createServiceFbAuthModule__security__authenticator(): FastyBird\AuthModule\Security\Authenticator
	{
		return new FastyBird\AuthModule\Security\Authenticator($this->getService('fbAuthModule.models.identityRepository'));
	}


	public function createServiceFbAuthModule__security__hash(): FastyBird\AuthModule\Helpers\SecurityHash
	{
		return new FastyBird\AuthModule\Helpers\SecurityHash($this->getService('01'));
	}


	public function createServiceFbAuthModule__security__identityFactory(): FastyBird\AuthModule\Security\IdentityFactory
	{
		return new FastyBird\AuthModule\Security\IdentityFactory($this->getService('fbSimpleAuth.doctrine.tokenRepository'));
	}


	public function createServiceFbAuthModule__subscribers__accountEntity(): FastyBird\AuthModule\Subscribers\AccountEntitySubscriber
	{
		return new FastyBird\AuthModule\Subscribers\AccountEntitySubscriber(
			$this->getService('fbAuthModule.models.accountRepository'),
			$this->getService('fbAuthModule.models.roleRepository')
		);
	}


	public function createServiceFbAuthModule__subscribers__emailEntity(): FastyBird\AuthModule\Subscribers\EmailEntitySubscriber
	{
		return new FastyBird\AuthModule\Subscribers\EmailEntitySubscriber($this->getService('fbAuthModule.models.emailRepository'));
	}


	public function createServiceFbDatabase__helpers__database(): FastyBird\Database\Helpers\Database
	{
		return new FastyBird\Database\Helpers\Database($this->getService('nettrineOrm.managerRegistry'));
	}


	public function createServiceFbDatabase__subscribers__databaseCheck(): FastyBird\Database\Subscribers\DatabaseCheckSubscriber
	{
		return new FastyBird\Database\Subscribers\DatabaseCheckSubscriber($this->getService('fbDatabase.helpers.database'));
	}


	public function createServiceFbDatabase__subscribers__exchange(): FastyBird\Database\Subscribers\ExchangeSubscriber
	{
		return new FastyBird\Database\Subscribers\ExchangeSubscriber($this->getService('nettrineOrm.managerRegistry'));
	}


	public function createServiceFbDevicesModule__commands__create(): FastyBird\DevicesModule\Commands\Devices\CreateCommand
	{
		return new FastyBird\DevicesModule\Commands\Devices\CreateCommand(
			$this->getService('fbDevicesModule.models.devicesManager'),
			$this->getService('contributteTranslation.translator')
		);
	}


	public function createServiceFbDevicesModule__commands__initialize(): FastyBird\DevicesModule\Commands\InitializeCommand
	{
		return new FastyBird\DevicesModule\Commands\InitializeCommand($this->getService('fbDatabase.helpers.database'));
	}


	public function createServiceFbDevicesModule__consumers__channelProperty(): FastyBird\DevicesModule\Consumers\ChannelPropertyMessageConsumer
	{
		return new FastyBird\DevicesModule\Consumers\ChannelPropertyMessageConsumer(
			$this->getService('fbDevicesModule.models.deviceRepository'),
			$this->getService('fbDevicesModule.models.channelRepository'),
			$this->getService('fbDevicesModule.models.channelPropertyRepository'),
			$this->getService('fbDevicesModule.helpers.property'),
			$this->getService('08'),
			$this->getService('09'),
			$this->getService('contributteMonolog.logger.default')
		);
	}


	public function createServiceFbDevicesModule__consumers__deviceProperty(): FastyBird\DevicesModule\Consumers\DevicePropertyMessageConsumer
	{
		return new FastyBird\DevicesModule\Consumers\DevicePropertyMessageConsumer(
			$this->getService('fbDevicesModule.models.deviceRepository'),
			$this->getService('fbDevicesModule.models.devicePropertyRepository'),
			$this->getService('fbDevicesModule.helpers.property'),
			$this->getService('08'),
			$this->getService('09'),
			$this->getService('contributteMonolog.logger.default')
		);
	}


	public function createServiceFbDevicesModule__controllers__channelConfiguration(): FastyBird\DevicesModule\Controllers\ChannelConfigurationV1Controller
	{
		$service = new FastyBird\DevicesModule\Controllers\ChannelConfigurationV1Controller(
			$this->getService('fbDevicesModule.models.deviceRepository'),
			$this->getService('fbDevicesModule.models.channelRepository'),
			$this->getService('fbDevicesModule.models.channelConfigurationRepository')
		);
		$service->injectLogger($this->getService('contributteMonolog.logger.default'));
		$service->injectManagerRegistry($this->getService('nettrineOrm.managerRegistry'));
		$service->injectTranslator($this->getService('contributteTranslation.translator'));
		return $service;
	}


	public function createServiceFbDevicesModule__controllers__channelProperites(): FastyBird\DevicesModule\Controllers\ChannelPropertiesV1Controller
	{
		$service = new FastyBird\DevicesModule\Controllers\ChannelPropertiesV1Controller(
			$this->getService('fbDevicesModule.models.deviceRepository'),
			$this->getService('fbDevicesModule.models.channelRepository'),
			$this->getService('fbDevicesModule.models.channelPropertyRepository')
		);
		$service->injectLogger($this->getService('contributteMonolog.logger.default'));
		$service->injectManagerRegistry($this->getService('nettrineOrm.managerRegistry'));
		$service->injectTranslator($this->getService('contributteTranslation.translator'));
		return $service;
	}


	public function createServiceFbDevicesModule__controllers__channels(): FastyBird\DevicesModule\Controllers\ChannelsV1Controller
	{
		$service = new FastyBird\DevicesModule\Controllers\ChannelsV1Controller(
			$this->getService('fbDevicesModule.models.deviceRepository'),
			$this->getService('fbDevicesModule.models.channelRepository'),
			$this->getService('fbDevicesModule.models.channelsManager'),
			$this->getService('fbDevicesModule.hydrators.channel')
		);
		$service->injectLogger($this->getService('contributteMonolog.logger.default'));
		$service->injectManagerRegistry($this->getService('nettrineOrm.managerRegistry'));
		$service->injectTranslator($this->getService('contributteTranslation.translator'));
		return $service;
	}


	public function createServiceFbDevicesModule__controllers__connectors(): FastyBird\DevicesModule\Controllers\ConnectorsV1Controller
	{
		$service = new FastyBird\DevicesModule\Controllers\ConnectorsV1Controller;
		$service->injectLogger($this->getService('contributteMonolog.logger.default'));
		$service->injectManagerRegistry($this->getService('nettrineOrm.managerRegistry'));
		$service->injectTranslator($this->getService('contributteTranslation.translator'));
		return $service;
	}


	public function createServiceFbDevicesModule__controllers__deviceChildren(): FastyBird\DevicesModule\Controllers\DeviceChildrenV1Controller
	{
		$service = new FastyBird\DevicesModule\Controllers\DeviceChildrenV1Controller($this->getService('fbDevicesModule.models.deviceRepository'));
		$service->injectLogger($this->getService('contributteMonolog.logger.default'));
		$service->injectManagerRegistry($this->getService('nettrineOrm.managerRegistry'));
		$service->injectTranslator($this->getService('contributteTranslation.translator'));
		return $service;
	}


	public function createServiceFbDevicesModule__controllers__deviceConfiguration(): FastyBird\DevicesModule\Controllers\DeviceConfigurationV1Controller
	{
		$service = new FastyBird\DevicesModule\Controllers\DeviceConfigurationV1Controller(
			$this->getService('fbDevicesModule.models.deviceRepository'),
			$this->getService('fbDevicesModule.models.deviceConfigurationRepository')
		);
		$service->injectLogger($this->getService('contributteMonolog.logger.default'));
		$service->injectManagerRegistry($this->getService('nettrineOrm.managerRegistry'));
		$service->injectTranslator($this->getService('contributteTranslation.translator'));
		return $service;
	}


	public function createServiceFbDevicesModule__controllers__deviceConnector(): FastyBird\DevicesModule\Controllers\DeviceConnectorV1Controller
	{
		$service = new FastyBird\DevicesModule\Controllers\DeviceConnectorV1Controller(
			$this->getService('fbDevicesModule.models.deviceRepository'),
			$this->getService('fbDevicesModule.models.devicesConnectorManager'),
			$this->getService('fbDevicesModule.hydrators.connectors')
		);
		$service->injectLogger($this->getService('contributteMonolog.logger.default'));
		$service->injectManagerRegistry($this->getService('nettrineOrm.managerRegistry'));
		$service->injectTranslator($this->getService('contributteTranslation.translator'));
		return $service;
	}


	public function createServiceFbDevicesModule__controllers__deviceProperties(): FastyBird\DevicesModule\Controllers\DevicePropertiesV1Controller
	{
		$service = new FastyBird\DevicesModule\Controllers\DevicePropertiesV1Controller(
			$this->getService('fbDevicesModule.models.deviceRepository'),
			$this->getService('fbDevicesModule.models.devicePropertyRepository')
		);
		$service->injectLogger($this->getService('contributteMonolog.logger.default'));
		$service->injectManagerRegistry($this->getService('nettrineOrm.managerRegistry'));
		$service->injectTranslator($this->getService('contributteTranslation.translator'));
		return $service;
	}


	public function createServiceFbDevicesModule__controllers__devices(): FastyBird\DevicesModule\Controllers\DevicesV1Controller
	{
		$service = new FastyBird\DevicesModule\Controllers\DevicesV1Controller(
			$this->getService('fbDevicesModule.models.deviceRepository'),
			$this->getService('fbDevicesModule.models.devicesManager'),
			$this->getService('fbDevicesModule.models.channelRepository'),
			$this->getService('fbDevicesModule.models.channelsManager'),
			$this->getService('fbDevicesModule.hydrators.device')
		);
		$service->injectLogger($this->getService('contributteMonolog.logger.default'));
		$service->injectManagerRegistry($this->getService('nettrineOrm.managerRegistry'));
		$service->injectTranslator($this->getService('contributteTranslation.translator'));
		return $service;
	}


	public function createServiceFbDevicesModule__helpers__hash(): FastyBird\DevicesModule\Helpers\NumberHashHelper
	{
		return new FastyBird\DevicesModule\Helpers\NumberHashHelper;
	}


	public function createServiceFbDevicesModule__helpers__property(): FastyBird\DevicesModule\Helpers\PropertyHelper
	{
		return new FastyBird\DevicesModule\Helpers\PropertyHelper;
	}


	public function createServiceFbDevicesModule__hydrators__channel(): FastyBird\DevicesModule\Hydrators\Channels\ChannelHydrator
	{
		return new FastyBird\DevicesModule\Hydrators\Channels\ChannelHydrator(
			$this->getService('nettrineOrm.managerRegistry'),
			$this->getService('contributteTranslation.translator')
		);
	}


	public function createServiceFbDevicesModule__hydrators__connectors(): FastyBird\DevicesModule\Hydrators\Devices\Connectors\ConnectorHydrator
	{
		return new FastyBird\DevicesModule\Hydrators\Devices\Connectors\ConnectorHydrator(
			$this->getService('nettrineOrm.managerRegistry'),
			$this->getService('contributteTranslation.translator')
		);
	}


	public function createServiceFbDevicesModule__hydrators__device(): FastyBird\DevicesModule\Hydrators\Devices\DeviceHydrator
	{
		return new FastyBird\DevicesModule\Hydrators\Devices\DeviceHydrator(
			$this->getService('nettrineOrm.managerRegistry'),
			$this->getService('contributteTranslation.translator')
		);
	}


	public function createServiceFbDevicesModule__middleware__access(): FastyBird\DevicesModule\Middleware\AccessMiddleware
	{
		return new FastyBird\DevicesModule\Middleware\AccessMiddleware($this->getService('contributteTranslation.translator'));
	}


	public function createServiceFbDevicesModule__models__channelConfigurationRepository(): FastyBird\DevicesModule\Models\Channels\Configuration\RowRepository
	{
		return new FastyBird\DevicesModule\Models\Channels\Configuration\RowRepository($this->getService('nettrineOrm.managerRegistry'));
	}


	public function createServiceFbDevicesModule__models__channelPropertyRepository(): FastyBird\DevicesModule\Models\Channels\Properties\PropertyRepository
	{
		return new FastyBird\DevicesModule\Models\Channels\Properties\PropertyRepository($this->getService('nettrineOrm.managerRegistry'));
	}


	public function createServiceFbDevicesModule__models__channelRepository(): FastyBird\DevicesModule\Models\Channels\ChannelRepository
	{
		return new FastyBird\DevicesModule\Models\Channels\ChannelRepository($this->getService('nettrineOrm.managerRegistry'));
	}


	public function createServiceFbDevicesModule__models__channelsConfigurationManager(): FastyBird\DevicesModule\Models\Channels\Configuration\RowsManager
	{
		return new FastyBird\DevicesModule\Models\Channels\Configuration\RowsManager($this->getService('ipubDoctrineCrud.crud')->create('FastyBird\DevicesModule\Entities\Channels\Configuration\Row'));
	}


	public function createServiceFbDevicesModule__models__channelsControlsManager(): FastyBird\DevicesModule\Models\Channels\Controls\ControlsManager
	{
		return new FastyBird\DevicesModule\Models\Channels\Controls\ControlsManager($this->getService('ipubDoctrineCrud.crud')->create('FastyBird\DevicesModule\Entities\Channels\Controls\Control'));
	}


	public function createServiceFbDevicesModule__models__channelsManager(): FastyBird\DevicesModule\Models\Channels\ChannelsManager
	{
		return new FastyBird\DevicesModule\Models\Channels\ChannelsManager($this->getService('ipubDoctrineCrud.crud')->create('FastyBird\DevicesModule\Entities\Channels\Channel'));
	}


	public function createServiceFbDevicesModule__models__channelsPropertiesManager(): FastyBird\DevicesModule\Models\Channels\Properties\PropertiesManager
	{
		return new FastyBird\DevicesModule\Models\Channels\Properties\PropertiesManager($this->getService('ipubDoctrineCrud.crud')->create('FastyBird\DevicesModule\Entities\Channels\Properties\Property'));
	}


	public function createServiceFbDevicesModule__models__deviceConfigurationRepository(): FastyBird\DevicesModule\Models\Devices\Configuration\RowRepository
	{
		return new FastyBird\DevicesModule\Models\Devices\Configuration\RowRepository($this->getService('nettrineOrm.managerRegistry'));
	}


	public function createServiceFbDevicesModule__models__devicePropertyRepository(): FastyBird\DevicesModule\Models\Devices\Properties\PropertyRepository
	{
		return new FastyBird\DevicesModule\Models\Devices\Properties\PropertyRepository($this->getService('nettrineOrm.managerRegistry'));
	}


	public function createServiceFbDevicesModule__models__deviceRepository(): FastyBird\DevicesModule\Models\Devices\DeviceRepository
	{
		return new FastyBird\DevicesModule\Models\Devices\DeviceRepository($this->getService('nettrineOrm.managerRegistry'));
	}


	public function createServiceFbDevicesModule__models__devicesConfigurationManager(): FastyBird\DevicesModule\Models\Devices\Configuration\RowsManager
	{
		return new FastyBird\DevicesModule\Models\Devices\Configuration\RowsManager($this->getService('ipubDoctrineCrud.crud')->create('FastyBird\DevicesModule\Entities\Devices\Configuration\Row'));
	}


	public function createServiceFbDevicesModule__models__devicesConnectorManager(): FastyBird\DevicesModule\Models\Devices\Connectors\ConnectorsManager
	{
		return new FastyBird\DevicesModule\Models\Devices\Connectors\ConnectorsManager($this->getService('ipubDoctrineCrud.crud')->create('FastyBird\DevicesModule\Entities\Devices\Connectors\Connector'));
	}


	public function createServiceFbDevicesModule__models__devicesControlsManager(): FastyBird\DevicesModule\Models\Devices\Controls\ControlsManager
	{
		return new FastyBird\DevicesModule\Models\Devices\Controls\ControlsManager($this->getService('ipubDoctrineCrud.crud')->create('FastyBird\DevicesModule\Entities\Devices\Controls\Control'));
	}


	public function createServiceFbDevicesModule__models__devicesManager(): FastyBird\DevicesModule\Models\Devices\DevicesManager
	{
		return new FastyBird\DevicesModule\Models\Devices\DevicesManager($this->getService('ipubDoctrineCrud.crud')->create('FastyBird\DevicesModule\Entities\Devices\Device'));
	}


	public function createServiceFbDevicesModule__models__devicesPropertiesManager(): FastyBird\DevicesModule\Models\Devices\Properties\PropertiesManager
	{
		return new FastyBird\DevicesModule\Models\Devices\Properties\PropertiesManager($this->getService('ipubDoctrineCrud.crud')->create('FastyBird\DevicesModule\Entities\Devices\Properties\Property'));
	}


	public function createServiceFbDevicesModule__router__routes(): FastyBird\DevicesModule\Router\Routes
	{
		return new FastyBird\DevicesModule\Router\Routes(
			$this->getService('fbDevicesModule.controllers.devices'),
			$this->getService('fbDevicesModule.controllers.deviceChildren'),
			$this->getService('fbDevicesModule.controllers.deviceProperties'),
			$this->getService('fbDevicesModule.controllers.deviceConfiguration'),
			$this->getService('fbDevicesModule.controllers.deviceConnector'),
			$this->getService('fbDevicesModule.controllers.channels'),
			$this->getService('fbDevicesModule.controllers.channelProperites'),
			$this->getService('fbDevicesModule.controllers.channelConfiguration'),
			$this->getService('fbDevicesModule.controllers.connectors'),
			$this->getService('fbDevicesModule.middleware.access'),
			$this->getService('fbSimpleAuth.middleware.access'),
			$this->getService('fbSimpleAuth.middleware.user')
		);
	}


	public function createServiceFbDevicesModule__schemas__channel(): FastyBird\DevicesModule\Schemas\Channels\ChannelSchema
	{
		return new FastyBird\DevicesModule\Schemas\Channels\ChannelSchema($this->getService('fbWebServer.routing.router'));
	}


	public function createServiceFbDevicesModule__schemas__channel__property(): FastyBird\DevicesModule\Schemas\Channels\Properties\PropertySchema
	{
		return new FastyBird\DevicesModule\Schemas\Channels\Properties\PropertySchema(
			$this->getService('fbWebServer.routing.router'),
			$this->getService('09')
		);
	}


	public function createServiceFbDevicesModule__schemas__configuration(): FastyBird\DevicesModule\Schemas\Channels\Configuration\RowSchema
	{
		return new FastyBird\DevicesModule\Schemas\Channels\Configuration\RowSchema($this->getService('fbWebServer.routing.router'));
	}


	public function createServiceFbDevicesModule__schemas__connector__fbBus(): FastyBird\DevicesModule\Schemas\Connectors\FbBusConnectorSchema
	{
		return new FastyBird\DevicesModule\Schemas\Connectors\FbBusConnectorSchema($this->getService('fbWebServer.routing.router'));
	}


	public function createServiceFbDevicesModule__schemas__connector__fbMqttV1(): FastyBird\DevicesModule\Schemas\Connectors\FbMqttV1ConnectorSchema
	{
		return new FastyBird\DevicesModule\Schemas\Connectors\FbMqttV1ConnectorSchema($this->getService('fbWebServer.routing.router'));
	}


	public function createServiceFbDevicesModule__schemas__device(): FastyBird\DevicesModule\Schemas\Devices\DeviceSchema
	{
		return new FastyBird\DevicesModule\Schemas\Devices\DeviceSchema(
			$this->getService('fbDevicesModule.models.deviceRepository'),
			$this->getService('fbDevicesModule.models.channelRepository'),
			$this->getService('fbWebServer.routing.router')
		);
	}


	public function createServiceFbDevicesModule__schemas__device__configuration(): FastyBird\DevicesModule\Schemas\Devices\Configuration\RowSchema
	{
		return new FastyBird\DevicesModule\Schemas\Devices\Configuration\RowSchema($this->getService('fbWebServer.routing.router'));
	}


	public function createServiceFbDevicesModule__schemas__device__connector(): FastyBird\DevicesModule\Schemas\Devices\Connectors\ConnectorSchema
	{
		return new FastyBird\DevicesModule\Schemas\Devices\Connectors\ConnectorSchema($this->getService('fbWebServer.routing.router'));
	}


	public function createServiceFbDevicesModule__schemas__device__properties(): FastyBird\DevicesModule\Schemas\Devices\Properties\PropertySchema
	{
		return new FastyBird\DevicesModule\Schemas\Devices\Properties\PropertySchema(
			$this->getService('fbWebServer.routing.router'),
			$this->getService('09')
		);
	}


	public function createServiceFbDevicesModule__subscribers__entities(): FastyBird\DevicesModule\Subscribers\EntitiesSubscriber
	{
		return new FastyBird\DevicesModule\Subscribers\EntitiesSubscriber(
			$this->getService('fbDevicesModule.helpers.hash'),
			$this->getService('01'),
			$this->getService('fbApplicationExchange.publisher'),
			$this->getService('nettrineOrm.entityManagerDecorator'),
			$this->getService('09')
		);
	}


	public function createServiceFbJsonApi__middlewares__jsonapi(): FastyBird\JsonApi\Middleware\JsonApiMiddleware
	{
		return new FastyBird\JsonApi\Middleware\JsonApiMiddleware(
			$this->getService('fbWebServer.routing.responseFactory'),
			$this,
			'FastyBird team',
			'FastyBird s.r.o',
			$this->getService('contributteMonolog.logger.default')
		);
	}


	public function createServiceFbJsonApi__schemas__container(): FastyBird\JsonApi\JsonApi\JsonApiSchemaContainer
	{
		$service = new FastyBird\JsonApi\JsonApi\JsonApiSchemaContainer;
		$service->add($this->getService('fbAuthModule.schemas.account'));
		$service->add($this->getService('fbAuthModule.schemas.email'));
		$service->add($this->getService('fbAuthModule.schemas.accountIdentity'));
		$service->add($this->getService('fbAuthModule.schemas.role'));
		$service->add($this->getService('fbAuthModule.schemas.session'));
		$service->add($this->getService('fbDevicesModule.schemas.device'));
		$service->add($this->getService('fbDevicesModule.schemas.device.properties'));
		$service->add($this->getService('fbDevicesModule.schemas.device.connector'));
		$service->add($this->getService('fbDevicesModule.schemas.device.configuration'));
		$service->add($this->getService('fbDevicesModule.schemas.channel'));
		$service->add($this->getService('fbDevicesModule.schemas.channel.property'));
		$service->add($this->getService('fbDevicesModule.schemas.configuration'));
		$service->add($this->getService('fbDevicesModule.schemas.connector.fbBus'));
		$service->add($this->getService('fbDevicesModule.schemas.connector.fbMqttV1'));
		$service->add($this->getService('fbTriggersModule.schemas.triggers.automatic'));
		$service->add($this->getService('fbTriggersModule.schemas.triggers.manual'));
		$service->add($this->getService('fbTriggersModule.schemas.actions.channelProperty'));
		$service->add($this->getService('fbTriggersModule.schemas.conditions.channelProperty'));
		$service->add($this->getService('fbTriggersModule.schemas.conditions.deviceProperty'));
		$service->add($this->getService('fbTriggersModule.schemas.conditions.date'));
		$service->add($this->getService('fbTriggersModule.schemas.conditions.time'));
		$service->add($this->getService('fbTriggersModule.schemas.notifications.email'));
		$service->add($this->getService('fbTriggersModule.schemas.notifications.sms'));
		$service->add($this->getService('fbUiModule.schemas.dashboard'));
		$service->add($this->getService('fbUiModule.schemas.group'));
		$service->add($this->getService('fbUiModule.schemas.widgets.analogActuator'));
		$service->add($this->getService('fbUiModule.schemas.widgets.analogSensor'));
		$service->add($this->getService('fbUiModule.schemas.widgets.digitalActuator'));
		$service->add($this->getService('fbUiModule.schemas.widgets.digitalSensor'));
		$service->add($this->getService('fbUiModule.schemas.dataSources.channelProperty'));
		$service->add($this->getService('fbUiModule.schemas.display.analogValue'));
		$service->add($this->getService('fbUiModule.schemas.display.button'));
		$service->add($this->getService('fbUiModule.schemas.display.chartGraph'));
		$service->add($this->getService('fbUiModule.schemas.display.digitalValue'));
		$service->add($this->getService('fbUiModule.schemas.display.gauge'));
		$service->add($this->getService('fbUiModule.schemas.display.groupedButton'));
		$service->add($this->getService('fbUiModule.schemas.display.slider'));
		return $service;
	}


	public function createServiceFbRedisDbStoragePlugin__client(): FastyBird\RedisDbStoragePlugin\Client\Client
	{
		return new FastyBird\RedisDbStoragePlugin\Client\Client($this->getService('fbRedisDbStoragePlugin.connection'));
	}


	public function createServiceFbRedisDbStoragePlugin__connection(): FastyBird\RedisDbStoragePlugin\Connections\Connection
	{
		return new FastyBird\RedisDbStoragePlugin\Connections\Connection('51.91.109.122', 5432);
	}


	public function createServiceFbRedisDbStoragePlugin__model__stateRepositoryFactory(): FastyBird\RedisDbStoragePlugin\Models\StateRepositoryFactory
	{
		return new FastyBird\RedisDbStoragePlugin\Models\StateRepositoryFactory(
			$this->getService('fbRedisDbStoragePlugin.client'),
			$this->getService('contributteMonolog.logger.default')
		);
	}


	public function createServiceFbRedisDbStoragePlugin__model__statesManagerFactory(): FastyBird\RedisDbStoragePlugin\Models\StatesManagerFactory
	{
		return new FastyBird\RedisDbStoragePlugin\Models\StatesManagerFactory(
			$this->getService('fbRedisDbStoragePlugin.client'),
			$this->getService('contributteMonolog.logger.default')
		);
	}


	public function createServiceFbSimpleAuth__auth(): FastyBird\SimpleAuth\Auth
	{
		return new FastyBird\SimpleAuth\Auth(
			$this->getService('fbSimpleAuth.token.reader'),
			$this->getService('fbAuthModule.security.identityFactory'),
			$this->getService('security.user')
		);
	}


	public function createServiceFbSimpleAuth__doctrine__driver(): FastyBird\SimpleAuth\Mapping\Driver\Owner
	{
		return new FastyBird\SimpleAuth\Mapping\Driver\Owner;
	}


	public function createServiceFbSimpleAuth__doctrine__subscriber(): FastyBird\SimpleAuth\Subscribers\UserSubscriber
	{
		return new FastyBird\SimpleAuth\Subscribers\UserSubscriber(
			$this->getService('fbSimpleAuth.doctrine.driver'),
			$this->getService('security.user')
		);
	}


	public function createServiceFbSimpleAuth__doctrine__tokenRepository(): FastyBird\SimpleAuth\Models\Tokens\TokenRepository
	{
		return new FastyBird\SimpleAuth\Models\Tokens\TokenRepository($this->getService('nettrineOrm.managerRegistry'));
	}


	public function createServiceFbSimpleAuth__doctrine__tokensManager(): FastyBird\SimpleAuth\Models\Tokens\TokensManager
	{
		return new FastyBird\SimpleAuth\Models\Tokens\TokensManager($this->getService('ipubDoctrineCrud.crud')->create('FastyBird\SimpleAuth\Entities\Tokens\Token'));
	}


	public function createServiceFbSimpleAuth__middleware__access(): FastyBird\SimpleAuth\Middleware\AccessMiddleware
	{
		return new FastyBird\SimpleAuth\Middleware\AccessMiddleware($this->getService('security.user'));
	}


	public function createServiceFbSimpleAuth__middleware__user(): FastyBird\SimpleAuth\Middleware\UserMiddleware
	{
		return new FastyBird\SimpleAuth\Middleware\UserMiddleware($this->getService('fbSimpleAuth.auth'));
	}


	public function createServiceFbSimpleAuth__security__userStorage(): FastyBird\SimpleAuth\Security\UserStorage
	{
		return new FastyBird\SimpleAuth\Security\UserStorage;
	}


	public function createServiceFbSimpleAuth__token__builder(): FastyBird\SimpleAuth\Security\TokenBuilder
	{
		return new FastyBird\SimpleAuth\Security\TokenBuilder(
			'g3xHbkELpMD9LRqW4WmJkHL7kz2bdNYAQJyEuFVzR3k=',
			'com.fastybird.miniserver',
			$this->getService('01')
		);
	}


	public function createServiceFbSimpleAuth__token__reader(): FastyBird\SimpleAuth\Security\TokenReader
	{
		return new FastyBird\SimpleAuth\Security\TokenReader($this->getService('fbSimpleAuth.token.validator'));
	}


	public function createServiceFbSimpleAuth__token__validator(): FastyBird\SimpleAuth\Security\TokenValidator
	{
		return new FastyBird\SimpleAuth\Security\TokenValidator(
			'g3xHbkELpMD9LRqW4WmJkHL7kz2bdNYAQJyEuFVzR3k=',
			'com.fastybird.miniserver',
			$this->getService('01')
		);
	}


	public function createServiceFbTriggersModule__commands__initialize(): FastyBird\TriggersModule\Commands\InitializeCommand
	{
		return new FastyBird\TriggersModule\Commands\InitializeCommand($this->getService('fbDatabase.helpers.database'));
	}


	public function createServiceFbTriggersModule__consumers__channel(): FastyBird\TriggersModule\Consumers\ChannelMessageConsumer
	{
		return new FastyBird\TriggersModule\Consumers\ChannelMessageConsumer(
			$this->getService('fbTriggersModule.models.triggerRepository'),
			$this->getService('fbTriggersModule.models.triggersManager'),
			$this->getService('fbTriggersModule.models.actionRepository'),
			$this->getService('fbTriggersModule.models.actionsManager'),
			$this->getService('fbTriggersModule.models.conditionRepository'),
			$this->getService('fbTriggersModule.models.conditionsManager'),
			$this->getService('contributteMonolog.logger.default')
		);
	}


	public function createServiceFbTriggersModule__consumers__channelProperty(): FastyBird\TriggersModule\Consumers\ChannelPropertyMessageConsumer
	{
		return new FastyBird\TriggersModule\Consumers\ChannelPropertyMessageConsumer(
			$this->getService('fbTriggersModule.models.triggerRepository'),
			$this->getService('fbTriggersModule.models.triggersManager'),
			$this->getService('fbTriggersModule.models.actionRepository'),
			$this->getService('fbTriggersModule.models.actionsManager'),
			$this->getService('fbTriggersModule.models.conditionRepository'),
			$this->getService('fbTriggersModule.models.conditionsManager'),
			$this->getService('fbApplicationExchange.publisher'),
			$this->getService('contributteMonolog.logger.default')
		);
	}


	public function createServiceFbTriggersModule__consumers__device(): FastyBird\TriggersModule\Consumers\DeviceMessageConsumer
	{
		return new FastyBird\TriggersModule\Consumers\DeviceMessageConsumer(
			$this->getService('fbTriggersModule.models.triggerRepository'),
			$this->getService('fbTriggersModule.models.triggersManager'),
			$this->getService('fbTriggersModule.models.actionRepository'),
			$this->getService('fbTriggersModule.models.actionsManager'),
			$this->getService('fbTriggersModule.models.conditionRepository'),
			$this->getService('fbTriggersModule.models.conditionsManager'),
			$this->getService('contributteMonolog.logger.default')
		);
	}


	public function createServiceFbTriggersModule__consumers__deviceProperty(): FastyBird\TriggersModule\Consumers\DevicePropertyMessageConsumer
	{
		return new FastyBird\TriggersModule\Consumers\DevicePropertyMessageConsumer(
			$this->getService('fbTriggersModule.models.conditionRepository'),
			$this->getService('fbTriggersModule.models.conditionsManager'),
			$this->getService('fbApplicationExchange.publisher'),
			$this->getService('contributteMonolog.logger.default')
		);
	}


	public function createServiceFbTriggersModule__controllers__actions(): FastyBird\TriggersModule\Controllers\ActionsV1Controller
	{
		$service = new FastyBird\TriggersModule\Controllers\ActionsV1Controller(
			$this->getService('fbTriggersModule.models.triggerRepository'),
			$this->getService('fbTriggersModule.models.actionRepository'),
			$this->getService('fbTriggersModule.models.actionsManager'),
			$this->getService('fbTriggersModule.hydrators.actions.channelProperty')
		);
		$service->injectLogger($this->getService('contributteMonolog.logger.default'));
		$service->injectManagerRegistry($this->getService('nettrineOrm.managerRegistry'));
		$service->injectTranslator($this->getService('contributteTranslation.translator'));
		return $service;
	}


	public function createServiceFbTriggersModule__controllers__conditions(): FastyBird\TriggersModule\Controllers\ConditionsV1Controller
	{
		$service = new FastyBird\TriggersModule\Controllers\ConditionsV1Controller(
			$this->getService('fbTriggersModule.models.triggerRepository'),
			$this->getService('fbTriggersModule.models.conditionRepository'),
			$this->getService('fbTriggersModule.models.conditionsManager'),
			$this->getService('fbTriggersModule.hydrators.conditions.deviceProperty'),
			$this->getService('fbTriggersModule.hydrators.conditions.channelProperty'),
			$this->getService('fbTriggersModule.hydrators.conditions.time')
		);
		$service->injectLogger($this->getService('contributteMonolog.logger.default'));
		$service->injectManagerRegistry($this->getService('nettrineOrm.managerRegistry'));
		$service->injectTranslator($this->getService('contributteTranslation.translator'));
		return $service;
	}


	public function createServiceFbTriggersModule__controllers__notifications(): FastyBird\TriggersModule\Controllers\NotificationsV1Controller
	{
		$service = new FastyBird\TriggersModule\Controllers\NotificationsV1Controller(
			$this->getService('fbTriggersModule.models.triggerRepository'),
			$this->getService('fbTriggersModule.models.notificationRepository'),
			$this->getService('fbTriggersModule.models.notificationsManager'),
			$this->getService('fbTriggersModule.hydrators.notifications.sms'),
			$this->getService('fbTriggersModule.hydrators.notifications.email')
		);
		$service->injectLogger($this->getService('contributteMonolog.logger.default'));
		$service->injectManagerRegistry($this->getService('nettrineOrm.managerRegistry'));
		$service->injectTranslator($this->getService('contributteTranslation.translator'));
		return $service;
	}


	public function createServiceFbTriggersModule__controllers__triggers(): FastyBird\TriggersModule\Controllers\TriggersV1Controller
	{
		$service = new FastyBird\TriggersModule\Controllers\TriggersV1Controller(
			$this->getService('fbTriggersModule.models.triggerRepository'),
			$this->getService('fbTriggersModule.models.triggersManager'),
			$this->getService('fbTriggersModule.hydrators.triggers.automatic'),
			$this->getService('fbTriggersModule.hydrators.triggers.manual')
		);
		$service->injectLogger($this->getService('contributteMonolog.logger.default'));
		$service->injectManagerRegistry($this->getService('nettrineOrm.managerRegistry'));
		$service->injectTranslator($this->getService('contributteTranslation.translator'));
		return $service;
	}


	public function createServiceFbTriggersModule__hydrators__actions__channelProperty(): FastyBird\TriggersModule\Hydrators\Actions\ChannelPropertyActionHydrator
	{
		return new FastyBird\TriggersModule\Hydrators\Actions\ChannelPropertyActionHydrator(
			$this->getService('nettrineOrm.managerRegistry'),
			$this->getService('contributteTranslation.translator')
		);
	}


	public function createServiceFbTriggersModule__hydrators__conditions__channelProperty(): FastyBird\TriggersModule\Hydrators\Conditions\ChannelPropertyConditionHydrator
	{
		return new FastyBird\TriggersModule\Hydrators\Conditions\ChannelPropertyConditionHydrator(
			$this->getService('nettrineOrm.managerRegistry'),
			$this->getService('contributteTranslation.translator')
		);
	}


	public function createServiceFbTriggersModule__hydrators__conditions__deviceProperty(): FastyBird\TriggersModule\Hydrators\Conditions\DevicePropertyConditionHydrator
	{
		return new FastyBird\TriggersModule\Hydrators\Conditions\DevicePropertyConditionHydrator(
			$this->getService('nettrineOrm.managerRegistry'),
			$this->getService('contributteTranslation.translator')
		);
	}


	public function createServiceFbTriggersModule__hydrators__conditions__time(): FastyBird\TriggersModule\Hydrators\Conditions\TimeConditionHydrator
	{
		return new FastyBird\TriggersModule\Hydrators\Conditions\TimeConditionHydrator(
			$this->getService('nettrineOrm.managerRegistry'),
			$this->getService('contributteTranslation.translator')
		);
	}


	public function createServiceFbTriggersModule__hydrators__notifications__email(): FastyBird\TriggersModule\Hydrators\Notifications\EmailNotificationHydrator
	{
		return new FastyBird\TriggersModule\Hydrators\Notifications\EmailNotificationHydrator(
			$this->getService('nettrineOrm.managerRegistry'),
			$this->getService('contributteTranslation.translator')
		);
	}


	public function createServiceFbTriggersModule__hydrators__notifications__sms(): FastyBird\TriggersModule\Hydrators\Notifications\SmsNotificationHydrator
	{
		return new FastyBird\TriggersModule\Hydrators\Notifications\SmsNotificationHydrator(
			$this->getService('ipubPhone.phone'),
			$this->getService('nettrineOrm.managerRegistry'),
			$this->getService('contributteTranslation.translator')
		);
	}


	public function createServiceFbTriggersModule__hydrators__triggers__automatic(): FastyBird\TriggersModule\Hydrators\Triggers\AutomaticTriggerHydrator
	{
		return new FastyBird\TriggersModule\Hydrators\Triggers\AutomaticTriggerHydrator(
			$this->getService('nettrineOrm.managerRegistry'),
			$this->getService('contributteTranslation.translator')
		);
	}


	public function createServiceFbTriggersModule__hydrators__triggers__manual(): FastyBird\TriggersModule\Hydrators\Triggers\ManualTriggerHydrator
	{
		return new FastyBird\TriggersModule\Hydrators\Triggers\ManualTriggerHydrator(
			$this->getService('nettrineOrm.managerRegistry'),
			$this->getService('contributteTranslation.translator')
		);
	}


	public function createServiceFbTriggersModule__middleware__access(): FastyBird\TriggersModule\Middleware\AccessMiddleware
	{
		return new FastyBird\TriggersModule\Middleware\AccessMiddleware($this->getService('contributteTranslation.translator'));
	}


	public function createServiceFbTriggersModule__models__actionRepository(): FastyBird\TriggersModule\Models\Actions\ActionRepository
	{
		return new FastyBird\TriggersModule\Models\Actions\ActionRepository($this->getService('nettrineOrm.managerRegistry'));
	}


	public function createServiceFbTriggersModule__models__actionsManager(): FastyBird\TriggersModule\Models\Actions\ActionsManager
	{
		return new FastyBird\TriggersModule\Models\Actions\ActionsManager($this->getService('ipubDoctrineCrud.crud')->create('FastyBird\TriggersModule\Entities\Actions\Action'));
	}


	public function createServiceFbTriggersModule__models__conditionRepository(): FastyBird\TriggersModule\Models\Conditions\ConditionRepository
	{
		return new FastyBird\TriggersModule\Models\Conditions\ConditionRepository($this->getService('nettrineOrm.managerRegistry'));
	}


	public function createServiceFbTriggersModule__models__conditionsManager(): FastyBird\TriggersModule\Models\Conditions\ConditionsManager
	{
		return new FastyBird\TriggersModule\Models\Conditions\ConditionsManager($this->getService('ipubDoctrineCrud.crud')->create('FastyBird\TriggersModule\Entities\Conditions\Condition'));
	}


	public function createServiceFbTriggersModule__models__notificationRepository(): FastyBird\TriggersModule\Models\Notifications\NotificationRepository
	{
		return new FastyBird\TriggersModule\Models\Notifications\NotificationRepository($this->getService('nettrineOrm.managerRegistry'));
	}


	public function createServiceFbTriggersModule__models__notificationsManager(): FastyBird\TriggersModule\Models\Notifications\NotificationsManager
	{
		return new FastyBird\TriggersModule\Models\Notifications\NotificationsManager($this->getService('ipubDoctrineCrud.crud')->create('FastyBird\TriggersModule\Entities\Notifications\Notification'));
	}


	public function createServiceFbTriggersModule__models__triggerRepository(): FastyBird\TriggersModule\Models\Triggers\TriggerRepository
	{
		return new FastyBird\TriggersModule\Models\Triggers\TriggerRepository($this->getService('nettrineOrm.managerRegistry'));
	}


	public function createServiceFbTriggersModule__models__triggersManager(): FastyBird\TriggersModule\Models\Triggers\TriggersManager
	{
		return new FastyBird\TriggersModule\Models\Triggers\TriggersManager($this->getService('ipubDoctrineCrud.crud')->create('FastyBird\TriggersModule\Entities\Triggers\Trigger'));
	}


	public function createServiceFbTriggersModule__router__routes(): FastyBird\TriggersModule\Router\Routes
	{
		return new FastyBird\TriggersModule\Router\Routes(
			$this->getService('fbTriggersModule.controllers.triggers'),
			$this->getService('fbTriggersModule.controllers.actions'),
			$this->getService('fbTriggersModule.controllers.notifications'),
			$this->getService('fbTriggersModule.controllers.conditions'),
			$this->getService('fbTriggersModule.middleware.access'),
			$this->getService('fbSimpleAuth.middleware.access'),
			$this->getService('fbSimpleAuth.middleware.user')
		);
	}


	public function createServiceFbTriggersModule__schemas__actions__channelProperty(): FastyBird\TriggersModule\Schemas\Actions\ChannelPropertyActionSchema
	{
		return new FastyBird\TriggersModule\Schemas\Actions\ChannelPropertyActionSchema($this->getService('fbWebServer.routing.router'));
	}


	public function createServiceFbTriggersModule__schemas__conditions__channelProperty(): FastyBird\TriggersModule\Schemas\Conditions\ChannelPropertyConditionSchema
	{
		return new FastyBird\TriggersModule\Schemas\Conditions\ChannelPropertyConditionSchema($this->getService('fbWebServer.routing.router'));
	}


	public function createServiceFbTriggersModule__schemas__conditions__date(): FastyBird\TriggersModule\Schemas\Conditions\DateConditionSchema
	{
		return new FastyBird\TriggersModule\Schemas\Conditions\DateConditionSchema($this->getService('fbWebServer.routing.router'));
	}


	public function createServiceFbTriggersModule__schemas__conditions__deviceProperty(): FastyBird\TriggersModule\Schemas\Conditions\DevicePropertyConditionSchema
	{
		return new FastyBird\TriggersModule\Schemas\Conditions\DevicePropertyConditionSchema($this->getService('fbWebServer.routing.router'));
	}


	public function createServiceFbTriggersModule__schemas__conditions__time(): FastyBird\TriggersModule\Schemas\Conditions\TimeConditionSchema
	{
		return new FastyBird\TriggersModule\Schemas\Conditions\TimeConditionSchema($this->getService('fbWebServer.routing.router'));
	}


	public function createServiceFbTriggersModule__schemas__notifications__email(): FastyBird\TriggersModule\Schemas\Notifications\EmailNotificationSchema
	{
		return new FastyBird\TriggersModule\Schemas\Notifications\EmailNotificationSchema($this->getService('fbWebServer.routing.router'));
	}


	public function createServiceFbTriggersModule__schemas__notifications__sms(): FastyBird\TriggersModule\Schemas\Notifications\SmsNotificationSchema
	{
		return new FastyBird\TriggersModule\Schemas\Notifications\SmsNotificationSchema($this->getService('fbWebServer.routing.router'));
	}


	public function createServiceFbTriggersModule__schemas__triggers__automatic(): FastyBird\TriggersModule\Schemas\Triggers\AutomaticTriggerSchema
	{
		return new FastyBird\TriggersModule\Schemas\Triggers\AutomaticTriggerSchema($this->getService('fbWebServer.routing.router'));
	}


	public function createServiceFbTriggersModule__schemas__triggers__manual(): FastyBird\TriggersModule\Schemas\Triggers\ManualTriggerSchema
	{
		return new FastyBird\TriggersModule\Schemas\Triggers\ManualTriggerSchema($this->getService('fbWebServer.routing.router'));
	}


	public function createServiceFbTriggersModule__subscribers__actionEntity(): FastyBird\TriggersModule\Subscribers\ActionEntitySubscriber
	{
		return new FastyBird\TriggersModule\Subscribers\ActionEntitySubscriber;
	}


	public function createServiceFbTriggersModule__subscribers__conditionEntity(): FastyBird\TriggersModule\Subscribers\ConditionEntitySubscriber
	{
		return new FastyBird\TriggersModule\Subscribers\ConditionEntitySubscriber;
	}


	public function createServiceFbTriggersModule__subscribers__entities(): FastyBird\TriggersModule\Subscribers\EntitiesSubscriber
	{
		return new FastyBird\TriggersModule\Subscribers\EntitiesSubscriber(
			$this->getService('fbApplicationExchange.publisher'),
			$this->getService('nettrineOrm.entityManagerDecorator')
		);
	}


	public function createServiceFbTriggersModule__subscribers__notificationEntity(): FastyBird\TriggersModule\Subscribers\NotificationEntitySubscriber
	{
		return new FastyBird\TriggersModule\Subscribers\NotificationEntitySubscriber;
	}


	public function createServiceFbUiModule__commands__initialize(): FastyBird\UIModule\Commands\InitializeCommand
	{
		return new FastyBird\UIModule\Commands\InitializeCommand($this->getService('fbDatabase.helpers.database'));
	}


	public function createServiceFbUiModule__controllers__dashboards(): FastyBird\UIModule\Controllers\DashboardsV1Controller
	{
		$service = new FastyBird\UIModule\Controllers\DashboardsV1Controller(
			$this->getService('fbUiModule.models.dashboardRepository'),
			$this->getService('fbUiModule.models.dashboardsManager'),
			$this->getService('fbUiModule.models.groupsManager'),
			$this->getService('fbUiModule.hydrators.dashboard')
		);
		$service->injectLogger($this->getService('contributteMonolog.logger.default'));
		$service->injectManagerRegistry($this->getService('nettrineOrm.managerRegistry'));
		$service->injectTranslator($this->getService('contributteTranslation.translator'));
		return $service;
	}


	public function createServiceFbUiModule__controllers__dataSources(): FastyBird\UIModule\Controllers\DataSourcesV1Controller
	{
		$service = new FastyBird\UIModule\Controllers\DataSourcesV1Controller(
			$this->getService('fbUiModule.models.dataSourceRepository'),
			$this->getService('fbUiModule.models.dataSourcesManager'),
			$this->getService('fbUiModule.models.widgetRepository'),
			$this->getService('fbUiModule.hydrators.dataSources.channelProperty')
		);
		$service->injectLogger($this->getService('contributteMonolog.logger.default'));
		$service->injectManagerRegistry($this->getService('nettrineOrm.managerRegistry'));
		$service->injectTranslator($this->getService('contributteTranslation.translator'));
		return $service;
	}


	public function createServiceFbUiModule__controllers__display(): FastyBird\UIModule\Controllers\DisplayV1Controller
	{
		$service = new FastyBird\UIModule\Controllers\DisplayV1Controller(
			$this->getService('fbUiModule.models.displaysManager'),
			$this->getService('fbUiModule.models.widgetRepository'),
			$this->getService('fbUiModule.hydrators.widgets.analogValue'),
			$this->getService('fbUiModule.hydrators.widgets.button'),
			$this->getService('fbUiModule.hydrators.widgets.chartGraph'),
			$this->getService('fbUiModule.hydrators.widgets.digitalValue'),
			$this->getService('fbUiModule.hydrators.widgets.gauge'),
			$this->getService('fbUiModule.hydrators.widgets.groupedButton'),
			$this->getService('fbUiModule.hydrators.widgets.slider')
		);
		$service->injectLogger($this->getService('contributteMonolog.logger.default'));
		$service->injectManagerRegistry($this->getService('nettrineOrm.managerRegistry'));
		$service->injectTranslator($this->getService('contributteTranslation.translator'));
		return $service;
	}


	public function createServiceFbUiModule__controllers__groups(): FastyBird\UIModule\Controllers\GroupsV1Controller
	{
		$service = new FastyBird\UIModule\Controllers\GroupsV1Controller(
			$this->getService('fbUiModule.models.groupRepository'),
			$this->getService('fbUiModule.models.groupsManager'),
			$this->getService('fbUiModule.models.dashboardRepository'),
			$this->getService('fbUiModule.hydrators.group')
		);
		$service->injectLogger($this->getService('contributteMonolog.logger.default'));
		$service->injectManagerRegistry($this->getService('nettrineOrm.managerRegistry'));
		$service->injectTranslator($this->getService('contributteTranslation.translator'));
		return $service;
	}


	public function createServiceFbUiModule__controllers__widgets(): FastyBird\UIModule\Controllers\WidgetsV1Controller
	{
		$service = new FastyBird\UIModule\Controllers\WidgetsV1Controller(
			$this->getService('fbUiModule.models.widgetRepository'),
			$this->getService('fbUiModule.models.widgetsManager'),
			$this->getService('fbUiModule.hydrators.widgets.analogActuator'),
			$this->getService('fbUiModule.hydrators.widgets.analogSensor'),
			$this->getService('fbUiModule.hydrators.widgets.digitalActuator'),
			$this->getService('fbUiModule.hydrators.widgets.digitalSensor')
		);
		$service->injectLogger($this->getService('contributteMonolog.logger.default'));
		$service->injectManagerRegistry($this->getService('nettrineOrm.managerRegistry'));
		$service->injectTranslator($this->getService('contributteTranslation.translator'));
		return $service;
	}


	public function createServiceFbUiModule__hydrators__dashboard(): FastyBird\UIModule\Hydrators\Dashboards\DashboardHydrator
	{
		return new FastyBird\UIModule\Hydrators\Dashboards\DashboardHydrator(
			$this->getService('nettrineOrm.managerRegistry'),
			$this->getService('contributteTranslation.translator')
		);
	}


	public function createServiceFbUiModule__hydrators__dataSources__channelProperty(): FastyBird\UIModule\Hydrators\Widgets\DataSources\ChannelPropertyDataSourceHydrator
	{
		return new FastyBird\UIModule\Hydrators\Widgets\DataSources\ChannelPropertyDataSourceHydrator(
			$this->getService('nettrineOrm.managerRegistry'),
			$this->getService('contributteTranslation.translator')
		);
	}


	public function createServiceFbUiModule__hydrators__group(): FastyBird\UIModule\Hydrators\Groups\GroupHydrator
	{
		return new FastyBird\UIModule\Hydrators\Groups\GroupHydrator(
			$this->getService('nettrineOrm.managerRegistry'),
			$this->getService('contributteTranslation.translator')
		);
	}


	public function createServiceFbUiModule__hydrators__widgets__analogActuator(): FastyBird\UIModule\Hydrators\Widgets\AnalogActuatorWidgetHydrator
	{
		return new FastyBird\UIModule\Hydrators\Widgets\AnalogActuatorWidgetHydrator(
			$this->getService('fbUiModule.models.groupRepository'),
			$this->getService('nettrineOrm.managerRegistry'),
			$this->getService('contributteTranslation.translator')
		);
	}


	public function createServiceFbUiModule__hydrators__widgets__analogSensor(): FastyBird\UIModule\Hydrators\Widgets\AnalogSensorWidgetHydrator
	{
		return new FastyBird\UIModule\Hydrators\Widgets\AnalogSensorWidgetHydrator(
			$this->getService('fbUiModule.models.groupRepository'),
			$this->getService('nettrineOrm.managerRegistry'),
			$this->getService('contributteTranslation.translator')
		);
	}


	public function createServiceFbUiModule__hydrators__widgets__analogValue(): FastyBird\UIModule\Hydrators\Widgets\Displays\AnalogValueHydrator
	{
		return new FastyBird\UIModule\Hydrators\Widgets\Displays\AnalogValueHydrator(
			$this->getService('nettrineOrm.managerRegistry'),
			$this->getService('contributteTranslation.translator')
		);
	}


	public function createServiceFbUiModule__hydrators__widgets__button(): FastyBird\UIModule\Hydrators\Widgets\Displays\ButtonHydrator
	{
		return new FastyBird\UIModule\Hydrators\Widgets\Displays\ButtonHydrator(
			$this->getService('nettrineOrm.managerRegistry'),
			$this->getService('contributteTranslation.translator')
		);
	}


	public function createServiceFbUiModule__hydrators__widgets__chartGraph(): FastyBird\UIModule\Hydrators\Widgets\Displays\ChartGraphHydrator
	{
		return new FastyBird\UIModule\Hydrators\Widgets\Displays\ChartGraphHydrator(
			$this->getService('nettrineOrm.managerRegistry'),
			$this->getService('contributteTranslation.translator')
		);
	}


	public function createServiceFbUiModule__hydrators__widgets__digitalActuator(): FastyBird\UIModule\Hydrators\Widgets\DigitalActuatorWidgetHydrator
	{
		return new FastyBird\UIModule\Hydrators\Widgets\DigitalActuatorWidgetHydrator(
			$this->getService('fbUiModule.models.groupRepository'),
			$this->getService('nettrineOrm.managerRegistry'),
			$this->getService('contributteTranslation.translator')
		);
	}


	public function createServiceFbUiModule__hydrators__widgets__digitalSensor(): FastyBird\UIModule\Hydrators\Widgets\DigitalSensorWidgetHydrator
	{
		return new FastyBird\UIModule\Hydrators\Widgets\DigitalSensorWidgetHydrator(
			$this->getService('fbUiModule.models.groupRepository'),
			$this->getService('nettrineOrm.managerRegistry'),
			$this->getService('contributteTranslation.translator')
		);
	}


	public function createServiceFbUiModule__hydrators__widgets__digitalValue(): FastyBird\UIModule\Hydrators\Widgets\Displays\DigitalValueHydrator
	{
		return new FastyBird\UIModule\Hydrators\Widgets\Displays\DigitalValueHydrator(
			$this->getService('nettrineOrm.managerRegistry'),
			$this->getService('contributteTranslation.translator')
		);
	}


	public function createServiceFbUiModule__hydrators__widgets__gauge(): FastyBird\UIModule\Hydrators\Widgets\Displays\GaugeHydrator
	{
		return new FastyBird\UIModule\Hydrators\Widgets\Displays\GaugeHydrator(
			$this->getService('nettrineOrm.managerRegistry'),
			$this->getService('contributteTranslation.translator')
		);
	}


	public function createServiceFbUiModule__hydrators__widgets__groupedButton(): FastyBird\UIModule\Hydrators\Widgets\Displays\GroupedButtonHydrator
	{
		return new FastyBird\UIModule\Hydrators\Widgets\Displays\GroupedButtonHydrator(
			$this->getService('nettrineOrm.managerRegistry'),
			$this->getService('contributteTranslation.translator')
		);
	}


	public function createServiceFbUiModule__hydrators__widgets__slider(): FastyBird\UIModule\Hydrators\Widgets\Displays\SliderHydrator
	{
		return new FastyBird\UIModule\Hydrators\Widgets\Displays\SliderHydrator(
			$this->getService('nettrineOrm.managerRegistry'),
			$this->getService('contributteTranslation.translator')
		);
	}


	public function createServiceFbUiModule__middleware__access(): FastyBird\UIModule\Middleware\AccessMiddleware
	{
		return new FastyBird\UIModule\Middleware\AccessMiddleware($this->getService('contributteTranslation.translator'));
	}


	public function createServiceFbUiModule__models__dashboardRepository(): FastyBird\UIModule\Models\Dashboards\DashboardRepository
	{
		return new FastyBird\UIModule\Models\Dashboards\DashboardRepository($this->getService('nettrineOrm.managerRegistry'));
	}


	public function createServiceFbUiModule__models__dashboardsManager(): FastyBird\UIModule\Models\Dashboards\DashboardsManager
	{
		return new FastyBird\UIModule\Models\Dashboards\DashboardsManager($this->getService('ipubDoctrineCrud.crud')->create('FastyBird\UIModule\Entities\Dashboards\Dashboard'));
	}


	public function createServiceFbUiModule__models__dataSourceRepository(): FastyBird\UIModule\Models\Widgets\DataSources\DataSourceRepository
	{
		return new FastyBird\UIModule\Models\Widgets\DataSources\DataSourceRepository($this->getService('nettrineOrm.managerRegistry'));
	}


	public function createServiceFbUiModule__models__dataSourcesManager(): FastyBird\UIModule\Models\Widgets\DataSources\DataSourcesManager
	{
		return new FastyBird\UIModule\Models\Widgets\DataSources\DataSourcesManager($this->getService('ipubDoctrineCrud.crud')->create('FastyBird\UIModule\Entities\Widgets\DataSources\DataSource'));
	}


	public function createServiceFbUiModule__models__displaysManager(): FastyBird\UIModule\Models\Widgets\Displays\DisplaysManager
	{
		return new FastyBird\UIModule\Models\Widgets\Displays\DisplaysManager($this->getService('ipubDoctrineCrud.crud')->create('FastyBird\UIModule\Entities\Widgets\Display\Display'));
	}


	public function createServiceFbUiModule__models__groupRepository(): FastyBird\UIModule\Models\Groups\GroupRepository
	{
		return new FastyBird\UIModule\Models\Groups\GroupRepository($this->getService('nettrineOrm.managerRegistry'));
	}


	public function createServiceFbUiModule__models__groupsManager(): FastyBird\UIModule\Models\Groups\GroupsManager
	{
		return new FastyBird\UIModule\Models\Groups\GroupsManager($this->getService('ipubDoctrineCrud.crud')->create('FastyBird\UIModule\Entities\Groups\Group'));
	}


	public function createServiceFbUiModule__models__widgetRepository(): FastyBird\UIModule\Models\Widgets\WidgetRepository
	{
		return new FastyBird\UIModule\Models\Widgets\WidgetRepository($this->getService('nettrineOrm.managerRegistry'));
	}


	public function createServiceFbUiModule__models__widgetsManager(): FastyBird\UIModule\Models\Widgets\WidgetsManager
	{
		return new FastyBird\UIModule\Models\Widgets\WidgetsManager($this->getService('ipubDoctrineCrud.crud')->create('FastyBird\UIModule\Entities\Widgets\Widget'));
	}


	public function createServiceFbUiModule__router__routes(): FastyBird\UIModule\Router\Routes
	{
		return new FastyBird\UIModule\Router\Routes(
			$this->getService('fbUiModule.controllers.dashboards'),
			$this->getService('fbUiModule.controllers.groups'),
			$this->getService('fbUiModule.controllers.widgets'),
			$this->getService('fbUiModule.controllers.display'),
			$this->getService('fbUiModule.controllers.dataSources'),
			$this->getService('fbUiModule.middleware.access'),
			$this->getService('fbSimpleAuth.middleware.access'),
			$this->getService('fbSimpleAuth.middleware.user')
		);
	}


	public function createServiceFbUiModule__schemas__dashboard(): FastyBird\UIModule\Schemas\Dashboards\DashboardSchema
	{
		return new FastyBird\UIModule\Schemas\Dashboards\DashboardSchema($this->getService('fbWebServer.routing.router'));
	}


	public function createServiceFbUiModule__schemas__dataSources__channelProperty(): FastyBird\UIModule\Schemas\Widgets\DataSources\ChannelPropertyDataSourceSchema
	{
		return new FastyBird\UIModule\Schemas\Widgets\DataSources\ChannelPropertyDataSourceSchema($this->getService('fbWebServer.routing.router'));
	}


	public function createServiceFbUiModule__schemas__display__analogValue(): FastyBird\UIModule\Schemas\Widgets\Display\AnalogValueSchema
	{
		return new FastyBird\UIModule\Schemas\Widgets\Display\AnalogValueSchema($this->getService('fbWebServer.routing.router'));
	}


	public function createServiceFbUiModule__schemas__display__button(): FastyBird\UIModule\Schemas\Widgets\Display\ButtonSchema
	{
		return new FastyBird\UIModule\Schemas\Widgets\Display\ButtonSchema($this->getService('fbWebServer.routing.router'));
	}


	public function createServiceFbUiModule__schemas__display__chartGraph(): FastyBird\UIModule\Schemas\Widgets\Display\ChartGraphSchema
	{
		return new FastyBird\UIModule\Schemas\Widgets\Display\ChartGraphSchema($this->getService('fbWebServer.routing.router'));
	}


	public function createServiceFbUiModule__schemas__display__digitalValue(): FastyBird\UIModule\Schemas\Widgets\Display\DigitalValueSchema
	{
		return new FastyBird\UIModule\Schemas\Widgets\Display\DigitalValueSchema($this->getService('fbWebServer.routing.router'));
	}


	public function createServiceFbUiModule__schemas__display__gauge(): FastyBird\UIModule\Schemas\Widgets\Display\GaugeSchema
	{
		return new FastyBird\UIModule\Schemas\Widgets\Display\GaugeSchema($this->getService('fbWebServer.routing.router'));
	}


	public function createServiceFbUiModule__schemas__display__groupedButton(): FastyBird\UIModule\Schemas\Widgets\Display\GroupedButtonSchema
	{
		return new FastyBird\UIModule\Schemas\Widgets\Display\GroupedButtonSchema($this->getService('fbWebServer.routing.router'));
	}


	public function createServiceFbUiModule__schemas__display__slider(): FastyBird\UIModule\Schemas\Widgets\Display\SliderSchema
	{
		return new FastyBird\UIModule\Schemas\Widgets\Display\SliderSchema($this->getService('fbWebServer.routing.router'));
	}


	public function createServiceFbUiModule__schemas__group(): FastyBird\UIModule\Schemas\Groups\GroupSchema
	{
		return new FastyBird\UIModule\Schemas\Groups\GroupSchema($this->getService('fbWebServer.routing.router'));
	}


	public function createServiceFbUiModule__schemas__widgets__analogActuator(): FastyBird\UIModule\Schemas\Widgets\AnalogActuatorSchema
	{
		return new FastyBird\UIModule\Schemas\Widgets\AnalogActuatorSchema($this->getService('fbWebServer.routing.router'));
	}


	public function createServiceFbUiModule__schemas__widgets__analogSensor(): FastyBird\UIModule\Schemas\Widgets\AnalogSensorSchema
	{
		return new FastyBird\UIModule\Schemas\Widgets\AnalogSensorSchema($this->getService('fbWebServer.routing.router'));
	}


	public function createServiceFbUiModule__schemas__widgets__digitalActuator(): FastyBird\UIModule\Schemas\Widgets\DigitalActuatorSchema
	{
		return new FastyBird\UIModule\Schemas\Widgets\DigitalActuatorSchema($this->getService('fbWebServer.routing.router'));
	}


	public function createServiceFbUiModule__schemas__widgets__digitalSensor(): FastyBird\UIModule\Schemas\Widgets\DigitalSensorSchema
	{
		return new FastyBird\UIModule\Schemas\Widgets\DigitalSensorSchema($this->getService('fbWebServer.routing.router'));
	}


	public function createServiceFbWebServer__command__server(): FastyBird\WebServer\Commands\HttpServerCommand
	{
		return new FastyBird\WebServer\Commands\HttpServerCommand(
			$this->getService('react.socketServer'),
			$this->getService('react.eventLoop'),
			$this->getService('fbWebServer.routing.router'),
			$this->getService('contributteEvents.dispatcher'),
			$this->getService('contributteMonolog.logger.default')
		);
	}


	public function createServiceFbWebServer__routing__responseFactory(): FastyBird\WebServer\Http\ResponseFactory
	{
		return new FastyBird\WebServer\Http\ResponseFactory;
	}


	public function createServiceFbWebServer__routing__router(): FastyBird\WebServer\Router\Router
	{
		$service = new FastyBird\WebServer\Router\Router($this->getService('fbWebServer.routing.responseFactory'));
		$service->registerRoutes($this->getService('fbAuthModule.router.routes'));
		$service->registerRoutes($this->getService('fbDevicesModule.router.routes'));
		$service->registerRoutes($this->getService('fbTriggersModule.router.routes'));
		$service->registerRoutes($this->getService('fbUiModule.router.routes'));
		$service->injectRoutes();
		$service->addMiddleware($this->getService('02'));
		$service->addMiddleware($this->getService('fbJsonApi.middlewares.jsonapi'));
		$service->addMiddleware($this->getService('fbAuthModule.middleware.urlFormat'));
		return $service;
	}


	public function createServiceHttp__request(): Nette\Http\Request
	{
		return $this->getService('http.requestFactory')->fromGlobals();
	}


	public function createServiceHttp__requestFactory(): Nette\Http\RequestFactory
	{
		$service = new Nette\Http\RequestFactory;
		$service->setProxy([]);
		return $service;
	}


	public function createServiceHttp__response(): Nette\Http\Response
	{
		return new Nette\Http\Response;
	}


	public function createServiceIpubDoctrineConsistence__subscriber(): IPub\DoctrineConsistence\Events\EnumSubscriber
	{
		return new IPub\DoctrineConsistence\Events\EnumSubscriber($this->getService('nettrineAnnotations.reader'));
	}


	public function createServiceIpubDoctrineCrud__crud(): IPub\DoctrineCrud\Crud\IEntityCrudFactory
	{
		return new class ($this) implements IPub\DoctrineCrud\Crud\IEntityCrudFactory {
			private $container;


			public function __construct(Container_9769c2aee7 $container)
			{
				$this->container = $container;
			}


			public function create($entityName): IPub\DoctrineCrud\Crud\EntityCrud
			{
				return new IPub\DoctrineCrud\Crud\EntityCrud(
					$entityName,
					$this->container->getService('ipubDoctrineCrud.entity.mapper'),
					$this->container->getService('ipubDoctrineCrud.entity.creator'),
					$this->container->getService('ipubDoctrineCrud.entity.updater'),
					$this->container->getService('ipubDoctrineCrud.entity.deleter')
				);
			}
		};
	}


	public function createServiceIpubDoctrineCrud__entity__creator(): IPub\DoctrineCrud\Crud\Create\IEntityCreator
	{
		return new class ($this) implements IPub\DoctrineCrud\Crud\Create\IEntityCreator {
			private $container;


			public function __construct(Container_9769c2aee7 $container)
			{
				$this->container = $container;
			}


			public function create(string $entityName, IPub\DoctrineCrud\Mapping\IEntityMapper $entityMapper): IPub\DoctrineCrud\Crud\Create\EntityCreator
			{
				return new IPub\DoctrineCrud\Crud\Create\EntityCreator(
					$entityName,
					$entityMapper,
					$this->container->getService('nettrineOrm.managerRegistry')
				);
			}
		};
	}


	public function createServiceIpubDoctrineCrud__entity__deleter(): IPub\DoctrineCrud\Crud\Delete\IEntityDeleter
	{
		return new class ($this) implements IPub\DoctrineCrud\Crud\Delete\IEntityDeleter {
			private $container;


			public function __construct(Container_9769c2aee7 $container)
			{
				$this->container = $container;
			}


			public function create(string $entityName): IPub\DoctrineCrud\Crud\Delete\EntityDeleter
			{
				return new IPub\DoctrineCrud\Crud\Delete\EntityDeleter($entityName, $this->container->getService('nettrineOrm.managerRegistry'));
			}
		};
	}


	public function createServiceIpubDoctrineCrud__entity__mapper(): IPub\DoctrineCrud\Mapping\EntityMapper
	{
		return new IPub\DoctrineCrud\Mapping\EntityMapper(
			\Nette\PhpGenerator\Dumper::createObject('Doctrine\Common\Annotations\CachedReader', [
				"\x00Doctrine\\Common\\Annotations\\CachedReader\x00delegate" => \Nette\PhpGenerator\Dumper::createObject('Doctrine\Common\Annotations\AnnotationReader', [
					"\x00Doctrine\\Common\\Annotations\\AnnotationReader\x00parser" => \Nette\PhpGenerator\Dumper::createObject('Doctrine\Common\Annotations\DocParser', [
						"\x00Doctrine\\Common\\Annotations\\DocParser\x00lexer" => \Nette\PhpGenerator\Dumper::createObject('Doctrine\Common\Annotations\DocLexer', [
							"\x00*\x00noCase" => [
								'@' => 101,
								',' => 104,
								'(' => 109,
								')' => 103,
								'{' => 108,
								'}' => 102,
								'=' => 105,
								':' => 112,
								'-' => 113,
								'\\' => 107,
							],
							"\x00*\x00withCase" => ['true' => 110, 'false' => 106, 'null' => 111],
							"\x00Doctrine\\Common\\Lexer\\AbstractLexer\x00input" => null,
							"\x00Doctrine\\Common\\Lexer\\AbstractLexer\x00tokens" => [],
							"\x00Doctrine\\Common\\Lexer\\AbstractLexer\x00position" => 0,
							"\x00Doctrine\\Common\\Lexer\\AbstractLexer\x00peek" => 0,
							'lookahead' => null,
							'token' => null,
							"\x00Doctrine\\Common\\Lexer\\AbstractLexer\x00regex" => null,
						]),
						"\x00Doctrine\\Common\\Annotations\\DocParser\x00target" => null,
						"\x00Doctrine\\Common\\Annotations\\DocParser\x00isNestedAnnotation" => false,
						"\x00Doctrine\\Common\\Annotations\\DocParser\x00imports" => [],
						"\x00Doctrine\\Common\\Annotations\\DocParser\x00classExists" => [],
						"\x00Doctrine\\Common\\Annotations\\DocParser\x00ignoreNotImportedAnnotations" => false,
						"\x00Doctrine\\Common\\Annotations\\DocParser\x00namespaces" => [],
						"\x00Doctrine\\Common\\Annotations\\DocParser\x00ignoredAnnotationNames" => [],
						"\x00Doctrine\\Common\\Annotations\\DocParser\x00ignoredAnnotationNamespaces" => [],
						"\x00Doctrine\\Common\\Annotations\\DocParser\x00context" => '',
					]),
					"\x00Doctrine\\Common\\Annotations\\AnnotationReader\x00preParser" => \Nette\PhpGenerator\Dumper::createObject('Doctrine\Common\Annotations\DocParser', [
						"\x00Doctrine\\Common\\Annotations\\DocParser\x00lexer" => \Nette\PhpGenerator\Dumper::createObject('Doctrine\Common\Annotations\DocLexer', [
							"\x00*\x00noCase" => [
								'@' => 101,
								',' => 104,
								'(' => 109,
								')' => 103,
								'{' => 108,
								'}' => 102,
								'=' => 105,
								':' => 112,
								'-' => 113,
								'\\' => 107,
							],
							"\x00*\x00withCase" => ['true' => 110, 'false' => 106, 'null' => 111],
							"\x00Doctrine\\Common\\Lexer\\AbstractLexer\x00input" => null,
							"\x00Doctrine\\Common\\Lexer\\AbstractLexer\x00tokens" => [],
							"\x00Doctrine\\Common\\Lexer\\AbstractLexer\x00position" => 0,
							"\x00Doctrine\\Common\\Lexer\\AbstractLexer\x00peek" => 0,
							'lookahead' => null,
							'token' => null,
							"\x00Doctrine\\Common\\Lexer\\AbstractLexer\x00regex" => null,
						]),
						"\x00Doctrine\\Common\\Annotations\\DocParser\x00target" => null,
						"\x00Doctrine\\Common\\Annotations\\DocParser\x00isNestedAnnotation" => false,
						"\x00Doctrine\\Common\\Annotations\\DocParser\x00imports" => [
							'ignoreannotation' => 'Doctrine\Common\Annotations\Annotation\IgnoreAnnotation',
						],
						"\x00Doctrine\\Common\\Annotations\\DocParser\x00classExists" => [],
						"\x00Doctrine\\Common\\Annotations\\DocParser\x00ignoreNotImportedAnnotations" => true,
						"\x00Doctrine\\Common\\Annotations\\DocParser\x00namespaces" => [],
						"\x00Doctrine\\Common\\Annotations\\DocParser\x00ignoredAnnotationNames" => [
							'Annotation' => true,
							'Attribute' => true,
							'Attributes' => true,
							'Required' => true,
							'Target' => true,
							'fix' => true,
							'fixme' => true,
							'override' => true,
							'abstract' => true,
							'access' => true,
							'code' => true,
							'deprec' => true,
							'endcode' => true,
							'exception' => true,
							'final' => true,
							'ingroup' => true,
							'inheritdoc' => true,
							'inheritDoc' => true,
							'magic' => true,
							'name' => true,
							'private' => true,
							'static' => true,
							'staticvar' => true,
							'staticVar' => true,
							'toc' => true,
							'tutorial' => true,
							'throw' => true,
							'api' => true,
							'author' => true,
							'category' => true,
							'copyright' => true,
							'deprecated' => true,
							'example' => true,
							'filesource' => true,
							'global' => true,
							'ignore' => true,
							'internal' => true,
							'license' => true,
							'link' => true,
							'method' => true,
							'package' => true,
							'param' => true,
							'property' => true,
							'property-read' => true,
							'property-write' => true,
							'return' => true,
							'see' => true,
							'since' => true,
							'source' => true,
							'subpackage' => true,
							'throws' => true,
							'todo' => true,
							'TODO' => true,
							'usedby' => true,
							'uses' => true,
							'var' => true,
							'version' => true,
							'after' => true,
							'afterClass' => true,
							'backupGlobals' => true,
							'backupStaticAttributes' => true,
							'before' => true,
							'beforeClass' => true,
							'codeCoverageIgnore' => true,
							'codeCoverageIgnoreStart' => true,
							'codeCoverageIgnoreEnd' => true,
							'covers' => true,
							'coversDefaultClass' => true,
							'coversNothing' => true,
							'dataProvider' => true,
							'depends' => true,
							'doesNotPerformAssertions' => true,
							'expectedException' => true,
							'expectedExceptionCode' => true,
							'expectedExceptionMessage' => true,
							'expectedExceptionMessageRegExp' => true,
							'group' => true,
							'large' => true,
							'medium' => true,
							'preserveGlobalState' => true,
							'requires' => true,
							'runTestsInSeparateProcesses' => true,
							'runInSeparateProcess' => true,
							'small' => true,
							'test' => true,
							'testdox' => true,
							'testWith' => true,
							'ticket' => true,
							'SuppressWarnings' => true,
							'noinspection' => true,
							'package_version' => true,
							'startuml' => true,
							'enduml' => true,
							'experimental' => true,
							'phpcsSuppress' => true,
							'codingStandardsIgnoreStart' => true,
							'codingStandardsIgnoreEnd' => true,
							'extends' => true,
							'implements' => true,
							'template' => true,
							'use' => true,
							'suppress' => true,
							'noRector' => true,
							'persistent' => true,
							'serializationVersion' => true,
							'writable' => true,
							'validator' => true,
							'module' => true,
						],
						"\x00Doctrine\\Common\\Annotations\\DocParser\x00ignoredAnnotationNamespaces" => [],
						"\x00Doctrine\\Common\\Annotations\\DocParser\x00context" => '',
					]),
					"\x00Doctrine\\Common\\Annotations\\AnnotationReader\x00phpParser" => \Nette\PhpGenerator\Dumper::createObject('Doctrine\Common\Annotations\PhpParser', [
					]),
					"\x00Doctrine\\Common\\Annotations\\AnnotationReader\x00imports" => [],
					"\x00Doctrine\\Common\\Annotations\\AnnotationReader\x00ignoredAnnotationNames" => [],
				]),
				"\x00Doctrine\\Common\\Annotations\\CachedReader\x00cache" => \Nette\PhpGenerator\Dumper::createObject('Doctrine\Common\Cache\ArrayCache', [
					"\x00Doctrine\\Common\\Cache\\ArrayCache\x00data" => [],
					"\x00Doctrine\\Common\\Cache\\ArrayCache\x00hitsCount" => 0,
					"\x00Doctrine\\Common\\Cache\\ArrayCache\x00missesCount" => 0,
					"\x00Doctrine\\Common\\Cache\\ArrayCache\x00upTime" => 1614116991,
					"\x00Doctrine\\Common\\Cache\\CacheProvider\x00namespace" => '',
					"\x00Doctrine\\Common\\Cache\\CacheProvider\x00namespaceVersion" => null,
				]),
				"\x00Doctrine\\Common\\Annotations\\CachedReader\x00debug" => false,
				"\x00Doctrine\\Common\\Annotations\\CachedReader\x00loadedAnnotations" => [],
				"\x00Doctrine\\Common\\Annotations\\CachedReader\x00loadedFilemtimes" => [],
			]),
			$this->getService('nettrineOrm.managerRegistry')
		);
	}


	public function createServiceIpubDoctrineCrud__entity__updater(): IPub\DoctrineCrud\Crud\Update\IEntityUpdater
	{
		return new class ($this) implements IPub\DoctrineCrud\Crud\Update\IEntityUpdater {
			private $container;


			public function __construct(Container_9769c2aee7 $container)
			{
				$this->container = $container;
			}


			public function create(string $entityName, IPub\DoctrineCrud\Mapping\IEntityMapper $entityMapper): IPub\DoctrineCrud\Crud\Update\EntityUpdater
			{
				return new IPub\DoctrineCrud\Crud\Update\EntityUpdater(
					$entityName,
					$entityMapper,
					$this->container->getService('nettrineOrm.managerRegistry')
				);
			}
		};
	}


	public function createServiceIpubDoctrineDynamicDiscriminatorMap__subscriber(): IPub\DoctrineDynamicDiscriminatorMap\Events\DynamicDiscriminatorSubscriber
	{
		return new IPub\DoctrineDynamicDiscriminatorMap\Events\DynamicDiscriminatorSubscriber;
	}


	public function createServiceIpubDoctrinePhone__subscriber(): IPub\DoctrinePhone\Events\PhoneObjectSubscriber
	{
		return new IPub\DoctrinePhone\Events\PhoneObjectSubscriber;
	}


	public function createServiceIpubDoctrineTimestampable__configuration(): IPub\DoctrineTimestampable\Configuration
	{
		return new IPub\DoctrineTimestampable\Configuration(false, true);
	}


	public function createServiceIpubDoctrineTimestampable__driver(): IPub\DoctrineTimestampable\Mapping\Driver\Timestampable
	{
		return new IPub\DoctrineTimestampable\Mapping\Driver\Timestampable($this->getService('ipubDoctrineTimestampable.configuration'));
	}


	public function createServiceIpubDoctrineTimestampable__subscriber(): IPub\DoctrineTimestampable\Events\TimestampableSubscriber
	{
		return new IPub\DoctrineTimestampable\Events\TimestampableSubscriber($this->getService('ipubDoctrineTimestampable.driver'));
	}


	public function createServiceIpubPhone__libphone__geoCoder(): libphonenumber\geocoding\PhoneNumberOfflineGeocoder
	{
		return libphonenumber\geocoding\PhoneNumberOfflineGeocoder::getInstance();
	}


	public function createServiceIpubPhone__libphone__mapper__carrier(): libphonenumber\PhoneNumberToCarrierMapper
	{
		return libphonenumber\PhoneNumberToCarrierMapper::getInstance();
	}


	public function createServiceIpubPhone__libphone__mapper__timezone(): libphonenumber\PhoneNumberToTimeZonesMapper
	{
		return libphonenumber\PhoneNumberToTimeZonesMapper::getInstance();
	}


	public function createServiceIpubPhone__libphone__shortNumber(): libphonenumber\ShortNumberInfo
	{
		return libphonenumber\ShortNumberInfo::getInstance();
	}


	public function createServiceIpubPhone__libphone__utils(): libphonenumber\PhoneNumberUtil
	{
		return libphonenumber\PhoneNumberUtil::getInstance();
	}


	public function createServiceIpubPhone__phone(): IPub\Phone\Phone
	{
		return new IPub\Phone\Phone(
			$this->getService('ipubPhone.libphone.utils'),
			$this->getService('ipubPhone.libphone.geoCoder'),
			$this->getService('ipubPhone.libphone.mapper.carrier'),
			$this->getService('ipubPhone.libphone.mapper.timezone'),
			$this->getService('contributteTranslation.translator')
		);
	}


	public function createServiceNettrineAnnotations__delegatedReader(): Doctrine\Common\Annotations\AnnotationReader
	{
		$service = new Doctrine\Common\Annotations\AnnotationReader;
		$service->addGlobalIgnoredName('persistent');
		$service->addGlobalIgnoredName('serializationVersion');
		$service->addGlobalIgnoredName('writable');
		$service->addGlobalIgnoredName('validator');
		$service->addGlobalIgnoredName('module');
		$service->addGlobalIgnoredName('author');
		$service->addGlobalIgnoredName('subpackage');
		$service->addGlobalIgnoredName('package');
		$service->addGlobalIgnoredName('phpcsSuppress');
		return $service;
	}


	public function createServiceNettrineAnnotations__reader(): Doctrine\Common\Annotations\Reader
	{
		return new Doctrine\Common\Annotations\CachedReader(
			$this->getService('nettrineAnnotations.delegatedReader'),
			$this->getService('nettrineCache.driver'),
			true
		);
	}


	public function createServiceNettrineCache__driver(): Doctrine\Common\Cache\Cache
	{
		return new Doctrine\Common\Cache\PhpFileCache('/Users/akadlec/Development/FastyBird/other/miniserver-app/vendor/fastybird/bootstrap/src/Boot/../../../../../var/temp/cache/nettrine.cache');
	}


	public function createServiceNettrineDbal__configuration(): Doctrine\DBAL\Configuration
	{
		$service = new Doctrine\DBAL\Configuration;
		$service->setSQLLogger($this->getService('nettrineDbal.logger'));
		$service->setResultCacheImpl($this->getService('nettrineCache.driver'));
		$service->setAutoCommit(true);
		return $service;
	}


	public function createServiceNettrineDbal__connection(): Doctrine\DBAL\Connection
	{
		return $this->getService('nettrineDbal.connectionFactory')->createConnection(
			[
				'serverVersion' => 5.7,
				'host' => '127.0.0.1',
				'port' => 3306,
				'driver' => 'pdo_mysql',
				'memory' => false,
				'dbname' => 'miniserver_app',
				'user' => 'root',
				'password' => 'root',
				'charset' => 'utf8',
				'types' => [
					'uuid_binary' => ['class' => 'Ramsey\Uuid\Doctrine\UuidBinaryType', 'commented' => false],
					'utcdatetime' => ['class' => 'IPub\DoctrineTimestampable\Types\UTCDateTime', 'commented' => false],
				],
				'typesMapping' => ['uuid_binary' => 'binary'],
			],
			$this->getService('nettrineDbal.configuration'),
			$this->getService('nettrineDbal.eventManager')
		);
	}


	public function createServiceNettrineDbal__connectionFactory(): Nettrine\DBAL\ConnectionFactory
	{
		return new Nettrine\DBAL\ConnectionFactory(
			[
				'uuid_binary' => ['class' => 'Ramsey\Uuid\Doctrine\UuidBinaryType', 'commented' => false],
				'utcdatetime' => ['class' => 'IPub\DoctrineTimestampable\Types\UTCDateTime', 'commented' => false],
			],
			['uuid_binary' => 'binary']
		);
	}


	public function createServiceNettrineDbal__eventManager(): Nettrine\DBAL\Events\ContainerAwareEventManager
	{
		$service = new Nettrine\DBAL\Events\ContainerAwareEventManager($this);
		$service->addEventListener(['loadClassMetadata', 'onFlush'], 'fbSimpleAuth.doctrine.subscriber');
		$service->addEventListener(['prePersist', 'onFlush'], 'fbAuthModule.subscribers.accountEntity');
		$service->addEventListener(['prePersist', 'onFlush'], 'fbAuthModule.subscribers.emailEntity');
		$service->addEventListener(['preFlush', 'onFlush', 'prePersist', 'postPersist', 'postUpdate'], 'fbDevicesModule.subscribers.entities');
		$service->addEventListener(['onFlush'], 'fbTriggersModule.subscribers.actionEntity');
		$service->addEventListener(['onFlush'], 'fbTriggersModule.subscribers.conditionEntity');
		$service->addEventListener(['onFlush'], 'fbTriggersModule.subscribers.notificationEntity');
		$service->addEventListener(['onFlush', 'postPersist', 'postUpdate'], 'fbTriggersModule.subscribers.entities');
		$service->addEventListener(['loadClassMetadata'], 'ipubDoctrinePhone.subscriber');
		$service->addEventListener(['postLoad'], 'ipubDoctrineConsistence.subscriber');
		$service->addEventListener(['loadClassMetadata', 'onFlush'], 'ipubDoctrineTimestampable.subscriber');
		$service->addEventListener(['loadClassMetadata'], 'ipubDoctrineDynamicDiscriminatorMap.subscriber');
		$service->addEventListener(['loadClassMetadata'], '010');
		$service->addEventListener(['loadClassMetadata'], '011');
		return $service;
	}


	public function createServiceNettrineDbal__logger(): Doctrine\DBAL\Logging\LoggerChain
	{
		return new Doctrine\DBAL\Logging\LoggerChain;
	}


	public function createServiceNettrineOrm__configuration(): Doctrine\ORM\Configuration
	{
		$service = new Doctrine\ORM\Configuration;
		$service->setProxyDir('/Users/akadlec/Development/FastyBird/other/miniserver-app/vendor/fastybird/bootstrap/src/Boot/../../../../../var/temp/cache/doctrine.proxies');
		$service->setAutoGenerateProxyClasses(2);
		$service->setProxyNamespace('Nettrine\Proxy');
		$service->setMetadataDriverImpl($this->getService('nettrineOrm.mappingDriver'));
		$service->setCustomStringFunctions([]);
		$service->setCustomNumericFunctions([]);
		$service->setCustomDatetimeFunctions([]);
		$service->setCustomHydrationModes([]);
		$service->setNamingStrategy(new Doctrine\ORM\Mapping\UnderscoreNamingStrategy);
		$service->setEntityListenerResolver($this->getService('nettrineOrm.entityListenerResolver'));
		$service->setQueryCacheImpl($this->getService('nettrineCache.driver'));
		$service->setHydrationCacheImpl($this->getService('nettrineCache.driver'));
		$service->setResultCacheImpl($this->getService('nettrineCache.driver'));
		$service->setMetadataCacheImpl($this->getService('nettrineCache.driver'));
		$service->setSecondLevelCacheEnabled();
		$service->setSecondLevelCacheConfiguration($this->getService('nettrineOrmCache.cacheConfiguration'));
		return $service;
	}


	public function createServiceNettrineOrm__entityListenerResolver(): Nettrine\ORM\Mapping\ContainerEntityListenerResolver
	{
		return new Nettrine\ORM\Mapping\ContainerEntityListenerResolver($this);
	}


	public function createServiceNettrineOrm__entityManagerDecorator(): Nettrine\ORM\EntityManagerDecorator
	{
		$service = new Nettrine\ORM\EntityManagerDecorator(Doctrine\ORM\EntityManager::create(
			$this->getService('nettrineDbal.connection'),
			$this->getService('nettrineOrm.configuration'),
			$this->getService('nettrineDbal.eventManager')
		));
		$service->getEventManager()->addEventSubscriber($this->getService('ipubDoctrinePhone.subscriber'));
		$service->getConfiguration()->addCustomStringFunction('DATE_FORMAT', 'IPub\DoctrineCrud\StringFunctions\DateFormat');
		$service->getEventManager()->addEventSubscriber($this->getService('ipubDoctrineTimestampable.subscriber'));
		$service->getEventManager()->addEventSubscriber($this->getService('ipubDoctrineDynamicDiscriminatorMap.subscriber'));
		return $service;
	}


	public function createServiceNettrineOrm__managerRegistry(): Nettrine\ORM\ManagerRegistry
	{
		return new Nettrine\ORM\ManagerRegistry(
			$this->getService('nettrineDbal.connection'),
			$this->getService('nettrineOrm.entityManagerDecorator'),
			$this
		);
	}


	public function createServiceNettrineOrm__mappingDriver(): Doctrine\Persistence\Mapping\Driver\MappingDriverChain
	{
		$service = new Doctrine\Persistence\Mapping\Driver\MappingDriverChain;
		$service->addDriver($this->getService('nettrineOrmAnnotations.annotationDriver'), 'FastyBird\SimpleAuth\Entities');
		$service->addDriver($this->getService('nettrineOrmAnnotations.annotationDriver'), 'FastyBird\AuthModule\Entities');
		$service->addDriver($this->getService('nettrineOrmAnnotations.annotationDriver'), 'FastyBird\DevicesModule\Entities');
		$service->addDriver($this->getService('nettrineOrmAnnotations.annotationDriver'), 'FastyBird\TriggersModule\Entities');
		$service->addDriver($this->getService('nettrineOrmAnnotations.annotationDriver'), 'FastyBird\UIModule\Entities');
		return $service;
	}


	public function createServiceNettrineOrmAnnotations__annotationDriver(): Doctrine\ORM\Mapping\Driver\AnnotationDriver
	{
		$service = new Doctrine\ORM\Mapping\Driver\AnnotationDriver($this->getService('nettrineAnnotations.reader'));
		$service->addExcludePaths([]);
		$service->addPaths([
			'/Users/akadlec/Development/FastyBird/other/miniserver-app/vendor/fastybird/simple-auth/src/DI/../Entities',
		]);
		$service->addPaths([
			'/Users/akadlec/Development/FastyBird/other/miniserver-app/vendor/fastybird/auth-module/src/DI/../Entities',
		]);
		$service->addPaths([
			'/Users/akadlec/Development/FastyBird/other/miniserver-app/vendor/fastybird/devices-module/src/DI/../Entities',
		]);
		$service->addPaths([
			'/Users/akadlec/Development/FastyBird/other/miniserver-app/vendor/fastybird/triggers-module/src/DI/../Entities',
		]);
		$service->addPaths([
			'/Users/akadlec/Development/FastyBird/other/miniserver-app/vendor/fastybird/ui-module/src/DI/../Entities',
		]);
		return $service;
	}


	public function createServiceNettrineOrmCache__cacheConfiguration(): Doctrine\ORM\Cache\CacheConfiguration
	{
		$service = new Doctrine\ORM\Cache\CacheConfiguration;
		$service->setCacheFactory($this->getService('nettrineOrmCache.cacheFactory'));
		return $service;
	}


	public function createServiceNettrineOrmCache__cacheFactory(): Doctrine\ORM\Cache\DefaultCacheFactory
	{
		return new Doctrine\ORM\Cache\DefaultCacheFactory(
			$this->getService('nettrineOrmCache.regions'),
			$this->getService('nettrineCache.driver')
		);
	}


	public function createServiceNettrineOrmCache__regions(): Doctrine\ORM\Cache\RegionsConfiguration
	{
		return new Doctrine\ORM\Cache\RegionsConfiguration;
	}


	public function createServiceReact__eventLoop(): React\EventLoop\LoopInterface
	{
		return React\EventLoop\Factory::create();
	}


	public function createServiceReact__socketServer(): React\Socket\Server
	{
		return new React\Socket\Server('0.0.0.0:8000', $this->getService('react.eventLoop'));
	}


	public function createServiceSecurity__user(): FastyBird\AuthModule\Security\User
	{
		return new FastyBird\AuthModule\Security\User(
			$this->getService('fbSimpleAuth.security.userStorage'),
			$this->getService('fbAuthModule.security.authenticator')
		);
	}


	public function createServiceSession__session(): Nette\Http\Session
	{
		return new Nette\Http\Session($this->getService('http.request'), $this->getService('http.response'));
	}


	public function createServiceTracy__bar(): Tracy\Bar
	{
		return Tracy\Debugger::getBar();
	}


	public function createServiceTracy__blueScreen(): Tracy\BlueScreen
	{
		return Tracy\Debugger::getBlueScreen();
	}


	public function createServiceTracy__logger(): Tracy\ILogger
	{
		return Tracy\Debugger::getLogger();
	}


	public function initialize()
	{
		Doctrine\Common\Annotations\AnnotationRegistry::registerUniqueLoader("class_exists");
		$this->getService('tracy.bar')->addPanel(new Nette\Bridges\DITracy\ContainerPanel($this));
		(function () {
			$response = $this->getService('http.response');
			$response->setHeader('X-Powered-By', 'Nette Framework 3');
			$response->setHeader('Content-Type', 'text/html; charset=utf-8');
			$response->setHeader('X-Frame-Options', 'SAMEORIGIN');
			$response->setCookie('nette-samesite', '1', 0, '/', null, null, true, 'Strict');
		})();
		$this->getService('session.session')->exists() && $this->getService('session.session')->start();
		(function () {
			if (!Tracy\Debugger::isEnabled()) { return; }
			$this->getService('session.session')->start();
			Tracy\Debugger::dispatch();
		})();
		$this->getService("tracy.logger");
		Tracy\Debugger::setLogger($this->getService('contributteMonolog.psrToTracyLazyAdapter'));
		$this->getService('tracy.bar')->addPanel($this->getService('contributteTranslation.tracyPanel'));

		if (!Doctrine\DBAL\Types\Type::hasType('phone')) { Doctrine\DBAL\Types\Type::addType('phone', 'IPub\DoctrinePhone\Types\Phone'); }
		if (!Doctrine\DBAL\Types\Type::hasType('boolean_enum')) { Doctrine\DBAL\Types\Type::addType('boolean_enum', 'Consistence\Doctrine\Enum\Type\BooleanEnumType'); }
		if (!Doctrine\DBAL\Types\Type::hasType('float_enum')) { Doctrine\DBAL\Types\Type::addType('float_enum', 'Consistence\Doctrine\Enum\Type\FloatEnumType'); }
		if (!Doctrine\DBAL\Types\Type::hasType('integer_enum')) { Doctrine\DBAL\Types\Type::addType('integer_enum', 'Consistence\Doctrine\Enum\Type\IntegerEnumType'); }
		if (!Doctrine\DBAL\Types\Type::hasType('string_enum')) { Doctrine\DBAL\Types\Type::addType('string_enum', 'Consistence\Doctrine\Enum\Type\StringEnumType'); }
	}
}
