<?php declare(strict_types = 1);

namespace FastyBird\Core\Tests\Cases\Unit\Security;

use FastyBird\Core\Configuration;
use LogicException;
use Nette\Application;
use Nette\Http;
use PHPUnit\Framework\TestCase;

/**
 * The redirect and home URLs Security\Presenters\HasAuthorization sends a refused request to,
 * as Core's root Configuration builds them today (census T10, T12-19). E5.8 (#640) splits
 * Configuration and types its children; the URLs must come out the same, through the same Nette
 * link generator. A real generator over Nette's simple router, so the URLs are what Nette makes
 * of each destination.
 */
final class AuthorizationRedirectTest extends TestCase
{

	/**
	 * @throws Application\UI\InvalidLinkException
	 */
	public function testTheRedirectUrlLinksTheSignInDestinationWithItsParameters(): void
	{
		self::assertSame(
			'http://localhost/?backlink=e5key&action=in&presenter=Accounts%3ASign',
			$this->configuration('Accounts:Sign:in')->getRedirectUrl(['backlink' => 'e5key']),
		);
	}

	/**
	 * @throws Application\UI\InvalidLinkException
	 */
	public function testThereIsNoRedirectUrlWithoutASignInDestination(): void
	{
		self::assertNull($this->configuration(null)->getRedirectUrl(['backlink' => 'e5key']));
	}

	/**
	 * @throws Application\UI\InvalidLinkException
	 */
	public function testTheHomeUrlLinksTheHomeDestination(): void
	{
		self::assertSame(
			'http://localhost/?action=default&presenter=App%3ADefault',
			$this->configuration(null, 'App:Default:default')->getHomeUrl(),
		);
	}

	/**
	 * Configuration's own default home destination, `/`, is not a destination Nette can link.
	 *
	 * @throws Application\UI\InvalidLinkException
	 */
	public function testTheDefaultHomeDestinationCannotBeLinked(): void
	{
		$this->expectException(LogicException::class);
		$this->expectExceptionMessage("Presenter must be specified in '/'.");

		$this->configuration(null)->getHomeUrl();
	}

	private function configuration(string|null $signInUrl, string|null $homeUrl = null): Configuration
	{
		$linkGenerator = new Application\LinkGenerator(
			new Application\Routers\SimpleRouter(),
			new Http\UrlScript('http://localhost/'),
		);

		return $homeUrl === null
			? new Configuration(
				$linkGenerator,
				'com.fastybird.e5',
				'e5-signature',
				true,
				true,
				true,
				true,
				applicationSignInUrl: $signInUrl,
			)
			: new Configuration(
				$linkGenerator,
				'com.fastybird.e5',
				'e5-signature',
				true,
				true,
				true,
				true,
				applicationSignInUrl: $signInUrl,
				applicationHomeUrl: $homeUrl,
			);
	}

}
