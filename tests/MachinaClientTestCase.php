<?php

namespace Code16\MachinaClient\Tests;

use Code16\Machina\MachinaServiceProvider;
use Code16\MachinaClient\MachinaClientServiceProvider;
use Code16\MachinaClient\Tests\Stubs\Client;
use Code16\MachinaClient\Tests\Stubs\TestClient;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Orchestra\Testbench\TestCase;

abstract class MachInaClientTestCase extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        Schema::create('clients', function($table) {
            $table->increments('id');
            $table->string('secret');
            $table->timestamps();
        });
    }

    /**
     * Define environment setup.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return void
     */
    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('app.key', str_random(32));
        $app['config']->set('database.default', "sqlite");
        $app['config']->set('database.connections.sqlite', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);

        $app->bind(
            \Code16\Machina\ClientRepositoryInterface::class,
            \Code16\MachinaClient\Tests\Stubs\ClientRepository::class
        );
    }

    protected function getPackageProviders($app)
    {
        return [
            MachinaServiceProvider::class,
            MachinaClientServiceProvider::class,
        ];
    }

    protected function buildTestClient(MockHandler $mockHandler = null)
    {
        $client = $this->createClient();
        $credentials = [
            "client" => $client->id,
            "secret" => $client->secret,
        ];

        if($mockHandler) {
            $handler = HandlerStack::create($mockHandler);
            $guzzleClient = new GuzzleClient(['handler' => $handler]);
        }
        else {
            $guzzleClient = new GuzzleClient;
        }
        $apiClient = new TestClient($guzzleClient, $credentials, "http://localhost/api");
        return $apiClient;
    }

    /**
     * Create and return test client
     *
     * @param  string|null $secret
     * @return Client
     */
    protected function createClient() : Client
    {
        $client = new Client;
        $client->id = 1;
        $client->secret = Str::random(32);
        $client->save();
        return $client;
    }
}
