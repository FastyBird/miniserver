<?php declare(strict_types = 1);

namespace FastyBird\Core\Configuration;

use Nette;
use Nette\Application;

/**
 * Application configuration storage.
 * Stores the authentication and entity timestamping settings
 */
class Configuration
{

	use Nette\SmartObject;

	public function __construct(
		private readonly Application\LinkGenerator $linkGenerator,
		private readonly string $tokenIssuer,
		private readonly string $tokenSignature,
		private readonly bool $enableMiddleware,
		private readonly bool $enableDoctrineMapping,
		private readonly bool $enableDoctrineModels,
		private readonly bool $enableNetteApplication,
		public readonly bool $lazyAssociation = false,
		public readonly bool $autoMapField = false,
		public readonly string $dbFieldType = 'datetime_immutable',
		private readonly string|null $applicationSignInUrl = null,
		private readonly string $applicationHomeUrl = '/',
	)
	{
	}

	// SIMPLE AUTH

	public function getTokenIssuer(): string
	{
		return $this->tokenIssuer;
	}

	public function getTokenSignature(): string
	{
		return $this->tokenSignature;
	}

	public function isEnableMiddleware(): bool
	{
		return $this->enableMiddleware;
	}

	public function isEnableDoctrineMapping(): bool
	{
		return $this->enableDoctrineMapping;
	}

	public function isEnableDoctrineModels(): bool
	{
		return $this->enableDoctrineModels;
	}

	public function isEnableNetteApplication(): bool
	{
		return $this->enableNetteApplication;
	}

	/**
	 * Build the URL for redirection if is set
	 *
	 * @param array<mixed> $params
	 *
	 * @throws Application\UI\InvalidLinkException
	 */
	public function getRedirectUrl(array $params = []): string|null
	{
		if ($this->applicationSignInUrl !== null) {
			return $this->linkGenerator->link($this->applicationSignInUrl, $params);
		}

		return null;
	}

	/**
	 * Build the URL for redirection to homepage
	 *
	 * @param array<mixed> $params
	 *
	 * @throws Application\UI\InvalidLinkException
	 */
	public function getHomeUrl(array $params = []): string|null
	{
		return $this->linkGenerator->link($this->applicationHomeUrl, $params);
	}

	// DOCTRINE TIMESTAMPABLE

	public function autoMapField(): bool
	{
		return $this->autoMapField === true;
	}

	public function useLazyAssociation(): bool
	{
		return $this->lazyAssociation === true;
	}

}
