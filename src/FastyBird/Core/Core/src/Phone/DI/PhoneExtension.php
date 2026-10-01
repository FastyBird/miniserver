<?php declare(strict_types = 1);

namespace FastyBird\Core\Phone\DI;

use Doctrine;
use FastyBird\Core\Phone\Services;
use FastyBird\Core\Phone\Subscribers;
use FastyBird\Core\Phone\Types;
use libphonenumber;
use Nette\DI;
use Nette\PhpGenerator;
use Override;
use function assert;

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

		$builder->addDefinition($this->prefix('doctrine.subscriber'))
			->setType(Subscribers\PhoneObjectSubscriber::class);
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

		// KNOWN DEFECT, kept verbatim on purpose (#564, D2 of the Epic #459 census): nettrine's
		// EventPass already subscribes this service by name, so this second subscription makes
		// PhoneObjectSubscriber run twice on loadClassMetadata. KnownDefectDoubleDoctrineSubscriptionTest
		// pins it. The lookup does not throw: several fbCore containers wire no Doctrine ORM at
		// all, and they get no subscription.
		$emServiceName = $builder->getByType(Doctrine\ORM\EntityManagerInterface::class);

		if ($emServiceName !== null) {
			$emService = $builder->getDefinition($emServiceName);
			assert($emService instanceof DI\Definitions\ServiceDefinition);
			$emService->addSetup('?->getEventManager()->addEventSubscriber(?)', [
				'@self',
				$builder->getDefinition($this->prefix('doctrine.subscriber')),
			]);
		}
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
