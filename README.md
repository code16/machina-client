# Machina Client

"code16/machina-client" is aimed to be used for implementing client to communicate with JSON APIs protected with the Code16/Machina JWT Token authentication guard. It's a simple wrapper around `GuzzleHttp` and takes cares of querying/refreshing JWT token for you. 

## Installation

```
    composer require code16/machina-client
```

## Usage

```php

    $client = new \Code16\MachinaClient\MachinaClient;

    $client->setBaseUrl("https://example.com/api");
    $client->setCredentials([
        "client" => "some-client-key",
        "secret" => "some-secret-key",
    ]);

    try {
        $client->get("/foo");  // ['foo => bar'];
    }
    catch(\Code16\MachinaClient\Exceptions\InvalidCredentialsException $e) 
    {
        // Incorrect credentials
    }

```

## License

(c) 2018 code16.fr

MIT
