<?php

namespace Code16\MachinaClient\Tests\Stubs;

use Code16\Machina\ClientRepositoryInterface;

class ClientRepository implements ClientRepositoryInterface
{
    public function find($id)
    {
        return Client::find($id);
    }

    public function findByCredentials($client, $secret)
    {
        return Client::where('id', $client)->where('secret', $secret)->first();
    }

}
