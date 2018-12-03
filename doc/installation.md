# Installation

In order to add the SDK to your project you will need to use the [Composer](https://getcomposer.org/)
tool. Once you have it installed and set up, run the following command in your project
directory:

```bash
composer require 1cart/sdk-php
```

Once it completes, the next step will be to configure an HTTP client for the SDK
to use in communication with the API.

# Configuring and creating the SDK client

The SDK client does not enforce any specific HTTP client implementation, but instead
can accept any that supports the [PSR-18 specification](https://www.php-fig.org/psr/psr-18/).

As of time of writing this document, it can be achieved through the [HTTPlug](http://httplug.io/)
library that provides adapters for [many different clients](http://docs.php-http.org/en/latest/clients.html).
In the following example we will show how to create the SDK client using [Guzzle's HTTP client](http://docs.guzzlephp.org/en/stable/),
utilizing the instructions given in the [HTTPlug documentation](http://docs.php-http.org/en/latest/clients/guzzle6-adapter.html).
For creating requests, we will use the [Zend Diactoros library](https://docs.zendframework.com/zend-diactoros/):

First, run this command to add the neccessary libraries:

```bash
composer require php-http/guzzle6-adapter zendframework/zend-diactoros
```

The HTTP client will need two SDK-specific parameters in order to be able to connect with the API:
1. Your 1cart seller's API key.
2. Your 1cart seller's API client ID.

Instructions on how to obtain these parameters can be found [here](api_keys.md).
Once you have them, you can create the client like this:

```php
use Http\Adapter\Guzzle6\Client as GuzzleAdapter;
use OneCart\Api\Client as OneCartClient;
use Zend\Diactoros\RequestFactory;

$httpClient = GuzzleAdapter::createWithConfig([
    'timeout'  => 2.0,
    'headers' => [
        'User-Agent' => '1cart API Client',
        'Accept' => 'application/json'
    ]
])

$apiClient = new OneCartClient(
    $httpClient,
    new RequestFactory(),
    'insert your API key here',
    'insert your API client ID here'
);
```

And that is it! Now you can [use](usage.md) your client to communicate with the API.
