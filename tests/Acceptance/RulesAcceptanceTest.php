<?php

namespace App\Tests\Acceptance;

class RulesAcceptanceTest extends AcceptanceTestCase
{
    public function testRulesPageLoads(): void
    {
        $this->seedUserAndCompany('rulesuser', 'Rules Co', 'Inner Sphere');

        $client = $this->login('rulesuser');
        $client->request('GET', '/rules');

        $this->assertResponseIsSuccessful();
    }
}
