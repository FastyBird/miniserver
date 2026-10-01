<?php declare(strict_types = 1);

namespace FastyBird\Core\Phone\DI;

use FastyBird\Core\Phone\Services;
use FastyBird\Core\Phone\Subscribers;
use FastyBird\Core\Phone\Types;
use libphonenumber;
use Nette\DI;
use Nette\PhpGenerator;
use Override;

/**
 * Phone number handling: libphonenumber, the phone helper, the Doctrine subscriber and the
 * `phone` DBAL type
 *
 * A child of the composite FastyBird\Core\DI\CoreExtension, which owns and runs it; it is never
 * registered with the compiler itself. It runs as fbCore.phone, so its services are
 * fbCore.phone.*. It has no configuration.
 */
final class PhoneExtension extends DI\CompilerExtension
{

	#[Override]
	public function loadConfiguration(): void
	{
		$builder = $this->getContainerBuilder();

		$builder->addDefinition($this->prefix('libphone.utils'))
			->setType(libphonenumber\PhoneNumberUtil::class)
			->setFactory('libphonenumber\PhoneNumberUtil::getInstance');

		$builder->addDefinition($this->prefix('libphone.geoCoder'))
			->setType(libphonenumber\geocoding\PhoneNumberOfflineGeocoder::class)
			->setFactory('libphonenumber\geocoding\PhoneNumberOfflineGeocoder::getInstance');

		$builder->addDefinition($this->prefix('libphone.shortNumber'))
			->setType(libphonenumber\ShortNumberInfo::class)
			->setFactory('libphonenumber\ShortNumberInfo::getInstance');

		$builder->addDefinition($this->prefix('libphone.mapper.carrier'))
			->setType(libphonenumber\PhoneNumberToCarrierMapper::class)
			->setFactory('libphonenumber\PhoneNumberToCarrierMapper::getInstance');

		$builder->addDefinition($this->prefix('libphone.mapper.timezone'))
			->setType(libphonenumber\PhoneNumberToTimeZonesMapper::class)
			->setFactory('libphonenumber\PhoneNumberToTimeZonesMapper::getInstance');

		$builder->addDefinition($this->prefix('helper'))
			->setType(Services\PhoneNumberHelper::class);

		// Defining the subscriber is all it takes. nettrine/orm's EventPass finds every service
		// typed Doctrine\Common\EventSubscriber and subscribes it on its ContainerEventManager by
		// service name. Until #564 a beforeCompile() here also called the entity manager's
		// addEventSubscriber() for it, which subscribed the same object a second time, so
		// PhoneObjectSubscriber ran twice on loadClassMetadata. DoctrineSubscriptionTest pins one
		// entry per subscriber.
		$builder->addDefinition($this->prefix('doctrine.subscriber'))
			->setType(Subscribers\PhoneObjectSubscriber::class);
	}

	#[Override]
	public function afterCompile(PhpGenerator\ClassType $class): void
	{
		parent::afterCompile($class);

		// Preserved from DoctrinePhoneExtension::afterCompile() -- registers the 'phone' DBAL
		// type. Entities map columns to it by name (Module/Triggers Entities\Notifications\Sms),
		// so without this every test that loads the Triggers metadata fails. It is written into
		// initialize() directly rather than through getInitialization(), which would wrap it in a
		// closure and change the generated code.
		$initialize = $class->getMethod('initialize');
		$initialize->addBody(
			'if (!Doctrine\DBAL\Types\Type::hasType(\'' . Types\PhoneType::PHONE . '\')) {'
			. ' Doctrine\DBAL\Types\Type::addType('
			. '\'' . Types\PhoneType::PHONE . '\', \'' . Types\PhoneType::class . '\''
			. '); }',
		);
	}

}
