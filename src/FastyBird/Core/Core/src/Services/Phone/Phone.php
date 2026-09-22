<?php declare(strict_types = 1);

namespace FastyBird\Core\Services\Phone;

use FastyBird\Core\Entities\Phone as Entities;
use FastyBird\Core\Exceptions;
use FastyBird\Core\Exceptions\InvalidArgument;
use libphonenumber;
use libphonenumber\PhoneNumberFormat;
use Nette;
use Nette\Localization;
use function constant;
use function ctype_alpha;
use function defined;
use function in_array;
use function method_exists;
use function sprintf;
use function strlen;
use function strtoupper;

/**
 * Phone number helpers
 */
final class Phone
{

	use Nette\SmartObject;

	// Define phone number types
	public const string TYPE_FIXED_LINE = 'FIXED_LINE';

	public const string TYPE_MOBILE = 'MOBILE';

	public const string TYPE_FIXED_LINE_OR_MOBILE = 'FIXED_LINE_OR_MOBILE';

	public const string TYPE_VOIP = 'VOIP';

	public const string TYPE_PAGER = 'PAGER';

	public const string TYPE_EMERGENCY = 'EMERGENCY';

	public const string TYPE_VOICEMAIL = 'VOICEMAIL';

	public const string TYPE_UNKNOWN = 'UNKNOWN';

	public const int FORMAT_E164 = PhoneNumberFormat::E164;

	public const int FORMAT_INTERNATIONAL = PhoneNumberFormat::INTERNATIONAL;

	public const int FORMAT_NATIONAL = PhoneNumberFormat::NATIONAL;

	public const int FORMAT_RFC3966 = PhoneNumberFormat::RFC3966;

	public function __construct(
		private libphonenumber\PhoneNumberUtil $phoneNumberUtil,
		private libphonenumber\geocoding\PhoneNumberOfflineGeocoder $phoneNumberGeocoder,
		private libphonenumber\PhoneNumberToCarrierMapper $carrierMapper,
		private libphonenumber\PhoneNumberToTimeZonesMapper $timeZonesMapper,
		private Localization\ITranslator|null $translator = null,
	)
	{
		// Lib phone library utils

		// Nette utils
	}

	/**
	 * @throws Exceptions\NoValidCountry
	 * @throws Exceptions\NoValidPhone
	 */
	public function parse(
		string $number,
		string $country = 'AUTO',
	): Entities\Phone
	{
		// Parse string into phone number
		return Entities\Phone::fromNumber($number, $country);
	}

	/**
	 * @throws Exceptions\NoValidCountry
	 * @throws Exceptions\NoValidType
	 */
	public function isValid(
		string $number,
		string $country = 'AUTO',
		string|null $type = null,
	): bool
	{
		// Check if country is valid
		$country = $this->validateCountry($country);

		// Check if phone type is valid
		$type = $type !== null ? $this->validateType($type) : null;

		try {
			// Parse string into phone number
			$phoneNumber = $this->phoneNumberUtil->parse($number, $country);

			if ($type !== null && $this->phoneNumberUtil->getNumberType($phoneNumber) !== $type) {
				return false;
			}

			// Automatic detection:
			if ($country === 'AUTO') {
				// Validate if the international phone number is valid for its contained country
				return $this->phoneNumberUtil->isValidNumber($phoneNumber);
			}

			// Validate number against the specified country
			return $this->phoneNumberUtil->isValidNumberForRegion($phoneNumber, $country);
		} catch (libphonenumber\NumberParseException) {
			return false;
		}
	}

	/**
	 * @throws Exceptions\NoValidCountry
	 * @throws Exceptions\NoValidPhone
	 */
	public function format(
		string $number,
		string $country = 'AUTO',
		int $format = self::FORMAT_INTERNATIONAL,
	): string|null
	{
		// Create phone entity
		$entity = Entities\Phone::fromNumber($number, $country);

		switch ($format) {
			case self::FORMAT_INTERNATIONAL:
				return $entity->getInternationalNumber();
			case self::FORMAT_NATIONAL:
				return $entity->getNationalNumber();
			case self::FORMAT_E164:
				return $entity->getRawOutput();
			case self::FORMAT_RFC3966:
				return $entity->getRfcFormat();
			default:
				throw new InvalidArgument(
					'Invalid number format given, provide valid phone number format.',
				);
		}
	}

	/**
	 * @throws Exceptions\NoValidCountry
	 * @throws Exceptions\NoValidPhone
	 * @throws Exceptions\NoValidType
	 * @throws libphonenumber\NumberParseException
	 */
	public function getLocation(
		string $number,
		string $country = 'AUTO',
		string|null $locale = null,
		string|null $userCountry = null,
	): string
	{
		if ($this->isValid($number, $country)) {
			$country = strtoupper($country);

			if ($userCountry !== null) {
				// Check for valid user country
				$userCountry = $this->validateCountry($userCountry);
			}

			// Parse phone number
			$parsed = $this->phoneNumberUtil->parse($number, $country);

			// Determine locale
			$locale = $locale === null && $this->translator !== null && method_exists(
				$this->translator,
				'getLocale',
			)
				? $this->translator->getLocale()
				: 'en_US';

			// Get phone number location
			return $this->phoneNumberGeocoder->getDescriptionForNumber($parsed, $locale, $userCountry);
		} else {
			throw new Exceptions\NoValidPhone(
				sprintf('Provided phone number "%s" is not valid phone number. Provide valid phone number.', $number),
			);
		}
	}

