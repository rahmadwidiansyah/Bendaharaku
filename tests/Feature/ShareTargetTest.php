<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Evidence\Jobs\ProcessEvidenceJob;
use App\Jobs\EvidenceLlmGroupingJob;
use App\Models\User;
use App\Services\Push\PushPayloadBuilder;
use Database\Factories\EvidenceFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ShareTargetTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function test_manifest_has_share_target(): void
    {
        $manifest = json_decode(file_get_contents(public_path('manifest.json')), true);
        $this->assertArrayHasKey('share_target', $manifest);
        $this->assertEquals('/chat/evidence/share', $manifest['share_target']['action']);
        $this->assertEquals('POST', $manifest['share_target']['method']);
        $files = $manifest['share_target']['params']['files'] ?? [];
        $this->assertNotEmpty($files);
        $this->assertEquals('image', $files[0]['name']);
    }

    /** @test */
    public function test_assetlinks_route_serves_json(): void
    {
        $resp = $this->get('/.well-known/assetlinks.json');
        $resp->assertOk();
        $this->assertStringContainsString('application/json', $resp->headers->get('Content-Type'));
        // BinaryFileResponse content not available via getContent in test, verify via file
        $data = json_decode(file_get_contents(public_path('.well-known/assetlinks.json')), true);
        $this->assertIsArray($data);
        $this->assertEquals('id.bendaharaku.twa', $data[0]['target']['package_name'] ?? null);
    }

    /** @test */
    public function test_share_requires_auth(): void
    {
        Storage::fake('evidence');
        $file = $this->makeFakeImage();
        $resp = $this->post('/chat/evidence/share', ['image' => $file, 'title' => 'test']);
        // unauthenticated → redirect to login
        $resp->assertRedirect(route('login'));
    }

    private function makeFakeImage(string $name = 'struk.jpg'): UploadedFile
    {
        // 1x1 JPEG base64 — passes dimensions validation without GD
        $base64 = '/9j/4AAQSkZJRgABAQEAYABgAAD/2wBDAAgGBgcGBQgHBwcJCQgKDBQNDAsLDBkSEw8UHRofHh0aHBwgJC4nICIsIxwcKDcpLDAxNDQ0Hyc5PTgyPC4zNDL/2wBDAQkJCQwLDBgNDRgyIRwhMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjL/wAARCAABAAEDASIAAhEBAxEB/8QAHwAAAQUBAQEBAQEAAAAAAAAAAAECAwQFBgcICQoL/8QAtRAAAgEDAwIEAwUFBAQAAAF9AQIDAAQRBRIhMUEGE1FhByJxFDKBkaEII0KxwRVS0fAkM2JyggkKFhcYGRolJicoKSo0NTY3ODk6Q0RFRkdISUpTVFVWV1hZWmNkZWZnaGlqc3R1dnd4eXqDhIWGh4iJipKTlJWWl5iZmqKjpKWmp6ipqrKztLW2t7i5usLDxMXGx8jJytLT1NXW19jZ2uHi4+Tl5ufo6erx8vP09fb3+Pn6/8QAHwEAAwEBAQEBAQEBAQAAAAAAAAECAwQFBgcICQoL/8QAtREAAgECBAQDBAcFBAQAAQJ3AAECAxEEBSExBhJBUQdhcRMiMoEIFEKRobHBCSMzUvAVYnLRChYkNOEl8RcYGRomJygpKjU2Nzg5OkNERUZHSElKU1RVVldYWVpjZGVmZ2hpanN2d3R4eXqCc4SFhoeIiYqSk5SVlpeYmZqio6Slpqeoqaqys7S1tre4ubrCw8TFxsfIycrS09TV1tfY2dri4+Tl5ufo6ery8/T19vf4+fr/2gAMAwEAAhEDEQA/APXKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigD//2Q==';
        $tmp = tempnam(sys_get_temp_dir(), 'testimg');
        file_put_contents($tmp, base64_decode($base64));

        return new UploadedFile($tmp, $name, 'image/jpeg', null, true);
    }

    /** @test */
    public function test_share_creates_evidence_and_chat_bubble(): void
    {
        Storage::fake('evidence');
        Queue::fake();
        $user = User::factory()->create();
        $file = $this->makeFakeImage();

        $resp = $this->actingAs($user)->post('/chat/evidence/share', [
            'image' => $file,
            'title' => 'Struk test',
            'text' => 'punyaku magelangan',
        ]);

        $resp->assertRedirect();
        // Should redirect to /chat?evidence_uuid=xxx&share=1
        $this->assertStringContainsString('/chat', $resp->headers->get('Location'));
        $this->assertStringContainsString('evidence_uuid', $resp->headers->get('Location'));

        $this->assertDatabaseHas('evidence', [
            'user_id' => $user->id,
            'source' => 'SHARE_TARGET',
        ]);

        $this->assertDatabaseHas('chat_messages', [
            'role' => 'user',
        ]);
        $this->assertDatabaseHas('chat_messages', [
            'role' => 'assistant',
        ]);
        Queue::assertPushed(ProcessEvidenceJob::class);
        Queue::assertPushed(EvidenceLlmGroupingJob::class);
    }

    /** @test */
    public function test_share_json_returns_evidence(): void
    {
        Storage::fake('evidence');
        Queue::fake();
        $user = User::factory()->create();
        $file = $this->makeFakeImage();

        $resp = $this->actingAs($user)->postJson('/chat/evidence/share', [
            'image' => $file,
            'text' => 'hello',
        ]);

        $resp->assertOk();
        $resp->assertJsonPath('success', true);
        $resp->assertJsonStructure(['evidence' => ['uuid', 'url']]);
    }

    /** @test */
    public function test_evidence_push_payload_url_to_chat(): void
    {
        $user = User::factory()->create(['locale' => 'id']);
        $evidence = EvidenceFactory::new()->create([
            'user_id' => $user->id,
            'ocr_text' => 'Total Rp 49.000',
        ]);
        // Mock parsed_data via update raw
        $evidence->update(['parsed_data' => ['document_type' => 'SHOPPING_RECEIPT', 'raw_text' => '', 'amount' => 49000, 'description' => 'Burjo Test', 'confidence' => 0.9]]);
        $evidence->refresh();

        $payload = PushPayloadBuilder::evidenceReady($user, $evidence);
        $this->assertEquals('/chat?evidence_uuid='.$evidence->uuid, $payload['url']);
        $this->assertEquals('evidence-'.$evidence->uuid, $payload['tag']);
        $this->assertStringContainsString('Rp', $payload['body']);
        $this->assertEquals('evidence', $payload['data']['kind']);
    }
}
