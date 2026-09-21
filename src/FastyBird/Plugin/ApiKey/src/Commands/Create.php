<?php declare(strict_types = 1);

/**
 * Create.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ApiKeyPlugin!
 * @subpackage     Commands
 * @since          1.0.0
 *
 * @date           21.10.22
 */

namespace FastyBird\Plugin\ApiKey\Commands;

use FastyBird\Core\Helpers\Tools as ToolsHelpers;
use FastyBird\Core\Types\Metadata as MetadataTypes;
use FastyBird\Plugin\ApiKey\Models;
use FastyBird\Plugin\ApiKey\Types;
use Nette;
use Nette\Utils;
use Psr\Log;
use Ramsey\Uuid;
use Symfony\Component\Console;
use Symfony\Component\Console\Input;
use Symfony\Component\Console\Output;
use Symfony\Component\Console\Style;
use Throwable;
use function sprintf;

/**
 * API key creation command
 *
 * @package        FastyBird:ApiKeyPlugin!
 * @subpackage     Commands
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class Create extends Console\Command\Command
{

	use Nette\SmartObject;

	public const NAME = 'fb:api-key:create';

	public function __construct(
		private readonly Models\Entities\KeysManager $keysManager,
		string|null $name = null,
		private readonly Log\LoggerInterface $logger = new Log\NullLogger(),
	)
	{
		parent::__construct($name);
	}

	/**
	 * @throws Console\Exception\InvalidArgumentException
	 */
	protected function configure(): void
	{
		$this
			->setName(self::NAME)
			->setDescription('Create API access key');
	}

	protected function execute(Input\InputInterface $input, Output\OutputInterface $output)
	{
		$io = new Style\SymfonyStyle($input, $output);

		$io->title('Api key plugin - create api key');

		$question = new Console\Question\Question('Provide key name');

		$name = $io->askQuestion($question);

		try {
			$createKey = new Utils\ArrayHash();
			$createKey->offsetSet('name', $name);
			$createKey->offsetSet('key', Uuid\Uuid::uuid4());
			$createKey->offsetSet('state', Types\KeyState::ACTIVE);

			$key = $this->keysManager->create($createKey);

			$io->success(
				sprintf(
					'API key: %s - %s was successfully created',
					$key->getName(),
					$key->getKey(),
				),
			);

			return self::SUCCESS;
		} catch (Throwable $ex) {
			$this->logger->error('Api key could not be created', [
				'source' => MetadataTypes\Sources\Plugin::API_KEY->value,
				'type' => 'create-command',
				'exception' => ToolsHelpers\Logger::buildException($ex),
				'cmd' => $this->getName(),
			]);

			$io->error('Key could not be created. Please try again later.');

			return self::FAILURE;
		}
	}

}
