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

    public function testCreateDropshipWithMekbaysSucceeds(): void
    {
        $ref = $this->seedUserAndCompany('mekbaynew', 'Mekbay New Co', 'Inner Sphere');

        $client = $this->login('mekbaynew');

        $crawler = $client->request('GET', '/dropship/new');
        $form = $crawler->selectButton('Save')->form([
            'dropship[name]' => 'Valkyrie VLK-KNT',
            'dropship[maxCapacity]' => 5,
            'dropship[mekbayCapacity]' => 3,
        ]);

        $client->submit($form);
        $this->assertResponseRedirects('/dropship/');

        $crawler = $client->followRedirect();
        $this->assertContainsText($crawler, 'Valkyrie VLK-KNT');
        $this->assertContainsText($crawler, '3');
    }

    public function testEditDropshipSucceeds(): void
    {
        $ref = $this->seedUserAndCompany('editdrop', 'Edit Drop Co', 'Inner Sphere');
        $dropshipId = $this->seedDropship($ref['companyId'], 'Old Dropship', 3);

        $client = $this->login('editdrop');

        $crawler = $client->request('GET', '/dropship/' . $dropshipId . '/edit');

        $this->assertResponseIsSuccessful();
    }

    public function testEditDropshipUpdatesMekbayCapacity(): void
    {
        $ref = $this->seedUserAndCompany('editmekbay', 'Edit Mekbay Co', 'Inner Sphere');
        $dropshipId = $this->seedDropship($ref['companyId'], 'Mekbay Dropship', 5, 2);

        $client = $this->login('editmekbay');

        $crawler = $client->request('GET', '/dropship/' . $dropshipId . '/edit');
        $form = $crawler->selectButton('Save Changes')->form([
            'dropship[name]' => 'Valkyrie VLK-KNT',
            'dropship[maxCapacity]' => 8,
            'dropship[mekbayCapacity]' => 4,
        ]);

        $client->submit($form);
        $this->assertResponseRedirects('/dropship/');

        $crawler = $client->followRedirect();
        $this->assertContainsText($crawler, 'Valkyrie VLK-KNT');
        $this->assertContainsText($crawler, '4');
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

    public function testDropshipShowDisplaysMekbayInfo(): void
    {
        $ref = $this->seedUserAndCompany('showmekbay', 'Show Mekbay Co', 'Inner Sphere');
        $this->seedDropship($ref['companyId'], 'Valkyrie', 5, 3);

        $client = $this->login('showmekbay');
        $crawler = $client->request('GET', '/dropship/');

        $this->assertResponseIsSuccessful();
        $this->assertContainsText($crawler, 'Mekbays');
        $this->assertContainsText($crawler, '0 / 3');
    }

    public function testDropshipShowDisplaysNoMekbaysLabelWhenZeroCapacity(): void
    {
        $ref = $this->seedUserAndCompany('nomekbays', 'No Mekbay Co', 'Inner Sphere');
        $this->seedDropship($ref['companyId'], 'Valkyrie', 5, 0);
        $this->seedUnit($ref['companyId'], 'Thunderbird THB-XQ', 'Thunderbird THB-XQ', 60, 200, 'mech');

        $client = $this->login('nomekbays');
        $crawler = $client->request('GET', '/dropship/');

        $this->assertResponseIsSuccessful();
        $this->assertContainsText($crawler, 'No mekbays');
    }

    public function testDropshipShowWithZeroMekbaysAndUnitsOnBoard(): void
    {
        $ref = $this->seedUserAndCompany('zeromkbyunits', 'Zero Mekbay Units Co', 'Inner Sphere');
        $this->seedDropship($ref['companyId'], 'Valkyrie', 5, 0);
        $this->seedUnit($ref['companyId'], 'Gravino GRV-NI1', 'Gravino GRV-NI1', 35, 150, 'mech');

        $client = $this->login('zeromkbyunits');
        $crawler = $client->request('GET', '/dropship/');

        $this->assertResponseIsSuccessful();
        $this->assertContainsText($crawler, '0 / 0');
    }

    public function testDropshipShowWithNonZeroMekbays(): void
    {
        $ref = $this->seedUserAndCompany('nonzeromkby', 'Non Zero Mekbay Co', 'Inner Sphere');
        $this->seedDropship($ref['companyId'], 'Valkyrie', 5, 2);
        $this->seedUnit($ref['companyId'], 'Gravino GRV-NI1', 'Gravino GRV-NI1', 35, 150, 'mech');

        $client = $this->login('nonzeromkby');
        $crawler = $client->request('GET', '/dropship/');

        $this->assertResponseIsSuccessful();
        $this->assertContainsText($crawler, '0 / 2');
    }
}
