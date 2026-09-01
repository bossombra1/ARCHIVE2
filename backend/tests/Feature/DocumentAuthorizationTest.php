<?php

namespace Tests\Feature;

use App\Models\Affectation;
use App\Models\AmbiguousAffectationException;
use App\Models\Document;
use App\Models\DocumentPermission;
use App\Models\Poste;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Helpers\DocumentTestHelper;
use Tests\TestCase;

/**
 * Tests d'autorisation documentaire.
 *
 * Couvre les 20 scénarios demandés :
 *   1.  admin voit tous les documents
 *   2.  DG voit les documents de toutes les directions de sa company
 *   3.  directeur voit sa direction uniquement
 *   4.  responsable département voit son département + services descendants
 *   5.  chef service voit son service uniquement
 *   6.  employé voit son service uniquement
 *   7.  agent temporaire voit son service uniquement
 *   8.  permission user valide fonctionne
 *   9.  permission poste valide fonctionne
 *   10. permission service valide fonctionne
 *   11. permission expirée ne fonctionne pas
 *   12. permission permanente fonctionne
 *   13. accès cross-company refusé
 *   14. accès direct à un document interdit refusé (404)
 *   15. téléchargement interdit refusé (404)
 *   16. upload dans un autre service impossible (le service_id vient du backend)
 *   17. document soft-deleted absent de la liste normale
 *   18. affectation échue non considérée comme active
 *   19. absence d'affectation active correctement gérée
 *   20. plusieurs affectations actives détectées et refusées
 */
class DocumentAuthorizationTest extends TestCase
{
    use RefreshDatabase;
    use DocumentTestHelper;

    private array $org;

    protected function setUp(): void
    {
        parent::setUp();
        // Seed les postes
        $this->seed(\Database\Seeders\PosteSeeder::class);
        $this->org = $this->createOrganization();
    }

    /*
    |--------------------------------------------------------------------------
    | Scénarios 1 à 7 : visibilité par périmètre
    |--------------------------------------------------------------------------
    */

    public function test_admin_voit_tous_les_documents_de_sa_company(): void
    {
        $org = $this->org;
        $admin = $this->createUserWithPoste($org['company'], 'admin');

        // Création de documents dans tous les services
        $docA1 = $this->createDocument($org['company'], $org['serviceA1'], $org['docType'], $admin);
        $docA2 = $this->createDocument($org['company'], $org['serviceA2'], $org['docType'], $admin);
        $docB1 = $this->createDocument($org['company'], $org['serviceB1'], $org['docType'], $admin);

        $this->actingAsSanctum($admin);

        $response = $this->getJson('/api/documents');
        $response->assertStatus(200);

        $ids = collect($response->json('data'))->pluck('id')->all();
        $this->assertContains($docA1->id, $ids);
        $this->assertContains($docA2->id, $ids);
        $this->assertContains($docB1->id, $ids);
    }

    public function test_dg_voit_les_documents_de_toutes_les_directions_de_sa_company(): void
    {
        $org = $this->org;
        $dg = $this->createUserWithPoste($org['company'], 'dg');

        $docA1 = $this->createDocument($org['company'], $org['serviceA1'], $org['docType'], $dg);
        $docB1 = $this->createDocument($org['company'], $org['serviceB1'], $org['docType'], $dg);

        $this->actingAsSanctum($dg);

        $response = $this->getJson('/api/documents');
        $response->assertStatus(200);

        $ids = collect($response->json('data'))->pluck('id')->all();
        $this->assertContains($docA1->id, $ids);
        $this->assertContains($docB1->id, $ids);
    }

    public function test_directeur_voit_uniquement_sa_direction(): void
    {
        $org = $this->org;
        $directeur = $this->createUserWithPoste(
            $org['company'],
            'directeur',
            $org['serviceA1'], // dans direction A
        );

        // Documents : un dans direction A, un dans direction B
        $docInDirA = $this->createDocument($org['company'], $org['serviceA1'], $org['docType'], $directeur);
        $docInDirB = $this->createDocument($org['company'], $org['serviceB1'], $org['docType'], $directeur);

        $this->actingAsSanctum($directeur);

        $response = $this->getJson('/api/documents');
        $response->assertStatus(200);

        $ids = collect($response->json('data'))->pluck('id')->all();
        $this->assertContains($docInDirA->id, $ids);
        $this->assertNotContains($docInDirB->id, $ids);
    }

