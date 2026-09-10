<p align="center">
	<img src="https://github.com/fastybird/.github/blob/main/assets/repo_title.png?raw=true" alt="FastyBird"/>
</p>

> [!IMPORTANT]
This documentation is meant to be used by developers or users which has basic programming skills. If you are regular user
please use FastyBird IoT documentation which is available on [miniserver.fastybird.com/docs](https://miniserver.fastybird.com/docs).

# Quick start

When a service within your extension requires the publication of messages to the data exchange bus for other extensions,
a recommended approach is to implement the `FastyBird\Core\Exchange\Publisher\Publisher` interface. This allows seamless
integration with the data exchange bus.

Following this implementation, you can register your custom publisher as a service. This structured approach ensures that
your extension's services can efficiently communicate and share relevant information with other components in
the [FastyBird](https://www.fastybird.com) [IoT](https://en.wikipedia.org/wiki/Internet_of_things) ecosystem.

***

## Creating custom publisher

If some service of your extension have to publish messages to data exchange bus for other extensions, you could just
implement `FastyBird\Core\Exchange\Publisher\Publisher` interface and register your publisher as service

```php
namespace Your\CoolApp\Publishers;

use FastyBird\Core\Exchange\Publisher\Publisher;
use FastyBird\Core\Application\Documents;
use FastyBird\Library\Metadata\Types;

class ModuleDataPublisher implements Publisher
{

    public function publish(
        Types\Sources\Module|Types\Sources\Plugin|Types\Sources\Connector $source,
        string $routingKey,
        Documents\Document|null $entity,
    ) : void {
        // Service logic here, e.g. publish message to RabbitMQ or Redis etc. 
    }

}
```

You could create as many publishers as you need. Publisher proxy then will collect all of them.

### Asynchronous publisher

As the [FastyBird](https://www.fastybird.com) [IoT](https://en.wikipedia.org/wiki/Internet_of_things) ecosystem utilizes
an asynchronous event-loop system within its services, it is recommended to implement asynchronous publishers. This ensures
that code processing remains unblocked. These publishers have to follow a Promise-based approach when publishing messages.

```php
namespace Your\CoolApp\Publishers;

use FastyBird\Core\Exchange\Publisher\Async\Publisher;
use FastyBird\Core\Application\Documents;
use FastyBird\Library\Metadata\Types;
use React\Promise\Deferred;
use React\Promise\PromiseInterface;

class ModuleDataPublisher implements Publisher
{

    /**
    * @return PromiseInterface<bool>
     */
    public function publish(
        Types\Sources\Module|Types\Sources\Plugin|Types\Sources\Connector $source,
        string $routingKey,
        Documents\Document|null $entity,
    ) : PromiseInterface {
        $deferred  = new Deferred();

        // Service logic here, e.g. publish message to RabbitMQ or Redis etc.
        
        return $deferred->promise(); 
    }

}
```

## Publishing message

In your code you could just import one publisher - proxy publisher.

```php
namespace Your\CoolApp\Actions;

use FastyBird\Core\Exchange\Publisher\Container;

class SomeHandler
{

    /** @var Container */
    private Container $publisher;

    public function __construct(
        Container $publisher
    ) {
        $this->publisher = $publisher;
    }

    public function updateSomething()
    {
        // Your interesting logic here...

        $this->publisher->publish(
            $origin,
            $routingKey,
            $entity,
        );
    }
}
```

And that is it, global publisher will call all your publishers and publish message to all your systems.

## Creating custom consumer

One part is done, message is published. Now have to be consumed.

If some service of your extension have is waiting for messages from data exchange bus from other extensions, you could just
implement `FastyBird\Core\Exchange\Consumer\Consumer` interface and register your consumer as service

```php
namespace Your\CoolApp\Publishers;

use FastyBird\Core\Exchange\Consumers\Consumer;
use FastyBird\Core\Application\Documents;
use FastyBird\Library\Metadata\Types;

class DataConsumer implements Consumer
{

    public function consume(
        Types\Sources\Module|Types\Sources\Plugin|Types\Sources\Connector $source,
        string $routingKey,
        Documents\Document|null $entity,
    ) : void {
        // Do your data processing logic here 
    }

}
```

You could create as many consumers as you need. Consumer proxy then will collect all of them.
