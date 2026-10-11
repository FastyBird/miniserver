<?php declare(strict_types = 1);

namespace FastyBird\Module\Ui\Events;

use FastyBird\Module\Ui\Documents;
use Symfony\Contracts\EventDispatcher;

/**
 * Socket action command received
 */
final class ActionCommandReceived extends EventDispatcher\Event
{

	public function __construct(
		private readonly Documents\Widgets\DataSources\Actions\Action $action,
		private readonly Documents\Widgets\DataSources\DataSource $dataSource,
	)
	{
	}

	public function getAction(): Documents\Widgets\DataSources\Actions\Action
	{
		return $this->action;
	}

	public function getDataSource(): Documents\Widgets\DataSources\DataSource
	{
		return $this->dataSource;
	}

}