    public function test_responsable_departement_voit_son_departement_et_services_descendants(): void
    {
        $org = $this->org;
        $resp = $this->createUserWithPoste(
            $org['company'],
            'responsable_departement',
            $org['serviceA1'], // dans departmentA
        );

        // 2 docs dans departmentA (un sur serviceA1, un sur serviceA2)
        $docA1 = $this->createDocument($org['company'], $org['serviceA1'], $org['docType'], $resp);
        $docA2 = $this->createDocument($org['company'], $org['serviceA2'], $org['docType'], $resp);
        // 1 doc dans departmentB (autre département, ne doit pas être visible)
        $docB1 = $this->createDocument($org['company'], $org['serviceB1'], $org['docType'], $resp);

        $this->actingAsSanctum($resp);

        $response = $this->getJson('/api/documents');
        $response->assertStatus(200);

        $ids = collect($response->json('data'))->pluck('id')->all();
        $this->assertContains($docA1->id, $ids);
        $this->assertContains($docA2->id, $ids);
        $this->assertNotContains($docB1->id, $ids);
    }

    public function test_chef_service_voit_uniquement_son_service(): void
    {
        $org = $this->org;
        $chef = $this->createUserWithPoste(
            $org['company'],
            'chef_service',
            $org['serviceA1'],
        );

        $docA1 = $this->createDocument($org['company'], $org['serviceA1'], $org['docType'], $chef);
        $docA2 = $this->createDocument($org['company'], $org['serviceA2'], $org['docType'], $chef); // même département, autre service

        $this->actingAsSanctum($chef);

        $response = $this->getJson('/api/documents');
        $response->assertStatus(200);

        $ids = collect($response->json('data'))->pluck('id')->all();
        $this->assertContains($docA1->id, $ids);
        $this->assertNotContains($docA2->id, $ids);
    }

    public function test_employe_voit_uniquement_son_service(): void
    {
        $org = $this->org;
        $employe = $this->createUserWithPoste(
            $org['company'],
            'employe',
            $org['serviceA1'],
        );

        $docA1 = $this->createDocument($org['company'], $org['serviceA1'], $org['docType'], $employe);
        $docA2 = $this->createDocument($org['company'], $org['serviceA2'], $org['docType'], $employe);

        $this->actingAsSanctum($employe);

        $response = $this->getJson('/api/documents');
        $response->assertStatus(200);

        $ids = collect($response->json('data'))->pluck('id')->all();
        $this->assertContains($docA1->id, $ids);
        $this->assertNotContains($docA2->id, $ids);
    }

    public function test_agent_temporaire_voit_uniquement_son_service(): void
    {
        $org = $this->org;
        $agent = $this->createUserWithPoste(
            $org['company'],
            'agent_temporaire',
            $org['serviceA1'],
        );

        $docA1 = $this->createDocument($org['company'], $org['serviceA1'], $org['docType'], $agent);
        $docB1 = $this->createDocument($org['company'], $org['serviceB1'], $org['docType'], $agent);

        $this->actingAsSanctum($agent);

        $response = $this->getJson('/api/documents');
        $response->assertStatus(200);

        $ids = collect($response->json('data'))->pluck('id')->all();
        $this->assertContains($docA1->id, $ids);
        $this->assertNotContains($docB1->id, $ids);
    }

    /*
    |--------------------------------------------------------------------------
    | Scénarios 8 à 12 : permissions
    |--------------------------------------------------------------------------
    */

    public function test_permission_user_valide_fonctionne(): void
    {
        $org = $this->org;
        $agent = $this->createUserWithPoste($org['company'], 'agent_temporaire', $org['serviceA1']);
        $docB1 = $this->createDocument($org['company'], $org['serviceB1'], $org['docType'], $agent);

        DocumentPermission::create([
            'document_id' => $docB1->id,
            'target_type' => DocumentPermission::TARGET_USER,
            'target_id' => $agent->id,
            'expires_at' => now()->addDay(),
        ]);

        $this->actingAsSanctum($agent);

        $response = $this->getJson('/api/documents');
        $ids = collect($response->json('data'))->pluck('id')->all();
        $this->assertContains($docB1->id, $ids, "L'agent doit voir le document via permission user valide.");
    }

    public function test_permission_poste_valide_fonctionne(): void
    {
        $org = $this->org;
        $agent = $this->createUserWithPoste($org['company'], 'agent_temporaire', $org['serviceA1']);
        $docB1 = $this->createDocument($org['company'], $org['serviceB1'], $org['docType'], $agent);

        $posteAgent = Poste::where('level', 'agent_temporaire')->first();

        DocumentPermission::create([
            'document_id' => $docB1->id,
            'target_type' => DocumentPermission::TARGET_POSTE,
            'target_id' => $posteAgent->id,
            'expires_at' => now()->addDay(),
        ]);

        $this->actingAsSanctum($agent);

        $response = $this->getJson('/api/documents');
        $ids = collect($response->json('data'))->pluck('id')->all();
        $this->assertContains($docB1->id, $ids);
    }

