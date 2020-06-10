<?php

namespace Code16\MachinaClient;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Response;
use Code16\MachinaClient\Exceptions\InvalidCredentialsException;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

class MachinaClient
{
    /**
     * Guzzle client instance
     *
     * @var \GuzzleHttp\Client
     */
    protected $client;

    /**
     * Credentials used to request JWT Token
     *
     * @var array
     */
    protected $credentials;

    /**
     * Store the JWT token
     *
     * @var string
     */
    protected $token;

    /**
     * Base url for API
     *
     * @var string
     */
    protected $baseUrl;

    /**
     * Custom headers
     *
     * @var array
     */
    protected $headers = [];

    /**
     * Optionnal logger
     *
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    public function __construct(
        Client $client,
        array $credentials = null,
        string $url = null)
    {
        $this->client = $client;
        $this->credentials = $credentials;
        $this->url = $url;
    }

    /**
     * Set credentials to be used on this instance
     *
     * @param array $credentials
     * @return static
     */
    public function setCredentials(array $credentials)
    {
        $this->credentials = $credentials;
        return $this;
    }

    /**
     * Set base API url that will be used to call endpoints on this instance
     *
     * @param string $baseUrl
     */
    public function setBaseUrl(string $baseUrl)
    {
        $this->baseUrl = $baseUrl;
        return $this;
    }

    /**
     * Set an optional logger to the client
     *
     * @param LoggerInterface $logger
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Define custom headers to be used with all request within this instance.
     *
     * @return static
     */
    public function withHeaders(array $headers)
    {
        $this->headers = $headers;
        return $this;
    }

    /**
     * Send a GET request
     *
     * @param  string     $uri
     * @param  array|null $data
     * @return array
     */
    public function get(string $uri, array $data = null)
    {
        return $this->sendRequest("get", $uri, $data);
    }

    /**
     * Send a POST request
     *
     * @param  string     $uri
     * @param  array|null $data
     * @return array
     */
    public function post(string $uri, array $data = null)
    {
        return $this->sendRequest("post", $uri, $data);
    }

    /**
     * Send a PUT request
     *
     * @param  string     $uri
     * @param  array|null $data
     * @return array
     */
    public function put(string $uri, array $data = null)
    {
        return $this->sendRequest("put", $uri, $data);
    }

    /**
     * Send a PATCH request
     *
     * @param  string     $uri
     * @param  array|null $data
     * @return array
     */
    public function patch(string $uri, array $data = null)
    {
        return $this->sendRequest("patch", $uri, $data);
    }

    /**
     * Send a DELETE request
     *
     * @param  string     $uri
     * @param  array|null $data
     * @return array
     */
    public function delete(string $uri, array $data = null)
    {
        return $this->sendRequest("delete", $uri, $data);
    }

    /**
     * Send Guzzle request, and catch any authentication error
     *
     * @param  string $method
     * @param  string $url
     * @param  array  $data
     * @return array
     */
    protected function sendRequest(string $method, string $uri, array $data = null)
    {
        $this->logInfo("Sending query : $method:$uri ");
        if($data) {
            $this->logDebug("request payload : ".json_encode($data));
        }

        if(! $this->token) {
            $this->token = $this->sendTokenRequest();
        }

        $client = $this->getHttpClient();

        try {
            $response = $data ?
                $client->request($method, $this->buildUrl($uri), [
                    'form_params' => $data,
                    'headers' => $this->buildHeaders(),
                ]) :
                $client->request($method, $this->buildUrl($uri), [
                    'headers' => $this->buildHeaders(),
                ]);
        }
        catch (RequestException $e) {

            $this->logError("Error ".$e->getCode().":".$e->getMessage());

            if($e->getCode() == 401) {
                $this->throwAuthenticationError($e->getMessage());
            }
            else throw $e;
        }

        if($response->getStatusCode() == 401) {
            $this->throwAuthenticationError("Server authentication failed.");
        }

        $this->parseResponseForRefreshedToken($response);

        $payload = $response->getBody()->getContents();

        $this->logDebug("Response was successfull : ".$payload);

        return json_decode($payload);
    }

    /**
     * Throw an authentication exception
     *
     * @throws InvalidCredentialException
     */
    protected function throwAuthenticationError(string $message)
    {
        throw new InvalidCredentialsException($message);
    }

    /**
     * Request a JWT using provided credentials
     *
     * @return string
     */
    protected function sendTokenRequest()
    {
        $client = $this->getHttpClient();

        $data = $this->credentials;

        $this->logDebug("Sending token request with credentials : ".json_encode($data));

        try {
            $response = $client->request("post", $this->buildUrl(config("machina-client.authentication_endpoint")), [
                'form_params' => $data,
            ]);
        }
        catch (RequestException $e) {
            if($e->getCode() == 401) {
                $this->logError("Could not complete request : invalid credentials.");
                throw new InvalidCredentialsException(
                    "Could not authenticate to server with provided credentials"
                );
            }
            else throw $e;
        }

        $payload = json_decode($response->getBody()->getContents());

        $this->logDebug("Token response : ".json_encode($payload));

        return $payload->access_token;
    }

    /**
     * Build an URL for the request
     *
     * @param  string $uri
     * @return string
     */
    public function buildUrl(string $uri) : string
    {
        $uri = Str::startsWith($uri, "/")
            ? substr($uri, 1)
            : $uri;

        $baseUrl = Str::endsWith($this->baseUrl, "/")
            ? $this->baseUrl
            : $this->baseUrl."/";

        return $baseUrl.$uri;
    }

    /**
     * Build the headers for the request
     *
     * @return array
     */
    protected function buildHeaders() : array
    {
        return array_merge(
            $this->headers,
            $this->buildAuthorizationHeader()
        );
    }

    /**
     * Return authorization header
     *
     * @return array
     */
    protected function buildAuthorizationHeader() : array
    {
        return ['authorization' => 'Bearer '.$this->token];
    }

    /**
     * Parse a response for a refresh token
     *
     * @param  $response
     * @return string|null
     */
    protected function parseResponseForRefreshedToken($response)
    {
        $authorization = $response->getHeader("authorization");

        if($authorization) {
            $this->token = substr($authorization[0], 7);
        }
    }

    /**
     * Log message of INFO level
     *
     * @param  mixed $message
     * @return void
     */
    protected function logInfo($message)
    {
        if($this->logger) {
            $this->logger->info($message);
        }
    }

    /**
     * Log message of DEBUG level
     *
     * @param  mixed $message
     * @return void
     */
    protected function logDebug($message)
    {
        if($this->logger) {
            $this->logger->debug($message);
        }
    }

    /**
     * Log message of ERROR level
     *
     * @param  mixed $message
     * @return void
     */
    protected function logError($message)
    {
        if($this->logger) {
            $this->logger->error($message);
        }
    }

    /**
     * Return instance of Guzzle client
     *
     * @return GuzzleHttp\Client
     */
    protected function getHttpClient() : Client
    {
        return $this->client;
    }

}
