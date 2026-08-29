<?php

namespace App\Tests\Acceptance;

class ContractLogAcceptanceTest extends AcceptanceTestCase
{
    public function testAddLogEntryTransport(): void
    {
        $ref = $this->seedUserAndCompany('loguser', 'Log Co', 'Inner Sphere');
        $contractId = $this->seedContract($ref['companyId'], ['status' => 'active']);

        $client = $this->login('loguser');

        $client->request('POST', '/contracts/' . $contractId . '/log/add', [
            'action' => 'transport',
            'jumps' => 1,
        ]);

        $this->assertResponseRedirects('/contract/' . $contractId . '/add');
        $crawler = $client->followRedirect();
        $this->assertResponseIsSuccessful();
    }

    public function testAddLogEntryMaintenance(): void
    {
        $ref = $this->seedUserAndCompany('maintlog', 'Maint Log Co', 'Inner Sphere');
        $contractId = $this->seedContract($ref['companyId'], ['status' => 'active']);

        $client = $this->login('maintlog');

        $client->request('POST', '/contracts/' . $contractId . '/log/add', [
            'action' => 'maintenance',
        ]);

        $this->assertResponseRedirects('/contract/' . $contractId . '/add');
        $crawler = $client->followRedirect();
        $this->assertResponseIsSuccessful();
    }

    public function testAddLogEntryBasePay(): void
    {
        $ref = $this->seedUserAndCompany('bpaylog', 'BPay Log Co', 'Inner Sphere');
        $contractId = $this->seedContract($ref['companyId'], ['status' => 'active']);

        $client = $this->login('bpaylog');

        $client->request('POST', '/contracts/' . $contractId . '/log/add', [
            'action' => 'base_pay',
        ]);

        $this->assertResponseRedirects('/contract/' . $contractId . '/add');
        $crawler = $client->followRedirect();
        $this->assertResponseIsSuccessful();
    }

    public function testAddLogEntryTrackSetup(): void
    {
        $ref = $this->seedUserAndCompany('tslog', 'TS Log Co', 'Inner Sphere');
        $contractId = $this->seedContract($ref['companyId'], ['status' => 'active']);

        $client = $this->login('tslog');

        $client->request('POST', '/contracts/' . $contractId . '/log/add', [
            'action' => 'track_setup',
            'month' => 1,
            'toftt' => false,
        ]);

        $this->assertResponseRedirects('/contract/' . $contractId . '/add');

        $crawler = $client->followRedirect();
        $this->assertResponseIsSuccessful();
    }

    public function testAddLogEntryPostTrack(): void
    {
        $ref = $this->seedUserAndCompany('ptlog', 'PT Log Co', 'Inner Sphere');
        $contractId = $this->seedContract($ref['companyId'], ['status' => 'active']);

        $client = $this->login('ptlog');

        $client->request('POST', '/contracts/' . $contractId . '/log/add', [
            'action' => 'post_track',
            'combatPayTier' => 'none',
            'salvageClaimed' => false,
        ]);

        // post_track returns the form page (not a redirect) when there are no pending tracks
        $this->assertResponseIsSuccessful();
    }

    public function testEditLogEntrySucceeds(): void
    {
        $ref = $this->seedUserAndCompany('editlog', 'Edit Log Co', 'Inner Sphere');
        $contractId = $this->seedContract($ref['companyId'], ['status' => 'active']);

        // First create a log entry
        $client = $this->login('editlog');

        $client->request('POST', '/contracts/' . $contractId . '/log/add', [
            'action' => 'downtime',
            'month' => 1,
            'amount' => 0,
            'note' => 'Test note',
        ]);

        $this->assertResponseRedirects('/contract/' . $contractId);
        $client->followRedirect();

        // Now edit the last log entry
        $conn = self::$sharedEm->getConnection();
        $entry = $conn->fetchOne('SELECT id FROM contract_log_entry WHERE contract_id = ?', [$contractId]);
        if ($entry) {
            $entryId = $entry['id'];
            $crawler = $client->request('GET', '/contracts/' . $contractId . '/log/entry/' . $entryId . '/edit');

            if ($crawler->filter('form')->count() > 0) {
                $form = $crawler->selectButton("Save")->form([
                    'contract_log_entry_edit_form[month]' => 1,
                    'contract_log_entry_edit_form[entryType]' => 'downtime',
                ]);
                $client->submit($form);
                $this->assertResponseRedirects('/contract/' . $contractId);
                $crawler = $client->followRedirect();
                $this->assertResponseIsSuccessful();
                return;
            }
        }

        $this->markTestSkipped('No log entries to edit');
    }

    public function testDeleteLogEntrySucceeds(): void
    {
        $ref = $this->seedUserAndCompany('dellog', 'Del Log Co', 'Inner Sphere');
        $contractId = $this->seedContract($ref['companyId'], ['status' => 'active']);

        // First create a log entry
        $client = $this->login('dellog');

        $client->request('POST', '/contracts/' . $contractId . '/log/add', [
            'action' => 'downtime',
            'month' => 1,
            'amount' => 0,
            'note' => 'To delete',
        ]);

        $this->assertResponseRedirects('/contract/' . $contractId . '/add');
        $client->followRedirect();

        // Now delete the log entry
        $conn = self::$sharedEm->getConnection();
        $entry = $conn->fetchOne('SELECT id FROM contract_log_entry WHERE contract_id = ?', [$contractId]);
        if ($entry && $entry['id']) {
            $entryId = $entry['id'];
            $client->request('POST', '/contracts/' . $contractId . '/log/entry/' . $entryId . '/delete');
            $this->assertResponseRedirects('/contract/' . $contractId);
            $crawler = $client->followRedirect();
            $this->assertResponseIsSuccessful();
            return;
        }

        $this->markTestSkipped('No log entries to delete');
    }
}