    public function test_permission_service_valide_fonctionne(): void
    {
        $org = $this->org;
        $agent = $this->createUserWithPoste($org['company'], 'agent_temporaire', $org['serviceA1']);
        $docB1 = $this->createDocument($org['company'], $org['serviceB1'], $org['docType'], $agent);

        DocumentPermission::create([
            'document_id' => $docB1->id,
            'target_type' => DocumentPermission::TARGET_SERVICE,
            'target_id' => $org['serviceA1']->id,
            'expires_at' => now()->addDay(),
        ]);

        $this->actingAsSanctum($agent);

        $response = $this->getJson('/api/documents');
        $ids = collect($response->json('data'))->pluck('id')->all();
        $this->assertContains($docB1->id, $ids);
    }

    public function test_permission_expiree_ne_fonctionne_pas(): void
    {
        $org = $this->org;
        $agent = $this->createUserWithPoste($org['company'], 'agent_temporaire', $org['serviceA1']);
        $docB1 = $this->createDocument($org['company'], $org['serviceB1'], $org['docType'], $agent);

        DocumentPermission::create([
            'document_id' => $docB1->id,
            'target_type' => DocumentPermission::TARGET_USER,
            'target_id' => $agent->id,
            'expires_at' => now()->subDay(), // expirée hier
        ]);

        $this->actingAsSanctum($agent);

        $response = $this->getJson('/api/documents');
        $ids = collect($response->json('data'))->pluck('id')->all();
        $this->assertNotContains($docB1->id, $ids, "Une permission expirée ne doit pas donner accès.");
    }

    public function test_permission_permanente_fonctionne(): void
    {
        $org = $this->org;
        $agent = $this->createUserWithPoste($org['company'], 'agent_temporaire', $org['serviceA1']);
        $docB1 = $this->createDocument($org['company'], $org['serviceB1'], $org['docType'], $agent);

        DocumentPermission::create([
            'document_id' => $docB1->id,
            'target_type' => DocumentPermission::TARGET_USER,
            'target_id' => $agent->id,
            'expires_at' => null, // permanente
        ]);

        $this->actingAsSanctum($agent);

        $response = $this->getJson('/api/documents');
        $ids = collect($response->json('data'))->pluck('id')->all();
        $this->assertContains($docB1->id, $ids);
    }

    /*
    |--------------------------------------------------------------------------
    | Scénarios 13 à 16 : cross-company, IDOR, upload cross-service
    |--------------------------------------------------------------------------
    */

    public function test_acces_cross_company_refuse(): void
    {
        $org = $this->org;
        $admin1 = $this->createUserWithPoste($org['company'], 'admin');

        // Deuxième company avec son propre admin
        $org2 = $this->createOrganization();
        $admin2 = $this->createUserWithPoste($org2['company'], 'admin');

        $docInCompany2 = $this->createDocument($org2['company'], $org2['serviceA1'], $org2['docType'], $admin2);

        $this->actingAsSanctum($admin1);

        // admin1 ne doit pas voir le document de company2
        $response = $this->getJson('/api/documents');
        $ids = collect($response->json('data'))->pluck('id')->all();
        $this->assertNotContains($docInCompany2->id, $ids);

        // Accès direct refusé (404)
        $show = $this->getJson("/api/documents/{$docInCompany2->id}");
        $show->assertStatus(404);
    }

    public function test_acces_direct_a_un_document_interdit_refuse_404(): void
    {
        $org = $this->org;
        $agent = $this->createUserWithPoste($org['company'], 'agent_temporaire', $org['serviceA1']);
        $docB1 = $this->createDocument($org['company'], $org['serviceB1'], $org['docType'], $agent);

        $this->actingAsSanctum($agent);

        $response = $this->getJson("/api/documents/{$docB1->id}");
        $response->assertStatus(404); // 404 pour limiter l'énumération
    }

    public function test_telechargement_interdit_refuse_404(): void
    {
        $org = $this->org;
        $agent = $this->createUserWithPoste($org['company'], 'agent_temporaire', $org['serviceA1']);
        $docB1 = $this->createDocument($org['company'], $org['serviceB1'], $org['docType'], $agent);

        $this->actingAsSanctum($agent);

        $response = $this->getJson("/api/documents/{$docB1->id}/download");
        $response->assertStatus(404);
    }

