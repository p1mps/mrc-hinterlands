<?php

namespace App\Tests\Acceptance;

class DropshipAcceptanceTest extends AcceptanceTestCase
{
    public function testDropshipShowLoads(): void
    {
        $ref = $this->seedUserAndCompany('dropuser', 'Drop Co', 'Inner Sphere');
        $this->seedDropship($ref['companyId']);

        $client = $this->login('dropuser');
        $crawler = $client->request('GET', '/dropship/');

        $this->assertResponseIsSuccessful();
    }

    public function testCreateDropshipSucceeds(): void
    {
        $ref = $this->seedUserAndCompany('adddrop', 'Add Drop Co', 'Clan');

        $client = $this->login('adddrop');

        $crawler = $client->request('GET', '/dropship/new');

        $this->assertResponseIsSuccessful();
    }

    public function testEditDropshipSucceeds(): void
    {
        $ref = $this->seedUserAndCompany('editdrop', 'Edit Drop Co', 'Inner Sphere');
        $dropshipId = $this->seedDropship($ref['companyId'], 'Old Dropship', 3);

        $client = $this->login('editdrop');

        $crawler = $client->request('GET', '/dropship/' . $dropshipId . '/edit');

        $this->assertResponseIsSuccessful();
    }

    public function testDeleteDropshipSucceeds(): void
    {
        $ref = $this->seedUserAndCompany('deldrop', 'Del Drop Co', 'Clan');
        $dropshipId = $this->seedDropship($ref['companyId']);

        $client = $this->login('deldrop');

        $client->request('POST', '/dropship/' . $dropshipId . '/delete');
        $this->assertResponseRedirects('/dropship/');
        $crawler = $client->followRedirect();
        $this->assertResponseIsSuccessful();
    }
}
