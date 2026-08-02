<?php

namespace App\Tests\Acceptance;

class RulesAcceptanceTest extends AcceptanceTestCase
{
    public function testRulesPageLoads(): void
    {
        $ref = $this->seedUserAndCompany('rulesuser', 'Rules Co', 'Inner Sphere');

        $client = $this->login('rulesuser');
        $crawler = $client->request('GET', '/rules');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('h1');
    }
}
