<?php

namespace Tests\Feature;

use App\Http\Middleware\VerifyCsrfToken;
use App\Mail\NotifyMail;
use App\Models\Status;
use App\Models\Transport;
use App\Models\TransportNotice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File as FileFacade;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class DocumentReturnFlowTest extends TestCase
{
    use RefreshDatabase;

    private const API_KEY = 'test-key';

    private string $origCwd;

    /** @var int[] transport ids whose upload dirs need cleanup */
    private array $uploadDirs = [];

    private int $seq = 0;

    protected function setUp(): void
    {
        parent::setUp();

        config(['api.key' => self::API_KEY]);

        // exists()/file() overujú is_readable() na ceste relatívnej k public/
        // (tak ako pri web requeste) – testy preto bežia s CWD = public.
        $this->origCwd = getcwd();
        chdir(public_path());

        Status::forceCreate(['name_sk' => 'Čaká na spracovanie', 'name_en' => 'Pending', 'slug' => 'uploaded']);
        Status::forceCreate(['name_sk' => 'Spracované', 'name_en' => 'Processed', 'slug' => 'processed']);
        Status::forceCreate(['name_sk' => 'Uhradené', 'name_en' => 'Paid', 'slug' => 'paid']);
    }

    protected function tearDown(): void
    {
        chdir($this->origCwd);

        foreach ($this->uploadDirs as $id) {
            FileFacade::deleteDirectory(public_path('data/transports/' . $id));
        }

        parent::tearDown();
    }

    // ---------------------------------------------------------------- helpers

    private function makeUser(): User
    {
        $this->seq++;

        $user = new User([
            'driver_id' => 90000 + $this->seq,
            'name' => 'Test Carrier ' . $this->seq,
            'email' => "carrier{$this->seq}@example.test",
            'country' => 'SK',
        ]);
        $user->password = bcrypt('secret');
        $user->token = 'test-token-' . $this->seq;
        $user->notify_email = $user->email;
        $user->save();

        return $user;
    }

    private function makeTransport(User $user, array $attrs = []): Transport
    {
        $this->seq++;

        $transport = $user->transports()->create(array_merge([
            'number' => 'T' . (202600000 + $this->seq),
            'transport_id' => (string) (500000 + $this->seq),
            'due_days' => 60,
        ], $attrs));

        $this->uploadDirs[] = $transport->id;

        return $transport;
    }

    /** Transport v stave "prevzaté damarom": oba doklady stiahnuté, lokálne súbory soft-deleted. */
    private function makeTakenOverTransport(User $user): Transport
    {
        $transport = $this->makeTransport($user);

        foreach (['bill', 'docs'] as $slot) {
            $file = $transport->files()->create([
                'filename' => 'local-' . $slot . '.pdf',
                'path' => "data/transports/{$transport->id}/files/",
                'type' => $slot,
            ]);
            $file->delete();

            $transport->{$slot . '_sent'} = 1;
            $transport->{$slot . '_file'} = 'dmr-' . $slot . '.pdf';
        }

        $transport->status_id = Status::where('slug', 'uploaded')->value('id');
        $transport->save();

        return $transport->fresh();
    }

    private function api(string $uri, array $data = [])
    {
        return $this->postJson('/api' . $uri, $data + ['api_key' => self::API_KEY]);
    }

    private function uploadSlot(User $user, Transport $transport, string $slot)
    {
        $route = $slot === 'bill' ? 'transports.bill_document' : 'transports.doc_document';

        return $this->actingAs($user)
            ->withoutMiddleware(VerifyCsrfToken::class)
            ->post(route($route, $transport->id), [
                $slot => UploadedFile::fake()->create($slot . '.pdf', 5, 'application/pdf'),
            ]);
    }

    private function syncPayload(Transport $transport, User $user, array $overrides = []): array
    {
        return [
            'transport' => array_merge([
                'number' => (string) $transport->number,
                'transport_id' => (string) $transport->transport_id,
                'due_days' => 60,
                'loading_date' => '2026-07-01',
                'loading' => 'BERGA',
                'unloading' => 'BRATISLAVA',
                'weight' => 5,
                'ldm' => '13.6',
            ], $overrides),
            'driver' => [
                'driver_id' => $user->driver_id,
                'name' => $user->name,
                'email' => $user->email,
                'country' => 'SK',
            ],
        ];
    }

    // ---------------------------------------------------------------- tests

    public function test_full_pipeline_upload_pickup_takeover(): void
    {
        $user = $this->makeUser();
        $transport = $this->makeTransport($user);

        // 1. Dopravca nahrá oba doklady.
        $this->uploadSlot($user, $transport, 'bill')->assertRedirect();
        $this->uploadSlot($user, $transport, 'docs')->assertRedirect();

        $transport->refresh();
        $this->assertNotNull($transport->bill_file);
        $this->assertNotNull($transport->docs_file);
        $this->assertSame(0, (int) $transport->bill_sent);
        $this->assertSame('uploaded', $transport->status_slug);

        // 2. Damaro vidí oba doklady na stiahnutie.
        $this->api('/transport/exists', ['id' => $transport->transport_id])
            ->assertOk()
            ->assertJson(['exists' => true, 'bill' => true, 'docs' => true]);

        // 3. Damaro stiahne a potvrdí.
        $this->api('/transport/file/success', ['id' => $transport->transport_id, 'file' => 'bill', 'filename' => 'dmr-bill.pdf'])->assertOk();
        $this->api('/transport/file/success', ['id' => $transport->transport_id, 'file' => 'docs', 'filename' => 'dmr-docs.pdf'])->assertOk();

        $transport->refresh();
        $this->assertSame(1, (int) $transport->bill_sent);
        $this->assertSame(1, (int) $transport->docs_sent);

        // 4. Sync prevezme súbory (soft-delete lokálnych) – žiadny returned stav.
        $this->api('/transport/request', $this->syncPayload($transport, $user, [
            'bill_file' => 'dmr-bill.pdf',
            'docs_file' => 'dmr-docs.pdf',
        ]))->assertOk();

        $transport->refresh();
        $this->assertSame(0, $transport->files()->count(), 'live files should be taken over (soft-deleted)');
        $this->assertSame(2, $transport->files()->withTrashed()->count());
        $this->assertNull($transport->bill_returned_at);
        $this->assertSame('uploaded', $transport->display_status_slug);
        $this->assertFalse($transport->needs_action);
    }

    public function test_return_bill_notice_then_delete(): void
    {
        Mail::fake();

        $user = $this->makeUser();
        $transport = $this->makeTakenOverTransport($user);

        // Poradie notice -> delete.
        $this->api('/transport/modify_driver_notice', [
            'id' => $transport->transport_id,
            'driver_notice' => 'Opravte fakturačnú adresu.',
        ])->assertOk();

        Mail::assertSent(NotifyMail::class);
        $this->assertSame(1, TransportNotice::where('transport_id', $transport->id)->where('source', 'notice')->count());

        $this->api('/transport/bill-delete', ['id' => $transport->transport_id])->assertOk();

        $transport->refresh();
        $this->assertNotNull($transport->bill_returned_at);
        $this->assertSame('Opravte fakturačnú adresu.', $transport->bill_return_reason);
        $this->assertNull($transport->bill_file);
        $this->assertSame(0, (int) $transport->bill_sent);
        $this->assertTrue($transport->isSlotReturned('bill'));
        $this->assertFalse($transport->isSlotReturned('docs'));
        $this->assertSame('returned', $transport->display_status_slug);
        $this->assertTrue($transport->needs_action);
        $this->assertSame(1, TransportNotice::where('transport_id', $transport->id)->where('source', 'return')->where('slot', 'bill')->count());
    }

    public function test_return_docs_delete_then_notice_fills_reason(): void
    {
        Mail::fake();

        $user = $this->makeUser();
        $transport = $this->makeTakenOverTransport($user);

        // Tiché vrátenie bez poznámky.
        $this->api('/transport/docs-delete', ['id' => $transport->transport_id])->assertOk();

        $transport->refresh();
        $this->assertNotNull($transport->docs_returned_at);
        $this->assertNull($transport->docs_return_reason);
        $this->assertSame('returned', $transport->display_status_slug);

        // Poznámka dodaná dodatočne doplní dôvod vrátenia.
        $this->api('/transport/modify_driver_notice', [
            'id' => $transport->transport_id,
            'driver_notice' => 'Chýba CMR z Ivanky.',
        ])->assertOk();

        $transport->refresh();
        $this->assertSame('Chýba CMR z Ivanky.', $transport->docs_return_reason);

        // Zmena textu poznámky prepíše aj dôvod stále vráteného slotu
        // (edit modal v damare: kôš a Uložiť sú samostatné requesty).
        $this->api('/transport/modify_driver_notice', [
            'id' => $transport->transport_id,
            'driver_notice' => 'Chýba CMR z Ivanky aj dodací list.',
        ])->assertOk();

        $transport->refresh();
        $this->assertSame('Chýba CMR z Ivanky aj dodací list.', $transport->docs_return_reason);
    }

    public function test_reupload_clears_returned_state_and_notice(): void
    {
        Mail::fake();

        $user = $this->makeUser();
        $transport = $this->makeTakenOverTransport($user);

        $this->api('/transport/modify_driver_notice', ['id' => $transport->transport_id, 'driver_notice' => 'Zlá suma.'])->assertOk();
        $this->api('/transport/bill-delete', ['id' => $transport->transport_id])->assertOk();

        $this->uploadSlot($user, $transport, 'bill')->assertRedirect();

        $transport->refresh();
        $this->assertNull($transport->bill_returned_at);
        $this->assertNull($transport->bill_return_reason);
        $this->assertNull($transport->driver_notice, 'notice is cleared once no slot awaits correction');
        // docs sú u damara (docs_file nastavený) + bill živý => Čaká na spracovanie.
        $this->assertSame('uploaded', $transport->display_status_slug);
        // História ostáva.
        $this->assertGreaterThanOrEqual(2, TransportNotice::where('transport_id', $transport->id)->count());
        // Damaro vidí opravu na stiahnutie.
        $this->api('/transport/exists', ['id' => $transport->transport_id])
            ->assertOk()
            ->assertJson(['bill' => true, 'docs' => false]);
    }

    public function test_sync_does_not_eat_fresh_reupload(): void
    {
        Mail::fake();

        $user = $this->makeUser();
        $transport = $this->makeTakenOverTransport($user);

        $this->api('/transport/bill-delete', ['id' => $transport->transport_id])->assertOk();
        $this->uploadSlot($user, $transport, 'bill')->assertRedirect();

        $transport->refresh();
        $localBill = $transport->bill_file;
        $this->assertNotNull($localBill);

        // Sync so starým bill_file z damara NESMIE zožrať čerstvý upload
        // (regres prípadu 202602114).
        $this->api('/transport/request', $this->syncPayload($transport, $user, [
            'bill_file' => 'old-dmr-bill.pdf',
            'docs_file' => 'dmr-docs.pdf',
        ]))->assertOk();

        $transport->refresh();
        $this->assertSame(1, $transport->files()->where('type', 'bill')->count(), 'fresh upload must stay alive');
        $this->assertSame(0, (int) $transport->bill_sent, 'fresh upload must remain offered to Damaro');
        $this->assertSame($localBill, $transport->bill_file, 'local filename must not be overwritten by stale sync');

        $this->api('/transport/exists', ['id' => $transport->transport_id])
            ->assertOk()
            ->assertJson(['bill' => true]);
    }

    public function test_bill_delete_skips_when_fresh_upload_pending(): void
    {
        $user = $this->makeUser();
        $transport = $this->makeTakenOverTransport($user);

        $this->api('/transport/bill-delete', ['id' => $transport->transport_id])->assertOk();
        $this->uploadSlot($user, $transport, 'bill')->assertRedirect();

        // Race: admin vráti doklad, ale oprava už čaká na vyzdvihnutie.
        $this->api('/transport/bill-delete', ['id' => $transport->transport_id])->assertOk();

        $transport->refresh();
        $this->assertSame(1, $transport->files()->where('type', 'bill')->count(), 'pending correction must not be deleted');
        $this->assertNull($transport->bill_returned_at, 'slot must not be marked returned while correction awaits pickup');
        $this->assertSame(0, (int) $transport->bill_sent);
    }

    public function test_paid_status_wins_over_returned(): void
    {
        $user = $this->makeUser();
        $transport = $this->makeTakenOverTransport($user);

        $transport->status_id = Status::where('slug', 'paid')->value('id');
        $transport->bill_returned_at = now();
        $transport->save();

        $this->assertSame('paid', $transport->fresh()->display_status_slug);
    }

    public function test_legacy_notice_without_returned_state_displays_as_before(): void
    {
        $user = $this->makeUser();
        $transport = $this->makeTransport($user, [
            'driver_notice' => 'Stará poznámka spred nasadenia.',
            'bill_sent' => 0,
            'docs_sent' => 1,
        ]);
        $transport->status_id = Status::where('slug', 'uploaded')->value('id');
        $transport->save();

        // Bez returned_at sa legacy riadok zobrazuje po starom (žiadny falošný červený badge).
        $this->assertSame('uploaded', $transport->fresh()->display_status_slug);
    }

    public function test_combined_documents_upload_sets_slot_columns(): void
    {
        $user = $this->makeUser();
        $transport = $this->makeTransport($user);

        $this->actingAs($user)
            ->withoutMiddleware(VerifyCsrfToken::class)
            ->post(route('transports.documents', $transport->id), [
                'bill' => UploadedFile::fake()->create('bill.pdf', 5, 'application/pdf'),
                'docs' => UploadedFile::fake()->create('docs.pdf', 5, 'application/pdf'),
                'email' => $user->email,
            ])->assertRedirect();

        $transport->refresh();
        $this->assertNotNull($transport->bill_file, 'documents() must set bill_file like bill_document() does');
        $this->assertNotNull($transport->docs_file);
        $this->assertSame(0, (int) $transport->bill_sent);
        $this->assertSame(0, (int) $transport->docs_sent);
        $this->assertSame('uploaded', $transport->status_slug);
    }
}