    public function test_upload_dans_un_autre_service_impossible(): void
    {
        $org = $this->org;
        $agent = $this->createUserWithPoste($org['company'], 'agent_temporaire', $org['serviceA1']);

        $this->actingAsSanctum($agent);

        // Le frontend tente d'envoyer service_id = serviceB1 (autre service)
        // Le backend ignore ce champ et utilise l'affectation active (serviceA1)
        $response = $this->postJson('/api/documents', [
            'title' => 'Test upload cross-service',
            'document_type_id' => $org['docType']->id,
            'service_id' => $org['serviceB1']->id, // tentative de contournement
            'file' => \Illuminate\Http\UploadedFile::fake()->create('test.pdf', 100, 'application/pdf'),
        ]);

        $response->assertStatus(201);

        // Vérifier que le document a été créé avec serviceA1 (affectation active), pas serviceB1
        $doc = Document::latest('id')->first();
        $this->assertEquals($org['serviceA1']->id, $doc->service_id);
        $this->assertNotEquals($org['serviceB1']->id, $doc->service_id);
    }

    /*
    |--------------------------------------------------------------------------
    | Scénarios 17 à 20 : soft delete, affectation échue, absence, ambiguë
    |--------------------------------------------------------------------------
    */

    public function test_document_soft_deleted_absent_de_la_liste_normale(): void
    {
        $org = $this->org;
        $admin = $this->createUserWithPoste($org['company'], 'admin');

        $doc = $this->createDocument($org['company'], $org['serviceA1'], $org['docType'], $admin);
        $doc->delete(); // soft delete

        $this->actingAsSanctum($admin);

        $response = $this->getJson('/api/documents');
        $ids = collect($response->json('data'))->pluck('id')->all();
        $this->assertNotContains($doc->id, $ids);

        // Accès direct au soft-deleted -> 404
        $show = $this->getJson("/api/documents/{$doc->id}");
        $show->assertStatus(404);
    }

    public function test_affectation_echue_non_considerer_active(): void
    {
        $org = $this->org;
        $agent = $this->createUserWithPoste($org['company'], 'agent_temporaire', $org['serviceA1']);

        // Clôture l'affectation
        $agent->affectations()->update([
            'is_active' => false,
            'ended_at' => today()->subDay(),
        ]);

        $doc = $this->createDocument($org['company'], $org['serviceA1'], $org['docType'], $agent);

        $this->actingAsSanctum($agent);

        // Pas d'affectation active -> refus
        $response = $this->getJson('/api/documents');
        $response->assertStatus(403);
    }

    public function test_absence_affectation_active_correctement_geree(): void
    {
        $org = $this->org;
        $user = \App\Models\User::factory()->for($org['company'])->create();
        // Aucune affectation créée

        $doc = $this->createDocument($org['company'], $org['serviceA1'], $org['docType'], $user);

        $this->actingAsSanctum($user);

        $response = $this->getJson('/api/documents');
        $response->assertStatus(403);
    }

    public function test_plusieurs_affectations_actives_gerees_et_refusees(): void
    {
        $org = $this->org;
        $agent = $this->createUserWithPoste($org['company'], 'agent_temporaire', $org['serviceA1']);

        // Ajoute une 2e affectation active simultanée
        $posteAgent = Poste::where('level', 'agent_temporaire')->first();
        Affectation::factory()->for($agent)->for($posteAgent)->for($org['serviceA2'])->create([
            'is_active' => true,
            'started_at' => today()->subMonth(),
            'ended_at' => null,
        ]);

        $doc = $this->createDocument($org['company'], $org['serviceA1'], $org['docType'], $agent);

        $this->actingAsSanctum($agent);

        // La policy doit attraper AmbiguousAffectationException et refuser
        $response = $this->getJson('/api/documents');
        $response->assertStatus(403);
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers internes
    |--------------------------------------------------------------------------
    */

    private function actingAsSanctum($user)
    {
        return $this->actingAs($user, 'sanctum');
    }

    private function createDocument($company, $service, $docType, $uploader)
    {
        return Document::create([
            'company_id' => $company->id,
            'document_type_id' => $docType->id,
            'uploaded_by' => $uploader->id,
            'service_id' => $service->id,
            'department_id' => $service->department_id,
            'direction_id' => $service->department?->direction_id,
            'title' => 'Doc in ' . $service->name,
            'description' => 'Test document',
            'file_path' => 'documents/test/' . \Illuminate\Support\Str::uuid() . '.pdf',
            'file_type' => 'pdf',
            'file_size' => 1024,
        ]);
    }
}
