<?php declare(strict_types = 1);

/**
 * Validator.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:Tools!
 * @subpackage     Schemas
 * @since          1.0.0
 *
 * @date           24.06.20
 */

namespace FastyBird\Core\Schemas\Tools;

use FastyBird\Core\Exceptions;
use FastyBird\Core\Exceptions\Tools as ToolsExceptions;
use Nette;
use Nette\Utils;
use Opis\JsonSchema;
use function array_key_exists;
use function count;
use function md5;
use function sprintf;

/**
 * JSON schema validator
 *
 * @package        FastyBird:Tools!
 * @subpackage     Schemas
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Validator
{

	use Nette\SmartObject;

	/** @var array<string, JsonSchema\Schema>  */
	private array $schemas = [];

	/**
	 * @throws ToolsExceptions\InvalidData
	 * @throws Exceptions\Logic
	 * @throws Exceptions\MalformedInput
	 */
	public function validate(string $data, string $schema): Utils\ArrayHash
	{
		try {
			$jsonData = Utils\Json::decode($data);

		} catch (Utils\JsonException $ex) {
			throw new Exceptions\MalformedInput('Failed to decode input data', 0, $ex);
		}

		$validator = new JsonSchema\Validator();

		if (!array_key_exists(md5($schema), $this->schemas)) {
			try {
				$this->schemas[md5($schema)] = $validator->loader()->loadObjectSchema(
					(object) Utils\Json::decode($schema),
				);

			} catch (Utils\JsonException $ex) {
				throw new Exceptions\Logic('Failed to decode schema', $ex->getCode(), $ex);
			}
		}

		$result = $validator->validate($jsonData, $this->schemas[md5($schema)]);

		if ($result->isValid()) {
			try {
				return Utils\ArrayHash::from(
					(array) Utils\Json::decode(Utils\Json::encode($jsonData), forceArrays: true),
				);
			} catch (Utils\JsonException $ex) {
				throw new Exceptions\Logic('Failed to encode input data', $ex->getCode(), $ex);
			}
		} else {
			$messages = [];

			$error = $result->error();

			if ($error !== null) {
				try {
					$errorInfo = [
						'keyword' => $error->keyword(),
					];

					$errorInfo['pointer'] = $error->data()->path();

					if (count($error->args()) > 0) {
						$errorInfo['error'] = $error->args();
					}

					foreach ($error->subErrors() as $subError) {
						$errorInfo['keyword'] = $subError->keyword();

						$errorInfo['pointer'] = $subError->data()->path();

						if (count($subError->args()) > 0) {
							$errorInfo['error'] = $subError->args();
						}
					}

					$formattedError = Utils\Json::encode($errorInfo);

				} catch (Utils\JsonException) {
					$formattedError = 'Invalid data';
				}

				$messages[] = sprintf('%s', $formattedError);
			}

			throw new ToolsExceptions\InvalidData($messages);
		}
	}

}
