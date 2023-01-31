# Symfony Messenger safe `sync://` transport

This package offers **Symfony Messenger**'s `sync://` transport a different approach:

- Messages are handled **synchronously**, like in `sync://` transport ✅

But:
- In case of failure (handler produces an exception), messages can be **retried** 
(whereas original `sync://` throws the exception and crashes) 👉
- When max retries have been reached, messages can fall into the `failure_transport` ☝️

This way, `safe-sync://` can be used as a **drop-in replacement** of any asynchronous transport, following the same logic 
(except messages are now processed synchronously). 👍

Whenever an exception is thrown during message handling, it is **caught by the transport**, which applies the rules you defined within your `messenger.yaml`.

## Installation

```bash
composer require bentools/safe-sync-transport
```

## Usage

Example:

```yaml
# config/packages/messenger.yaml
framework:
  messenger:
    failure_transport: failed
    transports:
      sync:
        dsn: 'safe-sync://'
        retry_strategy:
          max_retries: 3
          delay: 100
          multiplier: 2
      failed:
        dsn: 'doctrine://default'
    
    routing:
      App\DummyMessage: sync
```

## Tests

```bash
composer test
```

## License

MIT.