	/**
	 * @throws Exceptions\NoValidCountry
	 * @throws Exceptions\NoValidPhone
	 */
	public function getCarrier(
		string $number,
		string $country = 'AUTO',
	): string|null
	{
		// Create phone entity
		$entity = Entities\Phone::fromNumber($number, $country);

		// Extract carrier name from given phone number
		return $entity->getCarrier();
	}

	/**
	 * @return array<string>
	 *
	 * @throws Exceptions\NoValidCountry
	 * @throws Exceptions\NoValidPhone
	 */
	public function getTimeZones(
		string $number,
		string $country = 'AUTO',
	): array
	{
		// Create phone entity
		$entity = Entities\Phone::fromNumber($number, $country);

		// Extract carrier name from given phone number
		return $entity->getTimeZones();
	}

	/**
	 * Get list of library supported countries
	 *
	 * @return array<string>
	 */
	public function getSupportedCountries(): array
	{
		return $this->phoneNumberUtil->getSupportedRegions();
	}

	/**
	 * Get dialing country code for provided country
	 *
	 * @throws Exceptions\NoValidCountry
	 */
	public function getCountryCodeForCountry(string $country): int
	{
		// Check if country is valid
		$country = $this->validateCountry($country);

		// Transform country to country code
		$code = $this->phoneNumberUtil->getCountryCodeForRegion($country);

		if ($code !== 0) {
			return $code;
		} else {
			throw new Exceptions\NoValidCountry(
				sprintf('Provided country code "%s" is not valid. Provide valid country code.', $country),
			);
		}
	}

	/**
	 * Get example country national number
	 *
	 * @throws Exceptions\NoValidCountry
	 * @throws Exceptions\NoValidType
	 */
	public function getExampleNationalNumber(
		string $country,
		string $type = self::TYPE_FIXED_LINE,
	): string
	{
		return $this->getExampleNumber($country, PhoneNumberFormat::NATIONAL, $type);
	}

	/**
	 * Get example country international number
	 *
	 * @throws Exceptions\NoValidCountry
	 * @throws Exceptions\NoValidType
	 */
	public function getExampleInternationalNumber(
		string $country,
		string $type = self::TYPE_FIXED_LINE,
	): string
	{
		return $this->getExampleNumber($country, PhoneNumberFormat::INTERNATIONAL, $type);
	}

	/**
	 * @throws Exceptions\NoValidCountry
	 * @throws Exceptions\NoValidType
	 */
	private function getExampleNumber(
		string $country,
		int $format,
		string $type = self::TYPE_FIXED_LINE,
	): string
	{
		// Check if country is valid
		$country = $this->validateCountry($country);

		// Check if phone type is valid
		$type = $this->validateType($type);

		// Create example number
		$number = $this->phoneNumberUtil->getExampleNumberForType($country, $type);

		if ($number !== null) {
			return $this->phoneNumberUtil->format($number, $format);
		}

		throw new InvalidArgument('Provided values could not build example number');
	}

	/**
	 * @throws Exceptions\NoValidCountry
	 */
	private function validateCountry(string $country): string
	{
		// Country code have to be upper-cased
		$country = strtoupper($country);

		// Correct auto or null value
		if ($country === 'AUTO') {
			return 'AUTO';
		} elseif (
			strlen($country) === 2
			&& ctype_alpha($country)
			&& in_array($country, $this->phoneNumberUtil->getSupportedRegions(), true)
		) {
			return $country;
		} else {
			throw new Exceptions\NoValidCountry(
				'Provided country code "' . $country . '" is not valid. Provide valid country code or AUTO for automatic detection.',
			);
		}
	}

	/**
	 * @throws Exceptions\NoValidType
	 */
	private function validateType(string $type): int
	{
		$constant = $this->constructPhoneTypeConstant($type);

		if (defined($constant) && in_array($type, [
			self::TYPE_FIXED_LINE,
			self::TYPE_MOBILE,
			self::TYPE_VOIP,
			self::TYPE_PAGER,
			self::TYPE_EMERGENCY,
			self::TYPE_VOICEMAIL,
		], true)) {
			return constant($constant);
		} else {
			throw new Exceptions\NoValidType('Provide valid phone number type.');
		}
	}

	/**
	 * Constructs the corresponding namespaced class constant for a phone number type
	 */
	private function constructPhoneTypeConstant(string $type): string
	{
		return '\libphonenumber\PhoneNumberType::' . $type;
	}

}
