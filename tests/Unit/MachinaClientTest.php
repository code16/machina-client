<?php

namespace Code16\MachinaClients\Tests\Unit;

use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Handler\MockHandler;
use Code16\MachinaClient\MachinaClient;
use Code16\MachinaClient\Tests\MachinaClientTestCase;
use GuzzleHttp\Exception\RequestException;

class MachinaClientTest extends MachinaClientTestCase
{
    /** @test */
    function we_can_build_authentication_endpoint_url()
    {
        $client = $this->buildTestClient();
        $client->setBaseUrl("http://code16.fr/api");
        $this->assertEquals(
            "http://code16.fr/api/special/auth",
            $client->buildUrl("special/auth")
        );
    }

    /** @test */
    function we_can_instantiate_test_client()
    {
        $this->assertInstanceOf(MachinaClient::class, $this->buildTestClient());
    }

    /** @test */
    function we_can_use_a_custom_token_result_key()
    {
        config(["machina-client.token_result_key" => "toto_token"]);
        $client = $this->buildTestClient( new MockHandler([
            new Response(200, [], json_encode([
                'toto_token' => "tititata",
                'token_type' => 'bearer',
                'expires_in' => 3600,
            ]))
        ]));
        $this->assertEquals("tititata", $client->sendTokenRequest());
    }

    /** @test */
    function we_can_do_a_request()
    {
        $client = $this->buildTestClient( new MockHandler([
            new Response(200, [], json_encode([
                'access_token' => "1234",
                'token_type' => 'bearer',
                'expires_in' => 3600,
            ])),
            new Response(200, [], json_encode(['foo' => 'bar'])),
        ]));
        $this->assertEquals(['foo' => 'bar'], (array) $client->get('/test'));
    }

    /** @test */
    function client_used_refreshed_token_if_a_token_is_part_of_the_response()
    {
        $client = $this->buildTestClient( new MockHandler([
            new Response(200, [], json_encode([
                'access_token' => "1234",
                'token_type' => 'bearer',
                'expires_in' => 3600,
            ])),
            new Response(200, ['authorization' => "Bearer vvvvv"], json_encode(['foo' => 'bar'])),
        ]));
        $this->assertEquals(['foo' => 'bar'], (array) $client->get('/test'));
        $this->assertEquals("vvvvv", $client->getToken());
    }

    /** @test */
    function it_throws_an_exception_if_credentials_are_invalid()
    {
        $client = $this->buildTestClient( new MockHandler([
            new Response(401, [], json_encode([
                'error' => "invalid credentials",
            ])),
        ]));
        $this->expectException(\Code16\MachinaClient\Exceptions\InvalidCredentialsException::class);
        $this->assertEquals(['foo' => 'bar'], (array) $client->get('/test'));
    }


}
