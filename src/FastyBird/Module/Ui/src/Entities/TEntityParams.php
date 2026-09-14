<?php declare(strict_types = 1);

/**
 * TEntityParams.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:UIModule!
 * @subpackage     Entities
 * @since          1.0.0
 *
 * @date           25.05.20
 */

namespace FastyBird\Module\Ui\Entities;

use Doctrine\ORM\Mapping as ORM;
use FastyBird\Library\DoctrineCrud\Mapping\Attribute as IPubDoctrine;
use Nette\Utils;
use function array_key_exists;
use function array_merge;
use function array_pop;
use function count;
use function explode;
use function is_array;
use function is_string;
use function trim;

/**
 * Entity params field trait
 *
 * @package        FastyBird:Database!
 * @subpackage     UIModule
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
trait TEntityParams
{

	/** @var array<string, mixed>|null */
	#[IPubDoctrine\Crud(writable: true)]
	#[ORM\Column(name: 'params', type: 'json', nullable: true)]
	protected array|null $params = null;

	public function getParams(): Utils\ArrayHash
	{
		return $this->params !== null ? Utils\ArrayHash::from($this->params) : Utils\ArrayHash::from([]);
	}

	/**
	 * @param array<string, mixed> $params
	 *
	 * @throws Utils\JsonException
	 */
	public function setParams(array $params): void
	{
		$toUpdate = $this->params !== null ? array_merge($this->params, $params) : $params;
		/** @var array<string, mixed>|false $toUpdate */
		$toUpdate = Utils\Json::decode(Utils\Json::encode($toUpdate), forceArrays: true);

		if (is_array($toUpdate)) {
			$this->params = $toUpdate;
		}
	}

	public function setParam(string $key, mixed $value = null): void
	{
		if ($this->params === null) {
			$this->params = [];
		}

		$parts = explode('.', $key);

		if (count($parts) > 1) {
			$val = &$this->params;
			$last = array_pop($parts);

			foreach ($parts as $part) {
				if (!isset($val[$part]) || !is_array($val[$part])) {
					$val[$part] = [];
				}

				$val = &$val[$part];
			}

			if ($value === null) {
				unset($val[$last]);
			} else {
				$val[$last] = $value;
			}
		} else {
			if ($value === null) {
				unset($this->params[$parts[0]]);

			} else {
				$this->params[$parts[0]] = $value;
			}
		}
	}

	public function getParam(string $key, mixed $default = null): mixed
	{
		if ($this->params === null) {
			return $default;
		}

		$parts = explode('.', $key);

		if (array_key_exists($parts[0], $this->params)) {
			if (is_array($this->params[$parts[0]]) || $this->params[$parts[0]] instanceof Utils\ArrayHash) {
				$val = null;

				foreach ($parts as $part) {
					if (isset($val)) {
						$val = is_array($val) && array_key_exists($part, $val) ? $val[$part] : null;
					} else {
						$val = $this->params[$part] ?? $default;
					}
				}

				return $val ?? $default;
			} else {
				return is_string($this->params[$parts[0]]) ? trim($this->params[$parts[0]]) : $this->params[$parts[0]];
			}
		}

		return $default;
	}

}
