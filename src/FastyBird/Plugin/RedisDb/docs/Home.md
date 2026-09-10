<p align="center">
	<img src="https://github.com/fastybird/.github/blob/main/assets/repo_title.png?raw=true" alt="FastyBird"/>
</p>

> [!IMPORTANT]
> This documentation is meant to be used by developers or users which has basic programming skills. If you are regular user
> please use FastyBird IoT documentation which is available on [miniserver.fastybird.com/docs](https://miniserver.fastybird.com/docs). 

# About Plugin

This library has some services divided into namespaces. All services are preconfigured and imported into application
container automatically.

```
\FastyBird\Plugin\RedisDb
  \Clients - Services which manage connections to Redis database
  \Exchange - Services related to exchange bus
  \Models - Services for creating, reading, updating and deleting states in Redis database
  \Publishers - Services responsible for publishing messages to Redis PubSub
```

All services, helpers, etc. are written to be self-descriptive :wink:.

## Using Plugin

The plugin is ready to be used as is. Has configured all services in application container and there is no need to develop
some other services or bridges.

## Plugin Configuration

This plugin is preconfigured with default values. If you want to change its configuration, you will have to provide configuration
via Nette DI services configuration which is usually done via Neon configuration file.

```neon
fbRedisDbPlugin:  # Plugin extension name
    client:
        host: 127.0.0.1
        port: 6379
        username: username # Username and password could be left blank if connection to Redis is not secured
        password: username_secret
    exchange:
        channel: fb_exchange # Exchange name/channel which will be used for publishing messages
```

## Storing States in Redis Database

If some service needs to store some data in Redis database, model services could be used for this. This plugin has two
services, repository for reading data by identifier and manager for creating, updating and deleting data.

This plugin is equiped with automatic `entity` factory, so each data which will be returned from repository or manager
will be transformed to entity: `\FastyBird\Plugin\RedisDb\States\State`

```php
namespace Your\CoolApp\Actions;

use FastyBird\Plugin\RedisDb\Models\States\StatesRepository;
use FastyBird\Plugin\RedisDb\Models\States\StatesManager;
use FastyBird\Plugin\RedisDb\States\State;
use Nette\Utils\ArrayHash;
use Ramsey\Uuid\Uuid;

class SomeService
{

    private StatesRepository $repository;

    private StatesManager $manager;

    public function __construct(
        StatesRepository $repository,
        StatesManager $manager,
    ) {
        $this->publisher = $publisher;
    }

    public function readStatus(Uuid $id)
    {
        // Your interesting logic here...

        $state = $this->repository->find($id);
        assert($state instanceof State);
    }

    public function writeStatus(Uuid $id, ArrayHash $data)
    {
        // Your interesting logic here...

        $state = $this->manager->create($id, $data);
        assert($state instanceof State);
    }

}
```

### Using Custom State Entity

Because basic state entity has only identifier, it is necessary to define your custom state entity which will extend
this basic state entity.

What you have to have on your mind is to define `getCreateFields` and `getUpdateFields` methods and add entity attributes to them

- `getCreateFields` is used to provide field names which will manager search in provided data. It is possible to define which attribute is require and which not.
- `getUpdateFields` is used to provide field names which could be updated by manager. If some attribute is not provided, it will not be updated.

```php
namespace Your\CoolApp\States;

use Orisai\ObjectMapper;

class CustomState extends States\State
{

	public function __construct(
		Uuid\UuidInterface $id,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\StringValue(notEmpty: true),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		private readonly string|null $value = null,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\StringValue(notEmpty: true),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		private readonly string|null $camelCased = null,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\DateTimeValue(format: DateTimeInterface::ATOM),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		#[ObjectMapper\Modifiers\FieldName(self::CREATED_AT_FIELD)]
		private readonly DateTimeInterface|null $createdAt = null,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\DateTimeValue(format: DateTimeInterface::ATOM),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		#[ObjectMapper\Modifiers\FieldName(self::UPDATED_AT_FIELD)]
		private readonly DateTimeInterface|null $updatedAt = null,
	)
	{
		parent::__construct($id);
	}

	public static function getCreateFields(): array
	{
		return [
			0 => 'id',
			1 => 'value', // Required attribute
			self::CREATED_AT_FIELD => null, // Optional attribute witch default value null
			self::UPDATED_AT_FIELD => null, // Optional attribute witch default value null
		];
	}

	public static function getUpdateFields(): array
	{
		return [
			'value', // Editable attribute
			self::UPDATED_AT_FIELD,
		];
	}

	public function getValue(): string|null
	{
		return $this->value;
	}

	public function getCreated(): DateTimeInterface|null
	{
		return $this->createdAt;
	}

	public function getUpdated(): DateTimeInterface|null
	{
		return $this->updatedAt;
	}

	public function toArray(): array
	{
		return array_merge([
			'value' => $this->getValue(),
			'created_at' => $this->getCreated()?->format(DateTimeInterface::ATOM),
			'updated_at' => $this->getUpdated()?->format(DateTimeInterface::ATOM),
		], parent::toArray());
	}

}
```