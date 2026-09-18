<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\DocumentPermission;
use App\Models\Poste;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Helpers\DocumentTestHelper;
use Tests\TestCase;

/**
 * Tests de gestion des permissions documentaires.
 *
 * Couvre :
 *   - qui peut accorder (matrice grantors)
 *   - qui ne peut pas accorder (employe, agent_temporaire)
 *   - la cible doit appartenir au périmètre du grantor
 *   - la cible doit appartenir à la même company (cross-company refusé)
 *   - le retrait d'une permission prend effet immédiatement
 *   - une permission ne donne pas le droit de modification/suppression
 */
class DocumentPermissionTest extends TestCase
{
    use RefreshDatabase;
    use DocumentTestHelper;

    private array $org;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\PosteSeeder::class);
        $this->org = $this->createOrganization();
    }

    public function test_admin_peut_accorder_une_permission_user(): void
    {
        $org = $this->org;
        $admin = $this->createUserWithPoste($org['company'], 'admin');
        $doc = $this->createDoc($org['company'], $org['serviceA1'], $admin);
        $targetUser = $this->createUserWithPoste($org['company'], 'agent_temporaire', $org['serviceB1']);

        $this->actingAs($admin, 'sanctum');

        $response = $this->postJson("/api/documents/{$doc->id}/permissions", [
            'target_type' => 'user',
            'target_id' => $targetUser->id,
            'expires_at' => now()->addDay()->format('Y-m-d H:i:s'),
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('document_permissions', [
            'document_id' => $doc->id,
            'target_type' => 'user',
            'target_id' => $targetUser->id,
        ]);
    }

    public function test_employe_ne_peut_pas_accorder_de_permission(): void
    {
        $org = $this->org;
        $employe = $this->createUserWithPoste($org['company'], 'employe', $org['serviceA1']);
        $doc = $this->createDoc($org['company'], $org['serviceA1'], $employe);
        $targetUser = $this->createUserWithPoste($org['company'], 'agent_temporaire', $org['serviceA1']);

        $this->actingAs($employe, 'sanctum');

        $response = $this->postJson("/api/documents/{$doc->id}/permissions", [
            'target_type' => 'user',
            'target_id' => $targetUser->id,
        ]);

        $response->assertStatus(403);
    }

    public function test_agent_temporaire_ne_peut_pas_accorder_de_permission(): void
    {
        $org = $this->org;
        $agent = $this->createUserWithPoste($org['company'], 'agent_temporaire', $org['serviceA1']);
        $doc = $this->createDoc($org['company'], $org['serviceA1'], $agent);

        $this->actingAs($agent, 'sanctum');

        $response = $this->postJson("/api/documents/{$doc->id}/permissions", [
            'target_type' => 'user',
            'target_id' => $agent->id,
        ]);

        $response->assertStatus(403);
    }

    public function test_directeur_ne_peut_pas_cibler_user_hors_de_sa_direction(): void
    {
        $org = $this->org;
        $directeur = $this->createUserWithPoste($org['company'], 'directeur', $org['serviceA1']); // direction A
        $doc = $this->createDoc($org['company'], $org['serviceA1'], $directeur);

        // user dans direction B (autre direction)
        $userOutside = $this->createUserWithPoste($org['company'], 'agent_temporaire', $org['serviceB1']);

        $this->actingAs($directeur, 'sanctum');

        $response = $this->postJson("/api/documents/{$doc->id}/permissions", [
            'target_type' => 'user',
            'target_id' => $userOutside->id,
        ]);

        $response->assertStatus(422); // 422 car la cible est dans une autre direction
    }

    public function test_cible_user_cross_company_refusee(): void
    {
        $org = $this->org;
        $admin = $this->createUserWithPoste($org['company'], 'admin');
        $doc = $this->createDoc($org['company'], $org['serviceA1'], $admin);

        $org2 = $this->createOrganization();
        $userOutside = $this->createUserWithPoste($org2['company'], 'agent_temporaire', $org2['serviceA1']);

        $this->actingAs($admin, 'sanctum');

        $response = $this->postJson("/api/documents/{$doc->id}/permissions", [
            'target_type' => 'user',
            'target_id' => $userOutside->id,
        ]);

        $response->assertStatus(422); // La validation FormRequest doit rejeter (target_id n'existe pas dans la company)
    }

    public function test_permission_ne_donne_pas_droit_de_modification(): void
    {
        $org = $this->org;
        $agent = $this->createUserWithPoste($org['company'], 'agent_temporaire', $org['serviceA1']);
        $docB1 = $this->createDoc($org['company'], $org['serviceB1'], $this->createUserWithPoste($org['company'], 'agent_temporaire', $org['serviceB1'])); // pas son service, pas son document

        DocumentPermission::create([
            'document_id' => $docB1->id,
            'target_type' => DocumentPermission::TARGET_USER,
            'target_id' => $agent->id,
            'expires_at' => null,
        ]);

        $this->actingAs($agent, 'sanctum');

        // Le user peut voir le document via permission...
        $show = $this->getJson("/api/documents/{$docB1->id}");
        $show->assertStatus(200);

        // ... mais ne peut pas le modifier
        $update = $this->putJson("/api/documents/{$docB1->id}", [
            'title' => 'Modified title',
        ]);
        $update->assertStatus(403);
    }

    public function test_retrait_permission_prend_effet_immediat(): void
    {
        $org = $this->org;
        $agent = $this->createUserWithPoste($org['company'], 'agent_temporaire', $org['serviceA1']);
        $docB1 = $this->createDoc($org['company'], $org['serviceB1'], $agent);

        $permission = DocumentPermission::create([
            'document_id' => $docB1->id,
            'target_type' => DocumentPermission::TARGET_USER,
            'target_id' => $agent->id,
            'expires_at' => null,
        ]);

        $this->actingAs($agent, 'sanctum');

        // Visible tant que la permission existe
        $show = $this->getJson("/api/documents/{$docB1->id}");
        $show->assertStatus(200);

        // Admin révoque la permission
        $admin = $this->createUserWithPoste($org['company'], 'admin');
        $this->actingAs($admin, 'sanctum');
        $revoke = $this->deleteJson("/api/document-permissions/{$permission->id}");
        $revoke->assertStatus(200);

        // L'agent ne peut plus voir
        $this->actingAs($agent, 'sanctum');
        $show = $this->getJson("/api/documents/{$docB1->id}");
        $show->assertStatus(404);
    }

    /*
    | Helpers
    */
    private function createDoc($company, $service, $uploader)
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
