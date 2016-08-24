#### GeckoPackages

# Silex Gearman service

A service provider for [ Silex ](http://silex.sensiolabs.org) 2.* and the [ Gearman client ](https://secure.php.net/manual/en/class.gearmanclient.php) extension.

### Requirements

PHP >= 5.5.9 (PHP7 supported)

### Install

The package can be installed using [Composer](https://getcomposer.org/). Add the package to your `composer.json`.

```
"require": {
    "gecko-packages/gecko-silex-gearman-service" : "^1.0"
}
```

## Usage

```php
$app->register(new GearmanServiceProvider());
$app['gearman']->addTaskHigh("compressVideo", '["4K", "asset1"]', null, '2');
```

The service will provide a `GearmanService` object which extends the `GearmanClient` class.
See below for details.

## Options

The service takes the following options:
* `gearman.servers`
    Array of host names (`string`) as keys and port (`int`) as value.
    *default:* `['127.0.0.1' => 4730]`

* `gearman.timeout`
    Client timeout (`int`).
    *default:* `null` will use the `GEARMAN_DEFAULT_SOCKET_TIMEOUT` value.

* `gearman.options`
    Client options (`int`).
    *default:* `null` does not set options.

### Example

```php
$app->register(
    new GearmanServiceProvider(),
    [
        'gearman.servers' => ['192.168.0.1' => 111],
        'gearman.timeout' => 15,
        'gearman.options' => GEARMAN_CLIENT_NON_BLOCKING | GEARMAN_CLIENT_FREE_TASKS,
    ]
);
```

## Service methods and behavior

The `GearmanService` class extends the `GearmanClient` class so all its methods are available.
Additionally it provides the following methods:

```php
// returns an array<string, array<string, int>> with servers added to the client
$app['gearman']->getServers()

// alternative for GearmanClient::options, returns an <int>
$app['gearman']->getOptions()

// alternative for GearmanClient::timeout, returns an <int>
$app['gearman']->getTimeOut()
```

On construction of the service the timeout will be set explicitly to `GEARMAN_DEFAULT_SOCKET_TIMEOUT` if no other value is passed.

## Custom name registering / multiple services

The service can registered using a name other than the default name `gearman`.
The same method can be used to register multiple services on an application.
Pass the name at the constructor of the service and use the same name as prefix for the configuration.
For example:

```php
$app->register(new GearmanServiceProvider('gearman1'), ['gearman1.servers' => ['192.168.0.1' => 111]]);
$app->register(new GearmanServiceProvider('gearman.backup'), ['gearman.backup.servers' => ['192.168.0.5']]);
```

### License

The project is released under the MIT license, see the LICENSE file.
