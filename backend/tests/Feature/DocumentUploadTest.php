<?php

namespace Tests\Feature;

use App\Models\Document;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Helpers\DocumentTestHelper;
use Tests\TestCase;

/**
 * Tests d'upload documentaire.
 *
 * Couvre :
 *   - upload réussi avec PDF valide
 *   - validation des types MIME interdits (.exe, .php)
 *   - validation de la taille maximale
 *   - le périmètre organisationnel est déterminé côté backend
 *   - le fichier est stocké sur le disque privé (pas public)
 *   - le fichier est physiquement présent après l'upload
 */
class DocumentUploadTest extends TestCase
{
    use RefreshDatabase;
    use DocumentTestHelper;

    private array $org;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\PosteSeeder::class);
        $this->org = $this->createOrganization();
        Storage::fake('local');
    }

    public function test_upload_reussi_avec_pdf_valide(): void
    {
        $org = $this->org;
        $chef = $this->createUserWithPoste($org['company'], 'chef_service', $org['serviceA1']);

        $this->actingAs($chef, 'sanctum');

        $file = UploadedFile::fake()->create('doc.pdf', 100, 'application/pdf');

        $response = $this->postJson('/api/documents', [
            'title' => 'Mon document',
            'description' => 'Test description',
            'document_type_id' => $org['docType']->id,
            'file' => $file,
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('documents', [
            'title' => 'Mon document',
            'uploaded_by' => $chef->id,
            'service_id' => $org['serviceA1']->id,
            'company_id' => $org['company']->id,
        ]);

        // Vérifie que le fichier est physiquement stocké sur le disk 'local'
        $doc = Document::latest('id')->first();
        Storage::disk('local')->assertExists($doc->file_path);
    }

    public function test_upload_refuse_type_mime_interdit(): void
    {
        $org = $this->org;
        $chef = $this->createUserWithPoste($org['company'], 'chef_service', $org['serviceA1']);

        $this->actingAs($chef, 'sanctum');

        // .exe déguisé — finfo détectera application/x-dosexec
        $file = UploadedFile::fake()->create('malicious.pdf', 100, 'application/x-dosexec');

        $response = $this->postJson('/api/documents', [
            'title' => 'Malicious',
            'document_type_id' => $org['docType']->id,
            'file' => $file,
        ]);

        $response->assertStatus(422);
    }

    public function test_upload_refuse_extension_interdite(): void
    {
        $org = $this->org;
        $chef = $this->createUserWithPoste($org['company'], 'chef_service', $org['serviceA1']);

        $this->actingAs($chef, 'sanctum');

        $file = UploadedFile::fake()->create('script.php', 100, 'text/plain');

        $response = $this->postJson('/api/documents', [
            'title' => 'PHP script',
            'document_type_id' => $org['docType']->id,
            'file' => $file,
        ]);

        $response->assertStatus(422);
    }

    public function test_upload_refuse_fichier_trop_grand(): void
    {
        $org = $this->org;
        $chef = $this->createUserWithPoste($org['company'], 'chef_service', $org['serviceA1']);

        $this->actingAs($chef, 'sanctum');

        // 11 Mo > 10 Mo (config par défaut)
        $file = UploadedFile::fake()->create('big.pdf', 11 * 1024, 'application/pdf');

        $response = $this->postJson('/api/documents', [
            'title' => 'Big file',
            'document_type_id' => $org['docType']->id,
            'file' => $file,
        ]);

        $response->assertStatus(422);
    }

    public function test_upload_determine_perimetre_cote_backend(): void
    {
        $org = $this->org;
        $chef = $this->createUserWithPoste($org['company'], 'chef_service', $org['serviceA1']);

        $this->actingAs($chef, 'sanctum');

        $file = UploadedFile::fake()->create('doc.pdf', 100, 'application/pdf');

        $response = $this->postJson('/api/documents', [
            'title' => 'Doc avec tentative de contournement',
            'document_type_id' => $org['docType']->id,
            'service_id' => $org['serviceB1']->id, // tentative
            'department_id' => $org['departmentB']->id,
            'direction_id' => $org['directionB']->id,
            'file' => $file,
        ]);

        $response->assertStatus(201);

        $doc = Document::latest('id')->first();
        $this->assertEquals($org['serviceA1']->id, $doc->service_id, "Le service_id doit venir de l'affectation, pas du frontend.");
        $this->assertEquals($org['company']->id, $doc->company_id);
    }

    public function test_upload_sans_fichier_rejete(): void
    {
        $org = $this->org;
        $chef = $this->createUserWithPoste($org['company'], 'chef_service', $org['serviceA1']);

        $this->actingAs($chef, 'sanctum');

        $response = $this->postJson('/api/documents', [
            'title' => 'No file',
            'document_type_id' => $org['docType']->id,
        ]);

        $response->assertStatus(422);
    }
}
