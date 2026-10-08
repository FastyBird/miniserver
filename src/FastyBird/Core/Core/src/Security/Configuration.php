<?php declare(strict_types = 1);

namespace FastyBird\Core\Security;

use Nette\Application;

/**
 * The token and application settings of the fbCore > security section, registered by
 * SecurityExtension as fbCore.security.configuration
 */
final readonly class Configuration
{

	public function __construct(
		private readonly Application\LinkGenerator $linkGenerator,
		private readonly string $tokenIssuer,
		private readonly string $tokenSignature,
		private readonly bool $enableMiddleware,
		private readonly bool $enableDoctrineMapping,
		private readonly bool $enableDoctrineModels,
		private readonly bool $enableNetteApplication,
		private readonly string|null $applicationSignInUrl = null,
		private readonly string $applicationHomeUrl = '/',
	)
	{
	}

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
	public function getHomeUrl(array $params = []): string
	{
		return $this->linkGenerator->link($this->applicationHomeUrl, $params);
	}

}
