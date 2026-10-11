<?php declare(strict_types = 1);

namespace FastyBird\Plugin\ApiKey\Commands;

use FastyBird\Core\Logging;
use FastyBird\Core\Values\Types\Sources;
use FastyBird\Plugin\ApiKey\Models;
use FastyBird\Plugin\ApiKey\Types;
use Nette\Utils;
use Override;
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
 */
final class Create extends Console\Command\Command
{

	public const string NAME = 'fb:api-key:create';

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
	#[Override]
	protected function configure(): void
	{
		$this
			->setName(self::NAME)
			->setDescription('Create API access key');
	}

	#[Override]
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
				'source' => Sources\Plugin::API_KEY->value,
				'type' => 'create-command',
				'exception' => Logging\Logger::buildException($ex),
				'cmd' => $this->getName(),
			]);

			$io->error('Key could not be created. Please try again later.');

			return self::FAILURE;
		}
	}

}
