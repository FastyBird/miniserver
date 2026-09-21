<?php declare(strict_types = 1);

/**
 * Phone.php
 *
 * @copyright      More in LICENSE.md
 * @license        http://www.ipublikuj.eu
 * @author         Adam Kadlec <adam.kadlec@ipublikuj.eu>
 * @package        iPublikuj:Phone!
 * @subpackage     Entities
 * @since          1.0.1
 *
 * @date           17.12.15
 */

namespace FastyBird\Core\Entities\Phone;

use FastyBird\Core\Exceptions;
use FastyBird\Core\Services\Phone\Phone as PhoneHelper;
use libphonenumber;
use libphonenumber\PhoneNumberFormat;
use Nette;
use function ctype_alpha;
use function in_array;
use function sprintf;
use function strlen;
use function strtoupper;

/**
 * Phone number entity
 *
 * @package        iPublikuj:Phone!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@ipublikuj.eu>
 */
class Phone
{

	use Nette\SmartObject;

	/**
	 * The country code
	 */
	protected int|null $countryCode = null;

	/**
	 * The national number
	 */
	protected string|null $nationalNumber = null;

	/**
	 * The international number
	 */
	protected string|null $internationalNumber = null;

	/**
	 * The extension
	 */
	protected string|null $extension = null;

	/**
	 * Whether this phone number uses an italian leading zero
	 */
	protected bool $italianLeadingZero = false;

	/**
	 * The number of leading zeros of this phone number
	 */
	protected int|null $numberOfLeadingZeros;

	/**
	 * The raw input
	 */
	protected string|null $rawOutput = null;

	/**
	 * The RFC3966 number format
	 */
	protected string|null $rfcFormat = null;

	/**
	 * Phone number type
	 */
	protected string $type;

	/**
	 * Carrier name
	 */
	protected string|null $carrier;

	/**
	 * Country name
	 */
	protected string|null $country;

	/**
	 * List of time zones
	 *
	 * @var array<string>
	 */
	protected array $timeZones = [];

	public function __construct(
		string $rawInput,
		string $rfcFormat,
		string $nationalNumber,
		string $internationalNumber,
		int|null $countryCode,
		string|null $country,
		string $type,
		string|null $carrierName = null,
	)
	{
		$this->rawOutput = $rawInput;
		$this->rfcFormat = $rfcFormat;

		$this->nationalNumber = $nationalNumber;
		$this->internationalNumber = $internationalNumber;

		$this->countryCode = $countryCode;
		$this->country = $country;

		$this->type = $type;

		$this->carrier = ($carrierName !== '' && $carrierName !== null) ? $carrierName : null;
	}

