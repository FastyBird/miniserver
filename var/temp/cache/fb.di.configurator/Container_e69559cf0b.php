<?php
// source: /srv/fastybird/vendor/fastybird/fastybird/src/FastyBird/Library/Bootstrap/src/Boot/../../config/common.neon
// source: /srv/fastybird/vendor/fastybird/fastybird/src/FastyBird/Library/Bootstrap/src/Boot/../../config/defaults.neon
// source: /srv/fastybird/config/common.neon
// source: /srv/fastybird/config/defaults.neon
// source: /srv/fastybird/config/local.neon
// source: array

/** @noinspection PhpParamsInspection,PhpMethodMayBeStaticInspection */

declare(strict_types=1);

class Container_e69559cf0b extends Nette\DI\Container
{
	protected $types = ['container' => 'Nette\DI\Container'];

	protected $tags = [
		'nette.inject' => [
			'fbAccountsModule.controllers.account' => true,
			'fbAccountsModule.controllers.accountEmails' => true,
			'fbAccountsModule.controllers.accountIdentities' => true,
			'fbAccountsModule.controllers.accounts' => true,
			'fbAccountsModule.controllers.emails' => true,
			'fbAccountsModule.controllers.identities' => true,
			'fbAccountsModule.controllers.public' => true,
			'fbAccountsModule.controllers.roleChildren' => true,
			'fbAccountsModule.controllers.roles' => true,
			'fbAccountsModule.controllers.session' => true,
			'fbDevicesModule.controllers.channelControls' => true,
			'fbDevicesModule.controllers.channelProperties' => true,
			'fbDevicesModule.controllers.channelPropertyChildren' => true,
			'fbDevicesModule.controllers.channels' => true,
			'fbDevicesModule.controllers.connectorProperties' => true,
			'fbDevicesModule.controllers.connectors' => true,
			'fbDevicesModule.controllers.connectorsControls' => true,
			'fbDevicesModule.controllers.deviceAttributes' => true,
			'fbDevicesModule.controllers.deviceChildren' => true,
			'fbDevicesModule.controllers.deviceControls' => true,
			'fbDevicesModule.controllers.deviceParents' => true,
			'fbDevicesModule.controllers.deviceProperties' => true,
			'fbDevicesModule.controllers.devicePropertyChildren' => true,
			'fbDevicesModule.controllers.devices' => true,
			'fbHomeKitConnector.http.controllers.accessories' => true,
			'fbHomeKitConnector.http.controllers.characteristics' => true,
			'fbHomeKitConnector.http.controllers.pairing' => true,
			'fbTriggersModule.controllers.actions' => true,
			'fbTriggersModule.controllers.conditions' => true,
			'fbTriggersModule.controllers.notifications' => true,
			'fbTriggersModule.controllers.triggers' => true,
			'fbTriggersModule.controllers.triggersControls' => true,
			'fbWsExchangePlugin.controllers.exchange' => true,
			'nettrineFixtures.loadDataFixturesCommand' => true,
		],
		'middleware' => ['fbAccountsModule.middlewares.urlFormat' => true],
		'consumer_status' => [
			'fbDevicesModule.exchange.consumer.states' => false,
			'fbHomeKitConnector.consumers.exchange' => false,
			'fbWsExchangePlugin.exchange.consumer' => false,
		],
		'connector_type' => [
			'fbFbMqttConnector.executor.factory' => 'fb-mqtt',
			'fbHomeKitConnector.executor.factory' => 'homekit',
			'fbModbusConnector.executor.factory' => 'modbus',
			'fbShellyConnector.executor.factory' => 'shelly',
			'fbTuyaConnector.executor.factory' => 'tuya',
		],
		'ipub.websockets.controller' => [
			'fbWsExchangePlugin.controllers.exchange' => 'FastyBird\Plugin\WsExchange\Controllers\Exchange',
		],
		'console.command' => [
			'nettrineDbalConsole.reservedWordsCommand' => 'dbal:reserved-words',
			'nettrineDbalConsole.runSqlCommand' => 'dbal:run-sql',
			'nettrineFixtures.loadDataFixturesCommand' => 'doctrine:fixtures:load',
			'nettrineMigrations.currentCommand' => 'migrations:current',
			'nettrineMigrations.diffCommand' => 'migrations:diff',
			'nettrineMigrations.dumpSchemaCommand' => 'migrations:dump-schema',
			'nettrineMigrations.executeCommand' => 'migrations:execute',
			'nettrineMigrations.generateCommand' => 'migrations:generate',
			'nettrineMigrations.latestCommand' => 'migrations:latest',
			'nettrineMigrations.listCommand' => 'migrations:list',
			'nettrineMigrations.migrateCommand' => 'migrations:migrate',
			'nettrineMigrations.rollupCommand' => 'migrations:rollup',
			'nettrineMigrations.statusCommand' => 'migrations:status',
			'nettrineMigrations.syncMetadataCommand' => 'migrations:sync-metadata-storage',
			'nettrineMigrations.upToDateCommand' => 'migrations:up-to-date',
			'nettrineMigrations.versionCommand' => 'migrations:version',
			'nettrineOrmConsole.clearMetadataCacheCommand' => 'orm:clear-cache:metadata',
			'nettrineOrmConsole.convertMappingCommand' => 'orm:convert-mapping',
			'nettrineOrmConsole.ensureProductionSettingsCommand' => 'orm:ensure-production-settings',
			'nettrineOrmConsole.generateEntitiesCommand' => 'orm:generate-entities',
			'nettrineOrmConsole.generateProxiesCommand' => 'orm:generate-proxies',
			'nettrineOrmConsole.generateRepositoriesCommand' => 'orm:generate-repositories',
			'nettrineOrmConsole.infoCommand' => 'orm:info',
			'nettrineOrmConsole.mappingDescribeCommand' => 'orm:mapping:describe',
			'nettrineOrmConsole.runDqlCommand' => 'orm:run-dql',
			'nettrineOrmConsole.schemaToolCreateCommand' => 'orm:schema-tool:create',
			'nettrineOrmConsole.schemaToolDropCommand' => 'orm:schema-tool:drop',
			'nettrineOrmConsole.schemaToolUpdateCommand' => 'orm:schema-tool:update',
			'nettrineOrmConsole.validateSchemaCommand' => 'orm:validate-schema',
		],
		'nettrine.orm.mapping.driver' => ['nettrineOrm.mappingDriver' => true],
		'nettrine.orm.annotation.driver' => ['nettrineOrmAnnotations.annotationDriver' => true],
	];

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
		'Symfony\Component\Console\Application' => [['contributteConsole.application']],
		'Symfony\Contracts\Service\ResetInterface' => [['contributteConsole.application']],
		'Contributte\Console\Application' => [['contributteConsole.application']],
		'Monolog\Handler\AbstractProcessingHandler' => [
			0 => ['fbBootstrapLibrary.logger.handler.rotatingFile', 'fbBootstrapLibrary.logger.handler.stdOut'],
			2 => ['contributteMonolog.logger.default.handler.0'],
		],
		'Monolog\Handler\AbstractHandler' => [
			0 => ['fbBootstrapLibrary.logger.handler.rotatingFile', 'fbBootstrapLibrary.logger.handler.stdOut'],
			2 => ['contributteMonolog.logger.default.handler.0'],
		],
		'Monolog\Handler\Handler' => [
			0 => ['fbBootstrapLibrary.logger.handler.rotatingFile', 'fbBootstrapLibrary.logger.handler.stdOut'],
			2 => ['contributteMonolog.logger.default.handler.0'],
		],
		'Monolog\Handler\FormattableHandlerInterface' => [
			0 => ['fbBootstrapLibrary.logger.handler.rotatingFile', 'fbBootstrapLibrary.logger.handler.stdOut'],
			2 => ['contributteMonolog.logger.default.handler.0'],
		],
		'Monolog\Handler\ProcessableHandlerInterface' => [
			0 => ['fbBootstrapLibrary.logger.handler.rotatingFile', 'fbBootstrapLibrary.logger.handler.stdOut'],
			2 => ['contributteMonolog.logger.default.handler.0'],
		],
		'Monolog\ResettableInterface' => [
			0 => [
				'contributteMonolog.logger.default',
				'fbBootstrapLibrary.logger.handler.rotatingFile',
				'fbBootstrapLibrary.logger.handler.stdOut',
			],
			2 => ['contributteMonolog.logger.default.handler.0'],
		],
		'Monolog\Handler\HandlerInterface' => [
			0 => ['fbBootstrapLibrary.logger.handler.rotatingFile', 'fbBootstrapLibrary.logger.handler.stdOut'],
			2 => ['contributteMonolog.logger.default.handler.0'],
		],
		'Mangoweb\MonologTracyHandler\TracyHandler' => [2 => ['contributteMonolog.logger.default.handler.0']],
		'Monolog\Processor\MemoryProcessor' => [2 => ['contributteMonolog.logger.default.processor.0']],
		'Monolog\Processor\ProcessorInterface' => [
			2 => ['contributteMonolog.logger.default.processor.0', 'contributteMonolog.logger.default.processor.1'],
		],
		'Monolog\Processor\MemoryPeakUsageProcessor' => [2 => ['contributteMonolog.logger.default.processor.0']],
		'Mangoweb\MonologTracyHandler\TracyProcessor' => [2 => ['contributteMonolog.logger.default.processor.1']],
		'Psr\Log\LoggerInterface' => [['contributteMonolog.logger.default']],
		'Monolog\Logger' => [['contributteMonolog.logger.default']],
		'Tracy\Bridges\Psr\PsrToTracyLoggerAdapter' => [2 => ['contributteMonolog.psrToTracyAdapter']],
		'Contributte\Monolog\Tracy\LazyTracyLogger' => [['contributteMonolog.psrToTracyLazyAdapter']],
		'Monolog\Handler\StreamHandler' => [
			['fbBootstrapLibrary.logger.handler.rotatingFile', 'fbBootstrapLibrary.logger.handler.stdOut'],
		],
		'Monolog\Handler\RotatingFileHandler' => [['fbBootstrapLibrary.logger.handler.rotatingFile']],
		'FastyBird\Library\Bootstrap\Helpers\Database' => [['fbBootstrapLibrary.helpers.database']],
		'Contributte\Translation\LocaleResolver' => [['contributteTranslation.localeResolver']],
		'Contributte\Translation\FallbackResolver' => [['contributteTranslation.fallbackResolver']],
		'Symfony\Component\Config\ConfigCacheFactoryInterface' => [['contributteTranslation.configCacheFactory']],
		'Symfony\Component\Config\ConfigCacheFactory' => [['contributteTranslation.configCacheFactory']],
		'Symfony\Component\Translation\Translator' => [['contributteTranslation.translator']],
		'Symfony\Contracts\Translation\TranslatorInterface' => [['contributteTranslation.translator']],
		'Symfony\Component\Translation\TranslatorBagInterface' => [1 => ['contributteTranslation.translator']],
		'Symfony\Contracts\Translation\LocaleAwareInterface' => [1 => ['contributteTranslation.translator']],
		'Nette\Localization\Translator' => [['contributteTranslation.translator']],
		'Contributte\Translation\Translator' => [['contributteTranslation.translator']],
		'Symfony\Component\Translation\Loader\ArrayLoader' => [['contributteTranslation.loaderNeon']],
		'Symfony\Component\Translation\Loader\LoaderInterface' => [['contributteTranslation.loaderNeon']],
		'Contributte\Translation\Loaders\Neon' => [['contributteTranslation.loaderNeon']],
		'Tracy\IBarPanel' => [['contributteTranslation.tracyPanel']],
		'Contributte\Translation\Tracy\Panel' => [['contributteTranslation.tracyPanel']],
		'Symfony\Contracts\EventDispatcher\EventDispatcherInterface' => [['contributteEvents.dispatcher']],
		'Psr\EventDispatcher\EventDispatcherInterface' => [['contributteEvents.dispatcher']],
		'Symfony\Component\EventDispatcher\EventDispatcherInterface' => [['contributteEvents.dispatcher']],
		'League\Flysystem\FilesystemOperator' => [['contributeFlysystem.mountManager']],
		'League\Flysystem\FilesystemWriter' => [['contributeFlysystem.mountManager']],
		'League\Flysystem\FilesystemReader' => [['contributeFlysystem.mountManager']],
		'League\Flysystem\MountManager' => [['contributeFlysystem.mountManager']],
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
		'Doctrine\DBAL\Connection' => [['nettrineDbal.connection']],
		'Nettrine\DBAL\ConnectionAccessor' => [['nettrineDbal.connectionAccessor']],
		'Doctrine\DBAL\Tools\Console\ConnectionProvider' => [2 => ['nettrineDbalConsole.connectionProvider']],
		'Doctrine\DBAL\Tools\Console\ConnectionProvider\SingleConnectionProvider' => [
			2 => ['nettrineDbalConsole.connectionProvider'],
		],
		'Symfony\Component\Console\Command\Command' => [
			0 => [
				'nettrineFixtures.loadDataFixturesCommand',
				'ipubWebsockets.commands.server',
				'fbRedisDbPlugin.commands.client',
				'fbWebServerPlugin.commands.server',
				'fbWsExchangePlugin.commands.server',
				'fbAccountsModule.commands.create',
				'fbAccountsModule.commands.initialize',
				'fbDevicesModule.commands.initialize',
				'fbDevicesModule.commands.connector',
				'fbTriggersModule.commands.initialize',
				'fbShellyConnector.commands.initialize',
				'fbShellyConnector.commands.discovery',
				'fbShellyConnector.commands.execute',
				'fbModbusConnector.commands.initialize',
				'fbModbusConnector.commands.execute',
				'fbHomeKitConnector.commands.initialize',
				'fbHomeKitConnector.commands.execute',
				'fbTuyaConnector.commands.initialize',
				'fbTuyaConnector.commands.discovery',
				'fbTuyaConnector.commands.execute',
			],
			2 => [
				'nettrineDbalConsole.reservedWordsCommand',
				'nettrineDbalConsole.runSqlCommand',
				'nettrineOrmConsole.schemaToolCreateCommand',
				'nettrineOrmConsole.schemaToolUpdateCommand',
				'nettrineOrmConsole.schemaToolDropCommand',
				'nettrineOrmConsole.convertMappingCommand',
				'nettrineOrmConsole.ensureProductionSettingsCommand',
				'nettrineOrmConsole.generateEntitiesCommand',
				'nettrineOrmConsole.generateProxiesCommand',
				'nettrineOrmConsole.generateRepositoriesCommand',
				'nettrineOrmConsole.infoCommand',
				'nettrineOrmConsole.mappingDescribeCommand',
				'nettrineOrmConsole.runDqlCommand',
				'nettrineOrmConsole.validateSchemaCommand',
				'nettrineOrmConsole.clearMetadataCacheCommand',
				'nettrineMigrations.currentCommand',
				'nettrineMigrations.diffCommand',
				'nettrineMigrations.dumpSchemaCommand',
				'nettrineMigrations.executeCommand',
				'nettrineMigrations.generateCommand',
				'nettrineMigrations.latestCommand',
				'nettrineMigrations.listCommand',
				'nettrineMigrations.migrateCommand',
				'nettrineMigrations.rollupCommand',
				'nettrineMigrations.statusCommand',
				'nettrineMigrations.syncMetadataCommand',
				'nettrineMigrations.upToDateCommand',
				'nettrineMigrations.versionCommand',
			],
		],
		'Doctrine\DBAL\Tools\Console\Command\ReservedWordsCommand' => [2 => ['nettrineDbalConsole.reservedWordsCommand']],
		'Doctrine\DBAL\Tools\Console\Command\RunSqlCommand' => [2 => ['nettrineDbalConsole.runSqlCommand']],
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
		'Doctrine\ORM\Mapping\Driver\CompatibilityAnnotationDriver' => [2 => ['nettrineOrmAnnotations.annotationDriver']],
		'Doctrine\ORM\Mapping\Driver\AnnotationDriver' => [2 => ['nettrineOrmAnnotations.annotationDriver']],
		'Symfony\Component\Console\Helper\Helper' => [2 => ['nettrineOrmConsole.entityManagerHelper']],
		'Symfony\Component\Console\Helper\HelperInterface' => [2 => ['nettrineOrmConsole.entityManagerHelper']],
		'Doctrine\ORM\Tools\Console\Helper\EntityManagerHelper' => [2 => ['nettrineOrmConsole.entityManagerHelper']],
		'Doctrine\ORM\Tools\Console\Command\SchemaTool\AbstractCommand' => [
			2 => [
				'nettrineOrmConsole.schemaToolCreateCommand',
				'nettrineOrmConsole.schemaToolUpdateCommand',
				'nettrineOrmConsole.schemaToolDropCommand',
			],
		],
		'Doctrine\ORM\Tools\Console\Command\AbstractEntityManagerCommand' => [
			2 => [
				'nettrineOrmConsole.schemaToolCreateCommand',
				'nettrineOrmConsole.schemaToolUpdateCommand',
				'nettrineOrmConsole.schemaToolDropCommand',
				'nettrineOrmConsole.convertMappingCommand',
				'nettrineOrmConsole.ensureProductionSettingsCommand',
				'nettrineOrmConsole.generateEntitiesCommand',
				'nettrineOrmConsole.generateProxiesCommand',
				'nettrineOrmConsole.generateRepositoriesCommand',
				'nettrineOrmConsole.infoCommand',
				'nettrineOrmConsole.mappingDescribeCommand',
				'nettrineOrmConsole.runDqlCommand',
				'nettrineOrmConsole.validateSchemaCommand',
				'nettrineOrmConsole.clearMetadataCacheCommand',
			],
		],
		'Doctrine\ORM\Tools\Console\Command\SchemaTool\CreateCommand' => [
			2 => ['nettrineOrmConsole.schemaToolCreateCommand'],
		],
		'Doctrine\ORM\Tools\Console\Command\SchemaTool\UpdateCommand' => [
			2 => ['nettrineOrmConsole.schemaToolUpdateCommand'],
		],
		'Doctrine\ORM\Tools\Console\Command\SchemaTool\DropCommand' => [2 => ['nettrineOrmConsole.schemaToolDropCommand']],
		'Doctrine\ORM\Tools\Console\Command\ConvertMappingCommand' => [2 => ['nettrineOrmConsole.convertMappingCommand']],
		'Doctrine\ORM\Tools\Console\Command\EnsureProductionSettingsCommand' => [
			2 => ['nettrineOrmConsole.ensureProductionSettingsCommand'],
		],
		'Doctrine\ORM\Tools\Console\Command\GenerateEntitiesCommand' => [
			2 => ['nettrineOrmConsole.generateEntitiesCommand'],
		],
		'Doctrine\ORM\Tools\Console\Command\GenerateProxiesCommand' => [2 => ['nettrineOrmConsole.generateProxiesCommand']],
		'Doctrine\ORM\Tools\Console\Command\GenerateRepositoriesCommand' => [
			2 => ['nettrineOrmConsole.generateRepositoriesCommand'],
		],
		'Doctrine\ORM\Tools\Console\Command\InfoCommand' => [2 => ['nettrineOrmConsole.infoCommand']],
		'Doctrine\ORM\Tools\Console\Command\MappingDescribeCommand' => [2 => ['nettrineOrmConsole.mappingDescribeCommand']],
		'Doctrine\ORM\Tools\Console\Command\RunDqlCommand' => [2 => ['nettrineOrmConsole.runDqlCommand']],
		'Doctrine\ORM\Tools\Console\Command\ValidateSchemaCommand' => [2 => ['nettrineOrmConsole.validateSchemaCommand']],
		'Doctrine\ORM\Tools\Console\Command\ClearCache\MetadataCommand' => [
			2 => ['nettrineOrmConsole.clearMetadataCacheCommand'],
		],
		'Doctrine\ORM\Cache\RegionsConfiguration' => [2 => ['nettrineOrmCache.regions']],
		'Doctrine\ORM\Cache\CacheFactory' => [2 => ['nettrineOrmCache.cacheFactory']],
		'Doctrine\ORM\Cache\DefaultCacheFactory' => [2 => ['nettrineOrmCache.cacheFactory']],
		'Doctrine\ORM\Cache\CacheConfiguration' => [2 => ['nettrineOrmCache.cacheConfiguration']],
		'Doctrine\Migrations\Metadata\Storage\MetadataStorageConfiguration' => [
			['nettrineMigrations.configuration.tableStorage'],
		],
		'Doctrine\Migrations\Metadata\Storage\TableMetadataStorageConfiguration' => [
			['nettrineMigrations.configuration.tableStorage'],
		],
		'Doctrine\Migrations\Configuration\Configuration' => [['nettrineMigrations.configuration']],
		'Doctrine\Migrations\Version\MigrationFactory' => [['nettrineMigrations.migrationFactory']],
		'Nettrine\Migrations\Version\DbalMigrationFactory' => [['nettrineMigrations.migrationFactory']],
		'Nettrine\Migrations\DI\DependencyFactory' => [2 => ['nettrineMigrations.nettrineDependencyFactory']],
		'Doctrine\Migrations\DependencyFactory' => [['nettrineMigrations.dependencyFactory']],
		'Doctrine\Migrations\Tools\Console\Command\DoctrineCommand' => [
			2 => [
				'nettrineMigrations.currentCommand',
				'nettrineMigrations.diffCommand',
				'nettrineMigrations.dumpSchemaCommand',
				'nettrineMigrations.executeCommand',
				'nettrineMigrations.generateCommand',
				'nettrineMigrations.latestCommand',
				'nettrineMigrations.listCommand',
				'nettrineMigrations.migrateCommand',
				'nettrineMigrations.rollupCommand',
				'nettrineMigrations.statusCommand',
				'nettrineMigrations.syncMetadataCommand',
				'nettrineMigrations.upToDateCommand',
				'nettrineMigrations.versionCommand',
			],
		],
		'Doctrine\Migrations\Tools\Console\Command\CurrentCommand' => [2 => ['nettrineMigrations.currentCommand']],
		'Doctrine\Migrations\Tools\Console\Command\DiffCommand' => [2 => ['nettrineMigrations.diffCommand']],
		'Doctrine\Migrations\Tools\Console\Command\DumpSchemaCommand' => [2 => ['nettrineMigrations.dumpSchemaCommand']],
		'Doctrine\Migrations\Tools\Console\Command\ExecuteCommand' => [2 => ['nettrineMigrations.executeCommand']],
		'Doctrine\Migrations\Tools\Console\Command\GenerateCommand' => [2 => ['nettrineMigrations.generateCommand']],
		'Doctrine\Migrations\Tools\Console\Command\LatestCommand' => [2 => ['nettrineMigrations.latestCommand']],
		'Doctrine\Migrations\Tools\Console\Command\ListCommand' => [2 => ['nettrineMigrations.listCommand']],
		'Doctrine\Migrations\Tools\Console\Command\MigrateCommand' => [2 => ['nettrineMigrations.migrateCommand']],
		'Doctrine\Migrations\Tools\Console\Command\RollupCommand' => [2 => ['nettrineMigrations.rollupCommand']],
		'Doctrine\Migrations\Tools\Console\Command\StatusCommand' => [2 => ['nettrineMigrations.statusCommand']],
		'Doctrine\Migrations\Tools\Console\Command\SyncMetadataCommand' => [
			2 => ['nettrineMigrations.syncMetadataCommand'],
		],
		'Doctrine\Migrations\Tools\Console\Command\UpToDateCommand' => [2 => ['nettrineMigrations.upToDateCommand']],
		'Doctrine\Migrations\Tools\Console\Command\VersionCommand' => [2 => ['nettrineMigrations.versionCommand']],
		'Doctrine\Common\DataFixtures\Loader' => [['nettrineFixtures.fixturesLoader']],
		'Nettrine\Fixtures\Loader\FixturesLoader' => [['nettrineFixtures.fixturesLoader']],
		'Nettrine\Fixtures\Command\LoadDataFixturesCommand' => [['nettrineFixtures.loadDataFixturesCommand']],
		'libphonenumber\PhoneNumberUtil' => [['ipubPhone.libphone.utils']],
		'libphonenumber\geocoding\PhoneNumberOfflineGeocoder' => [['ipubPhone.libphone.geoCoder']],
		'libphonenumber\ShortNumberInfo' => [['ipubPhone.libphone.shortNumber']],
		'libphonenumber\PhoneNumberToCarrierMapper' => [['ipubPhone.libphone.mapper.carrier']],
		'libphonenumber\PhoneNumberToTimeZonesMapper' => [['ipubPhone.libphone.mapper.timezone']],
		'IPub\Phone\Phone' => [['ipubPhone.phone']],
		'Doctrine\Common\EventSubscriber' => [
			[
				'ipubDoctrinePhone.subscriber',
				'ipubDoctrineConsistence.subscriber',
				'ipubDoctrineTimestampable.subscriber',
				'ipubDoctrineDynamicDiscriminatorMap.subscriber',
				'fbSimpleAuth.doctrine.subscriber',
				'fbAccountsModule.subscribers.entities',
				'fbAccountsModule.subscribers.accountEntity',
				'fbAccountsModule.subscribers.emailEntity',
				'fbDevicesModule.subscribers.entities',
				'fbTriggersModule.subscribers.notificationEntity',
				'fbTriggersModule.subscribers.entities',
				'fbShellyConnector.subscribers.properties',
				'fbModbusConnector.subscribers.properties',
				'fbTuyaConnector.subscribers.properties',
			],
		],
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
		'IPub\WebSockets\Application\Controller\IControllerFactory' => [['ipubWebsockets.controllers.factory']],
		'IPub\WebSockets\Clients\Drivers\IDriver' => [['ipubWebsockets.clients.driver.memory']],
		'IPub\WebSockets\Clients\Drivers\InMemory' => [['ipubWebsockets.clients.driver.memory']],
		'IPub\WebSockets\Clients\IStorage' => [['ipubWebsockets.clients.storage']],
		'Traversable' => [
			2 => [
				'ipubWebsockets.clients.storage',
				'ipubWebsocketsWamp.topics.storage',
				'fbWebServerPlugin.routing.router',
				'fbHomeKitConnector.http.router',
			],
		],
		'IteratorAggregate' => [
			2 => [
				'ipubWebsockets.clients.storage',
				'ipubWebsocketsWamp.topics.storage',
				'fbWebServerPlugin.routing.router',
				'fbHomeKitConnector.http.router',
			],
		],
		'IPub\WebSockets\Clients\Storage' => [['ipubWebsockets.clients.storage']],
		'IPub\WebSockets\Router\IRouter' => [['ipubWebsockets.routing.router']],
		'IPub\WebSockets\Router\LinkGenerator' => [['ipubWebsockets.routing.generator']],
		'IPub\WebSockets\Server\IWrapper' => [['ipubWebsockets.server.wrapper', 'ipubWebsockets.server.flashWrapper']],
		'IPub\WebSockets\Server\Wrapper' => [['ipubWebsockets.server.wrapper']],
		'IPub\WebSockets\Server\FlashWrapper' => [['ipubWebsockets.server.flashWrapper']],
		'IPub\WebSockets\Server\Handlers' => [['ipubWebsockets.server.handlers']],
		'React\EventLoop\LoopInterface' => [['ipubWebsockets.server.loop']],
		'IPub\WebSockets\Server\Configuration' => [['ipubWebsockets.server.configuration']],
		'IPub\WebSockets\Server\Server' => [['ipubWebsockets.server.server']],
		'IPub\WebSockets\Commands\ServerCommand' => [['ipubWebsockets.commands.server']],
		'IPub\WebSocketsWAMP\Topics\Drivers\IDriver' => [['ipubWebsocketsWamp.topics.driver.memory']],
		'IPub\WebSocketsWAMP\Topics\Drivers\InMemory' => [['ipubWebsocketsWamp.topics.driver.memory']],
		'IPub\WebSocketsWAMP\Topics\IStorage' => [['ipubWebsocketsWamp.topics.storage']],
		'IPub\WebSocketsWAMP\Topics\Storage' => [['ipubWebsocketsWamp.topics.storage']],
		'IPub\WebSockets\Application\Application' => [['ipubWebsocketsWamp.application']],
		'IPub\WebSockets\Application\IApplication' => [['ipubWebsocketsWamp.application']],
		'IPub\WebSocketsWAMP\Application\IApplication' => [['ipubWebsocketsWamp.application']],
		'IPub\WebSocketsWAMP\Application\Application' => [['ipubWebsocketsWamp.application']],
		'IPub\WebSocketsWAMP\Serializers\PushMessageSerializer' => [['ipubWebsocketsWamp.serializer']],
		'IPub\WebSocketsWAMP\PushMessages\IConsumersRegistry' => [['ipubWebsocketsWamp.push.registry']],
		'IPub\WebSocketsWAMP\PushMessages\ConsumersRegistry' => [['ipubWebsocketsWamp.push.registry']],
		'IPub\WebSockets\Clients\IClientFactory' => [['ipubWebsocketsWamp.clients.factory']],
		'IPub\WebSocketsWAMP\Clients\ClientFactory' => [['ipubWebsocketsWamp.clients.factory']],
		'IPub\WebSocketsWAMP\Subscribers\OnServerStartHandler' => [['ipubWebsocketsWamp.subscribers.onServerStart']],
		'FastyBird\DateTimeFactory\Factory' => [['fbDateTimeFactory.datetime.factory']],
		'FastyBird\SimpleAuth\Auth' => [['fbSimpleAuth.auth']],
		'FastyBird\SimpleAuth\Security\TokenBuilder' => [['fbSimpleAuth.token.builder']],
		'FastyBird\SimpleAuth\Security\TokenReader' => [['fbSimpleAuth.token.reader']],
		'FastyBird\SimpleAuth\Security\TokenValidator' => [['fbSimpleAuth.token.validator']],
		'FastyBird\SimpleAuth\Security\IUserStorage' => [['fbSimpleAuth.security.userStorage']],
		'FastyBird\SimpleAuth\Security\UserStorage' => [['fbSimpleAuth.security.userStorage']],
		'FastyBird\SimpleAuth\Security\AnnotationChecker' => [['fbSimpleAuth.security.annotationChecker']],
		'Psr\Http\Server\MiddlewareInterface' => [
			[
				'fbSimpleAuth.middleware.access',
				'fbSimpleAuth.middleware.user',
				'fbJsonApi.middlewares.jsonapi',
				'fbAccountsModule.middlewares.access',
				'fbAccountsModule.middlewares.urlFormat',
				'fbDevicesModule.middlewares.access',
				'fbTriggersModule.middleware.access',
			],
		],
		'FastyBird\SimpleAuth\Middleware\Access' => [['fbSimpleAuth.middleware.access']],
		'FastyBird\SimpleAuth\Middleware\User' => [['fbSimpleAuth.middleware.user']],
		'FastyBird\SimpleAuth\Mapping\Driver\Owner' => [['fbSimpleAuth.doctrine.driver']],
		'FastyBird\SimpleAuth\Subscribers\User' => [['fbSimpleAuth.doctrine.subscriber']],
		'FastyBird\SimpleAuth\Models\Tokens\TokenRepository' => [['fbSimpleAuth.doctrine.tokenRepository']],
		'FastyBird\SimpleAuth\Models\Tokens\TokensManager' => [['fbSimpleAuth.doctrine.tokensManager']],
		'FastyBird\JsonApi\Builder\Builder' => [['fbJsonApi.builder']],
		'FastyBird\JsonApi\Middleware\JsonApi' => [['fbJsonApi.middlewares.jsonapi']],
		'FastyBird\JsonApi\Hydrators\Container' => [['fbJsonApi.hydrators.container']],
		'Neomerx\JsonApi\Schema\SchemaContainer' => [['fbJsonApi.schemas.container']],
		'Neomerx\JsonApi\Contracts\Schema\SchemaContainerInterface' => [['fbJsonApi.schemas.container']],
		'FastyBird\JsonApi\JsonApi\SchemaContainer' => [['fbJsonApi.schemas.container']],
		'FastyBird\JsonApi\Helpers\CrudReader' => [['fbJsonApi.helpers.crudReader']],
		'FastyBird\Library\Metadata\Schemas\Validator' => [['schema.validator']],
		'FastyBird\Library\Metadata\Loaders\MetadataLoader' => [['loaders.metadata']],
		'FastyBird\Library\Metadata\Loaders\SchemaLoader' => [['loaders.schema']],
		'FastyBird\Library\Metadata\Entities\EntityFactory' => [
			[
				'entity.factory.actions.connector.action',
				'entity.factory.actions.connector.property.action',
				'entity.factory.actions.device.action',
				'entity.factory.actions.device.property.action',
				'entity.factory.actions.channel.action',
				'entity.factory.actions.channel.property.action',
				'entity.factory.actions.trigger.action',
				'entity.factory.modules.accountsModule.account',
				'entity.factory.modules.accountsModule.email',
				'entity.factory.modules.accountsModule.identity',
				'entity.factory.modules.accountsModule.role',
				'entity.factory.modules.triggersModule.action',
				'entity.factory.modules.triggersModule.condition',
				'entity.factory.modules.triggersModule.notification',
				'entity.factory.modules.triggersModule.triggerControl',
				'entity.factory.modules.triggersModule.trigger',
				'entity.factory.modules.devicesModule.connector',
				'entity.factory.modules.devicesModule.connector.control',
				'entity.factory.modules.devicesModule.connector.property',
				'entity.factory.modules.devicesModule.device',
				'entity.factory.modules.devicesModule.device.control',
				'entity.factory.modules.devicesModule.device.property',
				'entity.factory.modules.devicesModule.device.attribute',
				'entity.factory.modules.devicesModule.channel',
				'entity.factory.modules.devicesModule.channel.control',
				'entity.factory.modules.devicesModule.channel.property',
			],
		],
		'FastyBird\Library\Metadata\Entities\Actions\ActionConnectorControlEntityFactory' => [
			['entity.factory.actions.connector.action'],
		],
		'FastyBird\Library\Metadata\Entities\Actions\ActionConnectorPropertyEntityFactory' => [
			['entity.factory.actions.connector.property.action'],
		],
		'FastyBird\Library\Metadata\Entities\Actions\ActionDeviceControlEntityFactory' => [
			['entity.factory.actions.device.action'],
		],
		'FastyBird\Library\Metadata\Entities\Actions\ActionDevicePropertyEntityFactory' => [
			['entity.factory.actions.device.property.action'],
		],
		'FastyBird\Library\Metadata\Entities\Actions\ActionChannelControlEntityFactory' => [
			['entity.factory.actions.channel.action'],
		],
		'FastyBird\Library\Metadata\Entities\Actions\ActionChannelPropertyEntityFactory' => [
			['entity.factory.actions.channel.property.action'],
		],
		'FastyBird\Library\Metadata\Entities\Actions\ActionTriggerControlEntityFactory' => [
			['entity.factory.actions.trigger.action'],
		],
		'FastyBird\Library\Metadata\Entities\AccountsModule\AccountEntityFactory' => [
			['entity.factory.modules.accountsModule.account'],
		],
		'FastyBird\Library\Metadata\Entities\AccountsModule\EmailEntityFactory' => [
			['entity.factory.modules.accountsModule.email'],
		],
		'FastyBird\Library\Metadata\Entities\AccountsModule\IdentityEntityFactory' => [
			['entity.factory.modules.accountsModule.identity'],
		],
		'FastyBird\Library\Metadata\Entities\AccountsModule\RoleEntityFactory' => [
			['entity.factory.modules.accountsModule.role'],
		],
		'FastyBird\Library\Metadata\Entities\TriggersModule\ActionEntityFactory' => [
			['entity.factory.modules.triggersModule.action'],
		],
		'FastyBird\Library\Metadata\Entities\TriggersModule\ConditionEntityFactory' => [
			['entity.factory.modules.triggersModule.condition'],
		],
		'FastyBird\Library\Metadata\Entities\TriggersModule\NotificationEntityFactory' => [
			['entity.factory.modules.triggersModule.notification'],
		],
		'FastyBird\Library\Metadata\Entities\TriggersModule\TriggerControlEntityFactory' => [
			['entity.factory.modules.triggersModule.triggerControl'],
		],
		'FastyBird\Library\Metadata\Entities\TriggersModule\TriggerEntityFactory' => [
			['entity.factory.modules.triggersModule.trigger'],
		],
		'FastyBird\Library\Metadata\Entities\DevicesModule\ConnectorEntityFactory' => [
			['entity.factory.modules.devicesModule.connector'],
		],
		'FastyBird\Library\Metadata\Entities\DevicesModule\ConnectorControlEntityFactory' => [
			['entity.factory.modules.devicesModule.connector.control'],
		],
		'FastyBird\Library\Metadata\Entities\DevicesModule\ConnectorPropertyEntityFactory' => [
			['entity.factory.modules.devicesModule.connector.property'],
		],
		'FastyBird\Library\Metadata\Entities\DevicesModule\DeviceEntityFactory' => [
			['entity.factory.modules.devicesModule.device'],
		],
		'FastyBird\Library\Metadata\Entities\DevicesModule\DeviceControlEntityFactory' => [
			['entity.factory.modules.devicesModule.device.control'],
		],
		'FastyBird\Library\Metadata\Entities\DevicesModule\DevicePropertyEntityFactory' => [
			['entity.factory.modules.devicesModule.device.property'],
		],
		'FastyBird\Library\Metadata\Entities\DevicesModule\DeviceAttributeEntityFactory' => [
			['entity.factory.modules.devicesModule.device.attribute'],
		],
		'FastyBird\Library\Metadata\Entities\DevicesModule\ChannelEntityFactory' => [
			['entity.factory.modules.devicesModule.channel'],
		],
		'FastyBird\Library\Metadata\Entities\DevicesModule\ChannelControlEntityFactory' => [
			['entity.factory.modules.devicesModule.channel.control'],
		],
		'FastyBird\Library\Metadata\Entities\DevicesModule\ChannelPropertyEntityFactory' => [
			['entity.factory.modules.devicesModule.channel.property'],
		],
		'FastyBird\Library\Exchange\Consumers\Consumer' => [
			0 => ['fbExchangeLibrary.consumer'],
			2 => [
				1 => 'fbWsExchangePlugin.exchange.consumer',
				'fbDevicesModule.exchange.consumer.states',
				'fbHomeKitConnector.consumers.exchange',
			],
		],
		'FastyBird\Library\Exchange\Consumers\Container' => [['fbExchangeLibrary.consumer']],
		'FastyBird\Library\Exchange\Publisher\Publisher' => [
			0 => ['fbExchangeLibrary.publisher'],
			2 => [1 => 'fbRedisDbPlugin.publisher'],
		],
		'FastyBird\Library\Exchange\Publisher\Container' => [['fbExchangeLibrary.publisher']],
		'FastyBird\Library\Exchange\Entities\EntityFactory' => [['fbExchangeLibrary.entityFactory']],
		'FastyBird\Plugin\RedisDb\Publishers\Publisher' => [2 => ['fbRedisDbPlugin.publisher']],
		'FastyBird\Plugin\RedisDb\Connections\Connection' => [['fbRedisDbPlugin.redis.connection']],
		'FastyBird\Plugin\RedisDb\Client\Client' => [['fbRedisDbPlugin.clients.sync']],
		'FastyBird\Plugin\RedisDb\Client\Factory' => [['fbRedisDbPlugin.clients.async.factory']],
		'FastyBird\Plugin\RedisDb\Models\StatesManagerFactory' => [['fbRedisDbPlugin.models.statesManagerFactory']],
		'FastyBird\Plugin\RedisDb\Models\StatesRepositoryFactory' => [['fbRedisDbPlugin.models.statesRepositoryFactory']],
		'Evenement\EventEmitter' => [['fbRedisDbPlugin.handlers.message', 'fbHomeKitConnector.helpers.connector']],
		'Evenement\EventEmitterInterface' => [['fbRedisDbPlugin.handlers.message', 'fbHomeKitConnector.helpers.connector']],
		'FastyBird\Plugin\RedisDb\Handlers\Message' => [['fbRedisDbPlugin.handlers.message']],
		'FastyBird\Plugin\RedisDb\Commands\RedisClient' => [['fbRedisDbPlugin.commands.client']],
		'FastyBird\Plugin\RedisDb\Utilities\IdentifierGenerator' => [['fbRedisDbPlugin.utilities.identifier']],
		'Symfony\Component\EventDispatcher\EventSubscriberInterface' => [
			[
				'fbRedisDbPlugin.subscribers.client',
				'fbWebServerPlugin.subscribers.server',
				'fbWsExchangePlugin.subscribers.client',
				'fbDevicesModule.subscribers.states',
				'fbHomeKitConnector.subscribers.connector',
			],
		],
		'FastyBird\Plugin\RedisDb\Subscribers\Client' => [['fbRedisDbPlugin.subscribers.client']],
		'Psr\Http\Message\ResponseFactoryInterface' => [['fbWebServerPlugin.routing.responseFactory']],
		'FastyBird\Plugin\WebServer\Http\ResponseFactory' => [['fbWebServerPlugin.routing.responseFactory']],
		'IPub\SlimRouter\Routing\Router' => [
			0 => ['fbWebServerPlugin.routing.router'],
			2 => [1 => 'fbHomeKitConnector.http.router'],
		],
		'IPub\SlimRouter\Routing\IRouter' => [
			0 => ['fbWebServerPlugin.routing.router'],
			2 => [1 => 'fbHomeKitConnector.http.router'],
		],
		'FastyBird\Plugin\WebServer\Router\Router' => [['fbWebServerPlugin.routing.router']],
		'FastyBird\Plugin\WebServer\Commands\HttpServer' => [['fbWebServerPlugin.commands.server']],
		'FastyBird\Plugin\WebServer\Middleware\Cors' => [['fbWebServerPlugin.middlewares.cors']],
		'FastyBird\Plugin\WebServer\Middleware\StaticFiles' => [['fbWebServerPlugin.middlewares.staticFiles']],
		'FastyBird\Plugin\WebServer\Middleware\Router' => [['fbWebServerPlugin.middlewares.router']],
		'FastyBird\Plugin\WebServer\Application\Console' => [['fbWebServerPlugin.application.console']],
		'FastyBird\Plugin\WebServer\Application\Application' => [['fbWebServerPlugin.application.classic']],
		'FastyBird\Plugin\WebServer\Server\Factory' => [['fbWebServerPlugin.server.factory']],
		'FastyBird\Plugin\WebServer\Subscribers\Server' => [['fbWebServerPlugin.subscribers.server']],
		'IPub\WebSockets\Application\Controller\Controller' => [['fbWsExchangePlugin.controllers.exchange']],
		'IPub\WebSockets\Application\Controller\IController' => [['fbWsExchangePlugin.controllers.exchange']],
		'FastyBird\Plugin\WsExchange\Controllers\Exchange' => [['fbWsExchangePlugin.controllers.exchange']],
		'FastyBird\Plugin\WsExchange\Publishers\Publisher' => [['fbWsExchangePlugin.exchange.publisher']],
		'FastyBird\Plugin\WsExchange\Consumers\Consumer' => [2 => ['fbWsExchangePlugin.exchange.consumer']],
		'FastyBird\Plugin\WsExchange\Commands\WsServer' => [['fbWsExchangePlugin.commands.server']],
		'FastyBird\Plugin\WsExchange\Server\Factory' => [['fbWsExchangePlugin.server.factory']],
		'FastyBird\Plugin\WsExchange\Subscribers\Client' => [['fbWsExchangePlugin.subscribers.client']],
		'FastyBird\Module\Accounts\Middleware\Access' => [['fbAccountsModule.middlewares.access']],
		'FastyBird\Module\Accounts\Middleware\UrlFormat' => [['fbAccountsModule.middlewares.urlFormat']],
		'FastyBird\Module\Accounts\Router\Routes' => [['fbAccountsModule.router.routes']],
		'FastyBird\Module\Accounts\Router\Validator' => [['fbAccountsModule.router.validator']],
		'FastyBird\Module\Accounts\Commands\Accounts\Create' => [['fbAccountsModule.commands.create']],
		'FastyBird\Module\Accounts\Commands\Initialize' => [['fbAccountsModule.commands.initialize']],
		'FastyBird\Module\Accounts\Models\Accounts\AccountsRepository' => [['fbAccountsModule.models.accountsRepository']],
		'FastyBird\Module\Accounts\Models\Emails\EmailsRepository' => [['fbAccountsModule.models.emailsRepository']],
		'FastyBird\Module\Accounts\Models\Identities\IdentitiesRepository' => [
			['fbAccountsModule.models.identitiesRepository'],
		],
		'FastyBird\Module\Accounts\Models\Roles\RolesRepository' => [['fbAccountsModule.models.rolesRepository']],
		'FastyBird\Module\Accounts\Models\Accounts\AccountsManager' => [['fbAccountsModule.models.accountsManager']],
		'FastyBird\Module\Accounts\Models\Emails\EmailsManager' => [['fbAccountsModule.models.emailsManager']],
		'FastyBird\Module\Accounts\Models\Identities\IdentitiesManager' => [['fbAccountsModule.models.identitiesManager']],
		'FastyBird\Module\Accounts\Models\Roles\RolesManager' => [['fbAccountsModule.models.rolesManager']],
		'FastyBird\Module\Accounts\Subscribers\ModuleEntities' => [['fbAccountsModule.subscribers.entities']],
		'FastyBird\Module\Accounts\Subscribers\AccountEntity' => [['fbAccountsModule.subscribers.accountEntity']],
		'FastyBird\Module\Accounts\Subscribers\EmailEntity' => [['fbAccountsModule.subscribers.emailEntity']],
		'FastyBird\Module\Accounts\Controllers\BaseV1' => [
			[
				'fbAccountsModule.controllers.session',
				'fbAccountsModule.controllers.account',
				'fbAccountsModule.controllers.accountEmails',
				'fbAccountsModule.controllers.accountIdentities',
				'fbAccountsModule.controllers.accounts',
				'fbAccountsModule.controllers.emails',
				'fbAccountsModule.controllers.identities',
				'fbAccountsModule.controllers.roles',
				'fbAccountsModule.controllers.roleChildren',
				'fbAccountsModule.controllers.public',
			],
		],
		'FastyBird\Module\Accounts\Controllers\SessionV1' => [['fbAccountsModule.controllers.session']],
		'FastyBird\Module\Accounts\Controllers\AccountV1' => [['fbAccountsModule.controllers.account']],
		'FastyBird\Module\Accounts\Controllers\AccountEmailsV1' => [['fbAccountsModule.controllers.accountEmails']],
		'FastyBird\Module\Accounts\Controllers\AccountIdentitiesV1' => [['fbAccountsModule.controllers.accountIdentities']],
		'FastyBird\Module\Accounts\Controllers\AccountsV1' => [['fbAccountsModule.controllers.accounts']],
		'FastyBird\Module\Accounts\Controllers\EmailsV1' => [['fbAccountsModule.controllers.emails']],
		'FastyBird\Module\Accounts\Controllers\IdentitiesV1' => [['fbAccountsModule.controllers.identities']],
		'FastyBird\Module\Accounts\Controllers\RolesV1' => [['fbAccountsModule.controllers.roles']],
		'FastyBird\Module\Accounts\Controllers\RoleChildrenV1' => [['fbAccountsModule.controllers.roleChildren']],
		'FastyBird\Module\Accounts\Controllers\PublicV1' => [['fbAccountsModule.controllers.public']],
		'FastyBird\JsonApi\Schemas\JsonApi' => [
			[
				'fbAccountsModule.schemas.account',
				'fbAccountsModule.schemas.email',
				'fbAccountsModule.schemas.identity',
				'fbAccountsModule.schemas.role',
				'fbAccountsModule.schemas.session',
				'fbDevicesModule.schemas.device.blank',
				'fbDevicesModule.schemas.device.property.dynamic',
				'fbDevicesModule.schemas.device.property.variable',
				'fbDevicesModule.schemas.device.property.mapped',
				'fbDevicesModule.schemas.device.control',
				'fbDevicesModule.schemas.device.attribute',
				'fbDevicesModule.schemas.channel',
				'fbDevicesModule.schemas.channel.property.dynamic',
				'fbDevicesModule.schemas.channel.property.variable',
				'fbDevicesModule.schemas.channel.property.mapped',
				'fbDevicesModule.schemas.control',
				'fbDevicesModule.schemas.connector.blank',
				'fbDevicesModule.schemas.connector.property.dynamic',
				'fbDevicesModule.schemas.connector.property.variable',
				'fbDevicesModule.schemas.connector.controls',
				'fbTriggersModule.schemas.triggers.automatic',
				'fbTriggersModule.schemas.triggers.manual',
				'fbTriggersModule.schemas.trigger.control',
				'fbTriggersModule.schemas.notifications.email',
				'fbTriggersModule.schemas.notifications.sms',
				'fbFbMqttConnector.schemas.connector.fbMqtt',
				'fbFbMqttConnector.schemas.device.fbMqtt',
				'fbShellyConnector.schemas.connector.shelly',
				'fbShellyConnector.schemas.device.shelly',
				'fbModbusConnector.schemas.connector.modbus',
				'fbModbusConnector.schemas.device.modbus',
				'fbHomeKitConnector.schemas.connector.homekit',
				'fbHomeKitConnector.schemas.device.homekit',
				'fbTuyaConnector.schemas.connector.tuya',
				'fbTuyaConnector.schemas.device.tuya',
			],
		],
		'Neomerx\JsonApi\Contracts\Schema\SchemaInterface' => [
			[
				'fbAccountsModule.schemas.account',
				'fbAccountsModule.schemas.email',
				'fbAccountsModule.schemas.identity',
				'fbAccountsModule.schemas.role',
				'fbAccountsModule.schemas.session',
				'fbDevicesModule.schemas.device.blank',
				'fbDevicesModule.schemas.device.property.dynamic',
				'fbDevicesModule.schemas.device.property.variable',
				'fbDevicesModule.schemas.device.property.mapped',
				'fbDevicesModule.schemas.device.control',
				'fbDevicesModule.schemas.device.attribute',
				'fbDevicesModule.schemas.channel',
				'fbDevicesModule.schemas.channel.property.dynamic',
				'fbDevicesModule.schemas.channel.property.variable',
				'fbDevicesModule.schemas.channel.property.mapped',
				'fbDevicesModule.schemas.control',
				'fbDevicesModule.schemas.connector.blank',
				'fbDevicesModule.schemas.connector.property.dynamic',
				'fbDevicesModule.schemas.connector.property.variable',
				'fbDevicesModule.schemas.connector.controls',
				'fbTriggersModule.schemas.triggers.automatic',
				'fbTriggersModule.schemas.triggers.manual',
				'fbTriggersModule.schemas.trigger.control',
				'fbTriggersModule.schemas.notifications.email',
				'fbTriggersModule.schemas.notifications.sms',
				'fbFbMqttConnector.schemas.connector.fbMqtt',
				'fbFbMqttConnector.schemas.device.fbMqtt',
				'fbShellyConnector.schemas.connector.shelly',
				'fbShellyConnector.schemas.device.shelly',
				'fbModbusConnector.schemas.connector.modbus',
				'fbModbusConnector.schemas.device.modbus',
				'fbHomeKitConnector.schemas.connector.homekit',
				'fbHomeKitConnector.schemas.device.homekit',
				'fbTuyaConnector.schemas.connector.tuya',
				'fbTuyaConnector.schemas.device.tuya',
			],
		],
		'FastyBird\Module\Accounts\Schemas\Accounts\Account' => [['fbAccountsModule.schemas.account']],
		'FastyBird\Module\Accounts\Schemas\Emails\Email' => [['fbAccountsModule.schemas.email']],
		'FastyBird\Module\Accounts\Schemas\Identities\Identity' => [['fbAccountsModule.schemas.identity']],
		'FastyBird\Module\Accounts\Schemas\Roles\Role' => [['fbAccountsModule.schemas.role']],
		'FastyBird\Module\Accounts\Schemas\Sessions\Session' => [['fbAccountsModule.schemas.session']],
		'FastyBird\JsonApi\Hydrators\Hydrator' => [
			[
				'fbAccountsModule.hydrators.accounts.profile',
				'fbAccountsModule.hydrators.accounts',
				'fbAccountsModule.hydrators.emails.profile',
				'fbAccountsModule.hydrators.emails.email',
				'fbAccountsModule.hydrators.identities.profile',
				'fbAccountsModule.hydrators.role',
				'fbDevicesModule.hydrators.device.blank',
				'fbDevicesModule.hydrators.device.property.dynamic',
				'fbDevicesModule.hydrators.device.property.variable',
				'fbDevicesModule.hydrators.device.property.mapped',
				'fbDevicesModule.hydrators.channel',
				'fbDevicesModule.hydrators.channel.property.dynamic',
				'fbDevicesModule.hydrators.channel.property.variable',
				'fbDevicesModule.hydrators.channel.property.mapped',
				'fbDevicesModule.hydrators.connectors.blank',
				'fbDevicesModule.hydrators.connector.property.dynamic',
				'fbDevicesModule.hydrators.connector.property.variable',
				'fbTriggersModule.hydrators.triggers.automatic',
				'fbTriggersModule.hydrators.triggers.manual',
				'fbTriggersModule.hydrators.notifications.email',
				'fbTriggersModule.hydrators.notifications.sms',
				'fbFbMqttConnector.hydrators.connector.fbMqtt',
				'fbFbMqttConnector.hydrators.device.fbMqtt',
				'fbShellyConnector.hydrators.connector.shelly',
				'fbShellyConnector.hydrators.device.shelly',
				'fbModbusConnector.hydrators.connector.modbus',
				'fbModbusConnector.hydrators.device.modbus',
				'fbHomeKitConnector.hydrators.connector.homekit',
				'fbHomeKitConnector.hydrators.device.homekit',
				'fbTuyaConnector.hydrators.connector.tuya',
				'fbTuyaConnector.hydrators.device.tuya',
			],
		],
		'FastyBird\Module\Accounts\Hydrators\Accounts\ProfileAccount' => [['fbAccountsModule.hydrators.accounts.profile']],
		'FastyBird\Module\Accounts\Hydrators\Accounts\Account' => [['fbAccountsModule.hydrators.accounts']],
		'FastyBird\Module\Accounts\Hydrators\Emails\ProfileEmail' => [['fbAccountsModule.hydrators.emails.profile']],
		'FastyBird\Module\Accounts\Hydrators\Emails\Email' => [['fbAccountsModule.hydrators.emails.email']],
		'FastyBird\Module\Accounts\Hydrators\Identities\Identity' => [['fbAccountsModule.hydrators.identities.profile']],
		'FastyBird\Module\Accounts\Hydrators\Roles\Role' => [['fbAccountsModule.hydrators.role']],
		'FastyBird\Module\Accounts\Helpers\SecurityHash' => [['fbAccountsModule.security.hash']],
		'FastyBird\SimpleAuth\Security\IIdentityFactory' => [['fbAccountsModule.security.identityFactory']],
		'FastyBird\Module\Accounts\Security\IdentityFactory' => [['fbAccountsModule.security.identityFactory']],
		'FastyBird\SimpleAuth\Security\IAuthenticator' => [['fbAccountsModule.security.authenticator']],
		'FastyBird\Module\Accounts\Security\Authenticator' => [['fbAccountsModule.security.authenticator']],
		'FastyBird\SimpleAuth\Security\User' => [['security.user']],
		'FastyBird\Module\Accounts\Security\User' => [['security.user']],
		'FastyBird\Module\Devices\Middleware\Access' => [['fbDevicesModule.middlewares.access']],
		'FastyBird\Module\Devices\Router\Routes' => [['fbDevicesModule.router.routes']],
		'FastyBird\Module\Devices\Router\Validator' => [['fbDevicesModule.router.validator']],
		'FastyBird\Module\Devices\Models\Devices\DevicesRepository' => [['fbDevicesModule.models.devicesRepository']],
		'FastyBird\Module\Devices\Models\Devices\Properties\PropertiesRepository' => [
			['fbDevicesModule.models.devicePropertiesRepository'],
		],
		'FastyBird\Module\Devices\Models\Devices\Controls\ControlsRepository' => [
			['fbDevicesModule.models.deviceControlsRepository'],
		],
		'FastyBird\Module\Devices\Models\Devices\Attributes\AttributesRepository' => [
			['fbDevicesModule.models.deviceAttributesRepository'],
		],
		'FastyBird\Module\Devices\Models\Channels\ChannelsRepository' => [['fbDevicesModule.models.channelsRepository']],
		'FastyBird\Module\Devices\Models\Channels\Properties\PropertiesRepository' => [
			['fbDevicesModule.models.channelPropertiesRepository'],
		],
		'FastyBird\Module\Devices\Models\Channels\Controls\ControlsRepository' => [
			['fbDevicesModule.models.channelControlsRepository'],
		],
		'FastyBird\Module\Devices\Models\Connectors\ConnectorsRepository' => [
			['fbDevicesModule.models.connectorsRepository'],
		],
		'FastyBird\Module\Devices\Models\Connectors\Properties\PropertiesRepository' => [
			['fbDevicesModule.models.connectorPropertiesRepository'],
		],
		'FastyBird\Module\Devices\Models\Connectors\Controls\ControlsRepository' => [
			['fbDevicesModule.models.connectorControlsRepository'],
		],
		'FastyBird\Module\Devices\Models\Devices\DevicesManager' => [['fbDevicesModule.models.devicesManager']],
		'FastyBird\Module\Devices\Models\Devices\Properties\PropertiesManager' => [
			['fbDevicesModule.models.devicesPropertiesManager'],
		],
		'FastyBird\Module\Devices\Models\Devices\Controls\ControlsManager' => [
			['fbDevicesModule.models.devicesControlsManager'],
		],
		'FastyBird\Module\Devices\Models\Devices\Attributes\AttributesManager' => [
			['fbDevicesModule.models.devicesAttributesManager'],
		],
		'FastyBird\Module\Devices\Models\Channels\ChannelsManager' => [['fbDevicesModule.models.channelsManager']],
		'FastyBird\Module\Devices\Models\Channels\Properties\PropertiesManager' => [
			['fbDevicesModule.models.channelsPropertiesManager'],
		],
		'FastyBird\Module\Devices\Models\Channels\Controls\ControlsManager' => [
			['fbDevicesModule.models.channelsControlsManager'],
		],
		'FastyBird\Module\Devices\Models\Connectors\ConnectorsManager' => [['fbDevicesModule.models.connectorsManager']],
		'FastyBird\Module\Devices\Models\Connectors\Properties\PropertiesManager' => [
			['fbDevicesModule.models.connectorsPropertiesManager'],
		],
		'FastyBird\Module\Devices\Models\Connectors\Controls\ControlsManager' => [
			['fbDevicesModule.models.connectorsControlsManager'],
		],
		'FastyBird\Module\Devices\Subscribers\ModuleEntities' => [['fbDevicesModule.subscribers.entities']],
		'FastyBird\Module\Devices\Subscribers\StateEntities' => [['fbDevicesModule.subscribers.states']],
		'FastyBird\Module\Devices\Controllers\BaseV1' => [
			[
				'fbDevicesModule.controllers.devices',
				'fbDevicesModule.controllers.deviceChildren',
				'fbDevicesModule.controllers.deviceParents',
				'fbDevicesModule.controllers.deviceProperties',
				'fbDevicesModule.controllers.devicePropertyChildren',
				'fbDevicesModule.controllers.deviceControls',
				'fbDevicesModule.controllers.deviceAttributes',
				'fbDevicesModule.controllers.channels',
				'fbDevicesModule.controllers.channelProperties',
				'fbDevicesModule.controllers.channelPropertyChildren',
				'fbDevicesModule.controllers.channelControls',
				'fbDevicesModule.controllers.connectors',
				'fbDevicesModule.controllers.connectorProperties',
				'fbDevicesModule.controllers.connectorsControls',
			],
		],
		'FastyBird\Module\Devices\Controllers\DevicesV1' => [['fbDevicesModule.controllers.devices']],
		'FastyBird\Module\Devices\Controllers\DeviceChildrenV1' => [['fbDevicesModule.controllers.deviceChildren']],
		'FastyBird\Module\Devices\Controllers\DeviceParentsV1' => [['fbDevicesModule.controllers.deviceParents']],
		'FastyBird\Module\Devices\Controllers\DevicePropertiesV1' => [['fbDevicesModule.controllers.deviceProperties']],
		'FastyBird\Module\Devices\Controllers\DevicePropertyChildrenV1' => [
			['fbDevicesModule.controllers.devicePropertyChildren'],
		],
		'FastyBird\Module\Devices\Controllers\DeviceControlsV1' => [['fbDevicesModule.controllers.deviceControls']],
		'FastyBird\Module\Devices\Controllers\DeviceAttributesV1' => [['fbDevicesModule.controllers.deviceAttributes']],
		'FastyBird\Module\Devices\Controllers\ChannelsV1' => [['fbDevicesModule.controllers.channels']],
		'FastyBird\Module\Devices\Controllers\ChannelPropertiesV1' => [['fbDevicesModule.controllers.channelProperties']],
		'FastyBird\Module\Devices\Controllers\ChannelPropertyChildrenV1' => [
			['fbDevicesModule.controllers.channelPropertyChildren'],
		],
		'FastyBird\Module\Devices\Controllers\ChannelControlsV1' => [['fbDevicesModule.controllers.channelControls']],
		'FastyBird\Module\Devices\Controllers\ConnectorsV1' => [['fbDevicesModule.controllers.connectors']],
		'FastyBird\Module\Devices\Controllers\ConnectorPropertiesV1' => [
			['fbDevicesModule.controllers.connectorProperties'],
		],
		'FastyBird\Module\Devices\Controllers\ConnectorControlsV1' => [['fbDevicesModule.controllers.connectorsControls']],
		'FastyBird\Module\Devices\Schemas\Devices\Device' => [
			[
				'fbDevicesModule.schemas.device.blank',
				'fbFbMqttConnector.schemas.device.fbMqtt',
				'fbShellyConnector.schemas.device.shelly',
				'fbModbusConnector.schemas.device.modbus',
				'fbHomeKitConnector.schemas.device.homekit',
				'fbTuyaConnector.schemas.device.tuya',
			],
		],
		'FastyBird\Module\Devices\Schemas\Devices\Blank' => [['fbDevicesModule.schemas.device.blank']],
		'FastyBird\Module\Devices\Schemas\Devices\Properties\Property' => [
			[
				'fbDevicesModule.schemas.device.property.dynamic',
				'fbDevicesModule.schemas.device.property.variable',
				'fbDevicesModule.schemas.device.property.mapped',
			],
		],
		'FastyBird\Module\Devices\Schemas\Devices\Properties\Dynamic' => [
			['fbDevicesModule.schemas.device.property.dynamic'],
		],
		'FastyBird\Module\Devices\Schemas\Devices\Properties\Variable' => [
			['fbDevicesModule.schemas.device.property.variable'],
		],
		'FastyBird\Module\Devices\Schemas\Devices\Properties\Mapped' => [
			['fbDevicesModule.schemas.device.property.mapped'],
		],
		'FastyBird\Module\Devices\Schemas\Devices\Controls\Control' => [['fbDevicesModule.schemas.device.control']],
		'FastyBird\Module\Devices\Schemas\Devices\Attributes\Attribute' => [['fbDevicesModule.schemas.device.attribute']],
		'FastyBird\Module\Devices\Schemas\Channels\Channel' => [['fbDevicesModule.schemas.channel']],
		'FastyBird\Module\Devices\Schemas\Channels\Properties\Property' => [
			[
				'fbDevicesModule.schemas.channel.property.dynamic',
				'fbDevicesModule.schemas.channel.property.variable',
				'fbDevicesModule.schemas.channel.property.mapped',
			],
		],
		'FastyBird\Module\Devices\Schemas\Channels\Properties\Dynamic' => [
			['fbDevicesModule.schemas.channel.property.dynamic'],
		],
		'FastyBird\Module\Devices\Schemas\Channels\Properties\Variable' => [
			['fbDevicesModule.schemas.channel.property.variable'],
		],
		'FastyBird\Module\Devices\Schemas\Channels\Properties\Mapped' => [
			['fbDevicesModule.schemas.channel.property.mapped'],
		],
		'FastyBird\Module\Devices\Schemas\Channels\Controls\Control' => [['fbDevicesModule.schemas.control']],
		'FastyBird\Module\Devices\Schemas\Connectors\Connector' => [
			[
				'fbDevicesModule.schemas.connector.blank',
				'fbFbMqttConnector.schemas.connector.fbMqtt',
				'fbShellyConnector.schemas.connector.shelly',
				'fbModbusConnector.schemas.connector.modbus',
				'fbHomeKitConnector.schemas.connector.homekit',
				'fbTuyaConnector.schemas.connector.tuya',
			],
		],
		'FastyBird\Module\Devices\Schemas\Connectors\Blank' => [['fbDevicesModule.schemas.connector.blank']],
		'FastyBird\Module\Devices\Schemas\Connectors\Properties\Property' => [
			['fbDevicesModule.schemas.connector.property.dynamic', 'fbDevicesModule.schemas.connector.property.variable'],
		],
		'FastyBird\Module\Devices\Schemas\Connectors\Properties\Dynamic' => [
			['fbDevicesModule.schemas.connector.property.dynamic'],
		],
		'FastyBird\Module\Devices\Schemas\Connectors\Properties\Variable' => [
			['fbDevicesModule.schemas.connector.property.variable'],
		],
		'FastyBird\Module\Devices\Schemas\Connectors\Controls\Control' => [['fbDevicesModule.schemas.connector.controls']],
		'FastyBird\Module\Devices\Hydrators\Devices\Device' => [
			[
				'fbDevicesModule.hydrators.device.blank',
				'fbFbMqttConnector.hydrators.device.fbMqtt',
				'fbShellyConnector.hydrators.device.shelly',
				'fbModbusConnector.hydrators.device.modbus',
				'fbHomeKitConnector.hydrators.device.homekit',
				'fbTuyaConnector.hydrators.device.tuya',
			],
		],
		'FastyBird\Module\Devices\Hydrators\Devices\Blank' => [['fbDevicesModule.hydrators.device.blank']],
		'FastyBird\Module\Devices\Hydrators\Properties\Device' => [
			[
				'fbDevicesModule.hydrators.device.property.dynamic',
				'fbDevicesModule.hydrators.device.property.variable',
				'fbDevicesModule.hydrators.device.property.mapped',
			],
		],
		'FastyBird\Module\Devices\Hydrators\Properties\Property' => [
			[
				'fbDevicesModule.hydrators.device.property.dynamic',
				'fbDevicesModule.hydrators.device.property.variable',
				'fbDevicesModule.hydrators.device.property.mapped',
				'fbDevicesModule.hydrators.channel.property.dynamic',
				'fbDevicesModule.hydrators.channel.property.variable',
				'fbDevicesModule.hydrators.channel.property.mapped',
				'fbDevicesModule.hydrators.connector.property.dynamic',
				'fbDevicesModule.hydrators.connector.property.variable',
			],
		],
		'FastyBird\Module\Devices\Hydrators\Properties\DeviceDynamic' => [
			['fbDevicesModule.hydrators.device.property.dynamic'],
		],
		'FastyBird\Module\Devices\Hydrators\Properties\DeviceVariable' => [
			['fbDevicesModule.hydrators.device.property.variable'],
		],
		'FastyBird\Module\Devices\Hydrators\Properties\DeviceMapped' => [
			['fbDevicesModule.hydrators.device.property.mapped'],
		],
		'FastyBird\Module\Devices\Hydrators\Channels\Channel' => [['fbDevicesModule.hydrators.channel']],
		'FastyBird\Module\Devices\Hydrators\Properties\Channel' => [
			[
				'fbDevicesModule.hydrators.channel.property.dynamic',
				'fbDevicesModule.hydrators.channel.property.variable',
				'fbDevicesModule.hydrators.channel.property.mapped',
			],
		],
		'FastyBird\Module\Devices\Hydrators\Properties\ChannelDynamic' => [
			['fbDevicesModule.hydrators.channel.property.dynamic'],
		],
		'FastyBird\Module\Devices\Hydrators\Properties\ChannelVariable' => [
			['fbDevicesModule.hydrators.channel.property.variable'],
		],
		'FastyBird\Module\Devices\Hydrators\Properties\ChannelMapped' => [
			['fbDevicesModule.hydrators.channel.property.mapped'],
		],
		'FastyBird\Module\Devices\Hydrators\Connectors\Connector' => [
			[
				'fbDevicesModule.hydrators.connectors.blank',
				'fbFbMqttConnector.hydrators.connector.fbMqtt',
				'fbShellyConnector.hydrators.connector.shelly',
				'fbModbusConnector.hydrators.connector.modbus',
				'fbHomeKitConnector.hydrators.connector.homekit',
				'fbTuyaConnector.hydrators.connector.tuya',
			],
		],
		'FastyBird\Module\Devices\Hydrators\Connectors\Blank' => [['fbDevicesModule.hydrators.connectors.blank']],
		'FastyBird\Module\Devices\Hydrators\Properties\Connector' => [
			[
				'fbDevicesModule.hydrators.connector.property.dynamic',
				'fbDevicesModule.hydrators.connector.property.variable',
			],
		],
		'FastyBird\Module\Devices\Hydrators\Properties\ConnectorDynamic' => [
			['fbDevicesModule.hydrators.connector.property.dynamic'],
		],
		'FastyBird\Module\Devices\Hydrators\Properties\ConnectorVariable' => [
			['fbDevicesModule.hydrators.connector.property.variable'],
		],
		'FastyBird\Module\Devices\Models\States\ConnectorPropertiesRepository' => [
			['fbDevicesModule.states.repositories.connectors.properties'],
		],
		'FastyBird\Module\Devices\Models\States\DevicePropertiesRepository' => [
			['fbDevicesModule.states.repositories.devices.properties'],
		],
		'FastyBird\Module\Devices\Models\States\ChannelPropertiesRepository' => [
			['fbDevicesModule.states.repositories.channels.properties'],
		],
		'FastyBird\Module\Devices\Models\States\ConnectorPropertiesManager' => [
			['fbDevicesModule.states.managers.connectors.properties'],
		],
		'FastyBird\Module\Devices\Models\States\DevicePropertiesManager' => [
			['fbDevicesModule.states.managers.devices.properties'],
		],
		'FastyBird\Module\Devices\Models\States\ChannelPropertiesManager' => [
			['fbDevicesModule.states.managers.channels.properties'],
		],
		'FastyBird\Module\Devices\Utilities\Database' => [['fbDevicesModule.utilities.database']],
		'FastyBird\Module\Devices\Utilities\ChannelPropertiesStates' => [['fbDevicesModule.utilities.channels.states']],
		'FastyBird\Module\Devices\Utilities\ConnectorPropertiesStates' => [['fbDevicesModule.utilities.connectors.states']],
		'FastyBird\Module\Devices\Utilities\DevicePropertiesStates' => [['fbDevicesModule.utilities.devices.states']],
		'FastyBird\Module\Devices\Utilities\DeviceConnection' => [['fbDevicesModule.utilities.devices.connection']],
		'FastyBird\Module\Devices\Utilities\ConnectorConnection' => [['fbDevicesModule.utilities.connector.connection']],
		'FastyBird\Module\Devices\Consumers\State' => [2 => ['fbDevicesModule.exchange.consumer.states']],
		'FastyBird\Module\Devices\Commands\Initialize' => [['fbDevicesModule.commands.initialize']],
		'FastyBird\Module\Devices\Commands\Connector' => [['fbDevicesModule.commands.connector']],
		'FastyBird\Module\Triggers\Middleware\Access' => [['fbTriggersModule.middleware.access']],
		'FastyBird\Module\Triggers\Router\Routes' => [['fbTriggersModule.router.routes']],
		'FastyBird\Module\Triggers\Router\Validator' => [['fbTriggersModule.router.validator']],
		'FastyBird\Module\Triggers\Models\Triggers\TriggersRepository' => [['fbTriggersModule.models.triggersRepository']],
		'FastyBird\Module\Triggers\Models\Triggers\Controls\ControlsRepository' => [
			['fbTriggersModule.models.triggeControlsRepository'],
		],
		'FastyBird\Module\Triggers\Models\Actions\ActionsRepository' => [['fbTriggersModule.models.actionsRepository']],
		'FastyBird\Module\Triggers\Models\Conditions\ConditionsRepository' => [
			['fbTriggersModule.models.conditionsRepository'],
		],
		'FastyBird\Module\Triggers\Models\Notifications\NotificationsRepository' => [
			['fbTriggersModule.models.notificationsRepository'],
		],
		'FastyBird\Module\Triggers\Models\Triggers\TriggersManager' => [['fbTriggersModule.models.triggersManager']],
		'FastyBird\Module\Triggers\Models\Triggers\Controls\ControlsManager' => [
			['fbTriggersModule.models.triggersControlsManager'],
		],
		'FastyBird\Module\Triggers\Models\Actions\ActionsManager' => [['fbTriggersModule.models.actionsManager']],
		'FastyBird\Module\Triggers\Models\Conditions\ConditionsManager' => [['fbTriggersModule.models.conditionsManager']],
		'FastyBird\Module\Triggers\Models\Notifications\NotificationsManager' => [
			['fbTriggersModule.models.notificationsManager'],
		],
		'FastyBird\Module\Triggers\Subscribers\NotificationEntity' => [['fbTriggersModule.subscribers.notificationEntity']],
		'FastyBird\Module\Triggers\Subscribers\ModuleEntities' => [['fbTriggersModule.subscribers.entities']],
		'FastyBird\Module\Triggers\Controllers\BaseV1' => [
			[
				'fbTriggersModule.controllers.triggers',
				'fbTriggersModule.controllers.actions',
				'fbTriggersModule.controllers.conditions',
				'fbTriggersModule.controllers.notifications',
				'fbTriggersModule.controllers.triggersControls',
			],
		],
		'FastyBird\Module\Triggers\Controllers\TriggersV1' => [['fbTriggersModule.controllers.triggers']],
		'FastyBird\Module\Triggers\Controllers\ActionsV1' => [['fbTriggersModule.controllers.actions']],
		'FastyBird\Module\Triggers\Controllers\ConditionsV1' => [['fbTriggersModule.controllers.conditions']],
		'FastyBird\Module\Triggers\Controllers\NotificationsV1' => [['fbTriggersModule.controllers.notifications']],
		'FastyBird\Module\Triggers\Controllers\TriggerControlsV1' => [['fbTriggersModule.controllers.triggersControls']],
		'FastyBird\Module\Triggers\Schemas\Triggers\Trigger' => [
			['fbTriggersModule.schemas.triggers.automatic', 'fbTriggersModule.schemas.triggers.manual'],
		],
		'FastyBird\Module\Triggers\Schemas\Triggers\AutomaticTrigger' => [['fbTriggersModule.schemas.triggers.automatic']],
		'FastyBird\Module\Triggers\Schemas\Triggers\ManualTrigger' => [['fbTriggersModule.schemas.triggers.manual']],
		'FastyBird\Module\Triggers\Schemas\Triggers\Controls\Control' => [['fbTriggersModule.schemas.trigger.control']],
		'FastyBird\Module\Triggers\Schemas\Notifications\Notification' => [
			['fbTriggersModule.schemas.notifications.email', 'fbTriggersModule.schemas.notifications.sms'],
		],
		'FastyBird\Module\Triggers\Schemas\Notifications\EmailNotification' => [
			['fbTriggersModule.schemas.notifications.email'],
		],
		'FastyBird\Module\Triggers\Schemas\Notifications\SmsNotification' => [
			['fbTriggersModule.schemas.notifications.sms'],
		],
		'FastyBird\Module\Triggers\Hydrators\Triggers\Trigger' => [
			['fbTriggersModule.hydrators.triggers.automatic', 'fbTriggersModule.hydrators.triggers.manual'],
		],
		'FastyBird\Module\Triggers\Hydrators\Triggers\AutomaticTrigger' => [
			['fbTriggersModule.hydrators.triggers.automatic'],
		],
		'FastyBird\Module\Triggers\Hydrators\Triggers\ManualTrigger' => [['fbTriggersModule.hydrators.triggers.manual']],
		'FastyBird\Module\Triggers\Hydrators\Notifications\Notification' => [
			['fbTriggersModule.hydrators.notifications.email', 'fbTriggersModule.hydrators.notifications.sms'],
		],
		'FastyBird\Module\Triggers\Hydrators\Notifications\EmailNotification' => [
			['fbTriggersModule.hydrators.notifications.email'],
		],
		'FastyBird\Module\Triggers\Hydrators\Notifications\SmsNotification' => [
			['fbTriggersModule.hydrators.notifications.sms'],
		],
		'FastyBird\Module\Triggers\Models\States\ActionsRepository' => [['fbTriggersModule.states.repositories.actions']],
		'FastyBird\Module\Triggers\Models\States\ConditionsRepository' => [
			['fbTriggersModule.states.repositories.conditions'],
		],
		'FastyBird\Module\Triggers\Models\States\ActionsManager' => [['fbTriggersModule.states.managers.actions']],
		'FastyBird\Module\Triggers\Models\States\ConditionsManager' => [['fbTriggersModule.states.managers.conditions']],
		'FastyBird\Module\Triggers\Utilities\Database' => [['fbTriggersModule.utilities.database']],
		'FastyBird\Module\Triggers\Commands\Initialize' => [['fbTriggersModule.commands.initialize']],
		'FastyBird\Connector\FbMqtt\Clients\ClientFactory' => [['fbFbMqttConnector.client.apiv1']],
		'FastyBird\Connector\FbMqtt\Clients\FbMqttV1Factory' => [['fbFbMqttConnector.client.apiv1']],
		'FastyBird\Connector\FbMqtt\API\V1Parser' => [['fbFbMqttConnector.api.v1parser']],
		'FastyBird\Connector\FbMqtt\API\V1Validator' => [['fbFbMqttConnector.api.v1validator']],
		'FastyBird\Connector\FbMqtt\API\V1Builder' => [['fbFbMqttConnector.api.v1builder']],
		'FastyBird\Connector\FbMqtt\Consumers\Consumer' => [
			[
				'fbFbMqttConnector.consumers.device.attribute.message',
				'fbFbMqttConnector.consumers.device.extension.message',
				'fbFbMqttConnector.consumers.device.property.message',
				'fbFbMqttConnector.consumers.channel.attribute.message',
				'fbFbMqttConnector.consumers.channel.property.message',
			],
		],
		'FastyBird\Connector\FbMqtt\Consumers\Messages\Device' => [
			['fbFbMqttConnector.consumers.device.attribute.message'],
		],
		'FastyBird\Connector\FbMqtt\Consumers\Messages\ExtensionAttribute' => [
			['fbFbMqttConnector.consumers.device.extension.message'],
		],
		'FastyBird\Connector\FbMqtt\Consumers\Messages\DeviceProperty' => [
			['fbFbMqttConnector.consumers.device.property.message'],
		],
		'FastyBird\Connector\FbMqtt\Consumers\Messages\Channel' => [
			['fbFbMqttConnector.consumers.channel.attribute.message'],
		],
		'FastyBird\Connector\FbMqtt\Consumers\Messages\ChannelProperty' => [
			['fbFbMqttConnector.consumers.channel.property.message'],
		],
		'FastyBird\Connector\FbMqtt\Consumers\Messages' => [['fbFbMqttConnector.consumers.proxy']],
		'FastyBird\Connector\FbMqtt\Schemas\FbMqttConnector' => [['fbFbMqttConnector.schemas.connector.fbMqtt']],
		'FastyBird\Connector\FbMqtt\Schemas\FbMqttDevice' => [['fbFbMqttConnector.schemas.device.fbMqtt']],
		'FastyBird\Connector\FbMqtt\Hydrators\FbMqttConnector' => [['fbFbMqttConnector.hydrators.connector.fbMqtt']],
		'FastyBird\Connector\FbMqtt\Hydrators\FbMqttDevice' => [['fbFbMqttConnector.hydrators.device.fbMqtt']],
		'FastyBird\Connector\FbMqtt\Helpers\Connector' => [['fbFbMqttConnector.helpers.connector']],
		'FastyBird\Connector\FbMqtt\Helpers\Property' => [['fbFbMqttConnector.helpers.property']],
		'FastyBird\Module\Devices\Connectors\ConnectorFactory' => [
			[
				'fbFbMqttConnector.executor.factory',
				'fbShellyConnector.executor.factory',
				'fbModbusConnector.executor.factory',
				'fbHomeKitConnector.executor.factory',
				'fbTuyaConnector.executor.factory',
			],
		],
		'FastyBird\Connector\FbMqtt\Connector\ConnectorFactory' => [['fbFbMqttConnector.executor.factory']],
		'FastyBird\Connector\Shelly\Clients\ClientFactory' => [['fbShellyConnector.clients.gen1']],
		'FastyBird\Connector\Shelly\Clients\Gen1Factory' => [['fbShellyConnector.clients.gen1']],
		'FastyBird\Connector\Shelly\Clients\Gen1\CoapFactory' => [['fbShellyConnector.clients.gen1.coap']],
		'FastyBird\Connector\Shelly\Clients\Gen1\MdnsFactory' => [['fbShellyConnector.clients.gen1.mdns']],
		'FastyBird\Connector\Shelly\Clients\Gen1\HttpFactory' => [['fbShellyConnector.clients.gen1.http']],
		'FastyBird\Connector\Shelly\Clients\Gen1\MqttFactory' => [['fbShellyConnector.clients.gen1.mqtt']],
		'FastyBird\Connector\Shelly\API\Gen1Parser' => [['fbShellyConnector.api.gen1parser']],
		'FastyBird\Connector\Shelly\API\Gen1Validator' => [['fbShellyConnector.api.gen1validator']],
		'FastyBird\Connector\Shelly\API\Gen1Transformer' => [['fbShellyConnector.api.gen1transformer']],
		'FastyBird\Connector\Shelly\Consumers\Consumer' => [
			[
				'fbShellyConnector.consumers.messages.device.description',
				'fbShellyConnector.consumers.messages.device.status',
				'fbShellyConnector.consumers.messages.device.info',
				'fbShellyConnector.consumers.messages.device.discovery',
			],
		],
		'FastyBird\Connector\Shelly\Consumers\Messages\Description' => [
			['fbShellyConnector.consumers.messages.device.description'],
		],
		'FastyBird\Connector\Shelly\Consumers\Messages\Status' => [['fbShellyConnector.consumers.messages.device.status']],
		'FastyBird\Connector\Shelly\Consumers\Messages\Info' => [['fbShellyConnector.consumers.messages.device.info']],
		'FastyBird\Connector\Shelly\Consumers\Messages\Discovery' => [
			['fbShellyConnector.consumers.messages.device.discovery'],
		],
		'FastyBird\Connector\Shelly\Consumers\Messages' => [['fbShellyConnector.consumers.messages']],
		'FastyBird\Connector\Shelly\Subscribers\Properties' => [['fbShellyConnector.subscribers.properties']],
		'FastyBird\Connector\Shelly\Schemas\ShellyConnector' => [['fbShellyConnector.schemas.connector.shelly']],
		'FastyBird\Connector\Shelly\Schemas\ShellyDevice' => [['fbShellyConnector.schemas.device.shelly']],
		'FastyBird\Connector\Shelly\Hydrators\ShellyConnector' => [['fbShellyConnector.hydrators.connector.shelly']],
		'FastyBird\Connector\Shelly\Hydrators\ShellyDevice' => [['fbShellyConnector.hydrators.device.shelly']],
		'FastyBird\Connector\Shelly\Helpers\Connector' => [['fbShellyConnector.helpers.connector']],
		'FastyBird\Connector\Shelly\Helpers\Device' => [['fbShellyConnector.helpers.device']],
		'FastyBird\Connector\Shelly\Helpers\Property' => [['fbShellyConnector.helpers.property']],
		'FastyBird\Connector\Shelly\Mappers\Sensor' => [['fbShellyConnector.mappers.sensor']],
		'FastyBird\Connector\Shelly\Connector\ConnectorFactory' => [['fbShellyConnector.executor.factory']],
		'FastyBird\Connector\Shelly\Commands\Initialize' => [['fbShellyConnector.commands.initialize']],
		'FastyBird\Connector\Shelly\Commands\Discovery' => [['fbShellyConnector.commands.discovery']],
		'FastyBird\Connector\Shelly\Commands\Execute' => [['fbShellyConnector.commands.execute']],
		'FastyBird\Connector\Modbus\Clients\ClientFactory' => [['fbModbusConnector.client.rtu']],
		'FastyBird\Connector\Modbus\Clients\RtuFactory' => [['fbModbusConnector.client.rtu']],
		'FastyBird\Connector\Modbus\API\Transformer' => [['fbModbusConnector.api.transformer']],
		'FastyBird\Connector\Modbus\Subscribers\Properties' => [['fbModbusConnector.subscribers.properties']],
		'FastyBird\Connector\Modbus\Schemas\ModbusConnector' => [['fbModbusConnector.schemas.connector.modbus']],
		'FastyBird\Connector\Modbus\Schemas\ModbusDevice' => [['fbModbusConnector.schemas.device.modbus']],
		'FastyBird\Connector\Modbus\Hydrators\ModbusConnector' => [['fbModbusConnector.hydrators.connector.modbus']],
		'FastyBird\Connector\Modbus\Hydrators\ModbusDevice' => [['fbModbusConnector.hydrators.device.modbus']],
		'FastyBird\Connector\Modbus\Helpers\Connector' => [['fbModbusConnector.helpers.connector']],
		'FastyBird\Connector\Modbus\Helpers\Device' => [['fbModbusConnector.helpers.device']],
		'FastyBird\Connector\Modbus\Helpers\Channel' => [['fbModbusConnector.helpers.channel']],
		'FastyBird\Connector\Modbus\Helpers\Property' => [['fbModbusConnector.helpers.property']],
		'FastyBird\Connector\Modbus\Connector\ConnectorFactory' => [['fbModbusConnector.executor.factory']],
		'FastyBird\Connector\Modbus\Commands\Initialize' => [['fbModbusConnector.commands.initialize']],
		'FastyBird\Connector\Modbus\Commands\Execute' => [['fbModbusConnector.commands.execute']],
		'FastyBird\Connector\HomeKit\Servers\ServerFactory' => [
			['fbHomeKitConnector.server.mdns', 'fbHomeKitConnector.server.http'],
		],
		'FastyBird\Connector\HomeKit\Servers\MdnsFactory' => [['fbHomeKitConnector.server.mdns']],
		'FastyBird\Connector\HomeKit\Servers\HttpFactory' => [['fbHomeKitConnector.server.http']],
		'FastyBird\Connector\HomeKit\Servers\SecureServerFactory' => [['fbHomeKitConnector.server.http.secure.server']],
		'FastyBird\Connector\HomeKit\Servers\SecureConnectionFactory' => [
			['fbHomeKitConnector.server.http.secure.connection'],
		],
		'FastyBird\Connector\HomeKit\Schemas\HomeKitConnector' => [['fbHomeKitConnector.schemas.connector.homekit']],
		'FastyBird\Connector\HomeKit\Schemas\HomeKitDevice' => [['fbHomeKitConnector.schemas.device.homekit']],
		'FastyBird\Connector\HomeKit\Hydrators\HomeKitConnector' => [['fbHomeKitConnector.hydrators.connector.homekit']],
		'FastyBird\Connector\HomeKit\Hydrators\HomeKitDevice' => [['fbHomeKitConnector.hydrators.device.homekit']],
		'FastyBird\Connector\HomeKit\Helpers\Connector' => [['fbHomeKitConnector.helpers.connector']],
		'FastyBird\Connector\HomeKit\Helpers\Loader' => [['fbHomeKitConnector.helpers.loader']],
		'FastyBird\Connector\HomeKit\Router\Router' => [2 => ['fbHomeKitConnector.http.router']],
		'FastyBird\Connector\HomeKit\Middleware\Router' => [['fbHomeKitConnector.http.middlewares.router']],
		'FastyBird\Connector\HomeKit\Controllers\BaseController' => [
			[
				'fbHomeKitConnector.http.controllers.accessories',
				'fbHomeKitConnector.http.controllers.characteristics',
				'fbHomeKitConnector.http.controllers.pairing',
			],
		],
		'FastyBird\Connector\HomeKit\Controllers\AccessoriesController' => [
			['fbHomeKitConnector.http.controllers.accessories'],
		],
		'FastyBird\Connector\HomeKit\Controllers\CharacteristicsController' => [
			['fbHomeKitConnector.http.controllers.characteristics'],
		],
		'FastyBird\Connector\HomeKit\Controllers\PairingController' => [['fbHomeKitConnector.http.controllers.pairing']],
		'FastyBird\Connector\HomeKit\Entities\Protocol\AccessoryFactory' => [
			['fbHomeKitConnector.entities.accessory.factory'],
		],
		'FastyBird\Connector\HomeKit\Entities\Protocol\ServiceFactory' => [['fbHomeKitConnector.entities.service.factory']],
		'FastyBird\Connector\HomeKit\Entities\Protocol\CharacteristicsFactory' => [
			['fbHomeKitConnector.entities.characteristic.factory'],
		],
		'FastyBird\Connector\HomeKit\Protocol\Tlv' => [['fbHomeKitConnector.protocol.tlv']],
		'FastyBird\Connector\HomeKit\Protocol\Driver' => [['fbHomeKitConnector.protocol.accessoryDriver']],
		'FastyBird\Connector\HomeKit\Clients\Subscriber' => [['fbHomeKitConnector.clients.subscriber']],
		'FastyBird\Connector\HomeKit\Models\Clients\ClientsRepository' => [['fbHomeKitConnector.models.clientsRepository']],
		'FastyBird\Connector\HomeKit\Models\Clients\ClientsManager' => [['fbHomeKitConnector.models.clientsManager']],
		'FastyBird\Connector\HomeKit\Connector\ConnectorFactory' => [['fbHomeKitConnector.executor.factory']],
		'FastyBird\Connector\HomeKit\Consumers\Consumer' => [2 => ['fbHomeKitConnector.consumers.exchange']],
		'FastyBird\Connector\HomeKit\Subscribers\Connector' => [['fbHomeKitConnector.subscribers.connector']],
		'FastyBird\Connector\HomeKit\Commands\Initialize' => [['fbHomeKitConnector.commands.initialize']],
		'FastyBird\Connector\HomeKit\Commands\Execute' => [['fbHomeKitConnector.commands.execute']],
		'FastyBird\Connector\Tuya\Consumers\Consumer' => [
			[
				'fbTuyaConnector.consumers.discovery.cloudDevice',
				'fbTuyaConnector.consumers.discovery.localDevice',
				'fbTuyaConnector.consumers.discovery.status',
				'fbTuyaConnector.consumers.discovery.state',
			],
		],
		'FastyBird\Connector\Tuya\Consumers\Messages\CloudDiscovery' => [
			['fbTuyaConnector.consumers.discovery.cloudDevice'],
		],
		'FastyBird\Connector\Tuya\Consumers\Messages\LocalDiscovery' => [
			['fbTuyaConnector.consumers.discovery.localDevice'],
		],
		'FastyBird\Connector\Tuya\Consumers\Messages\Status' => [['fbTuyaConnector.consumers.discovery.status']],
		'FastyBird\Connector\Tuya\Consumers\Messages\State' => [['fbTuyaConnector.consumers.discovery.state']],
		'FastyBird\Connector\Tuya\Consumers\Messages' => [['fbTuyaConnector.consumers.proxy']],
		'FastyBird\Connector\Tuya\API\OpenApiFactory' => [['fbTuyaConnector.api.openApi.api']],
		'FastyBird\Connector\Tuya\API\EntityFactory' => [['fbTuyaConnector.api.openApi.entityFactory']],
		'FastyBird\Connector\Tuya\API\LocalApiFactory' => [['fbTuyaConnector.api.localApi.api']],
		'FastyBird\Connector\Tuya\Clients\ClientFactory' => [
			['fbTuyaConnector.clients.local', 'fbTuyaConnector.clients.cloud'],
		],
		'FastyBird\Connector\Tuya\Clients\LocalFactory' => [['fbTuyaConnector.clients.local']],
		'FastyBird\Connector\Tuya\Clients\CloudFactory' => [['fbTuyaConnector.clients.cloud']],
		'FastyBird\Connector\Tuya\Clients\DiscoveryFactory' => [['fbTuyaConnector.clients.discover']],
		'FastyBird\Connector\Tuya\Subscribers\Properties' => [['fbTuyaConnector.subscribers.properties']],
		'FastyBird\Connector\Tuya\Schemas\TuyaConnector' => [['fbTuyaConnector.schemas.connector.tuya']],
		'FastyBird\Connector\Tuya\Schemas\TuyaDevice' => [['fbTuyaConnector.schemas.device.tuya']],
		'FastyBird\Connector\Tuya\Hydrators\TuyaConnector' => [['fbTuyaConnector.hydrators.connector.tuya']],
		'FastyBird\Connector\Tuya\Hydrators\TuyaDevice' => [['fbTuyaConnector.hydrators.device.tuya']],
		'FastyBird\Connector\Tuya\Helpers\Connector' => [['fbTuyaConnector.helpers.connector']],
		'FastyBird\Connector\Tuya\Helpers\Device' => [['fbTuyaConnector.helpers.device']],
		'FastyBird\Connector\Tuya\Helpers\Property' => [['fbTuyaConnector.helpers.property']],
		'FastyBird\Connector\Tuya\Mappers\DataPoint' => [['fbTuyaConnector.mappers.dataPoint']],
		'FastyBird\Connector\Tuya\Connector\ConnectorFactory' => [['fbTuyaConnector.executor.factory']],
		'FastyBird\Connector\Tuya\Commands\Initialize' => [['fbTuyaConnector.commands.initialize']],
		'FastyBird\Connector\Tuya\Commands\Discovery' => [['fbTuyaConnector.commands.discovery']],
		'FastyBird\Connector\Tuya\Commands\Execute' => [['fbTuyaConnector.commands.execute']],
	];


	public function __construct(array $params = [])
	{
		parent::__construct($params);
		$this->parameters += [
			'logger' => [
				'level' => 300,
				'rotatingFile' => 'app.log',
				'stdOut' => true,
				'console' => ['enabled' => false, 'level' => 200],
			],
			'sentry' => ['dsn' => null],
			'database' => [
				'version' => 5.7,
				'host' => 'database',
				'port' => 3306,
				'driver' => 'pdo_mysql',
				'memory' => false,
				'dbname' => 'fastybird',
				'username' => 'fastybird',
				'password' => 'fastybird',
			],
			'redis' => ['host' => 'redis', 'port' => 6379, 'username' => null, 'password' => null],
			'server' => ['address' => '0.0.0.0', 'port' => 8000, 'certificate' => null],
			'sockets' => ['address' => '0.0.0.0', 'port' => 8080],
			'security' => [
				'issuer' => 'com.fastybird.miniserver',
				'signature' => 'g3xHbkELpMD9LRqW4WmJkHL7kz2bdNYAQJyEuFVzR3k=',
			],
			'api' => ['prefixed' => ['modules' => true]],
			'appDir' => '/srv/fastybird',
			'wwwDir' => '/srv/fastybird/www',
			'vendorDir' => '/srv/fastybird/vendor',
			'debugMode' => true,
			'productionMode' => true,
			'consoleMode' => true,
			'tempDir' => '/srv/fastybird/var/temp',
			'logsDir' => '/srv/fastybird/var/logs',
		];
	}


	public function createServiceContainer(): Container_e69559cf0b
	{
		return $this;
	}


	public function createServiceContributeFlysystem__mountManager(): League\Flysystem\MountManager
	{
		return new League\Flysystem\MountManager([]);
	}


	public function createServiceContributteConsole__application(): Contributte\Console\Application
	{
		$service = new Contributte\Console\Application;
		$service->setName('FastyBird:MiniServer!');
		$service->setVersion('0.1.0');
		$service->setCatchExceptions(true);
		$service->setAutoExit(true);
		$service->add($this->getService('nettrineDbalConsole.reservedWordsCommand'));
		$service->add($this->getService('nettrineDbalConsole.runSqlCommand'));
		$service->add($this->getService('nettrineOrmConsole.schemaToolCreateCommand'));
		$service->add($this->getService('nettrineOrmConsole.schemaToolUpdateCommand'));
		$service->add($this->getService('nettrineOrmConsole.schemaToolDropCommand'));
		$service->add($this->getService('nettrineOrmConsole.convertMappingCommand'));
		$service->add($this->getService('nettrineOrmConsole.ensureProductionSettingsCommand'));
		$service->add($this->getService('nettrineOrmConsole.generateEntitiesCommand'));
		$service->add($this->getService('nettrineOrmConsole.generateProxiesCommand'));
		$service->add($this->getService('nettrineOrmConsole.generateRepositoriesCommand'));
		$service->add($this->getService('nettrineOrmConsole.infoCommand'));
		$service->add($this->getService('nettrineOrmConsole.mappingDescribeCommand'));
		$service->add($this->getService('nettrineOrmConsole.runDqlCommand'));
		$service->add($this->getService('nettrineOrmConsole.validateSchemaCommand'));
		$service->add($this->getService('nettrineOrmConsole.clearMetadataCacheCommand'));
		$service->add($this->getService('nettrineMigrations.currentCommand'));
		$service->add($this->getService('nettrineMigrations.diffCommand'));
		$service->add($this->getService('nettrineMigrations.dumpSchemaCommand'));
		$service->add($this->getService('nettrineMigrations.executeCommand'));
		$service->add($this->getService('nettrineMigrations.generateCommand'));
		$service->add($this->getService('nettrineMigrations.latestCommand'));
		$service->add($this->getService('nettrineMigrations.listCommand'));
		$service->add($this->getService('nettrineMigrations.migrateCommand'));
		$service->add($this->getService('nettrineMigrations.rollupCommand'));
		$service->add($this->getService('nettrineMigrations.statusCommand'));
		$service->add($this->getService('nettrineMigrations.syncMetadataCommand'));
		$service->add($this->getService('nettrineMigrations.upToDateCommand'));
		$service->add($this->getService('nettrineMigrations.versionCommand'));
		$service->add($this->getService('nettrineFixtures.loadDataFixturesCommand'));
		$service->add($this->getService('ipubWebsockets.commands.server'));
		$service->add($this->getService('fbRedisDbPlugin.commands.client'));
		$service->add($this->getService('fbWebServerPlugin.commands.server'));
		$service->add($this->getService('fbWsExchangePlugin.commands.server'));
		$service->add($this->getService('fbAccountsModule.commands.create'));
		$service->add($this->getService('fbAccountsModule.commands.initialize'));
		$service->add($this->getService('fbDevicesModule.commands.initialize'));
		$service->add($this->getService('fbDevicesModule.commands.connector'));
		$service->add($this->getService('fbTriggersModule.commands.initialize'));
		$service->add($this->getService('fbShellyConnector.commands.initialize'));
		$service->add($this->getService('fbShellyConnector.commands.discovery'));
		$service->add($this->getService('fbShellyConnector.commands.execute'));
		$service->add($this->getService('fbModbusConnector.commands.initialize'));
		$service->add($this->getService('fbModbusConnector.commands.execute'));
		$service->add($this->getService('fbHomeKitConnector.commands.initialize'));
		$service->add($this->getService('fbHomeKitConnector.commands.execute'));
		$service->add($this->getService('fbTuyaConnector.commands.initialize'));
		$service->add($this->getService('fbTuyaConnector.commands.discovery'));
		$service->add($this->getService('fbTuyaConnector.commands.execute'));
		$service->setDispatcher($this->getService('contributteEvents.dispatcher'));
		$service->getHelperSet()->set($this->getService('nettrineOrmConsole.entityManagerHelper'),'em');
		return $service;
	}


	public function createServiceContributteEvents__dispatcher(): Symfony\Component\EventDispatcher\EventDispatcherInterface
	{
		$service = new Contributte\EventDispatcher\LazyEventDispatcher($this);
		$service->addSubscriberLazy('FastyBird\Plugin\RedisDb\Events\ClientCreated', 'fbRedisDbPlugin.subscribers.client');
		$service->addSubscriberLazy('FastyBird\Plugin\WebServer\Events\Startup', 'fbWebServerPlugin.subscribers.server');
		$service->addSubscriberLazy('FastyBird\Plugin\WebServer\Events\Request', 'fbWebServerPlugin.subscribers.server');
		$service->addSubscriberLazy('FastyBird\Plugin\WebServer\Events\Response', 'fbWebServerPlugin.subscribers.server');
		$service->addSubscriberLazy(
			'FastyBird\Plugin\WsExchange\Events\ClientConnected',
			'fbWsExchangePlugin.subscribers.client',
		);
		$service->addSubscriberLazy(
			'FastyBird\Plugin\WsExchange\Events\IncomingMessage',
			'fbWsExchangePlugin.subscribers.client',
		);
		$service->addSubscriberLazy('FastyBird\Module\Devices\Events\StateEntityCreated', 'fbDevicesModule.subscribers.states');
		$service->addSubscriberLazy('FastyBird\Module\Devices\Events\StateEntityUpdated', 'fbDevicesModule.subscribers.states');
		$service->addSubscriberLazy('FastyBird\Module\Devices\Events\StateEntityDeleted', 'fbDevicesModule.subscribers.states');
		$service->addSubscriberLazy(
			'FastyBird\Module\Devices\Events\ConnectorStartup',
			'fbHomeKitConnector.subscribers.connector',
		);
		return $service;
	}


	public function createServiceContributteMonolog__logger__default(): Monolog\Logger
	{
		$service = new Monolog\Logger(
			'default',
			[$this->getService('contributteMonolog.logger.default.handler.0')],
			[
				$this->getService('contributteMonolog.logger.default.processor.0'),
				$this->getService('contributteMonolog.logger.default.processor.1'),
			],
		);
		$service->pushHandler($this->getService('fbBootstrapLibrary.logger.handler.rotatingFile'));
		$service->pushHandler($this->getService('fbBootstrapLibrary.logger.handler.stdOut'));
		return $service;
	}


	public function createServiceContributteMonolog__logger__default__handler__0(): Mangoweb\MonologTracyHandler\TracyHandler
	{
		return new Mangoweb\MonologTracyHandler\TracyHandler('/srv/fastybird/var/logs');
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
			'/srv/fastybird/vendor/fastybird/fastybird/src/FastyBird/Module/Accounts/src/Translations/commands.en_US.neon',
			'en_US',
			'commands',
		);
		$service->addResource(
			'/srv/fastybird/vendor/fastybird/fastybird/src/FastyBird/Module/Accounts/src/Translations/accounts-module.en_US.neon',
			'en_US',
			'accounts-module',
		);
		$service->addResource(
			'/srv/fastybird/vendor/fastybird/fastybird/src/FastyBird/Module/Devices/src/Translations/devices-module.en_US.neon',
			'en_US',
			'devices-module',
		);
		$service->addResource(
			'/srv/fastybird/vendor/fastybird/fastybird/src/FastyBird/Module/Triggers/src/Translations/triggers-module.en_US.neon',
			'en_US',
			'triggers-module',
		);
		$service->addResource('/srv/fastybird/vendor/fastybird/json-api/src/Translations/jsonApi.en_US.neon', 'en_US', 'jsonApi');
		return $service;
	}


	public function createServiceContributteTranslation__translator(): Contributte\Translation\Translator
	{
		$service = new Contributte\Translation\Translator(
			$this->getService('contributteTranslation.localeResolver'),
			$this->getService('contributteTranslation.fallbackResolver'),
			'en_US',
			'/srv/fastybird/var/temp/cache/translation',
			true,
			[],
		);
		$service->setLocalesWhitelist(null);
		$service->setConfigCacheFactory($this->getService('contributteTranslation.configCacheFactory'));
		$service->setFallbackLocales(['en_US', 'en']);
		$service->returnOriginalMessage = true;
		$service->addLoader('neon', $this->getService('contributteTranslation.loaderNeon'));
		$service->addResource(
			'neon',
			'/srv/fastybird/vendor/fastybird/fastybird/src/FastyBird/Module/Accounts/src/Translations/commands.en_US.neon',
			'en_US',
			'commands',
		);
		$service->addResource(
			'neon',
			'/srv/fastybird/vendor/fastybird/fastybird/src/FastyBird/Module/Accounts/src/Translations/accounts-module.en_US.neon',
			'en_US',
			'accounts-module',
		);
		$service->addResource(
			'neon',
			'/srv/fastybird/vendor/fastybird/fastybird/src/FastyBird/Module/Devices/src/Translations/devices-module.en_US.neon',
			'en_US',
			'devices-module',
		);
		$service->addResource(
			'neon',
			'/srv/fastybird/vendor/fastybird/fastybird/src/FastyBird/Module/Triggers/src/Translations/triggers-module.en_US.neon',
			'en_US',
			'triggers-module',
		);
		$service->addResource(
			'neon',
			'/srv/fastybird/vendor/fastybird/json-api/src/Translations/jsonApi.en_US.neon',
			'en_US',
			'jsonApi',
		);
		return $service;
	}


	public function createServiceEntity__factory__actions__channel__action(): FastyBird\Library\Metadata\Entities\Actions\ActionChannelControlEntityFactory
	{
		return new FastyBird\Library\Metadata\Entities\Actions\ActionChannelControlEntityFactory(
			$this->getService('loaders.schema'),
			$this->getService('schema.validator'),
		);
	}


	public function createServiceEntity__factory__actions__channel__property__action(): FastyBird\Library\Metadata\Entities\Actions\ActionChannelPropertyEntityFactory
	{
		return new FastyBird\Library\Metadata\Entities\Actions\ActionChannelPropertyEntityFactory(
			$this->getService('loaders.schema'),
			$this->getService('schema.validator'),
		);
	}


	public function createServiceEntity__factory__actions__connector__action(): FastyBird\Library\Metadata\Entities\Actions\ActionConnectorControlEntityFactory
	{
		return new FastyBird\Library\Metadata\Entities\Actions\ActionConnectorControlEntityFactory(
			$this->getService('loaders.schema'),
			$this->getService('schema.validator'),
		);
	}


	public function createServiceEntity__factory__actions__connector__property__action(): FastyBird\Library\Metadata\Entities\Actions\ActionConnectorPropertyEntityFactory
	{
		return new FastyBird\Library\Metadata\Entities\Actions\ActionConnectorPropertyEntityFactory(
			$this->getService('loaders.schema'),
			$this->getService('schema.validator'),
		);
	}


	public function createServiceEntity__factory__actions__device__action(): FastyBird\Library\Metadata\Entities\Actions\ActionDeviceControlEntityFactory
	{
		return new FastyBird\Library\Metadata\Entities\Actions\ActionDeviceControlEntityFactory(
			$this->getService('loaders.schema'),
			$this->getService('schema.validator'),
		);
	}


	public function createServiceEntity__factory__actions__device__property__action(): FastyBird\Library\Metadata\Entities\Actions\ActionDevicePropertyEntityFactory
	{
		return new FastyBird\Library\Metadata\Entities\Actions\ActionDevicePropertyEntityFactory(
			$this->getService('loaders.schema'),
			$this->getService('schema.validator'),
		);
	}


	public function createServiceEntity__factory__actions__trigger__action(): FastyBird\Library\Metadata\Entities\Actions\ActionTriggerControlEntityFactory
	{
		return new FastyBird\Library\Metadata\Entities\Actions\ActionTriggerControlEntityFactory(
			$this->getService('loaders.schema'),
			$this->getService('schema.validator'),
		);
	}


	public function createServiceEntity__factory__modules__accountsModule__account(): FastyBird\Library\Metadata\Entities\AccountsModule\AccountEntityFactory
	{
		return new FastyBird\Library\Metadata\Entities\AccountsModule\AccountEntityFactory(
			$this->getService('loaders.schema'),
			$this->getService('schema.validator'),
		);
	}


	public function createServiceEntity__factory__modules__accountsModule__email(): FastyBird\Library\Metadata\Entities\AccountsModule\EmailEntityFactory
	{
		return new FastyBird\Library\Metadata\Entities\AccountsModule\EmailEntityFactory(
			$this->getService('loaders.schema'),
			$this->getService('schema.validator'),
		);
	}


	public function createServiceEntity__factory__modules__accountsModule__identity(): FastyBird\Library\Metadata\Entities\AccountsModule\IdentityEntityFactory
	{
		return new FastyBird\Library\Metadata\Entities\AccountsModule\IdentityEntityFactory(
			$this->getService('loaders.schema'),
			$this->getService('schema.validator'),
		);
	}


	public function createServiceEntity__factory__modules__accountsModule__role(): FastyBird\Library\Metadata\Entities\AccountsModule\RoleEntityFactory
	{
		return new FastyBird\Library\Metadata\Entities\AccountsModule\RoleEntityFactory(
			$this->getService('loaders.schema'),
			$this->getService('schema.validator'),
		);
	}


	public function createServiceEntity__factory__modules__devicesModule__channel(): FastyBird\Library\Metadata\Entities\DevicesModule\ChannelEntityFactory
	{
		return new FastyBird\Library\Metadata\Entities\DevicesModule\ChannelEntityFactory(
			$this->getService('loaders.schema'),
			$this->getService('schema.validator'),
		);
	}


	public function createServiceEntity__factory__modules__devicesModule__channel__control(): FastyBird\Library\Metadata\Entities\DevicesModule\ChannelControlEntityFactory
	{
		return new FastyBird\Library\Metadata\Entities\DevicesModule\ChannelControlEntityFactory(
			$this->getService('loaders.schema'),
			$this->getService('schema.validator'),
		);
	}


	public function createServiceEntity__factory__modules__devicesModule__channel__property(): FastyBird\Library\Metadata\Entities\DevicesModule\ChannelPropertyEntityFactory
	{
		return new FastyBird\Library\Metadata\Entities\DevicesModule\ChannelPropertyEntityFactory(
			$this->getService('loaders.schema'),
			$this->getService('schema.validator'),
		);
	}


	public function createServiceEntity__factory__modules__devicesModule__connector(): FastyBird\Library\Metadata\Entities\DevicesModule\ConnectorEntityFactory
	{
		return new FastyBird\Library\Metadata\Entities\DevicesModule\ConnectorEntityFactory(
			$this->getService('loaders.schema'),
			$this->getService('schema.validator'),
		);
	}


	public function createServiceEntity__factory__modules__devicesModule__connector__control(): FastyBird\Library\Metadata\Entities\DevicesModule\ConnectorControlEntityFactory
	{
		return new FastyBird\Library\Metadata\Entities\DevicesModule\ConnectorControlEntityFactory(
			$this->getService('loaders.schema'),
			$this->getService('schema.validator'),
		);
	}


	public function createServiceEntity__factory__modules__devicesModule__connector__property(): FastyBird\Library\Metadata\Entities\DevicesModule\ConnectorPropertyEntityFactory
	{
		return new FastyBird\Library\Metadata\Entities\DevicesModule\ConnectorPropertyEntityFactory(
			$this->getService('loaders.schema'),
			$this->getService('schema.validator'),
		);
	}


	public function createServiceEntity__factory__modules__devicesModule__device(): FastyBird\Library\Metadata\Entities\DevicesModule\DeviceEntityFactory
	{
		return new FastyBird\Library\Metadata\Entities\DevicesModule\DeviceEntityFactory(
			$this->getService('loaders.schema'),
			$this->getService('schema.validator'),
		);
	}


	public function createServiceEntity__factory__modules__devicesModule__device__attribute(): FastyBird\Library\Metadata\Entities\DevicesModule\DeviceAttributeEntityFactory
	{
		return new FastyBird\Library\Metadata\Entities\DevicesModule\DeviceAttributeEntityFactory(
			$this->getService('loaders.schema'),
			$this->getService('schema.validator'),
		);
	}


	public function createServiceEntity__factory__modules__devicesModule__device__control(): FastyBird\Library\Metadata\Entities\DevicesModule\DeviceControlEntityFactory
	{
		return new FastyBird\Library\Metadata\Entities\DevicesModule\DeviceControlEntityFactory(
			$this->getService('loaders.schema'),
			$this->getService('schema.validator'),
		);
	}


	public function createServiceEntity__factory__modules__devicesModule__device__property(): FastyBird\Library\Metadata\Entities\DevicesModule\DevicePropertyEntityFactory
	{
		return new FastyBird\Library\Metadata\Entities\DevicesModule\DevicePropertyEntityFactory(
			$this->getService('loaders.schema'),
			$this->getService('schema.validator'),
		);
	}


	public function createServiceEntity__factory__modules__triggersModule__action(): FastyBird\Library\Metadata\Entities\TriggersModule\ActionEntityFactory
	{
		return new FastyBird\Library\Metadata\Entities\TriggersModule\ActionEntityFactory(
			$this->getService('loaders.schema'),
			$this->getService('schema.validator'),
		);
	}


	public function createServiceEntity__factory__modules__triggersModule__condition(): FastyBird\Library\Metadata\Entities\TriggersModule\ConditionEntityFactory
	{
		return new FastyBird\Library\Metadata\Entities\TriggersModule\ConditionEntityFactory(
			$this->getService('loaders.schema'),
			$this->getService('schema.validator'),
		);
	}


	public function createServiceEntity__factory__modules__triggersModule__notification(): FastyBird\Library\Metadata\Entities\TriggersModule\NotificationEntityFactory
	{
		return new FastyBird\Library\Metadata\Entities\TriggersModule\NotificationEntityFactory(
			$this->getService('loaders.schema'),
			$this->getService('schema.validator'),
			$this->getService('ipubPhone.phone'),
		);
	}


	public function createServiceEntity__factory__modules__triggersModule__trigger(): FastyBird\Library\Metadata\Entities\TriggersModule\TriggerEntityFactory
	{
		return new FastyBird\Library\Metadata\Entities\TriggersModule\TriggerEntityFactory(
			$this->getService('loaders.schema'),
			$this->getService('schema.validator'),
		);
	}


	public function createServiceEntity__factory__modules__triggersModule__triggerControl(): FastyBird\Library\Metadata\Entities\TriggersModule\TriggerControlEntityFactory
	{
		return new FastyBird\Library\Metadata\Entities\TriggersModule\TriggerControlEntityFactory(
			$this->getService('loaders.schema'),
			$this->getService('schema.validator'),
		);
	}


	public function createServiceFbAccountsModule__commands__create(): FastyBird\Module\Accounts\Commands\Accounts\Create
	{
		return new FastyBird\Module\Accounts\Commands\Accounts\Create(
			$this->getService('fbAccountsModule.models.accountsManager'),
			$this->getService('fbAccountsModule.models.emailsRepository'),
			$this->getService('fbAccountsModule.models.emailsManager'),
			$this->getService('fbAccountsModule.models.identitiesManager'),
			$this->getService('fbAccountsModule.models.rolesRepository'),
			$this->getService('contributteTranslation.translator'),
			$this->getService('nettrineOrm.managerRegistry'),
		);
	}


	public function createServiceFbAccountsModule__commands__initialize(): FastyBird\Module\Accounts\Commands\Initialize
	{
		return new FastyBird\Module\Accounts\Commands\Initialize($this->getService('contributteMonolog.logger.default'));
	}


	public function createServiceFbAccountsModule__controllers__account(): FastyBird\Module\Accounts\Controllers\AccountV1
	{
		$service = new FastyBird\Module\Accounts\Controllers\AccountV1(
			$this->getService('fbAccountsModule.hydrators.accounts.profile'),
			$this->getService('fbAccountsModule.models.accountsManager'),
		);
		$service->injectUser($this->getService('security.user'));
		$service->injectDateFactory($this->getService('fbDateTimeFactory.datetime.factory'));
		$service->injectTranslator($this->getService('contributteTranslation.translator'));
		$service->injectManagerRegistry($this->getService('nettrineOrm.managerRegistry'));
		$service->injectLogger($this->getService('contributteMonolog.logger.default'));
		$service->injectJsonApiBuilder($this->getService('fbJsonApi.builder'));
		$service->injectRoutesValidator($this->getService('fbAccountsModule.router.validator'));
		$service->injectHydratorsContainer($this->getService('fbJsonApi.hydrators.container'));
		return $service;
	}


	public function createServiceFbAccountsModule__controllers__accountEmails(): FastyBird\Module\Accounts\Controllers\AccountEmailsV1
	{
		$service = new FastyBird\Module\Accounts\Controllers\AccountEmailsV1(
			$this->getService('fbAccountsModule.hydrators.emails.profile'),
			$this->getService('fbAccountsModule.models.emailsRepository'),
			$this->getService('fbAccountsModule.models.emailsManager'),
			$this->getService('fbAccountsModule.security.hash'),
		);
		$service->injectUser($this->getService('security.user'));
		$service->injectDateFactory($this->getService('fbDateTimeFactory.datetime.factory'));
		$service->injectTranslator($this->getService('contributteTranslation.translator'));
		$service->injectManagerRegistry($this->getService('nettrineOrm.managerRegistry'));
		$service->injectLogger($this->getService('contributteMonolog.logger.default'));
		$service->injectJsonApiBuilder($this->getService('fbJsonApi.builder'));
		$service->injectRoutesValidator($this->getService('fbAccountsModule.router.validator'));
		$service->injectHydratorsContainer($this->getService('fbJsonApi.hydrators.container'));
		return $service;
	}


	public function createServiceFbAccountsModule__controllers__accountIdentities(): FastyBird\Module\Accounts\Controllers\AccountIdentitiesV1
	{
		$service = new FastyBird\Module\Accounts\Controllers\AccountIdentitiesV1(
			$this->getService('fbAccountsModule.models.identitiesRepository'),
			$this->getService('fbAccountsModule.models.identitiesManager'),
		);
		$service->injectUser($this->getService('security.user'));
		$service->injectDateFactory($this->getService('fbDateTimeFactory.datetime.factory'));
		$service->injectTranslator($this->getService('contributteTranslation.translator'));
		$service->injectManagerRegistry($this->getService('nettrineOrm.managerRegistry'));
		$service->injectLogger($this->getService('contributteMonolog.logger.default'));
		$service->injectJsonApiBuilder($this->getService('fbJsonApi.builder'));
		$service->injectRoutesValidator($this->getService('fbAccountsModule.router.validator'));
		$service->injectHydratorsContainer($this->getService('fbJsonApi.hydrators.container'));
		return $service;
	}


	public function createServiceFbAccountsModule__controllers__accounts(): FastyBird\Module\Accounts\Controllers\AccountsV1
	{
		$service = new FastyBird\Module\Accounts\Controllers\AccountsV1(
			$this->getService('fbAccountsModule.hydrators.accounts'),
			$this->getService('fbAccountsModule.models.accountsRepository'),
			$this->getService('fbAccountsModule.models.accountsManager'),
			$this->getService('fbAccountsModule.models.identitiesManager'),
		);
		$service->injectUser($this->getService('security.user'));
		$service->injectDateFactory($this->getService('fbDateTimeFactory.datetime.factory'));
		$service->injectTranslator($this->getService('contributteTranslation.translator'));
		$service->injectManagerRegistry($this->getService('nettrineOrm.managerRegistry'));
		$service->injectLogger($this->getService('contributteMonolog.logger.default'));
		$service->injectJsonApiBuilder($this->getService('fbJsonApi.builder'));
		$service->injectRoutesValidator($this->getService('fbAccountsModule.router.validator'));
		$service->injectHydratorsContainer($this->getService('fbJsonApi.hydrators.container'));
		return $service;
	}


	public function createServiceFbAccountsModule__controllers__emails(): FastyBird\Module\Accounts\Controllers\EmailsV1
	{
		$service = new FastyBird\Module\Accounts\Controllers\EmailsV1(
			$this->getService('fbAccountsModule.hydrators.emails.email'),
			$this->getService('fbAccountsModule.models.emailsRepository'),
			$this->getService('fbAccountsModule.models.emailsManager'),
			$this->getService('fbAccountsModule.models.accountsRepository'),
			$this->getService('fbAccountsModule.security.hash'),
		);
		$service->injectUser($this->getService('security.user'));
		$service->injectDateFactory($this->getService('fbDateTimeFactory.datetime.factory'));
		$service->injectTranslator($this->getService('contributteTranslation.translator'));
		$service->injectManagerRegistry($this->getService('nettrineOrm.managerRegistry'));
		$service->injectLogger($this->getService('contributteMonolog.logger.default'));
		$service->injectJsonApiBuilder($this->getService('fbJsonApi.builder'));
		$service->injectRoutesValidator($this->getService('fbAccountsModule.router.validator'));
		$service->injectHydratorsContainer($this->getService('fbJsonApi.hydrators.container'));
		return $service;
	}


	public function createServiceFbAccountsModule__controllers__identities(): FastyBird\Module\Accounts\Controllers\IdentitiesV1
	{
		$service = new FastyBird\Module\Accounts\Controllers\IdentitiesV1(
			$this->getService('fbAccountsModule.hydrators.identities.profile'),
			$this->getService('fbAccountsModule.models.identitiesRepository'),
			$this->getService('fbAccountsModule.models.identitiesManager'),
			$this->getService('fbAccountsModule.models.accountsRepository'),
		);
		$service->injectUser($this->getService('security.user'));
		$service->injectDateFactory($this->getService('fbDateTimeFactory.datetime.factory'));
		$service->injectTranslator($this->getService('contributteTranslation.translator'));
		$service->injectManagerRegistry($this->getService('nettrineOrm.managerRegistry'));
		$service->injectLogger($this->getService('contributteMonolog.logger.default'));
		$service->injectJsonApiBuilder($this->getService('fbJsonApi.builder'));
		$service->injectRoutesValidator($this->getService('fbAccountsModule.router.validator'));
		$service->injectHydratorsContainer($this->getService('fbJsonApi.hydrators.container'));
		return $service;
	}


	public function createServiceFbAccountsModule__controllers__public(): FastyBird\Module\Accounts\Controllers\PublicV1
	{
		$service = new FastyBird\Module\Accounts\Controllers\PublicV1(
			$this->getService('fbAccountsModule.models.identitiesRepository'),
			$this->getService('fbAccountsModule.models.accountsManager'),
			$this->getService('fbAccountsModule.security.hash'),
		);
		$service->injectUser($this->getService('security.user'));
		$service->injectDateFactory($this->getService('fbDateTimeFactory.datetime.factory'));
		$service->injectTranslator($this->getService('contributteTranslation.translator'));
		$service->injectManagerRegistry($this->getService('nettrineOrm.managerRegistry'));
		$service->injectLogger($this->getService('contributteMonolog.logger.default'));
		$service->injectJsonApiBuilder($this->getService('fbJsonApi.builder'));
		$service->injectRoutesValidator($this->getService('fbAccountsModule.router.validator'));
		$service->injectHydratorsContainer($this->getService('fbJsonApi.hydrators.container'));
		return $service;
	}


	public function createServiceFbAccountsModule__controllers__roleChildren(): FastyBird\Module\Accounts\Controllers\RoleChildrenV1
	{
		$service = new FastyBird\Module\Accounts\Controllers\RoleChildrenV1($this->getService('fbAccountsModule.models.rolesRepository'));
		$service->injectUser($this->getService('security.user'));
		$service->injectDateFactory($this->getService('fbDateTimeFactory.datetime.factory'));
		$service->injectTranslator($this->getService('contributteTranslation.translator'));
		$service->injectManagerRegistry($this->getService('nettrineOrm.managerRegistry'));
		$service->injectLogger($this->getService('contributteMonolog.logger.default'));
		$service->injectJsonApiBuilder($this->getService('fbJsonApi.builder'));
		$service->injectRoutesValidator($this->getService('fbAccountsModule.router.validator'));
		$service->injectHydratorsContainer($this->getService('fbJsonApi.hydrators.container'));
		return $service;
	}


	public function createServiceFbAccountsModule__controllers__roles(): FastyBird\Module\Accounts\Controllers\RolesV1
	{
		$service = new FastyBird\Module\Accounts\Controllers\RolesV1(
			$this->getService('fbAccountsModule.models.rolesRepository'),
			$this->getService('fbAccountsModule.models.rolesManager'),
			$this->getService('fbAccountsModule.hydrators.role'),
		);
		$service->injectUser($this->getService('security.user'));
		$service->injectDateFactory($this->getService('fbDateTimeFactory.datetime.factory'));
		$service->injectTranslator($this->getService('contributteTranslation.translator'));
		$service->injectManagerRegistry($this->getService('nettrineOrm.managerRegistry'));
		$service->injectLogger($this->getService('contributteMonolog.logger.default'));
		$service->injectJsonApiBuilder($this->getService('fbJsonApi.builder'));
		$service->injectRoutesValidator($this->getService('fbAccountsModule.router.validator'));
		$service->injectHydratorsContainer($this->getService('fbJsonApi.hydrators.container'));
		return $service;
	}


	public function createServiceFbAccountsModule__controllers__session(): FastyBird\Module\Accounts\Controllers\SessionV1
	{
		$service = new FastyBird\Module\Accounts\Controllers\SessionV1(
			$this->getService('fbSimpleAuth.doctrine.tokenRepository'),
			$this->getService('fbSimpleAuth.doctrine.tokensManager'),
			$this->getService('fbSimpleAuth.token.reader'),
			$this->getService('fbSimpleAuth.token.builder'),
		);
		$service->injectUser($this->getService('security.user'));
		$service->injectDateFactory($this->getService('fbDateTimeFactory.datetime.factory'));
		$service->injectTranslator($this->getService('contributteTranslation.translator'));
		$service->injectManagerRegistry($this->getService('nettrineOrm.managerRegistry'));
		$service->injectLogger($this->getService('contributteMonolog.logger.default'));
		$service->injectJsonApiBuilder($this->getService('fbJsonApi.builder'));
		$service->injectRoutesValidator($this->getService('fbAccountsModule.router.validator'));
		$service->injectHydratorsContainer($this->getService('fbJsonApi.hydrators.container'));
		return $service;
	}


	public function createServiceFbAccountsModule__hydrators__accounts(): FastyBird\Module\Accounts\Hydrators\Accounts\Account
	{
		return new FastyBird\Module\Accounts\Hydrators\Accounts\Account(
			$this->getService('nettrineOrm.managerRegistry'),
			$this->getService('contributteTranslation.translator'),
			$this->getService('fbJsonApi.helpers.crudReader'),
			$this->getService('nettrineCache.driver'),
		);
	}


	public function createServiceFbAccountsModule__hydrators__accounts__profile(): FastyBird\Module\Accounts\Hydrators\Accounts\ProfileAccount
	{
		return new FastyBird\Module\Accounts\Hydrators\Accounts\ProfileAccount(
			$this->getService('nettrineOrm.managerRegistry'),
			$this->getService('contributteTranslation.translator'),
			$this->getService('fbJsonApi.helpers.crudReader'),
			$this->getService('nettrineCache.driver'),
		);
	}


	public function createServiceFbAccountsModule__hydrators__emails__email(): FastyBird\Module\Accounts\Hydrators\Emails\Email
	{
		return new FastyBird\Module\Accounts\Hydrators\Emails\Email(
			$this->getService('nettrineOrm.managerRegistry'),
			$this->getService('contributteTranslation.translator'),
			$this->getService('fbJsonApi.helpers.crudReader'),
			$this->getService('nettrineCache.driver'),
		);
	}


	public function createServiceFbAccountsModule__hydrators__emails__profile(): FastyBird\Module\Accounts\Hydrators\Emails\ProfileEmail
	{
		return new FastyBird\Module\Accounts\Hydrators\Emails\ProfileEmail(
			$this->getService('nettrineOrm.managerRegistry'),
			$this->getService('contributteTranslation.translator'),
			$this->getService('fbJsonApi.helpers.crudReader'),
			$this->getService('nettrineCache.driver'),
		);
	}


	public function createServiceFbAccountsModule__hydrators__identities__profile(): FastyBird\Module\Accounts\Hydrators\Identities\Identity
	{
		return new FastyBird\Module\Accounts\Hydrators\Identities\Identity(
			$this->getService('nettrineOrm.managerRegistry'),
			$this->getService('contributteTranslation.translator'),
			$this->getService('fbJsonApi.helpers.crudReader'),
			$this->getService('nettrineCache.driver'),
		);
	}


	public function createServiceFbAccountsModule__hydrators__role(): FastyBird\Module\Accounts\Hydrators\Roles\Role
	{
		return new FastyBird\Module\Accounts\Hydrators\Roles\Role(
			$this->getService('nettrineOrm.managerRegistry'),
			$this->getService('contributteTranslation.translator'),
			$this->getService('fbJsonApi.helpers.crudReader'),
			$this->getService('nettrineCache.driver'),
		);
	}


	public function createServiceFbAccountsModule__middlewares__access(): FastyBird\Module\Accounts\Middleware\Access
	{
		return new FastyBird\Module\Accounts\Middleware\Access($this->getService('contributteTranslation.translator'));
	}


	public function createServiceFbAccountsModule__middlewares__urlFormat(): FastyBird\Module\Accounts\Middleware\UrlFormat
	{
		return new FastyBird\Module\Accounts\Middleware\UrlFormat(
			$this->getService('security.user'),
			$this->getService('contributteTranslation.translator'),
		);
	}


	public function createServiceFbAccountsModule__models__accountsManager(): FastyBird\Module\Accounts\Models\Accounts\AccountsManager
	{
		return new FastyBird\Module\Accounts\Models\Accounts\AccountsManager($this->getService('ipubDoctrineCrud.crud')->create('FastyBird\Module\Accounts\Entities\Accounts\Account'));
	}


	public function createServiceFbAccountsModule__models__accountsRepository(): FastyBird\Module\Accounts\Models\Accounts\AccountsRepository
	{
		return new FastyBird\Module\Accounts\Models\Accounts\AccountsRepository($this->getService('nettrineOrm.managerRegistry'));
	}


	public function createServiceFbAccountsModule__models__emailsManager(): FastyBird\Module\Accounts\Models\Emails\EmailsManager
	{
		return new FastyBird\Module\Accounts\Models\Emails\EmailsManager($this->getService('ipubDoctrineCrud.crud')->create('FastyBird\Module\Accounts\Entities\Emails\Email'));
	}


	public function createServiceFbAccountsModule__models__emailsRepository(): FastyBird\Module\Accounts\Models\Emails\EmailsRepository
	{
		return new FastyBird\Module\Accounts\Models\Emails\EmailsRepository($this->getService('nettrineOrm.managerRegistry'));
	}


	public function createServiceFbAccountsModule__models__identitiesManager(): FastyBird\Module\Accounts\Models\Identities\IdentitiesManager
	{
		return new FastyBird\Module\Accounts\Models\Identities\IdentitiesManager($this->getService('ipubDoctrineCrud.crud')->create('FastyBird\Module\Accounts\Entities\Identities\Identity'));
	}


	public function createServiceFbAccountsModule__models__identitiesRepository(): FastyBird\Module\Accounts\Models\Identities\IdentitiesRepository
	{
		return new FastyBird\Module\Accounts\Models\Identities\IdentitiesRepository($this->getService('nettrineOrm.managerRegistry'));
	}


	public function createServiceFbAccountsModule__models__rolesManager(): FastyBird\Module\Accounts\Models\Roles\RolesManager
	{
		return new FastyBird\Module\Accounts\Models\Roles\RolesManager($this->getService('ipubDoctrineCrud.crud')->create('FastyBird\Module\Accounts\Entities\Roles\Role'));
	}


	public function createServiceFbAccountsModule__models__rolesRepository(): FastyBird\Module\Accounts\Models\Roles\RolesRepository
	{
		return new FastyBird\Module\Accounts\Models\Roles\RolesRepository($this->getService('nettrineOrm.managerRegistry'));
	}


	public function createServiceFbAccountsModule__router__routes(): FastyBird\Module\Accounts\Router\Routes
	{
		return new FastyBird\Module\Accounts\Router\Routes(
			true,
			$this->getService('fbAccountsModule.controllers.public'),
			$this->getService('fbAccountsModule.controllers.session'),
			$this->getService('fbAccountsModule.controllers.account'),
			$this->getService('fbAccountsModule.controllers.accountEmails'),
			$this->getService('fbAccountsModule.controllers.accountIdentities'),
			$this->getService('fbAccountsModule.controllers.accounts'),
			$this->getService('fbAccountsModule.controllers.emails'),
			$this->getService('fbAccountsModule.controllers.identities'),
			$this->getService('fbAccountsModule.controllers.roles'),
			$this->getService('fbAccountsModule.controllers.roleChildren'),
			$this->getService('fbAccountsModule.middlewares.access'),
			$this->getService('fbSimpleAuth.middleware.access'),
			$this->getService('fbSimpleAuth.middleware.user'),
		);
	}


	public function createServiceFbAccountsModule__router__validator(): FastyBird\Module\Accounts\Router\Validator
	{
		return new FastyBird\Module\Accounts\Router\Validator($this);
	}


	public function createServiceFbAccountsModule__schemas__account(): FastyBird\Module\Accounts\Schemas\Accounts\Account
	{
		return new FastyBird\Module\Accounts\Schemas\Accounts\Account($this->getService('fbWebServerPlugin.routing.router'));
	}


	public function createServiceFbAccountsModule__schemas__email(): FastyBird\Module\Accounts\Schemas\Emails\Email
	{
		return new FastyBird\Module\Accounts\Schemas\Emails\Email($this->getService('fbWebServerPlugin.routing.router'));
	}


	public function createServiceFbAccountsModule__schemas__identity(): FastyBird\Module\Accounts\Schemas\Identities\Identity
	{
		return new FastyBird\Module\Accounts\Schemas\Identities\Identity($this->getService('fbWebServerPlugin.routing.router'));
	}


	public function createServiceFbAccountsModule__schemas__role(): FastyBird\Module\Accounts\Schemas\Roles\Role
	{
		return new FastyBird\Module\Accounts\Schemas\Roles\Role(
			$this->getService('fbAccountsModule.models.rolesRepository'),
			$this->getService('fbWebServerPlugin.routing.router'),
		);
	}


	public function createServiceFbAccountsModule__schemas__session(): FastyBird\Module\Accounts\Schemas\Sessions\Session
	{
		return new FastyBird\Module\Accounts\Schemas\Sessions\Session($this->getService('fbWebServerPlugin.routing.router'));
	}


	public function createServiceFbAccountsModule__security__authenticator(): FastyBird\Module\Accounts\Security\Authenticator
	{
		return new FastyBird\Module\Accounts\Security\Authenticator($this->getService('fbAccountsModule.models.identitiesRepository'));
	}


	public function createServiceFbAccountsModule__security__hash(): FastyBird\Module\Accounts\Helpers\SecurityHash
	{
		return new FastyBird\Module\Accounts\Helpers\SecurityHash($this->getService('fbDateTimeFactory.datetime.factory'));
	}


	public function createServiceFbAccountsModule__security__identityFactory(): FastyBird\Module\Accounts\Security\IdentityFactory
	{
		return new FastyBird\Module\Accounts\Security\IdentityFactory($this->getService('fbSimpleAuth.doctrine.tokenRepository'));
	}


	public function createServiceFbAccountsModule__subscribers__accountEntity(): FastyBird\Module\Accounts\Subscribers\AccountEntity
	{
		return new FastyBird\Module\Accounts\Subscribers\AccountEntity(
			$this->getService('fbAccountsModule.models.accountsRepository'),
			$this->getService('fbAccountsModule.models.rolesRepository'),
		);
	}


	public function createServiceFbAccountsModule__subscribers__emailEntity(): FastyBird\Module\Accounts\Subscribers\EmailEntity
	{
		return new FastyBird\Module\Accounts\Subscribers\EmailEntity($this->getService('fbAccountsModule.models.emailsRepository'));
	}


	public function createServiceFbAccountsModule__subscribers__entities(): FastyBird\Module\Accounts\Subscribers\ModuleEntities
	{
		return new FastyBird\Module\Accounts\Subscribers\ModuleEntities(
			$this->getService('fbExchangeLibrary.entityFactory'),
			$this->getService('nettrineOrm.entityManagerDecorator'),
			$this->getService('fbExchangeLibrary.publisher'),
		);
	}


	public function createServiceFbBootstrapLibrary__helpers__database(): FastyBird\Library\Bootstrap\Helpers\Database
	{
		return new FastyBird\Library\Bootstrap\Helpers\Database($this->getService('nettrineOrm.managerRegistry'));
	}


	public function createServiceFbBootstrapLibrary__logger__handler__rotatingFile(): Monolog\Handler\RotatingFileHandler
	{
		return new Monolog\Handler\RotatingFileHandler('/srv/fastybird/var/logs/app.log', 10, 300);
	}


	public function createServiceFbBootstrapLibrary__logger__handler__stdOut(): Monolog\Handler\StreamHandler
	{
		return new Monolog\Handler\StreamHandler('php://stdout', 300);
	}


	public function createServiceFbDateTimeFactory__datetime__factory(): FastyBird\DateTimeFactory\Factory
	{
		return new FastyBird\DateTimeFactory\Factory('UTC');
	}


	public function createServiceFbDevicesModule__commands__connector(): FastyBird\Module\Devices\Commands\Connector
	{
		$service = new FastyBird\Module\Devices\Commands\Connector(
			$this->getService('fbDevicesModule.models.connectorsRepository'),
			$this->getService('fbDevicesModule.models.devicesRepository'),
			$this->getService('fbDevicesModule.utilities.connector.connection'),
			$this->getService('fbDevicesModule.utilities.devices.connection'),
			$this->getService('fbDevicesModule.utilities.devices.states'),
			$this->getService('fbDevicesModule.utilities.channels.states'),
			$this->getService('fbDateTimeFactory.datetime.factory'),
			$this->getService('ipubWebsockets.server.loop'),
			$this->getService('contributteEvents.dispatcher'),
			$this->getService('contributteMonolog.logger.default'),
		);
		$service->attach($this->getService('fbFbMqttConnector.executor.factory'), 'fb-mqtt');
		$service->attach($this->getService('fbShellyConnector.executor.factory'), 'shelly');
		$service->attach($this->getService('fbModbusConnector.executor.factory'), 'modbus');
		$service->attach($this->getService('fbHomeKitConnector.executor.factory'), 'homekit');
		$service->attach($this->getService('fbTuyaConnector.executor.factory'), 'tuya');
		return $service;
	}


	public function createServiceFbDevicesModule__commands__initialize(): FastyBird\Module\Devices\Commands\Initialize
	{
		return new FastyBird\Module\Devices\Commands\Initialize($this->getService('contributteMonolog.logger.default'));
	}


	public function createServiceFbDevicesModule__controllers__channelControls(): FastyBird\Module\Devices\Controllers\ChannelControlsV1
	{
		$service = new FastyBird\Module\Devices\Controllers\ChannelControlsV1(
			$this->getService('fbDevicesModule.models.devicesRepository'),
			$this->getService('fbDevicesModule.models.channelsRepository'),
			$this->getService('fbDevicesModule.models.channelControlsRepository'),
		);
		$service->injectTranslator($this->getService('contributteTranslation.translator'));
		$service->injectManagerRegistry($this->getService('nettrineOrm.managerRegistry'));
		$service->injectLogger($this->getService('contributteMonolog.logger.default'));
		$service->injectJsonApiBuilder($this->getService('fbJsonApi.builder'));
		$service->injectRoutesValidator($this->getService('fbDevicesModule.router.validator'));
		$service->injectHydratorsContainer($this->getService('fbJsonApi.hydrators.container'));
		return $service;
	}


	public function createServiceFbDevicesModule__controllers__channelProperties(): FastyBird\Module\Devices\Controllers\ChannelPropertiesV1
	{
		$service = new FastyBird\Module\Devices\Controllers\ChannelPropertiesV1(
			$this->getService('fbDevicesModule.models.devicesRepository'),
			$this->getService('fbDevicesModule.models.channelsRepository'),
			$this->getService('fbDevicesModule.models.channelPropertiesRepository'),
			$this->getService('fbDevicesModule.models.channelsPropertiesManager'),
		);
		$service->injectTranslator($this->getService('contributteTranslation.translator'));
		$service->injectManagerRegistry($this->getService('nettrineOrm.managerRegistry'));
		$service->injectLogger($this->getService('contributteMonolog.logger.default'));
		$service->injectJsonApiBuilder($this->getService('fbJsonApi.builder'));
		$service->injectRoutesValidator($this->getService('fbDevicesModule.router.validator'));
		$service->injectHydratorsContainer($this->getService('fbJsonApi.hydrators.container'));
		return $service;
	}


	public function createServiceFbDevicesModule__controllers__channelPropertyChildren(): FastyBird\Module\Devices\Controllers\ChannelPropertyChildrenV1
	{
		$service = new FastyBird\Module\Devices\Controllers\ChannelPropertyChildrenV1(
			$this->getService('fbDevicesModule.models.devicesRepository'),
			$this->getService('fbDevicesModule.models.channelPropertiesRepository'),
		);
		$service->injectTranslator($this->getService('contributteTranslation.translator'));
		$service->injectManagerRegistry($this->getService('nettrineOrm.managerRegistry'));
		$service->injectLogger($this->getService('contributteMonolog.logger.default'));
		$service->injectJsonApiBuilder($this->getService('fbJsonApi.builder'));
		$service->injectRoutesValidator($this->getService('fbDevicesModule.router.validator'));
		$service->injectHydratorsContainer($this->getService('fbJsonApi.hydrators.container'));
		return $service;
	}


	public function createServiceFbDevicesModule__controllers__channels(): FastyBird\Module\Devices\Controllers\ChannelsV1
	{
		$service = new FastyBird\Module\Devices\Controllers\ChannelsV1(
			$this->getService('fbDevicesModule.models.devicesRepository'),
			$this->getService('fbDevicesModule.models.channelsRepository'),
			$this->getService('fbDevicesModule.models.channelsManager'),
		);
		$service->injectTranslator($this->getService('contributteTranslation.translator'));
		$service->injectManagerRegistry($this->getService('nettrineOrm.managerRegistry'));
		$service->injectLogger($this->getService('contributteMonolog.logger.default'));
		$service->injectJsonApiBuilder($this->getService('fbJsonApi.builder'));
		$service->injectRoutesValidator($this->getService('fbDevicesModule.router.validator'));
		$service->injectHydratorsContainer($this->getService('fbJsonApi.hydrators.container'));
		return $service;
	}


	public function createServiceFbDevicesModule__controllers__connectorProperties(): FastyBird\Module\Devices\Controllers\ConnectorPropertiesV1
	{
		$service = new FastyBird\Module\Devices\Controllers\ConnectorPropertiesV1(
			$this->getService('fbDevicesModule.models.connectorsRepository'),
			$this->getService('fbDevicesModule.models.connectorPropertiesRepository'),
			$this->getService('fbDevicesModule.models.connectorsPropertiesManager'),
		);
		$service->injectTranslator($this->getService('contributteTranslation.translator'));
		$service->injectManagerRegistry($this->getService('nettrineOrm.managerRegistry'));
		$service->injectLogger($this->getService('contributteMonolog.logger.default'));
		$service->injectJsonApiBuilder($this->getService('fbJsonApi.builder'));
		$service->injectRoutesValidator($this->getService('fbDevicesModule.router.validator'));
		$service->injectHydratorsContainer($this->getService('fbJsonApi.hydrators.container'));
		return $service;
	}


	public function createServiceFbDevicesModule__controllers__connectors(): FastyBird\Module\Devices\Controllers\ConnectorsV1
	{
		$service = new FastyBird\Module\Devices\Controllers\ConnectorsV1(
			$this->getService('fbDevicesModule.models.connectorsRepository'),
			$this->getService('fbDevicesModule.models.connectorsManager'),
		);
		$service->injectTranslator($this->getService('contributteTranslation.translator'));
		$service->injectManagerRegistry($this->getService('nettrineOrm.managerRegistry'));
		$service->injectLogger($this->getService('contributteMonolog.logger.default'));
		$service->injectJsonApiBuilder($this->getService('fbJsonApi.builder'));
		$service->injectRoutesValidator($this->getService('fbDevicesModule.router.validator'));
		$service->injectHydratorsContainer($this->getService('fbJsonApi.hydrators.container'));
		return $service;
	}


	public function createServiceFbDevicesModule__controllers__connectorsControls(): FastyBird\Module\Devices\Controllers\ConnectorControlsV1
	{
		$service = new FastyBird\Module\Devices\Controllers\ConnectorControlsV1(
			$this->getService('fbDevicesModule.models.connectorsRepository'),
			$this->getService('fbDevicesModule.models.connectorControlsRepository'),
		);
		$service->injectTranslator($this->getService('contributteTranslation.translator'));
		$service->injectManagerRegistry($this->getService('nettrineOrm.managerRegistry'));
		$service->injectLogger($this->getService('contributteMonolog.logger.default'));
		$service->injectJsonApiBuilder($this->getService('fbJsonApi.builder'));
		$service->injectRoutesValidator($this->getService('fbDevicesModule.router.validator'));
		$service->injectHydratorsContainer($this->getService('fbJsonApi.hydrators.container'));
		return $service;
	}


	public function createServiceFbDevicesModule__controllers__deviceAttributes(): FastyBird\Module\Devices\Controllers\DeviceAttributesV1
	{
		$service = new FastyBird\Module\Devices\Controllers\DeviceAttributesV1(
			$this->getService('fbDevicesModule.models.devicesRepository'),
			$this->getService('fbDevicesModule.models.deviceAttributesRepository'),
		);
		$service->injectTranslator($this->getService('contributteTranslation.translator'));
		$service->injectManagerRegistry($this->getService('nettrineOrm.managerRegistry'));
		$service->injectLogger($this->getService('contributteMonolog.logger.default'));
		$service->injectJsonApiBuilder($this->getService('fbJsonApi.builder'));
		$service->injectRoutesValidator($this->getService('fbDevicesModule.router.validator'));
		$service->injectHydratorsContainer($this->getService('fbJsonApi.hydrators.container'));
		return $service;
	}


	public function createServiceFbDevicesModule__controllers__deviceChildren(): FastyBird\Module\Devices\Controllers\DeviceChildrenV1
	{
		$service = new FastyBird\Module\Devices\Controllers\DeviceChildrenV1($this->getService('fbDevicesModule.models.devicesRepository'));
		$service->injectTranslator($this->getService('contributteTranslation.translator'));
		$service->injectManagerRegistry($this->getService('nettrineOrm.managerRegistry'));
		$service->injectLogger($this->getService('contributteMonolog.logger.default'));
		$service->injectJsonApiBuilder($this->getService('fbJsonApi.builder'));
		$service->injectRoutesValidator($this->getService('fbDevicesModule.router.validator'));
		$service->injectHydratorsContainer($this->getService('fbJsonApi.hydrators.container'));
		return $service;
	}


	public function createServiceFbDevicesModule__controllers__deviceControls(): FastyBird\Module\Devices\Controllers\DeviceControlsV1
	{
		$service = new FastyBird\Module\Devices\Controllers\DeviceControlsV1(
			$this->getService('fbDevicesModule.models.devicesRepository'),
			$this->getService('fbDevicesModule.models.deviceControlsRepository'),
		);
		$service->injectTranslator($this->getService('contributteTranslation.translator'));
		$service->injectManagerRegistry($this->getService('nettrineOrm.managerRegistry'));
		$service->injectLogger($this->getService('contributteMonolog.logger.default'));
		$service->injectJsonApiBuilder($this->getService('fbJsonApi.builder'));
		$service->injectRoutesValidator($this->getService('fbDevicesModule.router.validator'));
		$service->injectHydratorsContainer($this->getService('fbJsonApi.hydrators.container'));
		return $service;
	}


	public function createServiceFbDevicesModule__controllers__deviceParents(): FastyBird\Module\Devices\Controllers\DeviceParentsV1
	{
		$service = new FastyBird\Module\Devices\Controllers\DeviceParentsV1($this->getService('fbDevicesModule.models.devicesRepository'));
		$service->injectTranslator($this->getService('contributteTranslation.translator'));
		$service->injectManagerRegistry($this->getService('nettrineOrm.managerRegistry'));
		$service->injectLogger($this->getService('contributteMonolog.logger.default'));
		$service->injectJsonApiBuilder($this->getService('fbJsonApi.builder'));
		$service->injectRoutesValidator($this->getService('fbDevicesModule.router.validator'));
		$service->injectHydratorsContainer($this->getService('fbJsonApi.hydrators.container'));
		return $service;
	}


	public function createServiceFbDevicesModule__controllers__deviceProperties(): FastyBird\Module\Devices\Controllers\DevicePropertiesV1
	{
		$service = new FastyBird\Module\Devices\Controllers\DevicePropertiesV1(
			$this->getService('fbDevicesModule.models.devicesRepository'),
			$this->getService('fbDevicesModule.models.devicePropertiesRepository'),
			$this->getService('fbDevicesModule.models.devicesPropertiesManager'),
		);
		$service->injectTranslator($this->getService('contributteTranslation.translator'));
		$service->injectManagerRegistry($this->getService('nettrineOrm.managerRegistry'));
		$service->injectLogger($this->getService('contributteMonolog.logger.default'));
		$service->injectJsonApiBuilder($this->getService('fbJsonApi.builder'));
		$service->injectRoutesValidator($this->getService('fbDevicesModule.router.validator'));
		$service->injectHydratorsContainer($this->getService('fbJsonApi.hydrators.container'));
		return $service;
	}


	public function createServiceFbDevicesModule__controllers__devicePropertyChildren(): FastyBird\Module\Devices\Controllers\DevicePropertyChildrenV1
	{
		$service = new FastyBird\Module\Devices\Controllers\DevicePropertyChildrenV1(
			$this->getService('fbDevicesModule.models.devicesRepository'),
			$this->getService('fbDevicesModule.models.devicePropertiesRepository'),
		);
		$service->injectTranslator($this->getService('contributteTranslation.translator'));
		$service->injectManagerRegistry($this->getService('nettrineOrm.managerRegistry'));
		$service->injectLogger($this->getService('contributteMonolog.logger.default'));
		$service->injectJsonApiBuilder($this->getService('fbJsonApi.builder'));
		$service->injectRoutesValidator($this->getService('fbDevicesModule.router.validator'));
		$service->injectHydratorsContainer($this->getService('fbJsonApi.hydrators.container'));
		return $service;
	}


	public function createServiceFbDevicesModule__controllers__devices(): FastyBird\Module\Devices\Controllers\DevicesV1
	{
		$service = new FastyBird\Module\Devices\Controllers\DevicesV1(
			$this->getService('fbDevicesModule.models.devicesRepository'),
			$this->getService('fbDevicesModule.models.devicesManager'),
			$this->getService('fbDevicesModule.models.channelsRepository'),
			$this->getService('fbDevicesModule.models.channelsManager'),
		);
		$service->injectTranslator($this->getService('contributteTranslation.translator'));
		$service->injectManagerRegistry($this->getService('nettrineOrm.managerRegistry'));
		$service->injectLogger($this->getService('contributteMonolog.logger.default'));
		$service->injectJsonApiBuilder($this->getService('fbJsonApi.builder'));
		$service->injectRoutesValidator($this->getService('fbDevicesModule.router.validator'));
		$service->injectHydratorsContainer($this->getService('fbJsonApi.hydrators.container'));
		return $service;
	}


	public function createServiceFbDevicesModule__exchange__consumer__states(): FastyBird\Module\Devices\Consumers\State
	{
		return new FastyBird\Module\Devices\Consumers\State(
			$this->getService('fbExchangeLibrary.publisher'),
			$this->getService('fbExchangeLibrary.entityFactory'),
			$this->getService('fbDevicesModule.models.connectorPropertiesRepository'),
			$this->getService('fbDevicesModule.models.devicePropertiesRepository'),
			$this->getService('fbDevicesModule.models.channelPropertiesRepository'),
			$this->getService('fbDevicesModule.utilities.connectors.states'),
			$this->getService('fbDevicesModule.utilities.devices.states'),
			$this->getService('fbDevicesModule.utilities.channels.states'),
		);
	}


	public function createServiceFbDevicesModule__hydrators__channel(): FastyBird\Module\Devices\Hydrators\Channels\Channel
	{
		return new FastyBird\Module\Devices\Hydrators\Channels\Channel(
			$this->getService('nettrineOrm.managerRegistry'),
			$this->getService('contributteTranslation.translator'),
			$this->getService('fbJsonApi.helpers.crudReader'),
			$this->getService('nettrineCache.driver'),
		);
	}


	public function createServiceFbDevicesModule__hydrators__channel__property__dynamic(): FastyBird\Module\Devices\Hydrators\Properties\ChannelDynamic
	{
		return new FastyBird\Module\Devices\Hydrators\Properties\ChannelDynamic(
			$this->getService('nettrineOrm.managerRegistry'),
			$this->getService('contributteTranslation.translator'),
			$this->getService('fbJsonApi.helpers.crudReader'),
			$this->getService('nettrineCache.driver'),
		);
	}


	public function createServiceFbDevicesModule__hydrators__channel__property__mapped(): FastyBird\Module\Devices\Hydrators\Properties\ChannelMapped
	{
		return new FastyBird\Module\Devices\Hydrators\Properties\ChannelMapped(
			$this->getService('nettrineOrm.managerRegistry'),
			$this->getService('contributteTranslation.translator'),
			$this->getService('fbJsonApi.helpers.crudReader'),
			$this->getService('nettrineCache.driver'),
		);
	}


	public function createServiceFbDevicesModule__hydrators__channel__property__variable(): FastyBird\Module\Devices\Hydrators\Properties\ChannelVariable
	{
		return new FastyBird\Module\Devices\Hydrators\Properties\ChannelVariable(
			$this->getService('nettrineOrm.managerRegistry'),
			$this->getService('contributteTranslation.translator'),
			$this->getService('fbJsonApi.helpers.crudReader'),
			$this->getService('nettrineCache.driver'),
		);
	}


	public function createServiceFbDevicesModule__hydrators__connector__property__dynamic(): FastyBird\Module\Devices\Hydrators\Properties\ConnectorDynamic
	{
		return new FastyBird\Module\Devices\Hydrators\Properties\ConnectorDynamic(
			$this->getService('nettrineOrm.managerRegistry'),
			$this->getService('contributteTranslation.translator'),
			$this->getService('fbJsonApi.helpers.crudReader'),
			$this->getService('nettrineCache.driver'),
		);
	}


	public function createServiceFbDevicesModule__hydrators__connector__property__variable(): FastyBird\Module\Devices\Hydrators\Properties\ConnectorVariable
	{
		return new FastyBird\Module\Devices\Hydrators\Properties\ConnectorVariable(
			$this->getService('nettrineOrm.managerRegistry'),
			$this->getService('contributteTranslation.translator'),
			$this->getService('fbJsonApi.helpers.crudReader'),
			$this->getService('nettrineCache.driver'),
		);
	}


	public function createServiceFbDevicesModule__hydrators__connectors__blank(): FastyBird\Module\Devices\Hydrators\Connectors\Blank
	{
		return new FastyBird\Module\Devices\Hydrators\Connectors\Blank(
			$this->getService('nettrineOrm.managerRegistry'),
			$this->getService('contributteTranslation.translator'),
			$this->getService('fbJsonApi.helpers.crudReader'),
			$this->getService('nettrineCache.driver'),
		);
	}


	public function createServiceFbDevicesModule__hydrators__device__blank(): FastyBird\Module\Devices\Hydrators\Devices\Blank
	{
		return new FastyBird\Module\Devices\Hydrators\Devices\Blank(
			$this->getService('nettrineOrm.managerRegistry'),
			$this->getService('contributteTranslation.translator'),
			$this->getService('fbJsonApi.helpers.crudReader'),
			$this->getService('nettrineCache.driver'),
		);
	}


	public function createServiceFbDevicesModule__hydrators__device__property__dynamic(): FastyBird\Module\Devices\Hydrators\Properties\DeviceDynamic
	{
		return new FastyBird\Module\Devices\Hydrators\Properties\DeviceDynamic(
			$this->getService('nettrineOrm.managerRegistry'),
			$this->getService('contributteTranslation.translator'),
			$this->getService('fbJsonApi.helpers.crudReader'),
			$this->getService('nettrineCache.driver'),
		);
	}


	public function createServiceFbDevicesModule__hydrators__device__property__mapped(): FastyBird\Module\Devices\Hydrators\Properties\DeviceMapped
	{
		return new FastyBird\Module\Devices\Hydrators\Properties\DeviceMapped(
			$this->getService('nettrineOrm.managerRegistry'),
			$this->getService('contributteTranslation.translator'),
			$this->getService('fbJsonApi.helpers.crudReader'),
			$this->getService('nettrineCache.driver'),
		);
	}


	public function createServiceFbDevicesModule__hydrators__device__property__variable(): FastyBird\Module\Devices\Hydrators\Properties\DeviceVariable
	{
		return new FastyBird\Module\Devices\Hydrators\Properties\DeviceVariable(
			$this->getService('nettrineOrm.managerRegistry'),
			$this->getService('contributteTranslation.translator'),
			$this->getService('fbJsonApi.helpers.crudReader'),
			$this->getService('nettrineCache.driver'),
		);
	}


	public function createServiceFbDevicesModule__middlewares__access(): FastyBird\Module\Devices\Middleware\Access
	{
		return new FastyBird\Module\Devices\Middleware\Access($this->getService('contributteTranslation.translator'));
	}


	public function createServiceFbDevicesModule__models__channelControlsRepository(): FastyBird\Module\Devices\Models\Channels\Controls\ControlsRepository
	{
		return new FastyBird\Module\Devices\Models\Channels\Controls\ControlsRepository(
			$this->getService('fbDevicesModule.utilities.database'),
			$this->getService('nettrineOrm.managerRegistry'),
		);
	}


	public function createServiceFbDevicesModule__models__channelPropertiesRepository(): FastyBird\Module\Devices\Models\Channels\Properties\PropertiesRepository
	{
		return new FastyBird\Module\Devices\Models\Channels\Properties\PropertiesRepository(
			$this->getService('fbDevicesModule.utilities.database'),
			$this->getService('nettrineOrm.managerRegistry'),
		);
	}


	public function createServiceFbDevicesModule__models__channelsControlsManager(): FastyBird\Module\Devices\Models\Channels\Controls\ControlsManager
	{
		return new FastyBird\Module\Devices\Models\Channels\Controls\ControlsManager($this->getService('ipubDoctrineCrud.crud')->create('FastyBird\Module\Devices\Entities\Channels\Controls\Control'));
	}


	public function createServiceFbDevicesModule__models__channelsManager(): FastyBird\Module\Devices\Models\Channels\ChannelsManager
	{
		return new FastyBird\Module\Devices\Models\Channels\ChannelsManager($this->getService('ipubDoctrineCrud.crud')->create('FastyBird\Module\Devices\Entities\Channels\Channel'));
	}


	public function createServiceFbDevicesModule__models__channelsPropertiesManager(): FastyBird\Module\Devices\Models\Channels\Properties\PropertiesManager
	{
		return new FastyBird\Module\Devices\Models\Channels\Properties\PropertiesManager($this->getService('ipubDoctrineCrud.crud')->create('FastyBird\Module\Devices\Entities\Channels\Properties\Property'));
	}


	public function createServiceFbDevicesModule__models__channelsRepository(): FastyBird\Module\Devices\Models\Channels\ChannelsRepository
	{
		return new FastyBird\Module\Devices\Models\Channels\ChannelsRepository(
			$this->getService('fbDevicesModule.utilities.database'),
			$this->getService('nettrineOrm.managerRegistry'),
		);
	}


	public function createServiceFbDevicesModule__models__connectorControlsRepository(): FastyBird\Module\Devices\Models\Connectors\Controls\ControlsRepository
	{
		return new FastyBird\Module\Devices\Models\Connectors\Controls\ControlsRepository(
			$this->getService('fbDevicesModule.utilities.database'),
			$this->getService('nettrineOrm.managerRegistry'),
		);
	}


	public function createServiceFbDevicesModule__models__connectorPropertiesRepository(): FastyBird\Module\Devices\Models\Connectors\Properties\PropertiesRepository
	{
		return new FastyBird\Module\Devices\Models\Connectors\Properties\PropertiesRepository(
			$this->getService('fbDevicesModule.utilities.database'),
			$this->getService('nettrineOrm.managerRegistry'),
		);
	}


	public function createServiceFbDevicesModule__models__connectorsControlsManager(): FastyBird\Module\Devices\Models\Connectors\Controls\ControlsManager
	{
		return new FastyBird\Module\Devices\Models\Connectors\Controls\ControlsManager($this->getService('ipubDoctrineCrud.crud')->create('FastyBird\Module\Devices\Entities\Connectors\Controls\Control'));
	}


	public function createServiceFbDevicesModule__models__connectorsManager(): FastyBird\Module\Devices\Models\Connectors\ConnectorsManager
	{
		return new FastyBird\Module\Devices\Models\Connectors\ConnectorsManager($this->getService('ipubDoctrineCrud.crud')->create('FastyBird\Module\Devices\Entities\Connectors\Connector'));
	}


	public function createServiceFbDevicesModule__models__connectorsPropertiesManager(): FastyBird\Module\Devices\Models\Connectors\Properties\PropertiesManager
	{
		return new FastyBird\Module\Devices\Models\Connectors\Properties\PropertiesManager($this->getService('ipubDoctrineCrud.crud')->create('FastyBird\Module\Devices\Entities\Connectors\Properties\Property'));
	}


	public function createServiceFbDevicesModule__models__connectorsRepository(): FastyBird\Module\Devices\Models\Connectors\ConnectorsRepository
	{
		return new FastyBird\Module\Devices\Models\Connectors\ConnectorsRepository(
			$this->getService('fbDevicesModule.utilities.database'),
			$this->getService('nettrineOrm.managerRegistry'),
		);
	}


	public function createServiceFbDevicesModule__models__deviceAttributesRepository(): FastyBird\Module\Devices\Models\Devices\Attributes\AttributesRepository
	{
		return new FastyBird\Module\Devices\Models\Devices\Attributes\AttributesRepository(
			$this->getService('fbDevicesModule.utilities.database'),
			$this->getService('nettrineOrm.managerRegistry'),
		);
	}


	public function createServiceFbDevicesModule__models__deviceControlsRepository(): FastyBird\Module\Devices\Models\Devices\Controls\ControlsRepository
	{
		return new FastyBird\Module\Devices\Models\Devices\Controls\ControlsRepository(
			$this->getService('fbDevicesModule.utilities.database'),
			$this->getService('nettrineOrm.managerRegistry'),
		);
	}


	public function createServiceFbDevicesModule__models__devicePropertiesRepository(): FastyBird\Module\Devices\Models\Devices\Properties\PropertiesRepository
	{
		return new FastyBird\Module\Devices\Models\Devices\Properties\PropertiesRepository(
			$this->getService('fbDevicesModule.utilities.database'),
			$this->getService('nettrineOrm.managerRegistry'),
		);
	}


	public function createServiceFbDevicesModule__models__devicesAttributesManager(): FastyBird\Module\Devices\Models\Devices\Attributes\AttributesManager
	{
		return new FastyBird\Module\Devices\Models\Devices\Attributes\AttributesManager($this->getService('ipubDoctrineCrud.crud')->create('FastyBird\Module\Devices\Entities\Devices\Attributes\Attribute'));
	}


	public function createServiceFbDevicesModule__models__devicesControlsManager(): FastyBird\Module\Devices\Models\Devices\Controls\ControlsManager
	{
		return new FastyBird\Module\Devices\Models\Devices\Controls\ControlsManager($this->getService('ipubDoctrineCrud.crud')->create('FastyBird\Module\Devices\Entities\Devices\Controls\Control'));
	}


	public function createServiceFbDevicesModule__models__devicesManager(): FastyBird\Module\Devices\Models\Devices\DevicesManager
	{
		return new FastyBird\Module\Devices\Models\Devices\DevicesManager($this->getService('ipubDoctrineCrud.crud')->create('FastyBird\Module\Devices\Entities\Devices\Device'));
	}


	public function createServiceFbDevicesModule__models__devicesPropertiesManager(): FastyBird\Module\Devices\Models\Devices\Properties\PropertiesManager
	{
		return new FastyBird\Module\Devices\Models\Devices\Properties\PropertiesManager($this->getService('ipubDoctrineCrud.crud')->create('FastyBird\Module\Devices\Entities\Devices\Properties\Property'));
	}


	public function createServiceFbDevicesModule__models__devicesRepository(): FastyBird\Module\Devices\Models\Devices\DevicesRepository
	{
		return new FastyBird\Module\Devices\Models\Devices\DevicesRepository(
			$this->getService('fbDevicesModule.utilities.database'),
			$this->getService('nettrineOrm.managerRegistry'),
		);
	}


	public function createServiceFbDevicesModule__router__routes(): FastyBird\Module\Devices\Router\Routes
	{
		return new FastyBird\Module\Devices\Router\Routes(
			true,
			$this->getService('fbDevicesModule.controllers.devices'),
			$this->getService('fbDevicesModule.controllers.deviceParents'),
			$this->getService('fbDevicesModule.controllers.deviceChildren'),
			$this->getService('fbDevicesModule.controllers.deviceProperties'),
			$this->getService('fbDevicesModule.controllers.devicePropertyChildren'),
			$this->getService('fbDevicesModule.controllers.deviceControls'),
			$this->getService('fbDevicesModule.controllers.deviceAttributes'),
			$this->getService('fbDevicesModule.controllers.channels'),
			$this->getService('fbDevicesModule.controllers.channelProperties'),
			$this->getService('fbDevicesModule.controllers.channelPropertyChildren'),
			$this->getService('fbDevicesModule.controllers.channelControls'),
			$this->getService('fbDevicesModule.controllers.connectors'),
			$this->getService('fbDevicesModule.controllers.connectorProperties'),
			$this->getService('fbDevicesModule.controllers.connectorsControls'),
			$this->getService('fbDevicesModule.middlewares.access'),
			$this->getService('fbSimpleAuth.middleware.access'),
			$this->getService('fbSimpleAuth.middleware.user'),
		);
	}


	public function createServiceFbDevicesModule__router__validator(): FastyBird\Module\Devices\Router\Validator
	{
		return new FastyBird\Module\Devices\Router\Validator($this);
	}


	public function createServiceFbDevicesModule__schemas__channel(): FastyBird\Module\Devices\Schemas\Channels\Channel
	{
		return new FastyBird\Module\Devices\Schemas\Channels\Channel($this->getService('fbWebServerPlugin.routing.router'));
	}


	public function createServiceFbDevicesModule__schemas__channel__property__dynamic(): FastyBird\Module\Devices\Schemas\Channels\Properties\Dynamic
	{
		return new FastyBird\Module\Devices\Schemas\Channels\Properties\Dynamic(
			$this->getService('fbWebServerPlugin.routing.router'),
			$this->getService('fbDevicesModule.models.channelPropertiesRepository'),
			$this->getService('fbDevicesModule.states.repositories.channels.properties'),
		);
	}


	public function createServiceFbDevicesModule__schemas__channel__property__mapped(): FastyBird\Module\Devices\Schemas\Channels\Properties\Mapped
	{
		return new FastyBird\Module\Devices\Schemas\Channels\Properties\Mapped(
			$this->getService('fbWebServerPlugin.routing.router'),
			$this->getService('fbDevicesModule.models.channelPropertiesRepository'),
			$this->getService('fbDevicesModule.states.repositories.channels.properties'),
		);
	}


	public function createServiceFbDevicesModule__schemas__channel__property__variable(): FastyBird\Module\Devices\Schemas\Channels\Properties\Variable
	{
		return new FastyBird\Module\Devices\Schemas\Channels\Properties\Variable(
			$this->getService('fbWebServerPlugin.routing.router'),
			$this->getService('fbDevicesModule.models.channelPropertiesRepository'),
		);
	}


	public function createServiceFbDevicesModule__schemas__connector__blank(): FastyBird\Module\Devices\Schemas\Connectors\Blank
	{
		return new FastyBird\Module\Devices\Schemas\Connectors\Blank($this->getService('fbWebServerPlugin.routing.router'));
	}


	public function createServiceFbDevicesModule__schemas__connector__controls(): FastyBird\Module\Devices\Schemas\Connectors\Controls\Control
	{
		return new FastyBird\Module\Devices\Schemas\Connectors\Controls\Control($this->getService('fbWebServerPlugin.routing.router'));
	}


	public function createServiceFbDevicesModule__schemas__connector__property__dynamic(): FastyBird\Module\Devices\Schemas\Connectors\Properties\Dynamic
	{
		return new FastyBird\Module\Devices\Schemas\Connectors\Properties\Dynamic(
			$this->getService('fbWebServerPlugin.routing.router'),
			$this->getService('fbDevicesModule.states.repositories.connectors.properties'),
		);
	}


	public function createServiceFbDevicesModule__schemas__connector__property__variable(): FastyBird\Module\Devices\Schemas\Connectors\Properties\Variable
	{
		return new FastyBird\Module\Devices\Schemas\Connectors\Properties\Variable($this->getService('fbWebServerPlugin.routing.router'));
	}


	public function createServiceFbDevicesModule__schemas__control(): FastyBird\Module\Devices\Schemas\Channels\Controls\Control
	{
		return new FastyBird\Module\Devices\Schemas\Channels\Controls\Control($this->getService('fbWebServerPlugin.routing.router'));
	}


	public function createServiceFbDevicesModule__schemas__device__attribute(): FastyBird\Module\Devices\Schemas\Devices\Attributes\Attribute
	{
		return new FastyBird\Module\Devices\Schemas\Devices\Attributes\Attribute($this->getService('fbWebServerPlugin.routing.router'));
	}


	public function createServiceFbDevicesModule__schemas__device__blank(): FastyBird\Module\Devices\Schemas\Devices\Blank
	{
		return new FastyBird\Module\Devices\Schemas\Devices\Blank(
			$this->getService('fbDevicesModule.models.devicesRepository'),
			$this->getService('fbDevicesModule.models.channelsRepository'),
			$this->getService('fbWebServerPlugin.routing.router'),
		);
	}


	public function createServiceFbDevicesModule__schemas__device__control(): FastyBird\Module\Devices\Schemas\Devices\Controls\Control
	{
		return new FastyBird\Module\Devices\Schemas\Devices\Controls\Control($this->getService('fbWebServerPlugin.routing.router'));
	}


	public function createServiceFbDevicesModule__schemas__device__property__dynamic(): FastyBird\Module\Devices\Schemas\Devices\Properties\Dynamic
	{
		return new FastyBird\Module\Devices\Schemas\Devices\Properties\Dynamic(
			$this->getService('fbWebServerPlugin.routing.router'),
			$this->getService('fbDevicesModule.models.devicePropertiesRepository'),
			$this->getService('fbDevicesModule.states.repositories.devices.properties'),
		);
	}


	public function createServiceFbDevicesModule__schemas__device__property__mapped(): FastyBird\Module\Devices\Schemas\Devices\Properties\Mapped
	{
		return new FastyBird\Module\Devices\Schemas\Devices\Properties\Mapped(
			$this->getService('fbWebServerPlugin.routing.router'),
			$this->getService('fbDevicesModule.models.devicePropertiesRepository'),
			$this->getService('fbDevicesModule.states.repositories.devices.properties'),
		);
	}


	public function createServiceFbDevicesModule__schemas__device__property__variable(): FastyBird\Module\Devices\Schemas\Devices\Properties\Variable
	{
		return new FastyBird\Module\Devices\Schemas\Devices\Properties\Variable(
			$this->getService('fbWebServerPlugin.routing.router'),
			$this->getService('fbDevicesModule.models.devicePropertiesRepository'),
		);
	}


	public function createServiceFbDevicesModule__states__managers__channels__properties(): FastyBird\Module\Devices\Models\States\ChannelPropertiesManager
	{
		return new FastyBird\Module\Devices\Models\States\ChannelPropertiesManager(
			$this->getService('fbExchangeLibrary.entityFactory'),
			null,
			$this->getService('contributteEvents.dispatcher'),
		);
	}


	public function createServiceFbDevicesModule__states__managers__connectors__properties(): FastyBird\Module\Devices\Models\States\ConnectorPropertiesManager
	{
		return new FastyBird\Module\Devices\Models\States\ConnectorPropertiesManager(
			null,
			$this->getService('contributteEvents.dispatcher'),
		);
	}


	public function createServiceFbDevicesModule__states__managers__devices__properties(): FastyBird\Module\Devices\Models\States\DevicePropertiesManager
	{
		return new FastyBird\Module\Devices\Models\States\DevicePropertiesManager(
			null,
			$this->getService('contributteEvents.dispatcher'),
		);
	}


	public function createServiceFbDevicesModule__states__repositories__channels__properties(): FastyBird\Module\Devices\Models\States\ChannelPropertiesRepository
	{
		return new FastyBird\Module\Devices\Models\States\ChannelPropertiesRepository(null);
	}


	public function createServiceFbDevicesModule__states__repositories__connectors__properties(): FastyBird\Module\Devices\Models\States\ConnectorPropertiesRepository
	{
		return new FastyBird\Module\Devices\Models\States\ConnectorPropertiesRepository(null);
	}


	public function createServiceFbDevicesModule__states__repositories__devices__properties(): FastyBird\Module\Devices\Models\States\DevicePropertiesRepository
	{
		return new FastyBird\Module\Devices\Models\States\DevicePropertiesRepository(null);
	}


	public function createServiceFbDevicesModule__subscribers__entities(): FastyBird\Module\Devices\Subscribers\ModuleEntities
	{
		return new FastyBird\Module\Devices\Subscribers\ModuleEntities(
			$this->getService('nettrineOrm.entityManagerDecorator'),
			$this->getService('fbDevicesModule.states.managers.connectors.properties'),
			$this->getService('fbDevicesModule.states.managers.devices.properties'),
			$this->getService('fbDevicesModule.states.managers.channels.properties'),
			$this->getService('fbDevicesModule.utilities.connectors.states'),
			$this->getService('fbDevicesModule.utilities.devices.states'),
			$this->getService('fbDevicesModule.utilities.channels.states'),
			$this->getService('fbExchangeLibrary.entityFactory'),
			$this->getService('fbExchangeLibrary.publisher'),
		);
	}


	public function createServiceFbDevicesModule__subscribers__states(): FastyBird\Module\Devices\Subscribers\StateEntities
	{
		return new FastyBird\Module\Devices\Subscribers\StateEntities(
			$this->getService('fbDevicesModule.models.devicePropertiesRepository'),
			$this->getService('fbDevicesModule.models.channelPropertiesRepository'),
			$this->getService('fbExchangeLibrary.entityFactory'),
			$this->getService('fbExchangeLibrary.publisher'),
		);
	}


	public function createServiceFbDevicesModule__utilities__channels__states(): FastyBird\Module\Devices\Utilities\ChannelPropertiesStates
	{
		return new FastyBird\Module\Devices\Utilities\ChannelPropertiesStates(
			$this->getService('fbDevicesModule.models.channelPropertiesRepository'),
			$this->getService('fbDevicesModule.states.repositories.channels.properties'),
			$this->getService('fbDevicesModule.states.managers.channels.properties'),
			$this->getService('contributteMonolog.logger.default'),
		);
	}


	public function createServiceFbDevicesModule__utilities__connector__connection(): FastyBird\Module\Devices\Utilities\ConnectorConnection
	{
		return new FastyBird\Module\Devices\Utilities\ConnectorConnection(
			$this->getService('fbDevicesModule.models.connectorsPropertiesManager'),
			$this->getService('fbDevicesModule.utilities.connectors.states'),
		);
	}


	public function createServiceFbDevicesModule__utilities__connectors__states(): FastyBird\Module\Devices\Utilities\ConnectorPropertiesStates
	{
		return new FastyBird\Module\Devices\Utilities\ConnectorPropertiesStates(
			$this->getService('fbDevicesModule.states.repositories.connectors.properties'),
			$this->getService('fbDevicesModule.states.managers.connectors.properties'),
			$this->getService('contributteMonolog.logger.default'),
		);
	}


	public function createServiceFbDevicesModule__utilities__database(): FastyBird\Module\Devices\Utilities\Database
	{
		return new FastyBird\Module\Devices\Utilities\Database(
			$this->getService('fbBootstrapLibrary.helpers.database'),
			$this->getService('nettrineOrm.managerRegistry'),
		);
	}


	public function createServiceFbDevicesModule__utilities__devices__connection(): FastyBird\Module\Devices\Utilities\DeviceConnection
	{
		return new FastyBird\Module\Devices\Utilities\DeviceConnection(
			$this->getService('fbDevicesModule.models.devicesPropertiesManager'),
			$this->getService('fbDevicesModule.utilities.devices.states'),
		);
	}


	public function createServiceFbDevicesModule__utilities__devices__states(): FastyBird\Module\Devices\Utilities\DevicePropertiesStates
	{
		return new FastyBird\Module\Devices\Utilities\DevicePropertiesStates(
			$this->getService('fbDevicesModule.models.devicePropertiesRepository'),
			$this->getService('fbDevicesModule.states.repositories.devices.properties'),
			$this->getService('fbDevicesModule.states.managers.devices.properties'),
			$this->getService('contributteMonolog.logger.default'),
		);
	}


	public function createServiceFbExchangeLibrary__consumer(): FastyBird\Library\Exchange\Consumers\Container
	{
		$service = new FastyBird\Library\Exchange\Consumers\Container($this->getService('contributteEvents.dispatcher'));
		$service->register($this->getService('fbWsExchangePlugin.exchange.consumer'), false);
		$service->register($this->getService('fbDevicesModule.exchange.consumer.states'), false);
		$service->register($this->getService('fbHomeKitConnector.consumers.exchange'), false);
		return $service;
	}


	public function createServiceFbExchangeLibrary__entityFactory(): FastyBird\Library\Exchange\Entities\EntityFactory
	{
		return new FastyBird\Library\Exchange\Entities\EntityFactory(
			$this->getService('entity.factory.actions.connector.action'),
			$this->getService('entity.factory.actions.connector.property.action'),
			$this->getService('entity.factory.actions.device.action'),
			$this->getService('entity.factory.actions.device.property.action'),
			$this->getService('entity.factory.actions.channel.action'),
			$this->getService('entity.factory.actions.channel.property.action'),
			$this->getService('entity.factory.actions.trigger.action'),
			$this->getService('entity.factory.modules.accountsModule.account'),
			$this->getService('entity.factory.modules.accountsModule.email'),
			$this->getService('entity.factory.modules.accountsModule.identity'),
			$this->getService('entity.factory.modules.accountsModule.role'),
			$this->getService('entity.factory.modules.triggersModule.action'),
			$this->getService('entity.factory.modules.triggersModule.condition'),
			$this->getService('entity.factory.modules.triggersModule.notification'),
			$this->getService('entity.factory.modules.triggersModule.triggerControl'),
			$this->getService('entity.factory.modules.triggersModule.trigger'),
			$this->getService('entity.factory.modules.devicesModule.connector'),
			$this->getService('entity.factory.modules.devicesModule.connector.control'),
			$this->getService('entity.factory.modules.devicesModule.connector.property'),
			$this->getService('entity.factory.modules.devicesModule.device'),
			$this->getService('entity.factory.modules.devicesModule.device.control'),
			$this->getService('entity.factory.modules.devicesModule.device.property'),
			$this->getService('entity.factory.modules.devicesModule.device.attribute'),
			$this->getService('entity.factory.modules.devicesModule.channel'),
			$this->getService('entity.factory.modules.devicesModule.channel.control'),
			$this->getService('entity.factory.modules.devicesModule.channel.property'),
		);
	}


	public function createServiceFbExchangeLibrary__publisher(): FastyBird\Library\Exchange\Publisher\Container
	{
		$service = new FastyBird\Library\Exchange\Publisher\Container($this->getService('contributteEvents.dispatcher'));
		$service->register($this->getService('fbRedisDbPlugin.publisher'));
		return $service;
	}


	public function createServiceFbFbMqttConnector__api__v1builder(): FastyBird\Connector\FbMqtt\API\V1Builder
	{
		return new FastyBird\Connector\FbMqtt\API\V1Builder;
	}


	public function createServiceFbFbMqttConnector__api__v1parser(): FastyBird\Connector\FbMqtt\API\V1Parser
	{
		return new FastyBird\Connector\FbMqtt\API\V1Parser($this->getService('fbFbMqttConnector.api.v1validator'));
	}


	public function createServiceFbFbMqttConnector__api__v1validator(): FastyBird\Connector\FbMqtt\API\V1Validator
	{
		return new FastyBird\Connector\FbMqtt\API\V1Validator;
	}


	public function createServiceFbFbMqttConnector__client__apiv1(): FastyBird\Connector\FbMqtt\Clients\FbMqttV1Factory
	{
		return new class ($this) implements FastyBird\Connector\FbMqtt\Clients\FbMqttV1Factory {
			private $container;


			public function __construct(Container_e69559cf0b $container)
			{
				$this->container = $container;
			}


			public function create(FastyBird\Connector\FbMqtt\Entities\FbMqttConnector $connector): FastyBird\Connector\FbMqtt\Clients\FbMqttV1
			{
				return new FastyBird\Connector\FbMqtt\Clients\FbMqttV1(
					$connector,
					$this->container->getService('fbFbMqttConnector.api.v1validator'),
					$this->container->getService('fbFbMqttConnector.api.v1parser'),
					$this->container->getService('fbFbMqttConnector.api.v1builder'),
					$this->container->getService('fbFbMqttConnector.helpers.connector'),
					$this->container->getService('fbFbMqttConnector.helpers.property'),
					$this->container->getService('fbFbMqttConnector.consumers.proxy'),
					$this->container->getService('fbDevicesModule.utilities.devices.states'),
					$this->container->getService('fbDevicesModule.utilities.channels.states'),
					$this->container->getService('fbDevicesModule.utilities.devices.connection'),
					$this->container->getService('fbDateTimeFactory.datetime.factory'),
					$this->container->getService('ipubWebsockets.server.loop'),
					logger: $this->container->getService('contributteMonolog.logger.default'),
				);
			}
		};
	}


	public function createServiceFbFbMqttConnector__consumers__channel__attribute__message(): FastyBird\Connector\FbMqtt\Consumers\Messages\Channel
	{
		return new FastyBird\Connector\FbMqtt\Consumers\Messages\Channel(
			$this->getService('fbDevicesModule.models.devicesRepository'),
			$this->getService('fbDevicesModule.models.channelsManager'),
			$this->getService('fbDevicesModule.models.channelsPropertiesManager'),
			$this->getService('fbDevicesModule.models.channelsControlsManager'),
			$this->getService('fbDevicesModule.utilities.database'),
			$this->getService('contributteMonolog.logger.default'),
		);
	}


	public function createServiceFbFbMqttConnector__consumers__channel__property__message(): FastyBird\Connector\FbMqtt\Consumers\Messages\ChannelProperty
	{
		return new FastyBird\Connector\FbMqtt\Consumers\Messages\ChannelProperty(
			$this->getService('fbDevicesModule.models.devicesRepository'),
			$this->getService('fbDevicesModule.models.channelsPropertiesManager'),
			$this->getService('fbDevicesModule.utilities.channels.states'),
			$this->getService('fbDevicesModule.utilities.database'),
			$this->getService('contributteMonolog.logger.default'),
		);
	}


	public function createServiceFbFbMqttConnector__consumers__device__attribute__message(): FastyBird\Connector\FbMqtt\Consumers\Messages\Device
	{
		return new FastyBird\Connector\FbMqtt\Consumers\Messages\Device(
			$this->getService('fbDevicesModule.models.devicesRepository'),
			$this->getService('fbDevicesModule.models.devicesManager'),
			$this->getService('fbDevicesModule.models.devicesPropertiesManager'),
			$this->getService('fbDevicesModule.models.devicesControlsManager'),
			$this->getService('fbDevicesModule.models.devicesAttributesManager'),
			$this->getService('fbDevicesModule.models.channelsManager'),
			$this->getService('fbDevicesModule.utilities.devices.connection'),
			$this->getService('fbDevicesModule.utilities.database'),
			$this->getService('contributteMonolog.logger.default'),
		);
	}


	public function createServiceFbFbMqttConnector__consumers__device__extension__message(): FastyBird\Connector\FbMqtt\Consumers\Messages\ExtensionAttribute
	{
		return new FastyBird\Connector\FbMqtt\Consumers\Messages\ExtensionAttribute(
			$this->getService('fbDevicesModule.models.devicesRepository'),
			$this->getService('fbDevicesModule.models.devicesAttributesManager'),
			$this->getService('fbDevicesModule.utilities.database'),
			$this->getService('contributteMonolog.logger.default'),
		);
	}


	public function createServiceFbFbMqttConnector__consumers__device__property__message(): FastyBird\Connector\FbMqtt\Consumers\Messages\DeviceProperty
	{
		return new FastyBird\Connector\FbMqtt\Consumers\Messages\DeviceProperty(
			$this->getService('fbDevicesModule.models.devicesRepository'),
			$this->getService('fbDevicesModule.models.devicePropertiesRepository'),
			$this->getService('fbDevicesModule.models.devicesPropertiesManager'),
			$this->getService('fbDevicesModule.utilities.devices.states'),
			$this->getService('fbDevicesModule.utilities.database'),
			$this->getService('contributteMonolog.logger.default'),
		);
	}


	public function createServiceFbFbMqttConnector__consumers__proxy(): FastyBird\Connector\FbMqtt\Consumers\Messages
	{
		return new FastyBird\Connector\FbMqtt\Consumers\Messages(
			[
				'fbFbMqttConnector.consumers.device.attribute.message' => $this->getService('fbFbMqttConnector.consumers.device.attribute.message'),
				'fbFbMqttConnector.consumers.device.extension.message' => $this->getService('fbFbMqttConnector.consumers.device.extension.message'),
				'fbFbMqttConnector.consumers.device.property.message' => $this->getService('fbFbMqttConnector.consumers.device.property.message'),
				'fbFbMqttConnector.consumers.channel.attribute.message' => $this->getService('fbFbMqttConnector.consumers.channel.attribute.message'),
				'fbFbMqttConnector.consumers.channel.property.message' => $this->getService('fbFbMqttConnector.consumers.channel.property.message'),
			],
			$this->getService('contributteMonolog.logger.default'),
		);
	}


	public function createServiceFbFbMqttConnector__executor__factory(): FastyBird\Connector\FbMqtt\Connector\ConnectorFactory
	{
		return new class ($this) implements FastyBird\Connector\FbMqtt\Connector\ConnectorFactory {
			private $container;


			public function __construct(Container_e69559cf0b $container)
			{
				$this->container = $container;
			}


			public function create(FastyBird\Module\Devices\Entities\Connectors\Connector $connector): FastyBird\Connector\FbMqtt\Connector\Connector
			{
				return new FastyBird\Connector\FbMqtt\Connector\Connector(
					$connector,
					['fbFbMqttConnector.client.apiv1' => $this->container->getService('fbFbMqttConnector.client.apiv1')],
					$this->container->getService('fbFbMqttConnector.helpers.connector'),
					$this->container->getService('fbFbMqttConnector.consumers.proxy'),
					$this->container->getService('ipubWebsockets.server.loop'),
				);
			}
		};
	}


	public function createServiceFbFbMqttConnector__helpers__connector(): FastyBird\Connector\FbMqtt\Helpers\Connector
	{
		return new FastyBird\Connector\FbMqtt\Helpers\Connector;
	}


	public function createServiceFbFbMqttConnector__helpers__property(): FastyBird\Connector\FbMqtt\Helpers\Property
	{
		return new FastyBird\Connector\FbMqtt\Helpers\Property(
			$this->getService('fbDevicesModule.utilities.devices.states'),
			$this->getService('fbDevicesModule.utilities.channels.states'),
		);
	}


	public function createServiceFbFbMqttConnector__hydrators__connector__fbMqtt(): FastyBird\Connector\FbMqtt\Hydrators\FbMqttConnector
	{
		return new FastyBird\Connector\FbMqtt\Hydrators\FbMqttConnector(
			$this->getService('nettrineOrm.managerRegistry'),
			$this->getService('contributteTranslation.translator'),
			$this->getService('fbJsonApi.helpers.crudReader'),
			$this->getService('nettrineCache.driver'),
		);
	}


	public function createServiceFbFbMqttConnector__hydrators__device__fbMqtt(): FastyBird\Connector\FbMqtt\Hydrators\FbMqttDevice
	{
		return new FastyBird\Connector\FbMqtt\Hydrators\FbMqttDevice(
			$this->getService('nettrineOrm.managerRegistry'),
			$this->getService('contributteTranslation.translator'),
			$this->getService('fbJsonApi.helpers.crudReader'),
			$this->getService('nettrineCache.driver'),
		);
	}


	public function createServiceFbFbMqttConnector__schemas__connector__fbMqtt(): FastyBird\Connector\FbMqtt\Schemas\FbMqttConnector
	{
		return new FastyBird\Connector\FbMqtt\Schemas\FbMqttConnector($this->getService('fbWebServerPlugin.routing.router'));
	}


	public function createServiceFbFbMqttConnector__schemas__device__fbMqtt(): FastyBird\Connector\FbMqtt\Schemas\FbMqttDevice
	{
		return new FastyBird\Connector\FbMqtt\Schemas\FbMqttDevice(
			$this->getService('fbDevicesModule.models.devicesRepository'),
			$this->getService('fbDevicesModule.models.channelsRepository'),
			$this->getService('fbWebServerPlugin.routing.router'),
		);
	}


	public function createServiceFbHomeKitConnector__clients__subscriber(): FastyBird\Connector\HomeKit\Clients\Subscriber
	{
		return new FastyBird\Connector\HomeKit\Clients\Subscriber(
			$this->getService('ipubWebsockets.server.loop'),
			$this->getService('contributteMonolog.logger.default'),
		);
	}


	public function createServiceFbHomeKitConnector__commands__execute(): FastyBird\Connector\HomeKit\Commands\Execute
	{
		return new FastyBird\Connector\HomeKit\Commands\Execute(
			$this->getService('fbDevicesModule.models.connectorsRepository'),
			$this->getService('contributteMonolog.logger.default'),
		);
	}


	public function createServiceFbHomeKitConnector__commands__initialize(): FastyBird\Connector\HomeKit\Commands\Initialize
	{
		return new FastyBird\Connector\HomeKit\Commands\Initialize(
			$this->getService('fbDevicesModule.models.connectorsRepository'),
			$this->getService('fbDevicesModule.models.connectorsManager'),
			$this->getService('fbDevicesModule.models.connectorsPropertiesManager'),
			$this->getService('fbDevicesModule.models.connectorsControlsManager'),
			$this->getService('nettrineOrm.managerRegistry'),
			$this->getService('contributteMonolog.logger.default'),
		);
	}


	public function createServiceFbHomeKitConnector__consumers__exchange(): FastyBird\Connector\HomeKit\Consumers\Consumer
	{
		return new FastyBird\Connector\HomeKit\Consumers\Consumer(
			$this->getService('fbHomeKitConnector.protocol.accessoryDriver'),
			$this->getService('fbHomeKitConnector.clients.subscriber'),
			$this->getService('fbDevicesModule.models.channelsRepository'),
			$this->getService('contributteMonolog.logger.default'),
		);
	}


	public function createServiceFbHomeKitConnector__entities__accessory__factory(): FastyBird\Connector\HomeKit\Entities\Protocol\AccessoryFactory
	{
		return new FastyBird\Connector\HomeKit\Entities\Protocol\AccessoryFactory(
			$this->getService('fbHomeKitConnector.entities.service.factory'),
			$this->getService('fbHomeKitConnector.entities.characteristic.factory'),
		);
	}


	public function createServiceFbHomeKitConnector__entities__characteristic__factory(): FastyBird\Connector\HomeKit\Entities\Protocol\CharacteristicsFactory
	{
		return new FastyBird\Connector\HomeKit\Entities\Protocol\CharacteristicsFactory($this->getService('fbHomeKitConnector.helpers.loader'));
	}


	public function createServiceFbHomeKitConnector__entities__service__factory(): FastyBird\Connector\HomeKit\Entities\Protocol\ServiceFactory
	{
		return new FastyBird\Connector\HomeKit\Entities\Protocol\ServiceFactory($this->getService('fbHomeKitConnector.helpers.loader'));
	}


	public function createServiceFbHomeKitConnector__executor__factory(): FastyBird\Connector\HomeKit\Connector\ConnectorFactory
	{
		return new class ($this) implements FastyBird\Connector\HomeKit\Connector\ConnectorFactory {
			private $container;


			public function __construct(Container_e69559cf0b $container)
			{
				$this->container = $container;
			}


			public function create(FastyBird\Module\Devices\Entities\Connectors\Connector $connector): FastyBird\Connector\HomeKit\Connector\Connector
			{
				return new FastyBird\Connector\HomeKit\Connector\Connector(
					$connector,
					[
						'fbHomeKitConnector.server.mdns' => $this->container->getService('fbHomeKitConnector.server.mdns'),
						'fbHomeKitConnector.server.http' => $this->container->getService('fbHomeKitConnector.server.http'),
					],
					$this->container->getService('fbExchangeLibrary.consumer'),
					$this->container->getService('contributteMonolog.logger.default'),
				);
			}
		};
	}


	public function createServiceFbHomeKitConnector__helpers__connector(): FastyBird\Connector\HomeKit\Helpers\Connector
	{
		return new FastyBird\Connector\HomeKit\Helpers\Connector(
			$this->getService('fbDevicesModule.utilities.database'),
			$this->getService('fbDevicesModule.models.connectorsRepository'),
			$this->getService('fbDevicesModule.models.connectorPropertiesRepository'),
			$this->getService('fbDevicesModule.models.connectorsPropertiesManager'),
		);
	}


	public function createServiceFbHomeKitConnector__helpers__loader(): FastyBird\Connector\HomeKit\Helpers\Loader
	{
		return new FastyBird\Connector\HomeKit\Helpers\Loader;
	}


	public function createServiceFbHomeKitConnector__http__controllers__accessories(): FastyBird\Connector\HomeKit\Controllers\AccessoriesController
	{
		$service = new FastyBird\Connector\HomeKit\Controllers\AccessoriesController(
			$this->getService('fbHomeKitConnector.helpers.connector'),
			$this->getService('fbHomeKitConnector.protocol.accessoryDriver'),
		);
		$service->injectLogger($this->getService('contributteMonolog.logger.default'));
		return $service;
	}


	public function createServiceFbHomeKitConnector__http__controllers__characteristics(): FastyBird\Connector\HomeKit\Controllers\CharacteristicsController
	{
		$service = new FastyBird\Connector\HomeKit\Controllers\CharacteristicsController(
			$this->getService('fbHomeKitConnector.protocol.accessoryDriver'),
			$this->getService('fbHomeKitConnector.clients.subscriber'),
			$this->getService('fbDateTimeFactory.datetime.factory'),
			$this->getService('fbExchangeLibrary.entityFactory'),
			$this->getService('fbExchangeLibrary.publisher'),
			$this->getService('contributteEvents.dispatcher'),
			$this->getService('fbDevicesModule.models.connectorPropertiesRepository'),
			$this->getService('fbDevicesModule.models.connectorsPropertiesManager'),
			$this->getService('fbDevicesModule.models.devicePropertiesRepository'),
			$this->getService('fbDevicesModule.models.devicesPropertiesManager'),
			$this->getService('fbDevicesModule.models.channelPropertiesRepository'),
			$this->getService('fbDevicesModule.models.channelsPropertiesManager'),
			$this->getService('fbDevicesModule.utilities.database'),
		);
		$service->injectLogger($this->getService('contributteMonolog.logger.default'));
		return $service;
	}


	public function createServiceFbHomeKitConnector__http__controllers__pairing(): FastyBird\Connector\HomeKit\Controllers\PairingController
	{
		$service = new FastyBird\Connector\HomeKit\Controllers\PairingController(
			$this->getService('fbHomeKitConnector.helpers.connector'),
			$this->getService('fbHomeKitConnector.protocol.tlv'),
			$this->getService('fbHomeKitConnector.models.clientsRepository'),
			$this->getService('fbHomeKitConnector.models.clientsManager'),
			$this->getService('fbDevicesModule.models.connectorsRepository'),
			$this->getService('fbDevicesModule.utilities.database'),
		);
		$service->injectLogger($this->getService('contributteMonolog.logger.default'));
		return $service;
	}


	public function createServiceFbHomeKitConnector__http__middlewares__router(): FastyBird\Connector\HomeKit\Middleware\Router
	{
		return new FastyBird\Connector\HomeKit\Middleware\Router(
			$this->getService('fbHomeKitConnector.http.router'),
			$this->getService('contributteEvents.dispatcher'),
			$this->getService('contributteMonolog.logger.default'),
		);
	}


	public function createServiceFbHomeKitConnector__http__router(): FastyBird\Connector\HomeKit\Router\Router
	{
		return new FastyBird\Connector\HomeKit\Router\Router(
			$this->getService('fbHomeKitConnector.http.controllers.pairing'),
			$this->getService('fbHomeKitConnector.http.controllers.accessories'),
			$this->getService('fbHomeKitConnector.http.controllers.characteristics'),
		);
	}


	public function createServiceFbHomeKitConnector__hydrators__connector__homekit(): FastyBird\Connector\HomeKit\Hydrators\HomeKitConnector
	{
		return new FastyBird\Connector\HomeKit\Hydrators\HomeKitConnector(
			$this->getService('nettrineOrm.managerRegistry'),
			$this->getService('contributteTranslation.translator'),
			$this->getService('fbJsonApi.helpers.crudReader'),
			$this->getService('nettrineCache.driver'),
		);
	}


	public function createServiceFbHomeKitConnector__hydrators__device__homekit(): FastyBird\Connector\HomeKit\Hydrators\HomeKitDevice
	{
		return new FastyBird\Connector\HomeKit\Hydrators\HomeKitDevice(
			$this->getService('nettrineOrm.managerRegistry'),
			$this->getService('contributteTranslation.translator'),
			$this->getService('fbJsonApi.helpers.crudReader'),
			$this->getService('nettrineCache.driver'),
		);
	}


	public function createServiceFbHomeKitConnector__models__clientsManager(): FastyBird\Connector\HomeKit\Models\Clients\ClientsManager
	{
		return new FastyBird\Connector\HomeKit\Models\Clients\ClientsManager($this->getService('ipubDoctrineCrud.crud')->create('FastyBird\Connector\HomeKit\Entities\Client'));
	}


	public function createServiceFbHomeKitConnector__models__clientsRepository(): FastyBird\Connector\HomeKit\Models\Clients\ClientsRepository
	{
		return new FastyBird\Connector\HomeKit\Models\Clients\ClientsRepository(
			$this->getService('fbDevicesModule.utilities.database'),
			$this->getService('nettrineOrm.managerRegistry'),
		);
	}


	public function createServiceFbHomeKitConnector__protocol__accessoryDriver(): FastyBird\Connector\HomeKit\Protocol\Driver
	{
		return new FastyBird\Connector\HomeKit\Protocol\Driver;
	}


	public function createServiceFbHomeKitConnector__protocol__tlv(): FastyBird\Connector\HomeKit\Protocol\Tlv
	{
		return new FastyBird\Connector\HomeKit\Protocol\Tlv;
	}


	public function createServiceFbHomeKitConnector__schemas__connector__homekit(): FastyBird\Connector\HomeKit\Schemas\HomeKitConnector
	{
		return new FastyBird\Connector\HomeKit\Schemas\HomeKitConnector($this->getService('fbWebServerPlugin.routing.router'));
	}


	public function createServiceFbHomeKitConnector__schemas__device__homekit(): FastyBird\Connector\HomeKit\Schemas\HomeKitDevice
	{
		return new FastyBird\Connector\HomeKit\Schemas\HomeKitDevice(
			$this->getService('fbDevicesModule.models.devicesRepository'),
			$this->getService('fbDevicesModule.models.channelsRepository'),
			$this->getService('fbWebServerPlugin.routing.router'),
		);
	}


	public function createServiceFbHomeKitConnector__server__http(): FastyBird\Connector\HomeKit\Servers\HttpFactory
	{
		return new class ($this) implements FastyBird\Connector\HomeKit\Servers\HttpFactory {
			private $container;


			public function __construct(Container_e69559cf0b $container)
			{
				$this->container = $container;
			}


			public function create(FastyBird\Connector\HomeKit\Entities\HomeKitConnector $connector): FastyBird\Connector\HomeKit\Servers\Http
			{
				return new FastyBird\Connector\HomeKit\Servers\Http(
					$connector,
					$this->container->getService('fbHomeKitConnector.helpers.connector'),
					$this->container->getService('fbHomeKitConnector.http.middlewares.router'),
					$this->container->getService('fbHomeKitConnector.server.http.secure.server'),
					$this->container->getService('fbHomeKitConnector.clients.subscriber'),
					$this->container->getService('fbHomeKitConnector.protocol.accessoryDriver'),
					$this->container->getService('fbHomeKitConnector.entities.accessory.factory'),
					$this->container->getService('fbHomeKitConnector.entities.service.factory'),
					$this->container->getService('fbHomeKitConnector.entities.characteristic.factory'),
					$this->container->getService('ipubWebsockets.server.loop'),
					$this->container->getService('contributteMonolog.logger.default'),
				);
			}
		};
	}


	public function createServiceFbHomeKitConnector__server__http__secure__connection(): FastyBird\Connector\HomeKit\Servers\SecureConnectionFactory
	{
		return new class ($this) implements FastyBird\Connector\HomeKit\Servers\SecureConnectionFactory {
			private $container;


			public function __construct(Container_e69559cf0b $container)
			{
				$this->container = $container;
			}


			public function create(
				FastyBird\Connector\HomeKit\Entities\HomeKitConnector $connector,
				?string $sharedKey,
				React\Socket\ConnectionInterface $connection,
			): FastyBird\Connector\HomeKit\Servers\SecureConnection {
				return new FastyBird\Connector\HomeKit\Servers\SecureConnection(
					$connector,
					$sharedKey,
					$connection,
					$this->container->getService('contributteMonolog.logger.default'),
				);
			}
		};
	}


	public function createServiceFbHomeKitConnector__server__http__secure__server(): FastyBird\Connector\HomeKit\Servers\SecureServerFactory
	{
		return new class ($this) implements FastyBird\Connector\HomeKit\Servers\SecureServerFactory {
			private $container;


			public function __construct(Container_e69559cf0b $container)
			{
				$this->container = $container;
			}


			public function create(
				FastyBird\Connector\HomeKit\Entities\HomeKitConnector $connector,
				React\Socket\ServerInterface $server,
				?string $sharedKey = null,
			): FastyBird\Connector\HomeKit\Servers\SecureServer {
				return new FastyBird\Connector\HomeKit\Servers\SecureServer(
					$connector,
					$server,
					$this->container->getService('fbHomeKitConnector.server.http.secure.connection'),
					$sharedKey,
				);
			}
		};
	}


	public function createServiceFbHomeKitConnector__server__mdns(): FastyBird\Connector\HomeKit\Servers\MdnsFactory
	{
		return new class ($this) implements FastyBird\Connector\HomeKit\Servers\MdnsFactory {
			private $container;


			public function __construct(Container_e69559cf0b $container)
			{
				$this->container = $container;
			}


			public function create(FastyBird\Connector\HomeKit\Entities\HomeKitConnector $connector): FastyBird\Connector\HomeKit\Servers\Mdns
			{
				return new FastyBird\Connector\HomeKit\Servers\Mdns(
					$connector,
					$this->container->getService('fbHomeKitConnector.helpers.connector'),
					$this->container->getService('ipubWebsockets.server.loop'),
					$this->container->getService('contributteMonolog.logger.default'),
				);
			}
		};
	}


	public function createServiceFbHomeKitConnector__subscribers__connector(): FastyBird\Connector\HomeKit\Subscribers\Connector
	{
		return new FastyBird\Connector\HomeKit\Subscribers\Connector(
			$this->getService('fbExchangeLibrary.consumer'),
			$this->getService('contributteMonolog.logger.default'),
		);
	}


	public function createServiceFbJsonApi__builder(): FastyBird\JsonApi\Builder\Builder
	{
		return new FastyBird\JsonApi\Builder\Builder($this, 'FastyBird team', 'FastyBird s.r.o');
	}


	public function createServiceFbJsonApi__helpers__crudReader(): FastyBird\JsonApi\Helpers\CrudReader
	{
		return new FastyBird\JsonApi\Helpers\CrudReader($this->getService('nettrineCache.driver'));
	}


	public function createServiceFbJsonApi__hydrators__container(): FastyBird\JsonApi\Hydrators\Container
	{
		$service = new FastyBird\JsonApi\Hydrators\Container($this, $this->getService('contributteMonolog.logger.default'));
		$service->add($this->getService('fbAccountsModule.hydrators.accounts.profile'));
		$service->add($this->getService('fbAccountsModule.hydrators.accounts'));
		$service->add($this->getService('fbAccountsModule.hydrators.emails.profile'));
		$service->add($this->getService('fbAccountsModule.hydrators.emails.email'));
		$service->add($this->getService('fbAccountsModule.hydrators.identities.profile'));
		$service->add($this->getService('fbAccountsModule.hydrators.role'));
		$service->add($this->getService('fbDevicesModule.hydrators.device.blank'));
		$service->add($this->getService('fbDevicesModule.hydrators.device.property.dynamic'));
		$service->add($this->getService('fbDevicesModule.hydrators.device.property.variable'));
		$service->add($this->getService('fbDevicesModule.hydrators.device.property.mapped'));
		$service->add($this->getService('fbDevicesModule.hydrators.channel'));
		$service->add($this->getService('fbDevicesModule.hydrators.channel.property.dynamic'));
		$service->add($this->getService('fbDevicesModule.hydrators.channel.property.variable'));
		$service->add($this->getService('fbDevicesModule.hydrators.channel.property.mapped'));
		$service->add($this->getService('fbDevicesModule.hydrators.connectors.blank'));
		$service->add($this->getService('fbDevicesModule.hydrators.connector.property.dynamic'));
		$service->add($this->getService('fbDevicesModule.hydrators.connector.property.variable'));
		$service->add($this->getService('fbTriggersModule.hydrators.triggers.automatic'));
		$service->add($this->getService('fbTriggersModule.hydrators.triggers.manual'));
		$service->add($this->getService('fbTriggersModule.hydrators.notifications.email'));
		$service->add($this->getService('fbTriggersModule.hydrators.notifications.sms'));
		$service->add($this->getService('fbFbMqttConnector.hydrators.connector.fbMqtt'));
		$service->add($this->getService('fbFbMqttConnector.hydrators.device.fbMqtt'));
		$service->add($this->getService('fbShellyConnector.hydrators.connector.shelly'));
		$service->add($this->getService('fbShellyConnector.hydrators.device.shelly'));
		$service->add($this->getService('fbModbusConnector.hydrators.connector.modbus'));
		$service->add($this->getService('fbModbusConnector.hydrators.device.modbus'));
		$service->add($this->getService('fbHomeKitConnector.hydrators.connector.homekit'));
		$service->add($this->getService('fbHomeKitConnector.hydrators.device.homekit'));
		$service->add($this->getService('fbTuyaConnector.hydrators.connector.tuya'));
		$service->add($this->getService('fbTuyaConnector.hydrators.device.tuya'));
		return $service;
	}


	public function createServiceFbJsonApi__middlewares__jsonapi(): FastyBird\JsonApi\Middleware\JsonApi
	{
		return new FastyBird\JsonApi\Middleware\JsonApi(
			$this->getService('fbWebServerPlugin.routing.responseFactory'),
			$this,
			$this->getService('contributteMonolog.logger.default'),
		);
	}


	public function createServiceFbJsonApi__schemas__container(): FastyBird\JsonApi\JsonApi\SchemaContainer
	{
		$service = new FastyBird\JsonApi\JsonApi\SchemaContainer;
		$service->add($this->getService('fbAccountsModule.schemas.account'));
		$service->add($this->getService('fbAccountsModule.schemas.email'));
		$service->add($this->getService('fbAccountsModule.schemas.identity'));
		$service->add($this->getService('fbAccountsModule.schemas.role'));
		$service->add($this->getService('fbAccountsModule.schemas.session'));
		$service->add($this->getService('fbDevicesModule.schemas.device.blank'));
		$service->add($this->getService('fbDevicesModule.schemas.device.property.dynamic'));
		$service->add($this->getService('fbDevicesModule.schemas.device.property.variable'));
		$service->add($this->getService('fbDevicesModule.schemas.device.property.mapped'));
		$service->add($this->getService('fbDevicesModule.schemas.device.control'));
		$service->add($this->getService('fbDevicesModule.schemas.device.attribute'));
		$service->add($this->getService('fbDevicesModule.schemas.channel'));
		$service->add($this->getService('fbDevicesModule.schemas.channel.property.dynamic'));
		$service->add($this->getService('fbDevicesModule.schemas.channel.property.variable'));
		$service->add($this->getService('fbDevicesModule.schemas.channel.property.mapped'));
		$service->add($this->getService('fbDevicesModule.schemas.control'));
		$service->add($this->getService('fbDevicesModule.schemas.connector.blank'));
		$service->add($this->getService('fbDevicesModule.schemas.connector.property.dynamic'));
		$service->add($this->getService('fbDevicesModule.schemas.connector.property.variable'));
		$service->add($this->getService('fbDevicesModule.schemas.connector.controls'));
		$service->add($this->getService('fbTriggersModule.schemas.triggers.automatic'));
		$service->add($this->getService('fbTriggersModule.schemas.triggers.manual'));
		$service->add($this->getService('fbTriggersModule.schemas.trigger.control'));
		$service->add($this->getService('fbTriggersModule.schemas.notifications.email'));
		$service->add($this->getService('fbTriggersModule.schemas.notifications.sms'));
		$service->add($this->getService('fbFbMqttConnector.schemas.connector.fbMqtt'));
		$service->add($this->getService('fbFbMqttConnector.schemas.device.fbMqtt'));
		$service->add($this->getService('fbShellyConnector.schemas.connector.shelly'));
		$service->add($this->getService('fbShellyConnector.schemas.device.shelly'));
		$service->add($this->getService('fbModbusConnector.schemas.connector.modbus'));
		$service->add($this->getService('fbModbusConnector.schemas.device.modbus'));
		$service->add($this->getService('fbHomeKitConnector.schemas.connector.homekit'));
		$service->add($this->getService('fbHomeKitConnector.schemas.device.homekit'));
		$service->add($this->getService('fbTuyaConnector.schemas.connector.tuya'));
		$service->add($this->getService('fbTuyaConnector.schemas.device.tuya'));
		return $service;
	}


	public function createServiceFbModbusConnector__api__transformer(): FastyBird\Connector\Modbus\API\Transformer
	{
		return new FastyBird\Connector\Modbus\API\Transformer;
	}


	public function createServiceFbModbusConnector__client__rtu(): FastyBird\Connector\Modbus\Clients\RtuFactory
	{
		return new class ($this) implements FastyBird\Connector\Modbus\Clients\RtuFactory {
			private $container;


			public function __construct(Container_e69559cf0b $container)
			{
				$this->container = $container;
			}


			public function create(FastyBird\Connector\Modbus\Entities\ModbusConnector $connector): FastyBird\Connector\Modbus\Clients\Rtu
			{
				return new FastyBird\Connector\Modbus\Clients\Rtu(
					$connector,
					$this->container->getService('fbModbusConnector.helpers.connector'),
					$this->container->getService('fbModbusConnector.helpers.device'),
					$this->container->getService('fbModbusConnector.helpers.channel'),
					$this->container->getService('fbModbusConnector.helpers.property'),
					$this->container->getService('fbModbusConnector.api.transformer'),
					$this->container->getService('fbDevicesModule.utilities.devices.connection'),
					$this->container->getService('fbDevicesModule.utilities.devices.states'),
					$this->container->getService('fbDevicesModule.utilities.channels.states'),
					$this->container->getService('fbDateTimeFactory.datetime.factory'),
					$this->container->getService('ipubWebsockets.server.loop'),
					$this->container->getService('contributteMonolog.logger.default'),
				);
			}
		};
	}


	public function createServiceFbModbusConnector__commands__execute(): FastyBird\Connector\Modbus\Commands\Execute
	{
		return new FastyBird\Connector\Modbus\Commands\Execute(
			$this->getService('fbDevicesModule.models.connectorsRepository'),
			$this->getService('contributteMonolog.logger.default'),
		);
	}


	public function createServiceFbModbusConnector__commands__initialize(): FastyBird\Connector\Modbus\Commands\Initialize
	{
		return new FastyBird\Connector\Modbus\Commands\Initialize(
			$this->getService('fbDevicesModule.models.connectorsRepository'),
			$this->getService('fbDevicesModule.models.connectorsManager'),
			$this->getService('fbDevicesModule.models.connectorsPropertiesManager'),
			$this->getService('fbDevicesModule.models.connectorsControlsManager'),
			$this->getService('nettrineOrm.managerRegistry'),
			$this->getService('contributteMonolog.logger.default'),
		);
	}


	public function createServiceFbModbusConnector__executor__factory(): FastyBird\Connector\Modbus\Connector\ConnectorFactory
	{
		return new class ($this) implements FastyBird\Connector\Modbus\Connector\ConnectorFactory {
			private $container;


			public function __construct(Container_e69559cf0b $container)
			{
				$this->container = $container;
			}


			public function create(FastyBird\Module\Devices\Entities\Connectors\Connector $connector): FastyBird\Connector\Modbus\Connector\Connector
			{
				return new FastyBird\Connector\Modbus\Connector\Connector(
					$connector,
					['fbModbusConnector.client.rtu' => $this->container->getService('fbModbusConnector.client.rtu')],
					$this->container->getService('fbModbusConnector.helpers.connector'),
				);
			}
		};
	}


	public function createServiceFbModbusConnector__helpers__channel(): FastyBird\Connector\Modbus\Helpers\Channel
	{
		return new FastyBird\Connector\Modbus\Helpers\Channel;
	}


	public function createServiceFbModbusConnector__helpers__connector(): FastyBird\Connector\Modbus\Helpers\Connector
	{
		return new FastyBird\Connector\Modbus\Helpers\Connector;
	}


	public function createServiceFbModbusConnector__helpers__device(): FastyBird\Connector\Modbus\Helpers\Device
	{
		return new FastyBird\Connector\Modbus\Helpers\Device;
	}


	public function createServiceFbModbusConnector__helpers__property(): FastyBird\Connector\Modbus\Helpers\Property
	{
		return new FastyBird\Connector\Modbus\Helpers\Property(
			$this->getService('fbDevicesModule.utilities.devices.states'),
			$this->getService('fbDevicesModule.utilities.channels.states'),
		);
	}


	public function createServiceFbModbusConnector__hydrators__connector__modbus(): FastyBird\Connector\Modbus\Hydrators\ModbusConnector
	{
		return new FastyBird\Connector\Modbus\Hydrators\ModbusConnector(
			$this->getService('nettrineOrm.managerRegistry'),
			$this->getService('contributteTranslation.translator'),
			$this->getService('fbJsonApi.helpers.crudReader'),
			$this->getService('nettrineCache.driver'),
		);
	}


	public function createServiceFbModbusConnector__hydrators__device__modbus(): FastyBird\Connector\Modbus\Hydrators\ModbusDevice
	{
		return new FastyBird\Connector\Modbus\Hydrators\ModbusDevice(
			$this->getService('nettrineOrm.managerRegistry'),
			$this->getService('contributteTranslation.translator'),
			$this->getService('fbJsonApi.helpers.crudReader'),
			$this->getService('nettrineCache.driver'),
		);
	}


	public function createServiceFbModbusConnector__schemas__connector__modbus(): FastyBird\Connector\Modbus\Schemas\ModbusConnector
	{
		return new FastyBird\Connector\Modbus\Schemas\ModbusConnector($this->getService('fbWebServerPlugin.routing.router'));
	}


	public function createServiceFbModbusConnector__schemas__device__modbus(): FastyBird\Connector\Modbus\Schemas\ModbusDevice
	{
		return new FastyBird\Connector\Modbus\Schemas\ModbusDevice(
			$this->getService('fbDevicesModule.models.devicesRepository'),
			$this->getService('fbDevicesModule.models.channelsRepository'),
			$this->getService('fbWebServerPlugin.routing.router'),
		);
	}


	public function createServiceFbModbusConnector__subscribers__properties(): FastyBird\Connector\Modbus\Subscribers\Properties
	{
		return new FastyBird\Connector\Modbus\Subscribers\Properties($this->getService('fbDevicesModule.models.devicesPropertiesManager'));
	}


	public function createServiceFbRedisDbPlugin__clients__async__factory(): FastyBird\Plugin\RedisDb\Client\Factory
	{
		return new FastyBird\Plugin\RedisDb\Client\Factory(
			'fb_exchange',
			$this->getService('fbRedisDbPlugin.redis.connection'),
			$this->getService('fbRedisDbPlugin.handlers.message'),
			$this->getService('contributteEvents.dispatcher'),
		);
	}


	public function createServiceFbRedisDbPlugin__clients__sync(): FastyBird\Plugin\RedisDb\Client\Client
	{
		return new FastyBird\Plugin\RedisDb\Client\Client($this->getService('fbRedisDbPlugin.redis.connection'));
	}


	public function createServiceFbRedisDbPlugin__commands__client(): FastyBird\Plugin\RedisDb\Commands\RedisClient
	{
		return new FastyBird\Plugin\RedisDb\Commands\RedisClient(
			$this->getService('fbRedisDbPlugin.clients.async.factory'),
			$this->getService('ipubWebsockets.server.loop'),
			$this->getService('contributteEvents.dispatcher'),
			$this->getService('contributteMonolog.logger.default'),
		);
	}


	public function createServiceFbRedisDbPlugin__handlers__message(): FastyBird\Plugin\RedisDb\Handlers\Message
	{
		return new FastyBird\Plugin\RedisDb\Handlers\Message(
			$this->getService('fbRedisDbPlugin.utilities.identifier'),
			$this->getService('fbExchangeLibrary.entityFactory'),
			$this->getService('fbExchangeLibrary.consumer'),
			$this->getService('contributteEvents.dispatcher'),
			$this->getService('contributteMonolog.logger.default'),
		);
	}


	public function createServiceFbRedisDbPlugin__models__statesManagerFactory(): FastyBird\Plugin\RedisDb\Models\StatesManagerFactory
	{
		return new FastyBird\Plugin\RedisDb\Models\StatesManagerFactory(
			$this->getService('fbRedisDbPlugin.clients.sync'),
			$this->getService('fbDateTimeFactory.datetime.factory'),
			$this->getService('contributteEvents.dispatcher'),
			$this->getService('contributteMonolog.logger.default'),
		);
	}


	public function createServiceFbRedisDbPlugin__models__statesRepositoryFactory(): FastyBird\Plugin\RedisDb\Models\StatesRepositoryFactory
	{
		return new FastyBird\Plugin\RedisDb\Models\StatesRepositoryFactory(
			$this->getService('fbRedisDbPlugin.clients.sync'),
			$this->getService('contributteMonolog.logger.default'),
		);
	}


	public function createServiceFbRedisDbPlugin__publisher(): FastyBird\Plugin\RedisDb\Publishers\Publisher
	{
		return new FastyBird\Plugin\RedisDb\Publishers\Publisher(
			$this->getService('fbRedisDbPlugin.utilities.identifier'),
			'fb_exchange',
			$this->getService('fbRedisDbPlugin.clients.sync'),
			$this->getService('fbDateTimeFactory.datetime.factory'),
			$this->getService('contributteMonolog.logger.default'),
		);
	}


	public function createServiceFbRedisDbPlugin__redis__connection(): FastyBird\Plugin\RedisDb\Connections\Connection
	{
		return new FastyBird\Plugin\RedisDb\Connections\Connection('redis', 6379, null, null);
	}


	public function createServiceFbRedisDbPlugin__subscribers__client(): FastyBird\Plugin\RedisDb\Subscribers\Client
	{
		return new FastyBird\Plugin\RedisDb\Subscribers\Client(
			$this->getService('fbRedisDbPlugin.publisher'),
			$this->getService('fbRedisDbPlugin.models.statesRepositoryFactory'),
			$this->getService('fbRedisDbPlugin.models.statesManagerFactory'),
			$this->getService('contributteMonolog.logger.default'),
		);
	}


	public function createServiceFbRedisDbPlugin__utilities__identifier(): FastyBird\Plugin\RedisDb\Utilities\IdentifierGenerator
	{
		return new FastyBird\Plugin\RedisDb\Utilities\IdentifierGenerator;
	}


	public function createServiceFbShellyConnector__api__gen1parser(): FastyBird\Connector\Shelly\API\Gen1Parser
	{
		return new FastyBird\Connector\Shelly\API\Gen1Parser(
			$this->getService('fbShellyConnector.api.gen1validator'),
			$this->getService('fbShellyConnector.api.gen1transformer'),
			$this->getService('fbShellyConnector.mappers.sensor'),
			$this->getService('schema.validator'),
		);
	}


	public function createServiceFbShellyConnector__api__gen1transformer(): FastyBird\Connector\Shelly\API\Gen1Transformer
	{
		return new FastyBird\Connector\Shelly\API\Gen1Transformer;
	}


	public function createServiceFbShellyConnector__api__gen1validator(): FastyBird\Connector\Shelly\API\Gen1Validator
	{
		return new FastyBird\Connector\Shelly\API\Gen1Validator($this->getService('schema.validator'));
	}


	public function createServiceFbShellyConnector__clients__gen1(): FastyBird\Connector\Shelly\Clients\Gen1Factory
	{
		return new class ($this) implements FastyBird\Connector\Shelly\Clients\Gen1Factory {
			private $container;


			public function __construct(Container_e69559cf0b $container)
			{
				$this->container = $container;
			}


			public function create(FastyBird\Connector\Shelly\Entities\ShellyConnector $connector): FastyBird\Connector\Shelly\Clients\Gen1
			{
				return new FastyBird\Connector\Shelly\Clients\Gen1(
					$connector,
					$this->container->getService('fbShellyConnector.clients.gen1.coap'),
					$this->container->getService('fbShellyConnector.clients.gen1.mdns'),
					$this->container->getService('fbShellyConnector.clients.gen1.http'),
					$this->container->getService('fbShellyConnector.clients.gen1.mqtt'),
					$this->container->getService('fbShellyConnector.helpers.connector'),
					$this->container->getService('contributteMonolog.logger.default'),
				);
			}
		};
	}


	public function createServiceFbShellyConnector__clients__gen1__coap(): FastyBird\Connector\Shelly\Clients\Gen1\CoapFactory
	{
		return new class ($this) implements FastyBird\Connector\Shelly\Clients\Gen1\CoapFactory {
			private $container;


			public function __construct(Container_e69559cf0b $container)
			{
				$this->container = $container;
			}


			public function create(FastyBird\Connector\Shelly\Entities\ShellyConnector $connector): FastyBird\Connector\Shelly\Clients\Gen1\Coap
			{
				return new FastyBird\Connector\Shelly\Clients\Gen1\Coap(
					$connector,
					$this->container->getService('fbShellyConnector.api.gen1validator'),
					$this->container->getService('fbShellyConnector.api.gen1parser'),
					$this->container->getService('fbShellyConnector.consumers.messages'),
					$this->container->getService('ipubWebsockets.server.loop'),
					$this->container->getService('contributteMonolog.logger.default'),
				);
			}
		};
	}


	public function createServiceFbShellyConnector__clients__gen1__http(): FastyBird\Connector\Shelly\Clients\Gen1\HttpFactory
	{
		return new class ($this) implements FastyBird\Connector\Shelly\Clients\Gen1\HttpFactory {
			private $container;


			public function __construct(Container_e69559cf0b $container)
			{
				$this->container = $container;
			}


			public function create(FastyBird\Connector\Shelly\Entities\ShellyConnector $connector): FastyBird\Connector\Shelly\Clients\Gen1\Http
			{
				return new FastyBird\Connector\Shelly\Clients\Gen1\Http(
					$connector,
					$this->container->getService('fbShellyConnector.api.gen1validator'),
					$this->container->getService('fbShellyConnector.api.gen1parser'),
					$this->container->getService('fbShellyConnector.api.gen1transformer'),
					$this->container->getService('fbShellyConnector.helpers.device'),
					$this->container->getService('fbShellyConnector.helpers.property'),
					$this->container->getService('fbShellyConnector.consumers.messages'),
					$this->container->getService('fbDevicesModule.utilities.devices.connection'),
					$this->container->getService('fbDevicesModule.utilities.channels.states'),
					$this->container->getService('fbDateTimeFactory.datetime.factory'),
					$this->container->getService('ipubWebsockets.server.loop'),
					$this->container->getService('contributteMonolog.logger.default'),
				);
			}
		};
	}


	public function createServiceFbShellyConnector__clients__gen1__mdns(): FastyBird\Connector\Shelly\Clients\Gen1\MdnsFactory
	{
		return new class ($this) implements FastyBird\Connector\Shelly\Clients\Gen1\MdnsFactory {
			private $container;


			public function __construct(Container_e69559cf0b $container)
			{
				$this->container = $container;
			}


			public function create(FastyBird\Connector\Shelly\Entities\ShellyConnector $connector): FastyBird\Connector\Shelly\Clients\Gen1\Mdns
			{
				return new FastyBird\Connector\Shelly\Clients\Gen1\Mdns(
					$connector,
					$this->container->getService('fbShellyConnector.consumers.messages'),
					$this->container->getService('ipubWebsockets.server.loop'),
					$this->container->getService('contributteMonolog.logger.default'),
				);
			}
		};
	}


	public function createServiceFbShellyConnector__clients__gen1__mqtt(): FastyBird\Connector\Shelly\Clients\Gen1\MqttFactory
	{
		return new class ($this) implements FastyBird\Connector\Shelly\Clients\Gen1\MqttFactory {
			private $container;


			public function __construct(Container_e69559cf0b $container)
			{
				$this->container = $container;
			}


			public function create(FastyBird\Connector\Shelly\Entities\ShellyConnector $connector): FastyBird\Connector\Shelly\Clients\Gen1\Mqtt
			{
				return new FastyBird\Connector\Shelly\Clients\Gen1\Mqtt($connector);
			}
		};
	}


	public function createServiceFbShellyConnector__commands__discovery(): FastyBird\Connector\Shelly\Commands\Discovery
	{
		return new FastyBird\Connector\Shelly\Commands\Discovery(
			['fbShellyConnector.clients.gen1' => $this->getService('fbShellyConnector.clients.gen1')],
			$this->getService('fbShellyConnector.helpers.connector'),
			$this->getService('fbShellyConnector.helpers.device'),
			$this->getService('fbShellyConnector.consumers.messages'),
			$this->getService('fbDevicesModule.models.connectorsRepository'),
			$this->getService('fbDateTimeFactory.datetime.factory'),
			$this->getService('ipubWebsockets.server.loop'),
			$this->getService('contributteMonolog.logger.default'),
		);
	}


	public function createServiceFbShellyConnector__commands__execute(): FastyBird\Connector\Shelly\Commands\Execute
	{
		return new FastyBird\Connector\Shelly\Commands\Execute(
			$this->getService('fbDevicesModule.models.connectorsRepository'),
			$this->getService('contributteMonolog.logger.default'),
		);
	}


	public function createServiceFbShellyConnector__commands__initialize(): FastyBird\Connector\Shelly\Commands\Initialize
	{
		return new FastyBird\Connector\Shelly\Commands\Initialize(
			$this->getService('fbDevicesModule.models.connectorsRepository'),
			$this->getService('fbDevicesModule.models.connectorsManager'),
			$this->getService('fbDevicesModule.models.connectorsPropertiesManager'),
			$this->getService('fbDevicesModule.models.connectorsControlsManager'),
			$this->getService('nettrineOrm.managerRegistry'),
			$this->getService('contributteMonolog.logger.default'),
		);
	}


	public function createServiceFbShellyConnector__consumers__messages(): FastyBird\Connector\Shelly\Consumers\Messages
	{
		return new FastyBird\Connector\Shelly\Consumers\Messages(
			[
				'fbShellyConnector.consumers.messages.device.description' => $this->getService('fbShellyConnector.consumers.messages.device.description'),
				'fbShellyConnector.consumers.messages.device.status' => $this->getService('fbShellyConnector.consumers.messages.device.status'),
				'fbShellyConnector.consumers.messages.device.info' => $this->getService('fbShellyConnector.consumers.messages.device.info'),
				'fbShellyConnector.consumers.messages.device.discovery' => $this->getService('fbShellyConnector.consumers.messages.device.discovery'),
			],
			$this->getService('contributteMonolog.logger.default'),
		);
	}


	public function createServiceFbShellyConnector__consumers__messages__device__description(): FastyBird\Connector\Shelly\Consumers\Messages\Description
	{
		return new FastyBird\Connector\Shelly\Consumers\Messages\Description(
			$this->getService('fbDevicesModule.models.devicesRepository'),
			$this->getService('fbDevicesModule.models.devicesManager'),
			$this->getService('fbDevicesModule.models.devicePropertiesRepository'),
			$this->getService('fbDevicesModule.models.devicesPropertiesManager'),
			$this->getService('fbDevicesModule.models.deviceAttributesRepository'),
			$this->getService('fbDevicesModule.models.devicesAttributesManager'),
			$this->getService('fbDevicesModule.models.channelsManager'),
			$this->getService('fbDevicesModule.models.channelsPropertiesManager'),
			$this->getService('fbShellyConnector.mappers.sensor'),
			$this->getService('fbDevicesModule.utilities.database'),
			$this->getService('contributteMonolog.logger.default'),
		);
	}


	public function createServiceFbShellyConnector__consumers__messages__device__discovery(): FastyBird\Connector\Shelly\Consumers\Messages\Discovery
	{
		return new FastyBird\Connector\Shelly\Consumers\Messages\Discovery(
			$this->getService('fbDevicesModule.models.connectorsRepository'),
			$this->getService('fbDevicesModule.models.devicesRepository'),
			$this->getService('fbDevicesModule.models.devicesManager'),
			$this->getService('fbDevicesModule.models.devicePropertiesRepository'),
			$this->getService('fbDevicesModule.models.devicesPropertiesManager'),
			$this->getService('fbDevicesModule.models.deviceAttributesRepository'),
			$this->getService('fbDevicesModule.models.devicesAttributesManager'),
			$this->getService('fbDevicesModule.utilities.database'),
			$this->getService('contributteMonolog.logger.default'),
		);
	}


	public function createServiceFbShellyConnector__consumers__messages__device__info(): FastyBird\Connector\Shelly\Consumers\Messages\Info
	{
		return new FastyBird\Connector\Shelly\Consumers\Messages\Info(
			$this->getService('fbDevicesModule.models.devicesRepository'),
			$this->getService('fbDevicesModule.models.devicesManager'),
			$this->getService('fbDevicesModule.models.devicePropertiesRepository'),
			$this->getService('fbDevicesModule.models.devicesPropertiesManager'),
			$this->getService('fbDevicesModule.models.deviceAttributesRepository'),
			$this->getService('fbDevicesModule.models.devicesAttributesManager'),
			$this->getService('fbDevicesModule.utilities.database'),
			$this->getService('contributteMonolog.logger.default'),
		);
	}


	public function createServiceFbShellyConnector__consumers__messages__device__status(): FastyBird\Connector\Shelly\Consumers\Messages\Status
	{
		return new FastyBird\Connector\Shelly\Consumers\Messages\Status(
			$this->getService('fbDevicesModule.models.devicesRepository'),
			$this->getService('fbDevicesModule.utilities.devices.connection'),
			$this->getService('fbShellyConnector.mappers.sensor'),
			$this->getService('fbShellyConnector.helpers.property'),
			$this->getService('contributteMonolog.logger.default'),
		);
	}


	public function createServiceFbShellyConnector__executor__factory(): FastyBird\Connector\Shelly\Connector\ConnectorFactory
	{
		return new class ($this) implements FastyBird\Connector\Shelly\Connector\ConnectorFactory {
			private $container;


			public function __construct(Container_e69559cf0b $container)
			{
				$this->container = $container;
			}


			public function create(FastyBird\Module\Devices\Entities\Connectors\Connector $connector): FastyBird\Connector\Shelly\Connector\Connector
			{
				return new FastyBird\Connector\Shelly\Connector\Connector(
					$connector,
					['fbShellyConnector.clients.gen1' => $this->container->getService('fbShellyConnector.clients.gen1')],
					$this->container->getService('fbShellyConnector.helpers.connector'),
					$this->container->getService('fbShellyConnector.consumers.messages'),
					$this->container->getService('ipubWebsockets.server.loop'),
				);
			}
		};
	}


	public function createServiceFbShellyConnector__helpers__connector(): FastyBird\Connector\Shelly\Helpers\Connector
	{
		return new FastyBird\Connector\Shelly\Helpers\Connector;
	}


	public function createServiceFbShellyConnector__helpers__device(): FastyBird\Connector\Shelly\Helpers\Device
	{
		return new FastyBird\Connector\Shelly\Helpers\Device;
	}


	public function createServiceFbShellyConnector__helpers__property(): FastyBird\Connector\Shelly\Helpers\Property
	{
		return new FastyBird\Connector\Shelly\Helpers\Property(
			$this->getService('fbDevicesModule.utilities.devices.states'),
			$this->getService('fbDevicesModule.utilities.channels.states'),
		);
	}


	public function createServiceFbShellyConnector__hydrators__connector__shelly(): FastyBird\Connector\Shelly\Hydrators\ShellyConnector
	{
		return new FastyBird\Connector\Shelly\Hydrators\ShellyConnector(
			$this->getService('nettrineOrm.managerRegistry'),
			$this->getService('contributteTranslation.translator'),
			$this->getService('fbJsonApi.helpers.crudReader'),
			$this->getService('nettrineCache.driver'),
		);
	}


	public function createServiceFbShellyConnector__hydrators__device__shelly(): FastyBird\Connector\Shelly\Hydrators\ShellyDevice
	{
		return new FastyBird\Connector\Shelly\Hydrators\ShellyDevice(
			$this->getService('nettrineOrm.managerRegistry'),
			$this->getService('contributteTranslation.translator'),
			$this->getService('fbJsonApi.helpers.crudReader'),
			$this->getService('nettrineCache.driver'),
		);
	}


	public function createServiceFbShellyConnector__mappers__sensor(): FastyBird\Connector\Shelly\Mappers\Sensor
	{
		return new FastyBird\Connector\Shelly\Mappers\Sensor(
			$this->getService('fbDevicesModule.models.devicesRepository'),
			$this->getService('fbDevicesModule.models.channelPropertiesRepository'),
		);
	}


	public function createServiceFbShellyConnector__schemas__connector__shelly(): FastyBird\Connector\Shelly\Schemas\ShellyConnector
	{
		return new FastyBird\Connector\Shelly\Schemas\ShellyConnector($this->getService('fbWebServerPlugin.routing.router'));
	}


	public function createServiceFbShellyConnector__schemas__device__shelly(): FastyBird\Connector\Shelly\Schemas\ShellyDevice
	{
		return new FastyBird\Connector\Shelly\Schemas\ShellyDevice(
			$this->getService('fbDevicesModule.models.devicesRepository'),
			$this->getService('fbDevicesModule.models.channelsRepository'),
			$this->getService('fbWebServerPlugin.routing.router'),
		);
	}


	public function createServiceFbShellyConnector__subscribers__properties(): FastyBird\Connector\Shelly\Subscribers\Properties
	{
		return new FastyBird\Connector\Shelly\Subscribers\Properties($this->getService('fbDevicesModule.models.devicesPropertiesManager'));
	}


	public function createServiceFbSimpleAuth__auth(): FastyBird\SimpleAuth\Auth
	{
		return new FastyBird\SimpleAuth\Auth(
			$this->getService('fbSimpleAuth.token.reader'),
			$this->getService('fbAccountsModule.security.identityFactory'),
			$this->getService('security.user'),
		);
	}


	public function createServiceFbSimpleAuth__doctrine__driver(): FastyBird\SimpleAuth\Mapping\Driver\Owner
	{
		return new FastyBird\SimpleAuth\Mapping\Driver\Owner($this->getService('nettrineCache.driver'));
	}


	public function createServiceFbSimpleAuth__doctrine__subscriber(): FastyBird\SimpleAuth\Subscribers\User
	{
		return new FastyBird\SimpleAuth\Subscribers\User(
			$this->getService('fbSimpleAuth.doctrine.driver'),
			$this->getService('security.user'),
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


	public function createServiceFbSimpleAuth__middleware__access(): FastyBird\SimpleAuth\Middleware\Access
	{
		return new FastyBird\SimpleAuth\Middleware\Access(
			$this->getService('security.user'),
			$this->getService('fbSimpleAuth.security.annotationChecker'),
		);
	}


	public function createServiceFbSimpleAuth__middleware__user(): FastyBird\SimpleAuth\Middleware\User
	{
		return new FastyBird\SimpleAuth\Middleware\User($this->getService('fbSimpleAuth.auth'));
	}


	public function createServiceFbSimpleAuth__security__annotationChecker(): FastyBird\SimpleAuth\Security\AnnotationChecker
	{
		return new FastyBird\SimpleAuth\Security\AnnotationChecker;
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
			$this->getService('fbDateTimeFactory.datetime.factory'),
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
			$this->getService('fbDateTimeFactory.datetime.factory'),
		);
	}


	public function createServiceFbTriggersModule__commands__initialize(): FastyBird\Module\Triggers\Commands\Initialize
	{
		return new FastyBird\Module\Triggers\Commands\Initialize($this->getService('contributteMonolog.logger.default'));
	}


	public function createServiceFbTriggersModule__controllers__actions(): FastyBird\Module\Triggers\Controllers\ActionsV1
	{
		$service = new FastyBird\Module\Triggers\Controllers\ActionsV1(
			$this->getService('fbTriggersModule.models.triggersRepository'),
			$this->getService('fbTriggersModule.models.actionsRepository'),
			$this->getService('fbTriggersModule.models.actionsManager'),
		);
		$service->injectTranslator($this->getService('contributteTranslation.translator'));
		$service->injectManagerRegistry($this->getService('nettrineOrm.managerRegistry'));
		$service->injectLogger($this->getService('contributteMonolog.logger.default'));
		$service->injectJsonApiBuilder($this->getService('fbJsonApi.builder'));
		$service->injectRoutesValidator($this->getService('fbTriggersModule.router.validator'));
		$service->injectHydratorsContainer($this->getService('fbJsonApi.hydrators.container'));
		return $service;
	}


	public function createServiceFbTriggersModule__controllers__conditions(): FastyBird\Module\Triggers\Controllers\ConditionsV1
	{
		$service = new FastyBird\Module\Triggers\Controllers\ConditionsV1(
			$this->getService('fbTriggersModule.models.triggersRepository'),
			$this->getService('fbTriggersModule.models.conditionsRepository'),
			$this->getService('fbTriggersModule.models.conditionsManager'),
		);
		$service->injectTranslator($this->getService('contributteTranslation.translator'));
		$service->injectManagerRegistry($this->getService('nettrineOrm.managerRegistry'));
		$service->injectLogger($this->getService('contributteMonolog.logger.default'));
		$service->injectJsonApiBuilder($this->getService('fbJsonApi.builder'));
		$service->injectRoutesValidator($this->getService('fbTriggersModule.router.validator'));
		$service->injectHydratorsContainer($this->getService('fbJsonApi.hydrators.container'));
		return $service;
	}


	public function createServiceFbTriggersModule__controllers__notifications(): FastyBird\Module\Triggers\Controllers\NotificationsV1
	{
		$service = new FastyBird\Module\Triggers\Controllers\NotificationsV1(
			$this->getService('fbTriggersModule.models.triggersRepository'),
			$this->getService('fbTriggersModule.models.notificationsRepository'),
			$this->getService('fbTriggersModule.models.notificationsManager'),
		);
		$service->injectTranslator($this->getService('contributteTranslation.translator'));
		$service->injectManagerRegistry($this->getService('nettrineOrm.managerRegistry'));
		$service->injectLogger($this->getService('contributteMonolog.logger.default'));
		$service->injectJsonApiBuilder($this->getService('fbJsonApi.builder'));
		$service->injectRoutesValidator($this->getService('fbTriggersModule.router.validator'));
		$service->injectHydratorsContainer($this->getService('fbJsonApi.hydrators.container'));
		return $service;
	}


	public function createServiceFbTriggersModule__controllers__triggers(): FastyBird\Module\Triggers\Controllers\TriggersV1
	{
		$service = new FastyBird\Module\Triggers\Controllers\TriggersV1(
			$this->getService('fbTriggersModule.models.triggersRepository'),
			$this->getService('fbTriggersModule.models.triggersManager'),
		);
		$service->injectTranslator($this->getService('contributteTranslation.translator'));
		$service->injectManagerRegistry($this->getService('nettrineOrm.managerRegistry'));
		$service->injectLogger($this->getService('contributteMonolog.logger.default'));
		$service->injectJsonApiBuilder($this->getService('fbJsonApi.builder'));
		$service->injectRoutesValidator($this->getService('fbTriggersModule.router.validator'));
		$service->injectHydratorsContainer($this->getService('fbJsonApi.hydrators.container'));
		return $service;
	}


	public function createServiceFbTriggersModule__controllers__triggersControls(): FastyBird\Module\Triggers\Controllers\TriggerControlsV1
	{
		$service = new FastyBird\Module\Triggers\Controllers\TriggerControlsV1(
			$this->getService('fbTriggersModule.models.triggersRepository'),
			$this->getService('fbTriggersModule.models.triggeControlsRepository'),
		);
		$service->injectTranslator($this->getService('contributteTranslation.translator'));
		$service->injectManagerRegistry($this->getService('nettrineOrm.managerRegistry'));
		$service->injectLogger($this->getService('contributteMonolog.logger.default'));
		$service->injectJsonApiBuilder($this->getService('fbJsonApi.builder'));
		$service->injectRoutesValidator($this->getService('fbTriggersModule.router.validator'));
		$service->injectHydratorsContainer($this->getService('fbJsonApi.hydrators.container'));
		return $service;
	}


	public function createServiceFbTriggersModule__hydrators__notifications__email(): FastyBird\Module\Triggers\Hydrators\Notifications\EmailNotification
	{
		return new FastyBird\Module\Triggers\Hydrators\Notifications\EmailNotification(
			$this->getService('nettrineOrm.managerRegistry'),
			$this->getService('contributteTranslation.translator'),
			$this->getService('fbJsonApi.helpers.crudReader'),
			$this->getService('nettrineCache.driver'),
		);
	}


	public function createServiceFbTriggersModule__hydrators__notifications__sms(): FastyBird\Module\Triggers\Hydrators\Notifications\SmsNotification
	{
		return new FastyBird\Module\Triggers\Hydrators\Notifications\SmsNotification(
			$this->getService('ipubPhone.phone'),
			$this->getService('nettrineOrm.managerRegistry'),
			$this->getService('contributteTranslation.translator'),
		);
	}


	public function createServiceFbTriggersModule__hydrators__triggers__automatic(): FastyBird\Module\Triggers\Hydrators\Triggers\AutomaticTrigger
	{
		return new FastyBird\Module\Triggers\Hydrators\Triggers\AutomaticTrigger(
			$this->getService('nettrineOrm.managerRegistry'),
			$this->getService('contributteTranslation.translator'),
			$this->getService('fbJsonApi.helpers.crudReader'),
			$this->getService('nettrineCache.driver'),
		);
	}


	public function createServiceFbTriggersModule__hydrators__triggers__manual(): FastyBird\Module\Triggers\Hydrators\Triggers\ManualTrigger
	{
		return new FastyBird\Module\Triggers\Hydrators\Triggers\ManualTrigger(
			$this->getService('nettrineOrm.managerRegistry'),
			$this->getService('contributteTranslation.translator'),
			$this->getService('fbJsonApi.helpers.crudReader'),
			$this->getService('nettrineCache.driver'),
		);
	}


	public function createServiceFbTriggersModule__middleware__access(): FastyBird\Module\Triggers\Middleware\Access
	{
		return new FastyBird\Module\Triggers\Middleware\Access($this->getService('contributteTranslation.translator'));
	}


	public function createServiceFbTriggersModule__models__actionsManager(): FastyBird\Module\Triggers\Models\Actions\ActionsManager
	{
		return new FastyBird\Module\Triggers\Models\Actions\ActionsManager($this->getService('ipubDoctrineCrud.crud')->create('FastyBird\Module\Triggers\Entities\Actions\Action'));
	}


	public function createServiceFbTriggersModule__models__actionsRepository(): FastyBird\Module\Triggers\Models\Actions\ActionsRepository
	{
		return new FastyBird\Module\Triggers\Models\Actions\ActionsRepository(
			$this->getService('fbTriggersModule.utilities.database'),
			$this->getService('nettrineOrm.managerRegistry'),
		);
	}


	public function createServiceFbTriggersModule__models__conditionsManager(): FastyBird\Module\Triggers\Models\Conditions\ConditionsManager
	{
		return new FastyBird\Module\Triggers\Models\Conditions\ConditionsManager($this->getService('ipubDoctrineCrud.crud')->create('FastyBird\Module\Triggers\Entities\Conditions\Condition'));
	}


	public function createServiceFbTriggersModule__models__conditionsRepository(): FastyBird\Module\Triggers\Models\Conditions\ConditionsRepository
	{
		return new FastyBird\Module\Triggers\Models\Conditions\ConditionsRepository(
			$this->getService('fbTriggersModule.utilities.database'),
			$this->getService('nettrineOrm.managerRegistry'),
		);
	}


	public function createServiceFbTriggersModule__models__notificationsManager(): FastyBird\Module\Triggers\Models\Notifications\NotificationsManager
	{
		return new FastyBird\Module\Triggers\Models\Notifications\NotificationsManager($this->getService('ipubDoctrineCrud.crud')->create('FastyBird\Module\Triggers\Entities\Notifications\Notification'));
	}


	public function createServiceFbTriggersModule__models__notificationsRepository(): FastyBird\Module\Triggers\Models\Notifications\NotificationsRepository
	{
		return new FastyBird\Module\Triggers\Models\Notifications\NotificationsRepository(
			$this->getService('fbTriggersModule.utilities.database'),
			$this->getService('nettrineOrm.managerRegistry'),
		);
	}


	public function createServiceFbTriggersModule__models__triggeControlsRepository(): FastyBird\Module\Triggers\Models\Triggers\Controls\ControlsRepository
	{
		return new FastyBird\Module\Triggers\Models\Triggers\Controls\ControlsRepository(
			$this->getService('fbTriggersModule.utilities.database'),
			$this->getService('nettrineOrm.managerRegistry'),
		);
	}


	public function createServiceFbTriggersModule__models__triggersControlsManager(): FastyBird\Module\Triggers\Models\Triggers\Controls\ControlsManager
	{
		return new FastyBird\Module\Triggers\Models\Triggers\Controls\ControlsManager($this->getService('ipubDoctrineCrud.crud')->create('FastyBird\Module\Triggers\Entities\Triggers\Controls\Control'));
	}


	public function createServiceFbTriggersModule__models__triggersManager(): FastyBird\Module\Triggers\Models\Triggers\TriggersManager
	{
		return new FastyBird\Module\Triggers\Models\Triggers\TriggersManager($this->getService('ipubDoctrineCrud.crud')->create('FastyBird\Module\Triggers\Entities\Triggers\Trigger'));
	}


	public function createServiceFbTriggersModule__models__triggersRepository(): FastyBird\Module\Triggers\Models\Triggers\TriggersRepository
	{
		return new FastyBird\Module\Triggers\Models\Triggers\TriggersRepository(
			$this->getService('fbTriggersModule.utilities.database'),
			$this->getService('nettrineOrm.managerRegistry'),
		);
	}


	public function createServiceFbTriggersModule__router__routes(): FastyBird\Module\Triggers\Router\Routes
	{
		return new FastyBird\Module\Triggers\Router\Routes(
			true,
			$this->getService('fbTriggersModule.controllers.triggers'),
			$this->getService('fbTriggersModule.controllers.actions'),
			$this->getService('fbTriggersModule.controllers.notifications'),
			$this->getService('fbTriggersModule.controllers.conditions'),
			$this->getService('fbTriggersModule.controllers.triggersControls'),
			$this->getService('fbTriggersModule.middleware.access'),
			$this->getService('fbSimpleAuth.middleware.access'),
			$this->getService('fbSimpleAuth.middleware.user'),
		);
	}


	public function createServiceFbTriggersModule__router__validator(): FastyBird\Module\Triggers\Router\Validator
	{
		return new FastyBird\Module\Triggers\Router\Validator($this);
	}


	public function createServiceFbTriggersModule__schemas__notifications__email(): FastyBird\Module\Triggers\Schemas\Notifications\EmailNotification
	{
		return new FastyBird\Module\Triggers\Schemas\Notifications\EmailNotification($this->getService('fbWebServerPlugin.routing.router'));
	}


	public function createServiceFbTriggersModule__schemas__notifications__sms(): FastyBird\Module\Triggers\Schemas\Notifications\SmsNotification
	{
		return new FastyBird\Module\Triggers\Schemas\Notifications\SmsNotification($this->getService('fbWebServerPlugin.routing.router'));
	}


	public function createServiceFbTriggersModule__schemas__trigger__control(): FastyBird\Module\Triggers\Schemas\Triggers\Controls\Control
	{
		return new FastyBird\Module\Triggers\Schemas\Triggers\Controls\Control($this->getService('fbWebServerPlugin.routing.router'));
	}


	public function createServiceFbTriggersModule__schemas__triggers__automatic(): FastyBird\Module\Triggers\Schemas\Triggers\AutomaticTrigger
	{
		return new FastyBird\Module\Triggers\Schemas\Triggers\AutomaticTrigger(
			$this->getService('fbWebServerPlugin.routing.router'),
			$this->getService('fbTriggersModule.states.repositories.actions'),
			$this->getService('fbTriggersModule.states.repositories.conditions'),
		);
	}


	public function createServiceFbTriggersModule__schemas__triggers__manual(): FastyBird\Module\Triggers\Schemas\Triggers\ManualTrigger
	{
		return new FastyBird\Module\Triggers\Schemas\Triggers\ManualTrigger(
			$this->getService('fbWebServerPlugin.routing.router'),
			$this->getService('fbTriggersModule.states.repositories.actions'),
		);
	}


	public function createServiceFbTriggersModule__states__managers__actions(): FastyBird\Module\Triggers\Models\States\ActionsManager
	{
		return new FastyBird\Module\Triggers\Models\States\ActionsManager(
			null,
			$this->getService('fbExchangeLibrary.entityFactory'),
			$this->getService('fbExchangeLibrary.publisher'),
		);
	}


	public function createServiceFbTriggersModule__states__managers__conditions(): FastyBird\Module\Triggers\Models\States\ConditionsManager
	{
		return new FastyBird\Module\Triggers\Models\States\ConditionsManager(
			null,
			$this->getService('fbExchangeLibrary.entityFactory'),
			$this->getService('fbExchangeLibrary.publisher'),
		);
	}


	public function createServiceFbTriggersModule__states__repositories__actions(): FastyBird\Module\Triggers\Models\States\ActionsRepository
	{
		return new FastyBird\Module\Triggers\Models\States\ActionsRepository(null);
	}


	public function createServiceFbTriggersModule__states__repositories__conditions(): FastyBird\Module\Triggers\Models\States\ConditionsRepository
	{
		return new FastyBird\Module\Triggers\Models\States\ConditionsRepository(null);
	}


	public function createServiceFbTriggersModule__subscribers__entities(): FastyBird\Module\Triggers\Subscribers\ModuleEntities
	{
		return new FastyBird\Module\Triggers\Subscribers\ModuleEntities(
			$this->getService('fbTriggersModule.states.repositories.actions'),
			$this->getService('fbTriggersModule.states.repositories.conditions'),
			$this->getService('fbExchangeLibrary.entityFactory'),
			$this->getService('nettrineOrm.entityManagerDecorator'),
			$this->getService('fbExchangeLibrary.publisher'),
		);
	}


	public function createServiceFbTriggersModule__subscribers__notificationEntity(): FastyBird\Module\Triggers\Subscribers\NotificationEntity
	{
		return new FastyBird\Module\Triggers\Subscribers\NotificationEntity;
	}


	public function createServiceFbTriggersModule__utilities__database(): FastyBird\Module\Triggers\Utilities\Database
	{
		return new FastyBird\Module\Triggers\Utilities\Database(
			$this->getService('fbBootstrapLibrary.helpers.database'),
			$this->getService('nettrineOrm.managerRegistry'),
		);
	}


	public function createServiceFbTuyaConnector__api__localApi__api(): FastyBird\Connector\Tuya\API\LocalApiFactory
	{
		return new FastyBird\Connector\Tuya\API\LocalApiFactory(
			$this->getService('schema.validator'),
			$this->getService('fbDateTimeFactory.datetime.factory'),
			$this->getService('ipubWebsockets.server.loop'),
			$this->getService('contributteMonolog.logger.default'),
		);
	}


	public function createServiceFbTuyaConnector__api__openApi__api(): FastyBird\Connector\Tuya\API\OpenApiFactory
	{
		return new FastyBird\Connector\Tuya\API\OpenApiFactory(
			$this->getService('fbTuyaConnector.api.openApi.entityFactory'),
			$this->getService('fbTuyaConnector.helpers.connector'),
			$this->getService('schema.validator'),
			$this->getService('fbDateTimeFactory.datetime.factory'),
			$this->getService('contributteMonolog.logger.default'),
		);
	}


	public function createServiceFbTuyaConnector__api__openApi__entityFactory(): FastyBird\Connector\Tuya\API\EntityFactory
	{
		return new FastyBird\Connector\Tuya\API\EntityFactory;
	}


	public function createServiceFbTuyaConnector__clients__cloud(): FastyBird\Connector\Tuya\Clients\CloudFactory
	{
		return new class ($this) implements FastyBird\Connector\Tuya\Clients\CloudFactory {
			private $container;


			public function __construct(Container_e69559cf0b $container)
			{
				$this->container = $container;
			}


			public function create(FastyBird\Connector\Tuya\Entities\TuyaConnector $connector): FastyBird\Connector\Tuya\Clients\Cloud
			{
				return new FastyBird\Connector\Tuya\Clients\Cloud(
					$connector,
					$this->container->getService('fbTuyaConnector.helpers.connector'),
					$this->container->getService('fbTuyaConnector.helpers.property'),
					$this->container->getService('fbTuyaConnector.consumers.proxy'),
					$this->container->getService('fbTuyaConnector.api.openApi.api'),
					$this->container->getService('fbDevicesModule.utilities.devices.connection'),
					$this->container->getService('fbDevicesModule.utilities.channels.states'),
					$this->container->getService('fbDateTimeFactory.datetime.factory'),
					$this->container->getService('schema.validator'),
					$this->container->getService('ipubWebsockets.server.loop'),
					$this->container->getService('contributteMonolog.logger.default'),
				);
			}
		};
	}


	public function createServiceFbTuyaConnector__clients__discover(): FastyBird\Connector\Tuya\Clients\DiscoveryFactory
	{
		return new class ($this) implements FastyBird\Connector\Tuya\Clients\DiscoveryFactory {
			private $container;


			public function __construct(Container_e69559cf0b $container)
			{
				$this->container = $container;
			}


			public function create(FastyBird\Connector\Tuya\Entities\TuyaConnector $connector): FastyBird\Connector\Tuya\Clients\Discovery
			{
				return new FastyBird\Connector\Tuya\Clients\Discovery(
					$connector,
					$this->container->getService('fbTuyaConnector.api.openApi.api'),
					$this->container->getService('fbTuyaConnector.api.localApi.api'),
					$this->container->getService('fbTuyaConnector.helpers.connector'),
					$this->container->getService('fbTuyaConnector.consumers.proxy'),
					$this->container->getService('ipubWebsockets.server.loop'),
					$this->container->getService('contributteMonolog.logger.default'),
				);
			}
		};
	}


	public function createServiceFbTuyaConnector__clients__local(): FastyBird\Connector\Tuya\Clients\LocalFactory
	{
		return new class ($this) implements FastyBird\Connector\Tuya\Clients\LocalFactory {
			private $container;


			public function __construct(Container_e69559cf0b $container)
			{
				$this->container = $container;
			}


			public function create(FastyBird\Connector\Tuya\Entities\TuyaConnector $connector): FastyBird\Connector\Tuya\Clients\Local
			{
				return new FastyBird\Connector\Tuya\Clients\Local(
					$connector,
					$this->container->getService('fbTuyaConnector.helpers.device'),
					$this->container->getService('fbTuyaConnector.helpers.property'),
					$this->container->getService('fbTuyaConnector.consumers.proxy'),
					$this->container->getService('fbTuyaConnector.api.localApi.api'),
					$this->container->getService('fbDevicesModule.utilities.devices.connection'),
					$this->container->getService('fbDevicesModule.utilities.channels.states'),
					$this->container->getService('fbDateTimeFactory.datetime.factory'),
					$this->container->getService('ipubWebsockets.server.loop'),
					$this->container->getService('contributteMonolog.logger.default'),
				);
			}
		};
	}


	public function createServiceFbTuyaConnector__commands__discovery(): FastyBird\Connector\Tuya\Commands\Discovery
	{
		return new FastyBird\Connector\Tuya\Commands\Discovery(
			$this->getService('fbTuyaConnector.clients.discover'),
			$this->getService('fbTuyaConnector.helpers.connector'),
			$this->getService('fbTuyaConnector.helpers.device'),
			$this->getService('fbTuyaConnector.consumers.proxy'),
			$this->getService('fbDevicesModule.models.connectorsRepository'),
			$this->getService('fbDateTimeFactory.datetime.factory'),
			$this->getService('ipubWebsockets.server.loop'),
			$this->getService('contributteMonolog.logger.default'),
		);
	}


	public function createServiceFbTuyaConnector__commands__execute(): FastyBird\Connector\Tuya\Commands\Execute
	{
		return new FastyBird\Connector\Tuya\Commands\Execute(
			$this->getService('fbDevicesModule.models.connectorsRepository'),
			$this->getService('contributteMonolog.logger.default'),
		);
	}


	public function createServiceFbTuyaConnector__commands__initialize(): FastyBird\Connector\Tuya\Commands\Initialize
	{
		return new FastyBird\Connector\Tuya\Commands\Initialize(
			$this->getService('fbDevicesModule.models.connectorsRepository'),
			$this->getService('fbDevicesModule.models.connectorsManager'),
			$this->getService('fbDevicesModule.models.connectorsPropertiesManager'),
			$this->getService('fbDevicesModule.models.connectorsControlsManager'),
			$this->getService('nettrineOrm.managerRegistry'),
			$this->getService('contributteMonolog.logger.default'),
		);
	}


	public function createServiceFbTuyaConnector__consumers__discovery__cloudDevice(): FastyBird\Connector\Tuya\Consumers\Messages\CloudDiscovery
	{
		return new FastyBird\Connector\Tuya\Consumers\Messages\CloudDiscovery(
			$this->getService('fbDevicesModule.models.connectorsRepository'),
			$this->getService('fbDevicesModule.models.devicesRepository'),
			$this->getService('fbDevicesModule.models.devicesManager'),
			$this->getService('fbDevicesModule.models.devicePropertiesRepository'),
			$this->getService('fbDevicesModule.models.devicesPropertiesManager'),
			$this->getService('fbDevicesModule.models.deviceAttributesRepository'),
			$this->getService('fbDevicesModule.models.devicesAttributesManager'),
			$this->getService('fbDevicesModule.models.channelsRepository'),
			$this->getService('fbDevicesModule.models.channelsManager'),
			$this->getService('fbDevicesModule.models.channelsPropertiesManager'),
			$this->getService('fbDevicesModule.utilities.database'),
			$this->getService('contributteMonolog.logger.default'),
		);
	}


	public function createServiceFbTuyaConnector__consumers__discovery__localDevice(): FastyBird\Connector\Tuya\Consumers\Messages\LocalDiscovery
	{
		return new FastyBird\Connector\Tuya\Consumers\Messages\LocalDiscovery(
			$this->getService('fbDevicesModule.models.connectorsRepository'),
			$this->getService('fbDevicesModule.models.devicesRepository'),
			$this->getService('fbDevicesModule.models.devicesManager'),
			$this->getService('fbDevicesModule.models.devicePropertiesRepository'),
			$this->getService('fbDevicesModule.models.devicesPropertiesManager'),
			$this->getService('fbDevicesModule.models.deviceAttributesRepository'),
			$this->getService('fbDevicesModule.models.devicesAttributesManager'),
			$this->getService('fbDevicesModule.models.channelsRepository'),
			$this->getService('fbDevicesModule.models.channelsManager'),
			$this->getService('fbDevicesModule.models.channelsPropertiesManager'),
			$this->getService('fbDevicesModule.utilities.database'),
			$this->getService('contributteMonolog.logger.default'),
		);
	}


	public function createServiceFbTuyaConnector__consumers__discovery__state(): FastyBird\Connector\Tuya\Consumers\Messages\State
	{
		return new FastyBird\Connector\Tuya\Consumers\Messages\State(
			$this->getService('fbTuyaConnector.helpers.property'),
			$this->getService('fbDevicesModule.models.devicesRepository'),
			$this->getService('fbDevicesModule.utilities.devices.connection'),
			$this->getService('contributteMonolog.logger.default'),
		);
	}


	public function createServiceFbTuyaConnector__consumers__discovery__status(): FastyBird\Connector\Tuya\Consumers\Messages\Status
	{
		return new FastyBird\Connector\Tuya\Consumers\Messages\Status(
			$this->getService('fbDevicesModule.models.devicesRepository'),
			$this->getService('fbDevicesModule.utilities.devices.connection'),
			$this->getService('fbTuyaConnector.mappers.dataPoint'),
			$this->getService('fbTuyaConnector.helpers.property'),
			$this->getService('contributteMonolog.logger.default'),
		);
	}


	public function createServiceFbTuyaConnector__consumers__proxy(): FastyBird\Connector\Tuya\Consumers\Messages
	{
		return new FastyBird\Connector\Tuya\Consumers\Messages(
			[
				'fbTuyaConnector.consumers.discovery.cloudDevice' => $this->getService('fbTuyaConnector.consumers.discovery.cloudDevice'),
				'fbTuyaConnector.consumers.discovery.localDevice' => $this->getService('fbTuyaConnector.consumers.discovery.localDevice'),
				'fbTuyaConnector.consumers.discovery.status' => $this->getService('fbTuyaConnector.consumers.discovery.status'),
				'fbTuyaConnector.consumers.discovery.state' => $this->getService('fbTuyaConnector.consumers.discovery.state'),
			],
			$this->getService('contributteMonolog.logger.default'),
		);
	}


	public function createServiceFbTuyaConnector__executor__factory(): FastyBird\Connector\Tuya\Connector\ConnectorFactory
	{
		return new class ($this) implements FastyBird\Connector\Tuya\Connector\ConnectorFactory {
			private $container;


			public function __construct(Container_e69559cf0b $container)
			{
				$this->container = $container;
			}


			public function create(FastyBird\Module\Devices\Entities\Connectors\Connector $connector): FastyBird\Connector\Tuya\Connector\Connector
			{
				return new FastyBird\Connector\Tuya\Connector\Connector(
					$connector,
					[
						'fbTuyaConnector.clients.local' => $this->container->getService('fbTuyaConnector.clients.local'),
						'fbTuyaConnector.clients.cloud' => $this->container->getService('fbTuyaConnector.clients.cloud'),
					],
					$this->container->getService('fbTuyaConnector.helpers.connector'),
					$this->container->getService('fbTuyaConnector.consumers.proxy'),
					$this->container->getService('ipubWebsockets.server.loop'),
				);
			}
		};
	}


	public function createServiceFbTuyaConnector__helpers__connector(): FastyBird\Connector\Tuya\Helpers\Connector
	{
		return new FastyBird\Connector\Tuya\Helpers\Connector;
	}


	public function createServiceFbTuyaConnector__helpers__device(): FastyBird\Connector\Tuya\Helpers\Device
	{
		return new FastyBird\Connector\Tuya\Helpers\Device;
	}


	public function createServiceFbTuyaConnector__helpers__property(): FastyBird\Connector\Tuya\Helpers\Property
	{
		return new FastyBird\Connector\Tuya\Helpers\Property(
			$this->getService('fbDevicesModule.utilities.devices.states'),
			$this->getService('fbDevicesModule.utilities.channels.states'),
		);
	}


	public function createServiceFbTuyaConnector__hydrators__connector__tuya(): FastyBird\Connector\Tuya\Hydrators\TuyaConnector
	{
		return new FastyBird\Connector\Tuya\Hydrators\TuyaConnector(
			$this->getService('nettrineOrm.managerRegistry'),
			$this->getService('contributteTranslation.translator'),
			$this->getService('fbJsonApi.helpers.crudReader'),
			$this->getService('nettrineCache.driver'),
		);
	}


	public function createServiceFbTuyaConnector__hydrators__device__tuya(): FastyBird\Connector\Tuya\Hydrators\TuyaDevice
	{
		return new FastyBird\Connector\Tuya\Hydrators\TuyaDevice(
			$this->getService('nettrineOrm.managerRegistry'),
			$this->getService('contributteTranslation.translator'),
			$this->getService('fbJsonApi.helpers.crudReader'),
			$this->getService('nettrineCache.driver'),
		);
	}


	public function createServiceFbTuyaConnector__mappers__dataPoint(): FastyBird\Connector\Tuya\Mappers\DataPoint
	{
		return new FastyBird\Connector\Tuya\Mappers\DataPoint(
			$this->getService('fbDevicesModule.models.devicesRepository'),
			$this->getService('fbDevicesModule.models.channelPropertiesRepository'),
		);
	}


	public function createServiceFbTuyaConnector__schemas__connector__tuya(): FastyBird\Connector\Tuya\Schemas\TuyaConnector
	{
		return new FastyBird\Connector\Tuya\Schemas\TuyaConnector($this->getService('fbWebServerPlugin.routing.router'));
	}


	public function createServiceFbTuyaConnector__schemas__device__tuya(): FastyBird\Connector\Tuya\Schemas\TuyaDevice
	{
		return new FastyBird\Connector\Tuya\Schemas\TuyaDevice(
			$this->getService('fbDevicesModule.models.devicesRepository'),
			$this->getService('fbDevicesModule.models.channelsRepository'),
			$this->getService('fbWebServerPlugin.routing.router'),
		);
	}


	public function createServiceFbTuyaConnector__subscribers__properties(): FastyBird\Connector\Tuya\Subscribers\Properties
	{
		return new FastyBird\Connector\Tuya\Subscribers\Properties($this->getService('fbDevicesModule.models.devicesPropertiesManager'));
	}


	public function createServiceFbWebServerPlugin__application__classic(): FastyBird\Plugin\WebServer\Application\Application
	{
		return new FastyBird\Plugin\WebServer\Application\Application(
			$this->getService('fbWebServerPlugin.routing.router'),
			$this->getService('contributteEvents.dispatcher'),
		);
	}


	public function createServiceFbWebServerPlugin__application__console(): FastyBird\Plugin\WebServer\Application\Console
	{
		return new FastyBird\Plugin\WebServer\Application\Console($this->getService('contributteConsole.application'));
	}


	public function createServiceFbWebServerPlugin__commands__server(): FastyBird\Plugin\WebServer\Commands\HttpServer
	{
		return new FastyBird\Plugin\WebServer\Commands\HttpServer(
			'0.0.0.0',
			8000,
			$this->getService('fbWebServerPlugin.server.factory'),
			$this->getService('ipubWebsockets.server.loop'),
			$this->getService('contributteEvents.dispatcher'),
			$this->getService('contributteMonolog.logger.default'),
			null,
		);
	}


	public function createServiceFbWebServerPlugin__middlewares__cors(): FastyBird\Plugin\WebServer\Middleware\Cors
	{
		return new FastyBird\Plugin\WebServer\Middleware\Cors(
			false,
			'*',
			['GET', 'POST', 'PATCH', 'DELETE', 'OPTIONS'],
			true,
			[
				'Content-Type',
				'Authorization',
				'X-Requested-With',
				'Content-Type',
				'Authorization',
				'X-Requested-With',
				'X-Api-Key',
			],
		);
	}


	public function createServiceFbWebServerPlugin__middlewares__router(): FastyBird\Plugin\WebServer\Middleware\Router
	{
		return new FastyBird\Plugin\WebServer\Middleware\Router(
			$this->getService('fbWebServerPlugin.routing.router'),
			$this->getService('contributteEvents.dispatcher'),
		);
	}


	public function createServiceFbWebServerPlugin__middlewares__staticFiles(): FastyBird\Plugin\WebServer\Middleware\StaticFiles
	{
		return new FastyBird\Plugin\WebServer\Middleware\StaticFiles('/srv/fastybird/public/', true);
	}


	public function createServiceFbWebServerPlugin__routing__responseFactory(): FastyBird\Plugin\WebServer\Http\ResponseFactory
	{
		return new FastyBird\Plugin\WebServer\Http\ResponseFactory;
	}


	public function createServiceFbWebServerPlugin__routing__router(): FastyBird\Plugin\WebServer\Router\Router
	{
		$service = new FastyBird\Plugin\WebServer\Router\Router($this->getService('fbWebServerPlugin.routing.responseFactory'));
		$service->addMiddleware($this->getService('fbJsonApi.middlewares.jsonapi'));
		$service->addMiddleware($this->getService('fbAccountsModule.middlewares.urlFormat'));
		$this->getService('fbAccountsModule.router.routes')->registerRoutes($service);
		$this->getService('fbDevicesModule.router.routes')->registerRoutes($service);
		$this->getService('fbTriggersModule.router.routes')->registerRoutes($service);
		return $service;
	}


	public function createServiceFbWebServerPlugin__server__factory(): FastyBird\Plugin\WebServer\Server\Factory
	{
		return new FastyBird\Plugin\WebServer\Server\Factory(
			$this->getService('fbWebServerPlugin.middlewares.cors'),
			$this->getService('fbWebServerPlugin.middlewares.staticFiles'),
			$this->getService('fbWebServerPlugin.middlewares.router'),
			$this->getService('ipubWebsockets.server.loop'),
			$this->getService('contributteMonolog.logger.default'),
		);
	}


	public function createServiceFbWebServerPlugin__subscribers__server(): FastyBird\Plugin\WebServer\Subscribers\Server
	{
		return new FastyBird\Plugin\WebServer\Subscribers\Server($this->getService('fbBootstrapLibrary.helpers.database'));
	}


	public function createServiceFbWsExchangePlugin__commands__server(): FastyBird\Plugin\WsExchange\Commands\WsServer
	{
		return new FastyBird\Plugin\WsExchange\Commands\WsServer(
			$this->getService('ipubWebsockets.server.configuration'),
			$this->getService('fbWsExchangePlugin.server.factory'),
			$this->getService('ipubWebsockets.server.loop'),
			$this->getService('contributteEvents.dispatcher'),
			$this->getService('contributteMonolog.logger.default'),
		);
	}


	public function createServiceFbWsExchangePlugin__controllers__exchange(): FastyBird\Plugin\WsExchange\Controllers\Exchange
	{
		$service = new FastyBird\Plugin\WsExchange\Controllers\Exchange(
			$this->getService('loaders.schema'),
			$this->getService('schema.validator'),
			$this->getService('fbExchangeLibrary.entityFactory'),
			$this->getService('fbExchangeLibrary.publisher'),
			$this->getService('contributteEvents.dispatcher'),
			$this->getService('contributteMonolog.logger.default'),
		);
		$service->injectPrimary(
			$this,
			$this->getService('ipubWebsockets.controllers.factory'),
			$this->getService('ipubWebsockets.routing.router'),
			$this->getService('ipubWebsockets.routing.generator'),
		);
		return $service;
	}


	public function createServiceFbWsExchangePlugin__exchange__consumer(): FastyBird\Plugin\WsExchange\Consumers\Consumer
	{
		return new FastyBird\Plugin\WsExchange\Consumers\Consumer(
			$this->getService('fbWsExchangePlugin.exchange.publisher'),
			$this->getService('contributteMonolog.logger.default'),
		);
	}


	public function createServiceFbWsExchangePlugin__exchange__publisher(): FastyBird\Plugin\WsExchange\Publishers\Publisher
	{
		return new FastyBird\Plugin\WsExchange\Publishers\Publisher(
			$this->getService('ipubWebsockets.routing.generator'),
			$this->getService('ipubWebsocketsWamp.topics.storage'),
			$this->getService('contributteMonolog.logger.default'),
		);
	}


	public function createServiceFbWsExchangePlugin__server__factory(): FastyBird\Plugin\WsExchange\Server\Factory
	{
		return new FastyBird\Plugin\WsExchange\Server\Factory(
			$this->getService('ipubWebsockets.server.handlers'),
			$this->getService('ipubWebsockets.server.configuration'),
			$this->getService('contributteMonolog.logger.default'),
		);
	}


	public function createServiceFbWsExchangePlugin__subscribers__client(): FastyBird\Plugin\WsExchange\Subscribers\Client
	{
		return new FastyBird\Plugin\WsExchange\Subscribers\Client(
			$this->getService('fbBootstrapLibrary.helpers.database'),
			$this->getService('contributteMonolog.logger.default'),
			null,
			null,
		);
	}


	public function createServiceHttp__request(): Nette\Http\Request
	{
		return new Nette\Http\Request(new Nette\Http\UrlScript('https://www.fastybird.com'));
	}


	public function createServiceHttp__requestFactory(): Nette\Http\RequestFactory
	{
		$service = new Nette\Http\RequestFactory;
		$service->setProxy([]);
		return $service;
	}


	public function createServiceHttp__response(): Nette\Http\Response
	{
		$service = new Nette\Http\Response;
		$service->cookieSecure = $this->getService('http.request')->isSecured();
		return $service;
	}


	public function createServiceIpubDoctrineConsistence__subscriber(): IPub\DoctrineConsistence\Events\EnumSubscriber
	{
		return new IPub\DoctrineConsistence\Events\EnumSubscriber($this->getService('nettrineAnnotations.reader'));
	}


	public function createServiceIpubDoctrineCrud__crud(): IPub\DoctrineCrud\Crud\IEntityCrudFactory
	{
		return new class ($this) implements IPub\DoctrineCrud\Crud\IEntityCrudFactory {
			private $container;


			public function __construct(Container_e69559cf0b $container)
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
					$this->container->getService('ipubDoctrineCrud.entity.deleter'),
				);
			}
		};
	}


	public function createServiceIpubDoctrineCrud__entity__creator(): IPub\DoctrineCrud\Crud\Create\IEntityCreator
	{
		return new class ($this) implements IPub\DoctrineCrud\Crud\Create\IEntityCreator {
			private $container;


			public function __construct(Container_e69559cf0b $container)
			{
				$this->container = $container;
			}


			public function create(
				string $entityName,
				IPub\DoctrineCrud\Mapping\IEntityMapper $entityMapper,
			): IPub\DoctrineCrud\Crud\Create\EntityCreator {
				return new IPub\DoctrineCrud\Crud\Create\EntityCreator(
					$entityName,
					$entityMapper,
					$this->container->getService('nettrineOrm.managerRegistry'),
				);
			}
		};
	}


	public function createServiceIpubDoctrineCrud__entity__deleter(): IPub\DoctrineCrud\Crud\Delete\IEntityDeleter
	{
		return new class ($this) implements IPub\DoctrineCrud\Crud\Delete\IEntityDeleter {
			private $container;


			public function __construct(Container_e69559cf0b $container)
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
			$this->getService('nettrineCache.driver'),
			$this->getService('nettrineOrm.managerRegistry'),
		);
	}


	public function createServiceIpubDoctrineCrud__entity__updater(): IPub\DoctrineCrud\Crud\Update\IEntityUpdater
	{
		return new class ($this) implements IPub\DoctrineCrud\Crud\Update\IEntityUpdater {
			private $container;


			public function __construct(Container_e69559cf0b $container)
			{
				$this->container = $container;
			}


			public function create(
				string $entityName,
				IPub\DoctrineCrud\Mapping\IEntityMapper $entityMapper,
			): IPub\DoctrineCrud\Crud\Update\EntityUpdater {
				return new IPub\DoctrineCrud\Crud\Update\EntityUpdater(
					$entityName,
					$entityMapper,
					$this->container->getService('nettrineOrm.managerRegistry'),
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
		return new IPub\DoctrineTimestampable\Configuration(false, true, 'datetime');
	}


	public function createServiceIpubDoctrineTimestampable__driver(): IPub\DoctrineTimestampable\Mapping\Driver\Timestampable
	{
		return new IPub\DoctrineTimestampable\Mapping\Driver\Timestampable(
			$this->getService('ipubDoctrineTimestampable.configuration'),
			$this->getService('nettrineCache.driver'),
		);
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
			$this->getService('contributteTranslation.translator'),
		);
	}


	public function createServiceIpubWebsockets__clients__driver__memory(): IPub\WebSockets\Clients\Drivers\InMemory
	{
		return new IPub\WebSockets\Clients\Drivers\InMemory;
	}


	public function createServiceIpubWebsockets__clients__storage(): IPub\WebSockets\Clients\Storage
	{
		$service = new IPub\WebSockets\Clients\Storage(0, $this->getService('contributteMonolog.logger.default'));
		$service->setStorageDriver($this->getService('ipubWebsockets.clients.driver.memory'));
		return $service;
	}


	public function createServiceIpubWebsockets__commands__server(): IPub\WebSockets\Commands\ServerCommand
	{
		return new IPub\WebSockets\Commands\ServerCommand(
			$this->getService('ipubWebsockets.server.server'),
			$this->getService('contributteMonolog.logger.default'),
		);
	}


	public function createServiceIpubWebsockets__controllers__factory(): IPub\WebSockets\Application\Controller\IControllerFactory
	{
		$service = new IPub\WebSockets\Application\Controller\ControllerFactory(null, $this);
		$service->setMapping(['*' => 'FastyBird\WsServerPlugin\Controllers\*Controller']);
		return $service;
	}


	public function createServiceIpubWebsockets__routing__generator(): IPub\WebSockets\Router\LinkGenerator
	{
		return new IPub\WebSockets\Router\LinkGenerator(
			$this->getService('ipubWebsockets.routing.router'),
			$this->getService('ipubWebsockets.controllers.factory'),
		);
	}


	public function createServiceIpubWebsockets__routing__router(): IPub\WebSockets\Router\IRouter
	{
		$service = new IPub\WebSockets\Router\RouteList;
		$service[] = new IPub\WebSockets\Router\Route('/io/exchange', 'Exchange:');;
		return $service;
	}


	public function createServiceIpubWebsockets__server__configuration(): IPub\WebSockets\Server\Configuration
	{
		return new IPub\WebSockets\Server\Configuration(8080, '0.0.0.0', false, []);
	}


	public function createServiceIpubWebsockets__server__flashWrapper(): IPub\WebSockets\Server\FlashWrapper
	{
		$service = new IPub\WebSockets\Server\FlashWrapper;
		$service->addAllowedAccess('localhost', '80');
		$service->addAllowedAccess('localhost', '8080');
		return $service;
	}


	public function createServiceIpubWebsockets__server__handlers(): IPub\WebSockets\Server\Handlers
	{
		return new IPub\WebSockets\Server\Handlers(
			$this->getService('ipubWebsockets.server.wrapper'),
			$this->getService('ipubWebsockets.server.flashWrapper'),
			$this->getService('ipubWebsockets.clients.storage'),
			$this->getService('ipubWebsocketsWamp.clients.factory'),
			$this->getService('contributteMonolog.logger.default'),
		);
	}


	public function createServiceIpubWebsockets__server__loop(): React\EventLoop\LoopInterface
	{
		return React\EventLoop\Factory::create();
	}


	public function createServiceIpubWebsockets__server__server(): IPub\WebSockets\Server\Server
	{
		$service = new IPub\WebSockets\Server\Server(
			$this->getService('ipubWebsockets.server.handlers'),
			$this->getService('ipubWebsockets.server.loop'),
			$this->getService('ipubWebsockets.server.configuration'),
			$this->getService('contributteMonolog.logger.default'),
		);
		$service->onStart[] = function() {$this->getService('contributteEvents.dispatcher')->dispatch(new IPub\WebSockets\Events\Server\StartEvent(...func_get_args()));};
		$service->onStop[] = function() {$this->getService('contributteEvents.dispatcher')->dispatch(new IPub\WebSockets\Events\Server\StopEvent(...func_get_args()));};
		$service->onStart[] = $this->getService('ipubWebsocketsWamp.subscribers.onServerStart');
		return $service;
	}


	public function createServiceIpubWebsockets__server__wrapper(): IPub\WebSockets\Server\Wrapper
	{
		$service = new IPub\WebSockets\Server\Wrapper(
			$this->getService('ipubWebsocketsWamp.application'),
			$this->getService('ipubWebsockets.clients.storage'),
		);
		$service->onClientConnected[] = function() {$this->getService('contributteEvents.dispatcher')->dispatch(new IPub\WebSockets\Events\Wrapper\ClientConnectEvent(...func_get_args()));};
		$service->onClientDisconnected[] = function() {$this->getService('contributteEvents.dispatcher')->dispatch(new IPub\WebSockets\Events\Wrapper\ClientDisconnectEvent(...func_get_args()));};
		$service->onClientError[] = function() {$this->getService('contributteEvents.dispatcher')->dispatch(new IPub\WebSockets\Events\Wrapper\ClientErrorEvent(...func_get_args()));};
		$service->onIncomingMessage[] = function() {$this->getService('contributteEvents.dispatcher')->dispatch(new IPub\WebSockets\Events\Wrapper\IncommingMessageEvent(...func_get_args()));};
		$service->onAfterIncomingMessage[] = function() {$this->getService('contributteEvents.dispatcher')->dispatch(new IPub\WebSockets\Events\Wrapper\AfterIncommingMessageEvent(...func_get_args()));};
		$service->onClientConnected[] = function() {$this->getService('contributteEvents.dispatcher')->dispatch(new FastyBird\Plugin\WsExchange\Events\ClientConnected(...func_get_args()));};
		$service->onIncomingMessage[] = function() {$this->getService('contributteEvents.dispatcher')->dispatch(new FastyBird\Plugin\WsExchange\Events\IncomingMessage(...func_get_args()));};
		return $service;
	}


	public function createServiceIpubWebsocketsWamp__application(): IPub\WebSocketsWAMP\Application\Application
	{
		$service = new IPub\WebSocketsWAMP\Application\Application(
			$this->getService('ipubWebsocketsWamp.topics.storage'),
			$this->getService('ipubWebsockets.routing.router'),
			$this->getService('ipubWebsockets.controllers.factory'),
			$this->getService('ipubWebsockets.clients.storage'),
			$this->getService('contributteMonolog.logger.default'),
		);
		$service->onOpen[] = function() {$this->getService('contributteEvents.dispatcher')->dispatch(new IPub\WebSockets\Events\Application\OpenEvent(...func_get_args()));};
		$service->onClose[] = function() {$this->getService('contributteEvents.dispatcher')->dispatch(new IPub\WebSockets\Events\Application\CloseEvent(...func_get_args()));};
		$service->onMessage[] = function() {$this->getService('contributteEvents.dispatcher')->dispatch(new IPub\WebSockets\Events\Application\MessageEvent(...func_get_args()));};
		$service->onError[] = function() {$this->getService('contributteEvents.dispatcher')->dispatch(new IPub\WebSockets\Events\Application\ErrorEvent(...func_get_args()));};
		$service->onPush[] = function() {$this->getService('contributteEvents.dispatcher')->dispatch(new IPub\WebSocketsWAMP\Events\Application\PushEvent(...func_get_args()));};
		return $service;
	}


	public function createServiceIpubWebsocketsWamp__clients__factory(): IPub\WebSocketsWAMP\Clients\ClientFactory
	{
		return new IPub\WebSocketsWAMP\Clients\ClientFactory;
	}


	public function createServiceIpubWebsocketsWamp__push__registry(): IPub\WebSocketsWAMP\PushMessages\ConsumersRegistry
	{
		return new IPub\WebSocketsWAMP\PushMessages\ConsumersRegistry;
	}


	public function createServiceIpubWebsocketsWamp__serializer(): IPub\WebSocketsWAMP\Serializers\PushMessageSerializer
	{
		return new IPub\WebSocketsWAMP\Serializers\PushMessageSerializer;
	}


	public function createServiceIpubWebsocketsWamp__subscribers__onServerStart(): IPub\WebSocketsWAMP\Subscribers\OnServerStartHandler
	{
		return new IPub\WebSocketsWAMP\Subscribers\OnServerStartHandler(
			$this->getService('ipubWebsocketsWamp.push.registry'),
			$this->getService('ipubWebsocketsWamp.application'),
		);
	}


	public function createServiceIpubWebsocketsWamp__topics__driver__memory(): IPub\WebSocketsWAMP\Topics\Drivers\InMemory
	{
		return new IPub\WebSocketsWAMP\Topics\Drivers\InMemory;
	}


	public function createServiceIpubWebsocketsWamp__topics__storage(): IPub\WebSocketsWAMP\Topics\Storage
	{
		$service = new IPub\WebSocketsWAMP\Topics\Storage(0, $this->getService('contributteMonolog.logger.default'));
		$service->setStorageDriver($this->getService('ipubWebsocketsWamp.topics.driver.memory'));
		return $service;
	}


	public function createServiceLoaders__metadata(): FastyBird\Library\Metadata\Loaders\MetadataLoader
	{
		return new FastyBird\Library\Metadata\Loaders\MetadataLoader($this->getService('schema.validator'));
	}


	public function createServiceLoaders__schema(): FastyBird\Library\Metadata\Loaders\SchemaLoader
	{
		return new FastyBird\Library\Metadata\Loaders\SchemaLoader;
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
			true,
		);
	}


	public function createServiceNettrineCache__driver(): Doctrine\Common\Cache\Cache
	{
		return new Doctrine\Common\Cache\PhpFileCache('/srv/fastybird/var/temp/cache/nettrine.cache');
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
				'host' => 'database',
				'port' => 3306,
				'driver' => 'pdo_mysql',
				'memory' => false,
				'dbname' => 'fastybird',
				'user' => 'fastybird',
				'password' => 'fastybird',
				'charset' => 'utf8',
				'types' => [
					'uuid_binary' => ['class' => 'Ramsey\Uuid\Doctrine\UuidBinaryType', 'commented' => false],
					'utcdatetime' => ['class' => 'IPub\DoctrineTimestampable\Types\UTCDateTime', 'commented' => false],
				],
				'typesMapping' => ['uuid_binary' => 'binary'],
			],
			$this->getService('nettrineDbal.configuration'),
			$this->getService('nettrineDbal.eventManager'),
		);
	}


	public function createServiceNettrineDbal__connectionAccessor(): Nettrine\DBAL\ConnectionAccessor
	{
		return new class ($this) implements Nettrine\DBAL\ConnectionAccessor {
			private $container;


			public function __construct(Container_e69559cf0b $container)
			{
				$this->container = $container;
			}


			public function get(): Doctrine\DBAL\Connection
			{
				return $this->container->getService('nettrineDbal.connection');
			}
		};
	}


	public function createServiceNettrineDbal__connectionFactory(): Nettrine\DBAL\ConnectionFactory
	{
		return new Nettrine\DBAL\ConnectionFactory(
			[
				'uuid_binary' => ['class' => 'Ramsey\Uuid\Doctrine\UuidBinaryType', 'commented' => false],
				'utcdatetime' => ['class' => 'IPub\DoctrineTimestampable\Types\UTCDateTime', 'commented' => false],
			],
			['uuid_binary' => 'binary'],
		);
	}


	public function createServiceNettrineDbal__eventManager(): Nettrine\DBAL\Events\ContainerAwareEventManager
	{
		$service = new Nettrine\DBAL\Events\ContainerAwareEventManager($this);
		$service->addEventListener(['loadClassMetadata'], 'ipubDoctrinePhone.subscriber');
		$service->addEventListener(['postLoad'], 'ipubDoctrineConsistence.subscriber');
		$service->addEventListener(['loadClassMetadata', 'onFlush'], 'ipubDoctrineTimestampable.subscriber');
		$service->addEventListener(['loadClassMetadata'], 'ipubDoctrineDynamicDiscriminatorMap.subscriber');
		$service->addEventListener(['loadClassMetadata', 'onFlush'], 'fbSimpleAuth.doctrine.subscriber');
		$service->addEventListener(['onFlush', 'postPersist', 'postUpdate'], 'fbAccountsModule.subscribers.entities');
		$service->addEventListener(['prePersist', 'onFlush'], 'fbAccountsModule.subscribers.accountEntity');
		$service->addEventListener(['prePersist', 'onFlush'], 'fbAccountsModule.subscribers.emailEntity');
		$service->addEventListener(['postPersist', 'postUpdate', 'postRemove'], 'fbDevicesModule.subscribers.entities');
		$service->addEventListener(['onFlush'], 'fbTriggersModule.subscribers.notificationEntity');
		$service->addEventListener(['onFlush', 'prePersist', 'postPersist', 'postUpdate'], 'fbTriggersModule.subscribers.entities');
		$service->addEventListener(['postPersist'], 'fbShellyConnector.subscribers.properties');
		$service->addEventListener(['postPersist'], 'fbModbusConnector.subscribers.properties');
		$service->addEventListener(['postPersist'], 'fbTuyaConnector.subscribers.properties');
		return $service;
	}


	public function createServiceNettrineDbal__logger(): Doctrine\DBAL\Logging\LoggerChain
	{
		return new Doctrine\DBAL\Logging\LoggerChain([]);
	}


	public function createServiceNettrineDbalConsole__connectionProvider(): Doctrine\DBAL\Tools\Console\ConnectionProvider\SingleConnectionProvider
	{
		return new Doctrine\DBAL\Tools\Console\ConnectionProvider\SingleConnectionProvider($this->getService('nettrineDbal.connection'));
	}


	public function createServiceNettrineDbalConsole__reservedWordsCommand(): Doctrine\DBAL\Tools\Console\Command\ReservedWordsCommand
	{
		return new Doctrine\DBAL\Tools\Console\Command\ReservedWordsCommand($this->getService('nettrineDbalConsole.connectionProvider'));
	}


	public function createServiceNettrineDbalConsole__runSqlCommand(): Doctrine\DBAL\Tools\Console\Command\RunSqlCommand
	{
		return new Doctrine\DBAL\Tools\Console\Command\RunSqlCommand($this->getService('nettrineDbalConsole.connectionProvider'));
	}


	public function createServiceNettrineFixtures__fixturesLoader(): Nettrine\Fixtures\Loader\FixturesLoader
	{
		$service = new Nettrine\Fixtures\Loader\FixturesLoader(['/srv/fastybird/fixtures'], $this);
		$service->addFixture(\Nette\PhpGenerator\Dumper::createObject(\FastyBird\Connector\Modbus\Fixtures\Connector::class, [
			"\x00*\x00referenceRepository" => null,
		]));
		$service->addFixture(\Nette\PhpGenerator\Dumper::createObject(\FastyBird\Connector\Modbus\Fixtures\ConnectorProperties::class, [
			"\x00*\x00referenceRepository" => null,
		]));
		$service->addFixture(\Nette\PhpGenerator\Dumper::createObject(\FastyBird\Connector\Modbus\Fixtures\Devices::class, [
			"\x00*\x00referenceRepository" => null,
		]));
		$service->addFixture(\Nette\PhpGenerator\Dumper::createObject(\FastyBird\Connector\Modbus\Fixtures\DevicesProperties::class, [
			"\x00*\x00referenceRepository" => null,
		]));
		$service->addFixture(\Nette\PhpGenerator\Dumper::createObject(\FastyBird\Connector\Modbus\Fixtures\Channels::class, [
			"\x00*\x00referenceRepository" => null,
		]));
		$service->addFixture(\Nette\PhpGenerator\Dumper::createObject(\FastyBird\Connector\Modbus\Fixtures\ChannelsProperties::class, [
			"\x00*\x00referenceRepository" => null,
		]));
		return $service;
	}


	public function createServiceNettrineFixtures__loadDataFixturesCommand(): Nettrine\Fixtures\Command\LoadDataFixturesCommand
	{
		return new Nettrine\Fixtures\Command\LoadDataFixturesCommand(
			$this->getService('nettrineFixtures.fixturesLoader'),
			$this->getService('nettrineOrm.managerRegistry'),
		);
	}


	public function createServiceNettrineMigrations__configuration(): Doctrine\Migrations\Configuration\Configuration
	{
		$service = new Doctrine\Migrations\Configuration\Configuration;
		$service->setCustomTemplate(null);
		$service->setMetadataStorageConfiguration($this->getService('nettrineMigrations.configuration.tableStorage'));
		$service->addMigrationsDirectory('Migrations', '/srv/fastybird/migrations');
		return $service;
	}


	public function createServiceNettrineMigrations__configuration__tableStorage(): Doctrine\Migrations\Metadata\Storage\TableMetadataStorageConfiguration
	{
		$service = new Doctrine\Migrations\Metadata\Storage\TableMetadataStorageConfiguration;
		$service->setTableName('doctrine_migrations');
		$service->setVersionColumnName('version');
		return $service;
	}


	public function createServiceNettrineMigrations__currentCommand(): Doctrine\Migrations\Tools\Console\Command\CurrentCommand
	{
		return new Doctrine\Migrations\Tools\Console\Command\CurrentCommand($this->getService('nettrineMigrations.dependencyFactory'));
	}


	public function createServiceNettrineMigrations__dependencyFactory(): Doctrine\Migrations\DependencyFactory
	{
		return $this->getService('nettrineMigrations.nettrineDependencyFactory')->createDependencyFactory();
	}


	public function createServiceNettrineMigrations__diffCommand(): Doctrine\Migrations\Tools\Console\Command\DiffCommand
	{
		return new Doctrine\Migrations\Tools\Console\Command\DiffCommand($this->getService('nettrineMigrations.dependencyFactory'));
	}


	public function createServiceNettrineMigrations__dumpSchemaCommand(): Doctrine\Migrations\Tools\Console\Command\DumpSchemaCommand
	{
		return new Doctrine\Migrations\Tools\Console\Command\DumpSchemaCommand($this->getService('nettrineMigrations.dependencyFactory'));
	}


	public function createServiceNettrineMigrations__executeCommand(): Doctrine\Migrations\Tools\Console\Command\ExecuteCommand
	{
		return new Doctrine\Migrations\Tools\Console\Command\ExecuteCommand($this->getService('nettrineMigrations.dependencyFactory'));
	}


	public function createServiceNettrineMigrations__generateCommand(): Doctrine\Migrations\Tools\Console\Command\GenerateCommand
	{
		return new Doctrine\Migrations\Tools\Console\Command\GenerateCommand($this->getService('nettrineMigrations.dependencyFactory'));
	}


	public function createServiceNettrineMigrations__latestCommand(): Doctrine\Migrations\Tools\Console\Command\LatestCommand
	{
		return new Doctrine\Migrations\Tools\Console\Command\LatestCommand($this->getService('nettrineMigrations.dependencyFactory'));
	}


	public function createServiceNettrineMigrations__listCommand(): Doctrine\Migrations\Tools\Console\Command\ListCommand
	{
		return new Doctrine\Migrations\Tools\Console\Command\ListCommand($this->getService('nettrineMigrations.dependencyFactory'));
	}


	public function createServiceNettrineMigrations__migrateCommand(): Doctrine\Migrations\Tools\Console\Command\MigrateCommand
	{
		return new Doctrine\Migrations\Tools\Console\Command\MigrateCommand($this->getService('nettrineMigrations.dependencyFactory'));
	}


	public function createServiceNettrineMigrations__migrationFactory(): Nettrine\Migrations\Version\DbalMigrationFactory
	{
		return new Nettrine\Migrations\Version\DbalMigrationFactory(
			$this,
			$this->getService('nettrineDbal.connection'),
			$this->getService('contributteMonolog.logger.default'),
		);
	}


	public function createServiceNettrineMigrations__nettrineDependencyFactory(): Nettrine\Migrations\DI\DependencyFactory
	{
		return new Nettrine\Migrations\DI\DependencyFactory(
			$this->getService('nettrineMigrations.configuration'),
			$this->getService('nettrineMigrations.migrationFactory'),
			$this->getService('nettrineDbal.connection'),
			$this->getService('nettrineOrm.entityManagerDecorator'),
			$this->getService('contributteMonolog.logger.default'),
		);
	}


	public function createServiceNettrineMigrations__rollupCommand(): Doctrine\Migrations\Tools\Console\Command\RollupCommand
	{
		return new Doctrine\Migrations\Tools\Console\Command\RollupCommand($this->getService('nettrineMigrations.dependencyFactory'));
	}


	public function createServiceNettrineMigrations__statusCommand(): Doctrine\Migrations\Tools\Console\Command\StatusCommand
	{
		return new Doctrine\Migrations\Tools\Console\Command\StatusCommand($this->getService('nettrineMigrations.dependencyFactory'));
	}


	public function createServiceNettrineMigrations__syncMetadataCommand(): Doctrine\Migrations\Tools\Console\Command\SyncMetadataCommand
	{
		return new Doctrine\Migrations\Tools\Console\Command\SyncMetadataCommand($this->getService('nettrineMigrations.dependencyFactory'));
	}


	public function createServiceNettrineMigrations__upToDateCommand(): Doctrine\Migrations\Tools\Console\Command\UpToDateCommand
	{
		return new Doctrine\Migrations\Tools\Console\Command\UpToDateCommand($this->getService('nettrineMigrations.dependencyFactory'));
	}


	public function createServiceNettrineMigrations__versionCommand(): Doctrine\Migrations\Tools\Console\Command\VersionCommand
	{
		return new Doctrine\Migrations\Tools\Console\Command\VersionCommand($this->getService('nettrineMigrations.dependencyFactory'));
	}


	public function createServiceNettrineOrm__configuration(): Doctrine\ORM\Configuration
	{
		$service = new Doctrine\ORM\Configuration;
		$service->setProxyDir('/srv/fastybird/var/temp/cache/doctrine.proxies');
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
		$service->setSecondLevelCacheEnabled(true);
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
			$this->getService('nettrineDbal.eventManager'),
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
			$this,
		);
	}


	public function createServiceNettrineOrm__mappingDriver(): Doctrine\Persistence\Mapping\Driver\MappingDriverChain
	{
		$service = new Doctrine\Persistence\Mapping\Driver\MappingDriverChain;
		$service->addDriver($this->getService('nettrineOrmAnnotations.annotationDriver'), 'FastyBird\SimpleAuth\Entities');
		$service->addDriver($this->getService('nettrineOrmAnnotations.annotationDriver'), 'FastyBird\Module\Accounts\Entities');
		$service->addDriver($this->getService('nettrineOrmAnnotations.annotationDriver'), 'FastyBird\Module\Devices\Entities');
		$service->addDriver($this->getService('nettrineOrmAnnotations.annotationDriver'), 'FastyBird\Module\Triggers\Entities');
		$service->addDriver($this->getService('nettrineOrmAnnotations.annotationDriver'), 'FastyBird\Connector\FbMqtt\Entities');
		$service->addDriver($this->getService('nettrineOrmAnnotations.annotationDriver'), 'FastyBird\Connector\Shelly\Entities');
		$service->addDriver($this->getService('nettrineOrmAnnotations.annotationDriver'), 'FastyBird\Connector\Modbus\Entities');
		$service->addDriver($this->getService('nettrineOrmAnnotations.annotationDriver'), 'FastyBird\Connector\HomeKit\Entities');
		$service->addDriver($this->getService('nettrineOrmAnnotations.annotationDriver'), 'FastyBird\Connector\Tuya\Entities');
		return $service;
	}


	public function createServiceNettrineOrmAnnotations__annotationDriver(): Doctrine\ORM\Mapping\Driver\AnnotationDriver
	{
		$service = new Doctrine\ORM\Mapping\Driver\AnnotationDriver($this->getService('nettrineAnnotations.reader'));
		$service->addExcludePaths([]);
		$service->addPaths(['/srv/fastybird/vendor/fastybird/simple-auth/src/DI/../Entities']);
		$service->addPaths(['/srv/fastybird/vendor/fastybird/fastybird/src/FastyBird/Module/Accounts/src/DI/../Entities']);
		$service->addPaths(['/srv/fastybird/vendor/fastybird/fastybird/src/FastyBird/Module/Devices/src/DI/../Entities']);
		$service->addPaths(['/srv/fastybird/vendor/fastybird/fastybird/src/FastyBird/Module/Triggers/src/DI/../Entities']);
		$service->addPaths(['/srv/fastybird/vendor/fastybird/fastybird/src/FastyBird/Connector/FbMqtt/src/DI/../Entities']);
		$service->addPaths(['/srv/fastybird/vendor/fastybird/fastybird/src/FastyBird/Connector/Shelly/src/DI/../Entities']);
		$service->addPaths(['/srv/fastybird/vendor/fastybird/fastybird/src/FastyBird/Connector/Modbus/src/DI/../Entities']);
		$service->addPaths(['/srv/fastybird/vendor/fastybird/fastybird/src/FastyBird/Connector/HomeKit/src/DI/../Entities']);
		$service->addPaths(['/srv/fastybird/vendor/fastybird/fastybird/src/FastyBird/Connector/Tuya/src/DI/../Entities']);
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
			$this->getService('nettrineCache.driver'),
		);
	}


	public function createServiceNettrineOrmCache__regions(): Doctrine\ORM\Cache\RegionsConfiguration
	{
		return new Doctrine\ORM\Cache\RegionsConfiguration;
	}


	public function createServiceNettrineOrmConsole__clearMetadataCacheCommand(): Doctrine\ORM\Tools\Console\Command\ClearCache\MetadataCommand
	{
		return new Doctrine\ORM\Tools\Console\Command\ClearCache\MetadataCommand;
	}


	public function createServiceNettrineOrmConsole__convertMappingCommand(): Doctrine\ORM\Tools\Console\Command\ConvertMappingCommand
	{
		return new Doctrine\ORM\Tools\Console\Command\ConvertMappingCommand;
	}


	public function createServiceNettrineOrmConsole__ensureProductionSettingsCommand(): Doctrine\ORM\Tools\Console\Command\EnsureProductionSettingsCommand
	{
		return new Doctrine\ORM\Tools\Console\Command\EnsureProductionSettingsCommand;
	}


	public function createServiceNettrineOrmConsole__entityManagerHelper(): Doctrine\ORM\Tools\Console\Helper\EntityManagerHelper
	{
		return new Doctrine\ORM\Tools\Console\Helper\EntityManagerHelper($this->getService('nettrineOrm.entityManagerDecorator'));
	}


	public function createServiceNettrineOrmConsole__generateEntitiesCommand(): Doctrine\ORM\Tools\Console\Command\GenerateEntitiesCommand
	{
		return new Doctrine\ORM\Tools\Console\Command\GenerateEntitiesCommand;
	}


	public function createServiceNettrineOrmConsole__generateProxiesCommand(): Doctrine\ORM\Tools\Console\Command\GenerateProxiesCommand
	{
		return new Doctrine\ORM\Tools\Console\Command\GenerateProxiesCommand;
	}


	public function createServiceNettrineOrmConsole__generateRepositoriesCommand(): Doctrine\ORM\Tools\Console\Command\GenerateRepositoriesCommand
	{
		return new Doctrine\ORM\Tools\Console\Command\GenerateRepositoriesCommand;
	}


	public function createServiceNettrineOrmConsole__infoCommand(): Doctrine\ORM\Tools\Console\Command\InfoCommand
	{
		return new Doctrine\ORM\Tools\Console\Command\InfoCommand;
	}


	public function createServiceNettrineOrmConsole__mappingDescribeCommand(): Doctrine\ORM\Tools\Console\Command\MappingDescribeCommand
	{
		return new Doctrine\ORM\Tools\Console\Command\MappingDescribeCommand;
	}


	public function createServiceNettrineOrmConsole__runDqlCommand(): Doctrine\ORM\Tools\Console\Command\RunDqlCommand
	{
		return new Doctrine\ORM\Tools\Console\Command\RunDqlCommand;
	}


	public function createServiceNettrineOrmConsole__schemaToolCreateCommand(): Doctrine\ORM\Tools\Console\Command\SchemaTool\CreateCommand
	{
		return new Doctrine\ORM\Tools\Console\Command\SchemaTool\CreateCommand;
	}


	public function createServiceNettrineOrmConsole__schemaToolDropCommand(): Doctrine\ORM\Tools\Console\Command\SchemaTool\DropCommand
	{
		return new Doctrine\ORM\Tools\Console\Command\SchemaTool\DropCommand;
	}


	public function createServiceNettrineOrmConsole__schemaToolUpdateCommand(): Doctrine\ORM\Tools\Console\Command\SchemaTool\UpdateCommand
	{
		return new Doctrine\ORM\Tools\Console\Command\SchemaTool\UpdateCommand;
	}


	public function createServiceNettrineOrmConsole__validateSchemaCommand(): Doctrine\ORM\Tools\Console\Command\ValidateSchemaCommand
	{
		return new Doctrine\ORM\Tools\Console\Command\ValidateSchemaCommand;
	}


	public function createServiceSchema__validator(): FastyBird\Library\Metadata\Schemas\Validator
	{
		return new FastyBird\Library\Metadata\Schemas\Validator;
	}


	public function createServiceSecurity__user(): FastyBird\Module\Accounts\Security\User
	{
		return new FastyBird\Module\Accounts\Security\User(
			$this->getService('fbSimpleAuth.security.userStorage'),
			$this->getService('fbAccountsModule.security.authenticator'),
		);
	}


	public function createServiceSession__session(): Nette\Http\Session
	{
		$service = new Nette\Http\Session($this->getService('http.request'), $this->getService('http.response'));
		$service->setOptions(['readAndClose' => null, 'cookieSamesite' => 'Lax']);
		return $service;
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
		// di.
		(function () {
			$this->getService('tracy.bar')->addPanel(new Nette\Bridges\DITracy\ContainerPanel($this));
		})();
		// tracy.
		(function () {
			if (!Tracy\Debugger::isEnabled()) { return; }
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
