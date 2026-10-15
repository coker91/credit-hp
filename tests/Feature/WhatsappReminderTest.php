<?php

namespace Tests\Feature;

use App\Filament\Resources\WhatsappReminderResource;
use App\Models\Contract;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use App\Models\WhatsappReminder;
use App\Services\PaymentReminderService;
use App\Services\WaService;
use Carbon\Carbon;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class WhatsappReminderTest extends TestCase
{
    use RefreshDatabase;

    private Contract $contract;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(Carbon::parse('2026-10-15 09:05:00', 'Asia/Jakarta'));
        config([
            'whatsapp.provider' => 'evolution', 'whatsapp.sender_number' => '628111111111',
            'whatsapp.evolution.url' => 'https://gateway.example.test',
            'whatsapp.evolution.key' => 'test-key', 'whatsapp.evolution.instance' => 'credit-hp',
            'whatsapp.reminders.enabled' => true, 'whatsapp.reminders.time' => '09:00',
            'whatsapp.reminders.timezone' => 'Asia/Jakarta',
        ]);
        Http::preventStrayRequests();
        $this->gateway();
        $customer = Customer::create([
            'nik' => '9999000000000001', 'name' => 'Nasabah Uji',
            'phone_number' => '081234567890', 'address' => 'Alamat uji',
        ]);
        $product = Product::create(['brand' => 'HP Pengujian', 'cost_price' => 1000000, 'selling_price' => 1200000]);
        $this->contract = Contract::create([
            'contract_number' => 'WA-TEST', 'customer_id' => $customer->id, 'product_id' => $product->id,
            'total_price' => 1200000, 'tenor' => 3, 'installment' => 400000,
            'start_date' => '2026-10-01', 'status' => 'active',
        ]);
    }

    protected function tearDown(): void
    {
        $this->travelBack();
        parent::tearDown();
    }

    private function gateway(int $status = 201, ?array $body = null): void
    {
        Cache::flush();
        Http::swap(new Factory);
        Http::preventStrayRequests();
        Http::fake([
            '*/instance/fetchInstances*' => Http::response([
                ['name' => 'credit-hp', 'ownerJid' => '628111111111@s.whatsapp.net', 'connectionStatus' => 'open'],
            ]),
            '*/message/sendText/*' => Http::response($body ?? ['key' => ['id' => 'message-id']], $status),
        ]);
    }

    private function payment(string $date = '2026-10-16', string $status = 'unpaid'): Payment
    {
        return $this->contract->payments()->create([
            'installment_number' => $this->contract->payments()->count() + 1,
            'due_date' => $date, 'amount' => 400000, 'status' => $status,
        ]);
    }

    private function assertPosts(int $expected): void
    {
        $this->assertCount($expected, Http::recorded(fn ($request) => $request->method() === 'POST'));
    }

    public function test_h_minus_one_and_due_date_send_once_each_with_customer_product_and_amount(): void
    {
        $payment = $this->payment();
        $this->artisan('whatsapp:reminders')->assertSuccessful();
        $this->artisan('whatsapp:reminders')->assertSuccessful();
        $this->assertPosts(1);
        $this->assertDatabaseHas('whatsapp_reminders', ['payment_id' => $payment->id, 'kind' => 'before_due', 'status' => 'sent', 'attempts' => 1]);
        Http::assertSent(fn ($r) => $r->method() === 'POST' && $r['number'] === '6281234567890'
            && str_contains($r['text'], 'Nasabah Uji') && str_contains($r['text'], 'HP Pengujian')
            && str_contains($r['text'], 'Rp 400.000') && str_contains($r['text'], '*besok*')
            && str_contains($r['text'], '16 Oktober 2026'));

        $this->travelTo(Carbon::parse('2026-10-16 09:05', 'Asia/Jakarta'));
        $this->artisan('whatsapp:reminders')->assertSuccessful();
        $this->artisan('whatsapp:reminders')->assertSuccessful();
        $this->assertPosts(2);
        $this->assertDatabaseHas('whatsapp_reminders', ['payment_id' => $payment->id, 'kind' => 'due', 'status' => 'sent']);
        Http::assertSent(fn ($r) => $r->method() === 'POST' && str_contains($r['text'], '*hari ini*'));
    }

    public function test_paid_between_reminders_is_not_reminded_on_due_date(): void
    {
        $payment = $this->payment();
        $this->artisan('whatsapp:reminders')->assertSuccessful();
        $payment->update(['status' => 'paid', 'paid_amount' => 400000, 'paid_at' => now()]);
        $this->travelTo(Carbon::parse('2026-10-16 09:05', 'Asia/Jakarta'));
        $this->artisan('whatsapp:reminders')->assertSuccessful();
        $this->assertPosts(1);
    }

    public function test_only_today_and_tomorrow_unpaid_active_contracts_are_selected(): void
    {
        $this->payment('2026-10-15');
        $this->payment('2026-10-16');
        $this->payment('2026-10-14', 'overdue');
        $this->payment('2026-10-17');
        $this->payment('2026-10-16', 'paid');
        $this->artisan('whatsapp:reminders')->assertSuccessful();
        $this->assertPosts(2);
        $this->assertDatabaseCount('whatsapp_reminders', 2);
    }

    public function test_completed_or_defaulted_contracts_are_not_reminded_automatically(): void
    {
        $this->payment();
        foreach (['completed', 'defaulted'] as $status) {
            $this->contract->update(['status' => $status]);
            $this->artisan('whatsapp:reminders')->assertSuccessful();
        }
        $this->assertPosts(0);
    }

    public function test_disabled_and_before_sending_time_do_not_contact_gateway(): void
    {
        $this->payment();
        config(['whatsapp.reminders.enabled' => false]);
        $this->artisan('whatsapp:reminders')->assertSuccessful();
        config(['whatsapp.reminders.enabled' => true]);
        $this->travelTo(Carbon::parse('2026-10-15 08:59', 'Asia/Jakarta'));
        $this->artisan('whatsapp:reminders')->assertSuccessful();
        Http::assertNothingSent();
        $this->assertDatabaseCount('whatsapp_reminders', 0);
    }

    public function test_dry_run_never_sends_or_writes_even_when_enabled(): void
    {
        $this->payment();
        $this->artisan('whatsapp:reminders --dry-run')->expectsOutputToContain('Eligible H-1 / due-date reminders: 1')->assertSuccessful();
        Http::assertNothingSent();
        $this->assertDatabaseCount('whatsapp_reminders', 0);
    }

    public function test_missing_credentials_stop_without_poisoning_reminders(): void
    {
        $this->payment();
        config(['whatsapp.evolution.key' => '']);
        $this->artisan('whatsapp:reminders')->assertFailed();
        Http::assertNothingSent();
        $this->assertDatabaseCount('whatsapp_reminders', 0);
    }

    public function test_sender_number_mismatch_prevents_message_submission(): void
    {
        $this->payment();
        config(['whatsapp.sender_number' => '628222222222']);
        $this->artisan('whatsapp:reminders')->expectsOutputToContain('sender_number_mismatch')->assertFailed();
        $this->assertPosts(0);
        $this->assertDatabaseCount('whatsapp_reminders', 0);
    }

    public function test_rate_limit_retries_after_fifteen_minutes_up_to_three_attempts(): void
    {
        $payment = $this->payment();
        $this->gateway(429, ['error' => 'rate limited']);
        for ($attempt = 1; $attempt <= 3; $attempt++) {
            $this->artisan('whatsapp:reminders')->assertFailed();
            $this->assertDatabaseHas('whatsapp_reminders', ['payment_id' => $payment->id, 'status' => 'failed', 'attempts' => $attempt]);
            $this->artisan('whatsapp:reminders')->assertSuccessful();
            $this->assertPosts($attempt);
            $this->travel(15)->minutes();
        }
        $this->artisan('whatsapp:reminders')->assertSuccessful();
        $this->assertPosts(3);
        $this->assertNull($payment->whatsappReminders()->first()->next_attempt_at);
    }

    public function test_ambiguous_timeout_is_not_retried_automatically(): void
    {
        $payment = $this->payment();
        Http::swap(new Factory);
        Http::preventStrayRequests();
        Http::fake([
            '*/instance/fetchInstances*' => Http::response([
                ['name' => 'credit-hp', 'ownerJid' => '628111111111@s.whatsapp.net', 'connectionStatus' => 'open'],
            ]),
            '*/message/sendText/*' => fn () => throw new ConnectionException('timeout'),
        ]);
        $this->artisan('whatsapp:reminders')->assertFailed();
        $this->assertDatabaseHas('whatsapp_reminders', ['payment_id' => $payment->id, 'status' => 'unknown', 'error_code' => 'connection_uncertain']);
        $this->artisan('whatsapp:reminders')->assertSuccessful();
        $this->assertSame(1, $payment->whatsappReminders()->first()->attempts);
        $this->assertNull($payment->fresh()->wa_reminder_sent_at);
    }

    public function test_server_error_requires_review(): void
    {
        $payment = $this->payment();
        $this->gateway(503, ['error' => 'unavailable']);
        $this->artisan('whatsapp:reminders')->assertFailed();
        $this->assertDatabaseHas('whatsapp_reminders', ['payment_id' => $payment->id, 'status' => 'unknown']);
        $this->assertNull($payment->fresh()->wa_reminder_sent_at);
    }

    public function test_invalid_number_is_logged_without_sending(): void
    {
        $this->payment();
        $this->contract->customer->update(['phone_number' => 'invalid']);
        $this->artisan('whatsapp:reminders')->assertFailed();
        $this->assertDatabaseHas('whatsapp_reminders', ['status' => 'failed', 'error_code' => 'invalid_phone']);
        $this->assertPosts(0);
    }

    public function test_interrupted_sender_is_not_replayed(): void
    {
        $payment = $this->payment();
        WhatsappReminder::create([
            'payment_id' => $payment->id, 'kind' => 'before_due', 'status' => 'sending',
            'attempts' => 1, 'attempted_at' => now()->subMinutes(31),
        ]);
        $this->artisan('whatsapp:reminders')->assertSuccessful();
        $this->assertPosts(0);
        $this->assertDatabaseHas('whatsapp_reminders', ['status' => 'unknown', 'error_code' => 'sender_interrupted']);
    }

    public function test_existing_same_day_manual_notice_suppresses_duplicate_automation(): void
    {
        $payment = $this->payment();
        $this->assertSame('sent', app(PaymentReminderService::class)->send($payment->id, automatic: false));
        $this->artisan('whatsapp:reminders')->assertSuccessful();
        $this->assertPosts(1);
    }

    public function test_known_invalid_number_can_be_corrected_and_retried_manually(): void
    {
        $payment = $this->payment();
        $this->contract->customer->update(['phone_number' => 'invalid']);
        $this->assertSame('failed', app(PaymentReminderService::class)->send($payment->id));
        $this->contract->customer->update(['phone_number' => '081234567890']);
        $this->assertSame('sent', app(PaymentReminderService::class)->send($payment->id, automatic: false));
        $this->assertPosts(1);
        $this->assertDatabaseHas('whatsapp_reminders', ['payment_id' => $payment->id, 'kind' => 'before_due', 'attempts' => 2]);
    }

    public function test_rechecks_current_payment_status_and_due_date(): void
    {
        $payment = $this->payment();
        $payment->update(['status' => 'paid']);
        $this->assertSame('skipped', app(PaymentReminderService::class)->send($payment->id));
        $payment->update(['status' => 'unpaid', 'due_date' => '2026-10-20']);
        $this->assertSame('skipped', app(PaymentReminderService::class)->send($payment->id));
        Http::assertNothingSent();
    }

    public function test_configuration_errors_are_rejected_without_sending(): void
    {
        $this->artisan('whatsapp:reminders --limit=0')->assertFailed();
        config(['whatsapp.reminders.timezone' => 'invalid']);
        $this->artisan('whatsapp:reminders')->assertFailed();
        config(['whatsapp.reminders.timezone' => 'Asia/Jakarta', 'whatsapp.reminders.time' => '99:00']);
        $this->artisan('whatsapp:reminders')->assertFailed();
        Http::assertNothingSent();
    }

    public function test_history_is_visible_only_with_payment_view_permission(): void
    {
        $this->payment();
        $this->artisan('whatsapp:reminders')->assertSuccessful();
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $user = User::factory()->create(['password_changed_at' => now()]);
        $this->actingAs($user)->get(WhatsappReminderResource::getUrl())->assertForbidden();
        $user->givePermissionTo(Permission::findOrCreate('view_any_payment', 'web'));
        $this->actingAs($user)->get(WhatsappReminderResource::getUrl())->assertOk()->assertSee('Diterima gateway')->assertSee('Nasabah Uji');
    }

    public function test_unexpected_gateway_success_does_not_mark_payment_sent(): void
    {
        $this->payment();
        $this->gateway(200, ['error' => 'not a message acknowledgement']);
        $this->artisan('whatsapp:reminders')->assertFailed();
        $this->assertDatabaseHas('whatsapp_reminders', ['status' => 'unknown', 'error_code' => 'unexpected_response']);
    }

    public function test_sender_disconnected_prevents_submission(): void
    {
        $this->payment();
        Http::swap(new Factory);
        Http::preventStrayRequests();
        Http::fake(['*/instance/fetchInstances*' => Http::response([
            ['name' => 'credit-hp', 'ownerJid' => '628111111111@s.whatsapp.net', 'connectionStatus' => 'close'],
        ])]);
        $this->artisan('whatsapp:reminders')->expectsOutputToContain('sender_not_connected')->assertFailed();
        $this->assertPosts(0);
    }

    public function test_jakarta_calendar_date_is_used_even_when_utc_is_previous_day(): void
    {
        $this->travelTo(Carbon::parse('2026-10-15 00:05', 'Asia/Jakarta'));
        config(['whatsapp.reminders.time' => '00:00']);
        $today = $this->payment('2026-10-15');
        $this->payment('2026-10-14');
        $this->artisan('whatsapp:reminders')->assertSuccessful();
        $this->assertPosts(1);
        $this->assertDatabaseHas('whatsapp_reminders', ['payment_id' => $today->id, 'kind' => 'due']);
    }

    public function test_gateway_connect_saves_a_private_qr_without_sending_messages(): void
    {
        $temporary = sys_get_temp_dir().'/wa-qr-'.bin2hex(random_bytes(6));
        mkdir($temporary.'/app/private', 0700, true);
        $this->app->useStoragePath($temporary);
        $png = "\x89PNG\r\n\x1a\nfixture";
        Http::fake(['*/instance/connect/*' => Http::response(['base64' => 'data:image/png;base64,'.base64_encode($png)])]);
        try {
            $this->artisan('whatsapp:gateway connect')->assertSuccessful();
            $this->assertSame($png, file_get_contents($temporary.'/app/private/whatsapp-pairing.png'));
            $this->assertSame(0600, fileperms($temporary.'/app/private/whatsapp-pairing.png') & 0777);
            $this->assertPosts(0);
        } finally {
            @unlink($temporary.'/app/private/whatsapp-pairing.png');
            rmdir($temporary.'/app/private');
            rmdir($temporary.'/app');
            rmdir($temporary);
        }
    }

    public function test_gateway_create_uses_configured_sender_and_integration(): void
    {
        Http::fake(['*/instance/create' => Http::response([], 201)]);
        // A successful create without a QR must still ask the operator to check/connect.
        $this->artisan('whatsapp:gateway create')->assertFailed();
        Http::assertSent(fn ($r) => str_ends_with($r->url(), '/instance/create')
            && $r['number'] === '628111111111' && $r['integration'] === 'WHATSAPP-BAILEYS');
        $this->assertDatabaseCount('whatsapp_reminders', 0);
    }

    public function test_fonnte_false_success_is_not_marked_as_sent(): void
    {
        config(['whatsapp.provider' => 'fonnte', 'whatsapp.fonnte.token' => 'test-token']);
        Http::swap(new Factory);
        Http::preventStrayRequests();
        Http::fake(['api.fonnte.com/*' => Http::response(['status' => false, 'reason' => 'device disconnected'])]);
        $this->assertFalse(WaService::sendMessage('081234567890', 'Test'));
    }
}