	/**
	 * @throws Exceptions\NoValidCountry
	 * @throws Exceptions\NoValidPhone
	 */
	public static function fromNumber(string $number, string $country = 'AUTO'): self
	{
		$phoneNumberUtil = libphonenumber\PhoneNumberUtil::getInstance();
		$carrierMapper = libphonenumber\PhoneNumberToCarrierMapper::getInstance();
		$timeZonesMapper = libphonenumber\PhoneNumberToTimeZonesMapper::getInstance();

		// Country code have to be upper-cased
		$country = strtoupper($country);

		// Correct auto or null value
		if ($country === 'AUTO') {
			$country = 'AUTO';

		} elseif (strlen($country) !== 2 || ctype_alpha($country) === false || !in_array(
			$country,
			$phoneNumberUtil->getSupportedRegions(),
			true,
		)) {
			throw new Exceptions\NoValidCountry(
				sprintf(
					'Provided country code "%s" is not valid. Provide valid country code or AUTO for automatic detection.',
					$country,
				),
			);
		}

		try {
			// Parse string into phone number
			$parsed = $phoneNumberUtil->parse($number, $country);

			// Check if number is valid
			if (
				(
					$country === 'AUTO'
					&& $phoneNumberUtil->isValidNumber($parsed) === false
				)
				|| (
					$country !== 'AUTO'
					&& $phoneNumberUtil->isValidNumberForRegion(
						$parsed,
						$country,
					) === false
				)
			) {
				throw new Exceptions\NoValidPhone(
					sprintf(
						'Provided phone number "%s" is not valid phone number. Provide valid phone number.',
						$number,
					),
				);
			}
		} catch (libphonenumber\NumberParseException $ex) {
			switch ($ex->getErrorType()) {
				case libphonenumber\NumberParseException::INVALID_COUNTRY_CODE:
					throw new Exceptions\NoValidCountry('Missing or invalid country.');
				case libphonenumber\NumberParseException::NOT_A_NUMBER:
					throw new Exceptions\NoValidPhone(
						'The string supplied did not seem to be a phone number.',
					);
				case libphonenumber\NumberParseException::TOO_SHORT_AFTER_IDD:
					throw new Exceptions\NoValidPhone(
						'Phone number had an IDD, but after this was not long enough to be a viable phone number.',
					);
				case libphonenumber\NumberParseException::TOO_SHORT_NSN:
					throw new Exceptions\NoValidPhone(
						'The string supplied is too short to be a phone number.',
					);
				case libphonenumber\NumberParseException::TOO_LONG:
					throw new Exceptions\NoValidPhone(
						'The string supplied was too long to parse into phone number.',
					);
				default:
					throw new Exceptions\NoValidPhone(
						sprintf(
							'Provided phone number "%s" is not valid phone number. Provide valid phone number.',
							$number,
						),
					);
			}
		}

		switch ($phoneNumberUtil->getNumberType($parsed)) {
			case libphonenumber\PhoneNumberType::MOBILE:
				$numberType = PhoneHelper::TYPE_MOBILE;

				break;
			case libphonenumber\PhoneNumberType::FIXED_LINE:
				$numberType = PhoneHelper::TYPE_FIXED_LINE;

				break;
			case libphonenumber\PhoneNumberType::FIXED_LINE_OR_MOBILE:
				$numberType = PhoneHelper::TYPE_FIXED_LINE_OR_MOBILE;

				break;
			case libphonenumber\PhoneNumberType::VOIP:
				$numberType = PhoneHelper::TYPE_VOIP;

				break;
			case libphonenumber\PhoneNumberType::PAGER:
				$numberType = PhoneHelper::TYPE_PAGER;

				break;
			case libphonenumber\PhoneNumberType::EMERGENCY:
				$numberType = PhoneHelper::TYPE_EMERGENCY;

				break;
			case libphonenumber\PhoneNumberType::VOICEMAIL:
				$numberType = PhoneHelper::TYPE_VOICEMAIL;

				break;
			default:
				$numberType = PhoneHelper::TYPE_UNKNOWN;

				break;
		}

		$entity = new self(
			$phoneNumberUtil->format($parsed, PhoneNumberFormat::E164),
			$phoneNumberUtil->format($parsed, PhoneNumberFormat::RFC3966),
			$phoneNumberUtil->format($parsed, PhoneNumberFormat::NATIONAL),
			$phoneNumberUtil->format($parsed, PhoneNumberFormat::INTERNATIONAL),
			$parsed->getCountryCode(),
			$phoneNumberUtil->getRegionCodeForNumber($parsed),
			$numberType,
			$carrierMapper->getNameForNumber($parsed, 'en'),
		);

		$entity->setItalianLeadingZero($parsed->hasItalianLeadingZero());

		$entity->setTimeZones($timeZonesMapper->getTimeZonesForNumber($parsed));

		if ($parsed->hasExtension() && $parsed->getExtension() !== null) {
			$entity->setExtension($parsed->getExtension());
		}

		if ($parsed->hasNumberOfLeadingZeros()) {
			$entity->setNumberOfLeadingZeros($parsed->getNumberOfLeadingZeros());
		}

		return $entity;
	}

	public function getCountryCode(): int|null
	{
		return $this->countryCode;
	}

	public function getNationalNumber(): string|null
	{
		return $this->nationalNumber;
	}

	public function getInternationalNumber(): string|null
	{
		return $this->internationalNumber;
	}

	public function setExtension(string $extension): void
	{
		$this->extension = $extension;
	}

	public function getExtension(): string|null
	{
		return $this->extension;
	}

	public function setItalianLeadingZero(bool $italianLeadingZero): void
	{
		$this->italianLeadingZero = $italianLeadingZero;
	}

	public function getItalianLeadingZero(): bool
	{
		return $this->italianLeadingZero;
	}

	public function setNumberOfLeadingZeros(int $numberOfLeadingZeros): void
	{
		$this->numberOfLeadingZeros = $numberOfLeadingZeros;
	}

	public function getNumberOfLeadingZeros(): int|null
	{
		return $this->numberOfLeadingZeros;
	}

	public function getRawOutput(): string|null
	{
		return $this->rawOutput;
	}

	public function getRfcFormat(): string|null
	{
		return $this->rfcFormat;
	}

	public function getType(): string
	{
		return $this->type;
	}

	public function getCarrier(): string|null
	{
		return $this->carrier;
	}

	public function getCountry(): string|null
	{
		return $this->country;
	}

	/**
	 * @param array<string> $timeZones
	 */
	public function setTimeZones(array $timeZones): void
	{
		$this->timeZones = $timeZones;
	}

	/**
	 * @return array<string>
	 */
	public function getTimeZones(): array
	{
		return $this->timeZones;
	}

	public function isInTimeZone(string $timeZone): bool
	{
		return in_array($timeZone, $this->timeZones, true);
	}

	public function __toString(): string
	{
		return (string) $this->rawOutput;
	}

}
