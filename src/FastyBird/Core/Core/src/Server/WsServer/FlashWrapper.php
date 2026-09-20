<?php declare(strict_types = 1);

namespace FastyBird\Core\Server\WsServer;

use FastyBird\Core\Entities\WsServer as Entities;
use FastyBird\Core\Exceptions;
use SimpleXMLElement;
use Throwable;
use function in_array;
use function preg_match;

final class FlashWrapper implements IWrapper
{

	/**
	 * Contains the root policy node
	 */
	private string $policy = '<?xml version="1.0"?>'
		. '<!DOCTYPE cross-domain-policy SYSTEM "http://www.adobe.com/xml/dtds/cross-domain-policy.dtd">'
		. '<cross-domain-policy></cross-domain-policy>';

	/**
	 * Stores an array of allowed domains and their ports
	 */
	private array $access = [];

	private string $siteControl = '';

	private string $cache = '';

	private bool $cacheValid = false;

	/**
	 * Add a domain to an allowed access list.
	 *
	 * @param string $domain Specifies a requesting domain to be granted access. Both named domains and IP
	 *                       addresses are acceptable values. Subdomains are considered different domains. A wildcard (*) can
	 *                       be used to match all domains when used alone, or multiple domains (subdomains) when used as a
	 *                       prefix for an explicit, second-level domain name separated with a dot (.)
	 * @param string $ports  A comma-separated list of ports or range of ports that a socket connection
	 *                       is allowed to connect to. A range of ports is specified through a dash (-) between two port numbers.
	 *                       Ranges can be used with individual ports when separated with a comma. A single wildcard (*) can
	 *                       be used to allow all ports.
	 *
	 * @throws Exceptions\UnexpectedValue
	 */
	public function addAllowedAccess(string $domain, string $ports = '*', bool $secure = false): void
	{
		if (!$this->validateDomain($domain)) {
			throw new Exceptions\UnexpectedValue('Invalid domain');
		}

		if (!$this->validatePorts($ports)) {
			throw new Exceptions\UnexpectedValue('Invalid Port');
		}

		$this->access[] = [$domain, $ports, $secure];
		$this->cacheValid = false;
	}

	/**
	 * Removes all domains from the allowed access list
	 */
	public function clearAllowedAccess(): void
	{
		$this->access = [];
		$this->cacheValid = false;
	}

	/**
	 * site-control defines the meta-policy for the current domain. A meta-policy specifies acceptable
	 * domain policy files other than the master policy file located in the target domain's root and named
	 * crossdomain.xml.
	 *
	 * @throws Exceptions\UnexpectedValue
	 */
	public function setSiteControl(string $permittedCrossDomainPolicies = 'all'): void
	{
		if (!$this->validateSiteControl($permittedCrossDomainPolicies)) {
			throw new Exceptions\UnexpectedValue('Invalid site control set');
		}

		$this->siteControl = $permittedCrossDomainPolicies;
		$this->cacheValid = false;
	}

	public function handleOpen(Entities\IClient $client): void
	{
		// The Flash policy file is served entirely from handleMessage()
	}

	public function handleMessage(Entities\IClient $client, string $message): void
	{
		if (!$this->cacheValid) {
			$this->cache = $this->renderPolicy()->asXML();
			$this->cacheValid = true;
		}

		$client->getConnection()->write($this->cache . "\0");
		$client->getConnection()->end();
	}

	public function handleClose(Entities\IClient $client): void
	{
		// The connection is already closed by handleMessage() after the policy is sent
	}

	public function handleError(Entities\IClient $client, Throwable $ex): void
	{
		$client->getConnection()->end();
	}

	/**
	 * Builds the crossdomain file based on the template policy
	 *
	 * @throws Exceptions\UnexpectedValue
	 */
	public function renderPolicy(): SimpleXMLElement
	{
		$policy = new SimpleXMLElement($this->policy);

		$siteControl = $policy->addChild('site-control');

		if ($this->siteControl === '') {
			$this->setSiteControl();
		}

		$siteControl->addAttribute('permitted-cross-domain-policies', $this->siteControl);

		if ($this->access === []) {
			throw new Exceptions\UnexpectedValue('You must add a domain through addAllowedAccess()');
		}

		foreach ($this->access as $access) {
			$tmp = $policy->addChild('allow-access-from');
			$tmp->addAttribute('domain', (string) $access[0]);
			$tmp->addAttribute('to-ports', (string) $access[1]);
			$tmp->addAttribute('secure', $access[2] === true ? 'true' : 'false');
		}

		return $policy;
	}

	/**
	 * Make sure the proper site control was passed
	 */
	private function validateSiteControl(string $permittedCrossDomainPolicies): bool
	{
		//'by-content-type' and 'by-ftp-filename' are not available for sockets
		return in_array($permittedCrossDomainPolicies, ['none', 'master-only', 'all'], true);
	}

	/**
	 * Validate for proper domains (wildcards allowed)
	 */
	private function validateDomain(string $domain): bool
	{
		return (bool) preg_match('/^((http(s)?:\/\/)?([a-z0-9-_]+\.|\*\.)*([a-z0-9-_\.]+)|\*)$/i', $domain);
	}

	/**
	 * Make sure valid ports were passed
	 */
	private function validatePorts(string $port): bool
	{
		return (bool) preg_match('/^(\*|(\d+[,-]?)*\d+)$/', $port);
	}

}
