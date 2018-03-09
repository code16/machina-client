<?php

namespace Code16\MachinaClient\Tests\Stubs;

use Code16\MachinaClient\MachinaClient;

class TestClient extends MachinaClient
{
    public function getToken()
    {
        return $this->token;
    }

    public function setToken($token)
    {
        $this->token = $token;
    }
}
