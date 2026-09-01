<?php

namespace Tests\Unit;

use App\Models\Affectation;
use App\Models\AmbiguousAffectationException;
use App\Models\Document;
use App\Models\DocumentPermission;
use App\Services\DocumentVisibilityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Helpers\DocumentTestHelper;
use Tests\TestCase;

/**
 * Tests unitaires du service de visibilité documentaire.
 *
 * Teste directement le service sans passer par HTTP, ce qui permet
 * d'isoler la logique métier.
 */
class DocumentVisibilityServiceTest extends TestCase
{
    use RefreshDatabase;
    use DocumentTestHelper;

    private DocumentVisibilityService $service;
    private array $org;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\PosteSeeder::class);
        $this->service = app(DocumentVisibilityService::class);
        $this->org = $this->createOrganization();
    }

    public function test_scopeForUser_admin_retourne_tous_les_documents_de_sa_company(): void
    {
        $org = $this->org;
        $admin = $this->createUserWithPoste($org['company'], 'admin');
        $doc1 = $this->makeDoc($org['company'], $org['serviceA1'], $admin);
        $doc2 = $this->makeDoc($org['company'], $org['serviceB1'], $admin);

        $ids = $this->service->scopeForUser($admin)->pluck('id')->all();

        $this->assertContains($doc1->id, $ids);
        $this->assertContains($doc2->id, $ids);
    }

    public function test_scopeForUser_user_sans_affectation_active_retourne_ensemble_vide(): void
    {
        $org = $this->org;
        $user = \App\Models\User::factory()->for($org['company'])->create();

        $doc = $this->makeDoc($org['company'], $org['serviceA1'], $user);

        $count = $this->service->scopeForUser($user)->count();
        $this->assertEquals(0, $count);
    }

    public function test_scopeForUser_user_avec_affectation_echue_retourne_ensemble_vide(): void
    {
        $org = $this->org;
        $agent = $this->createUserWithPoste($org['company'], 'agent_temporaire', $org['serviceA1']);
        $agent->affectations()->update([
            'is_active' => false,
            'ended_at' => today()->subDay(),
        ]);

        $count = $this->service->scopeForUser($agent)->count();
        $this->assertEquals(0, $count);
    }

    public function test_canView_retourne_true_pour_admin_sur_doc_cross_service(): void
    {
        $org = $this->org;
        $admin = $this->createUserWithPoste($org['company'], 'admin');
        $doc = $this->makeDoc($org['company'], $org['serviceB1'], $admin);

        $this->assertTrue($this->service->canView($admin, $doc));
    }

    public function test_canView_agent_voisin_retourne_false(): void
    {
        $org = $this->org;
        $agentA = $this->createUserWithPoste($org['company'], 'agent_temporaire', $org['serviceA1']);
        $agentB = $this->createUserWithPoste($org['company'], 'agent_temporaire', $org['serviceB1']);
        $docB = $this->makeDoc($org['company'], $org['serviceB1'], $agentB);

        $this->assertFalse($this->service->canView($agentA, $docB));
    }

    public function test_canView_agent_avec_permission_user_valide_retourne_true(): void
    {
        $org = $this->org;
        $agentA = $this->createUserWithPoste($org['company'], 'agent_temporaire', $org['serviceA1']);
        $agentB = $this->createUserWithPoste($org['company'], 'agent_temporaire', $org['serviceB1']);
        $docB = $this->makeDoc($org['company'], $org['serviceB1'], $agentB);

        DocumentPermission::create([
            'document_id' => $docB->id,
            'target_type' => DocumentPermission::TARGET_USER,
            'target_id' => $agentA->id,
            'expires_at' => now()->addDay(),
        ]);

        $this->assertTrue($this->service->canView($agentA, $docB));
    }

    public function test_canView_agent_avec_permission_expiree_retourne_false(): void
    {
        $org = $this->org;
        $agentA = $this->createUserWithPoste($org['company'], 'agent_temporaire', $org['serviceA1']);
        $docB = $this->makeDoc($org['company'], $org['serviceB1'], $agentA);

        DocumentPermission::create([
            'document_id' => $docB->id,
            'target_type' => DocumentPermission::TARGET_USER,
            'target_id' => $agentA->id,
            'expires_at' => now()->subDay(),
        ]);

        $this->assertFalse($this->service->canView($agentA, $docB));
    }

    public function test_canUpdate_employe_sur_propre_document_retourne_true(): void
    {
        $org = $this->org;
        $employe = $this->createUserWithPoste($org['company'], 'employe', $org['serviceA1']);
        $doc = $this->makeDoc($org['company'], $org['serviceA1'], $employe);

        $this->assertTrue($this->service->canUpdate($employe, $doc));
    }

    public function test_canUpdate_employe_sur_document_collegue_retourne_false(): void
    {
        $org = $this->org;
        $employe1 = $this->createUserWithPoste($org['company'], 'employe', $org['serviceA1']);
        $employe2 = $this->createUserWithPoste($org['company'], 'employe', $org['serviceA1']);
        $doc = $this->makeDoc($org['company'], $org['serviceA1'], $employe2);

        $this->assertFalse($this->service->canUpdate($employe1, $doc));
    }

    public function test_canUpdate_chef_service_sur_document_de_son_service_retourne_true(): void
    {
        $org = $this->org;
        $chef = $this->createUserWithPoste($org['company'], 'chef_service', $org['serviceA1']);
        $employe = $this->createUserWithPoste($org['company'], 'employe', $org['serviceA1']);
        $doc = $this->makeDoc($org['company'], $org['serviceA1'], $employe);

        $this->assertTrue($this->service->canUpdate($chef, $doc));
    }

    public function test_canGrantPermission_employe_retourne_false(): void
    {
        $org = $this->org;
        $employe = $this->createUserWithPoste($org['company'], 'employe', $org['serviceA1']);
        $doc = $this->makeDoc($org['company'], $org['serviceA1'], $employe);

        $this->assertFalse($this->service->canGrantPermission($employe, $doc));
    }

    public function test_canGrantPermission_chef_service_sur_document_hors_service_retourne_false(): void
    {
        $org = $this->org;
        $chef = $this->createUserWithPoste($org['company'], 'chef_service', $org['serviceA1']);
        $doc = $this->makeDoc($org['company'], $org['serviceB1'], $chef);

        $this->assertFalse($this->service->canGrantPermission($chef, $doc));
    }

    public function test_canView_user_ambigu_retourne_false(): void
    {
        $org = $this->org;
        $agent = $this->createUserWithPoste($org['company'], 'agent_temporaire', $org['serviceA1']);

        // 2e affectation active
        Affectation::factory()->for($agent)
            ->for(\App\Models\Poste::where('level', 'agent_temporaire')->first())
            ->for($org['serviceA2'])
            ->create([
                'is_active' => true,
                'started_at' => today()->subMonth(),
                'ended_at' => null,
            ]);

        $doc = $this->makeDoc($org['company'], $org['serviceA1'], $agent);

        // safeActiveAffectation attrape AmbiguousAffectationException et retourne false
        $this->assertFalse($this->service->canView($agent, $doc));
    }

    /*
    | Helpers
    */
    private function makeDoc($company, $service, $uploader)
    {
        return Document::create([
            'company_id' => $company->id,
            'document_type_id' => $this->org['docType']->id,
            'uploaded_by' => $uploader->id,
            'service_id' => $service->id,
            'department_id' => $service->department_id,
            'direction_id' => $service->department?->direction_id,
            'title' => 'Doc test',
            'file_path' => 'documents/test/' . \Illuminate\Support\Str::uuid() . '.pdf',
            'file_type' => 'pdf',
            'file_size' => 1024,
        ]);
    }
}
