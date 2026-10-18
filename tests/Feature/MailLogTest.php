<?php

namespace Tests\Feature;

use App\Mail\AspirasiMail;
use App\Mail\TanggapanMail;
use App\Models\MailLog;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Contracts\Queue\Job;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Mail\SendQueuedMailable;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Facades\Mail;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class MailLogTest extends TestCase
{
    use RefreshDatabase;

    private function userWithRole(string $slug, array $permissions = []): User
    {
        $role = Role::firstOrCreate(['slug' => $slug], ['name' => $slug, 'description' => null]);
        foreach ($permissions as $permission) {
            $role->givePermissionTo(Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']));
        }

        return User::factory()->create(['role_id' => $role->id, 'is_active' => true]);
    }

    public function test_super_admin_can_view_mail_logs(): void
    {
        MailLog::factory()->create(['recipients' => 'warga@example.com', 'subject' => 'Aspirasi Anda Telah Kami Terima']);
        MailLog::factory()->failed()->create(['recipients' => 'gagal@example.com']);

        $this->actingAs($this->userWithRole('super-admin'))
            ->get(route('mail-logs.index'))
            ->assertOk()
            ->assertSee('Log Email')
            ->assertSee('warga@example.com')
            ->assertSee('gagal@example.com')
            ->assertSee('Connection could not be established')
            ->assertViewHas('stats', fn (array $stats) => $stats['sent'] === 1 && $stats['failed'] === 1);
    }

    public function test_other_roles_cannot_access_even_with_log_permissions(): void
    {
        $admin = $this->userWithRole('admin-bappeda', ['logs.view', 'logs.manage', 'visitors.view']);

        $this->actingAs($admin)->get(route('mail-logs.index'))->assertForbidden();
        $this->actingAs($admin)->get(route('logs.index'))->assertOk()->assertDontSee('Log Email');
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('mail-logs.index'))->assertRedirect(route('login'));
    }

    public function test_sidebar_shows_mail_log_link_for_super_admin(): void
    {
        $this->actingAs($this->userWithRole('super-admin'))
            ->get(route('logs.index'))
            ->assertOk()
            ->assertSee('href="'.route('mail-logs.index').'"', false);
    }

    public function test_status_filter_and_search(): void
    {
        MailLog::factory()->create(['recipients' => 'budi@example.com']);
        MailLog::factory()->create(['recipients' => 'ani@example.com']);
        MailLog::factory()->failed()->create(['recipients' => 'budi.gagal@example.com']);
        $superAdmin = $this->userWithRole('super-admin');

        $this->actingAs($superAdmin)
            ->get(route('mail-logs.index', ['status' => 'failed']))
            ->assertSee('budi.gagal@example.com')
            ->assertDontSee('ani@example.com');

        $this->actingAs($superAdmin)
            ->get(route('mail-logs.index', ['q' => 'BUDI']))
            ->assertSee('budi@example.com')
            ->assertSee('budi.gagal@example.com')
            ->assertDontSee('ani@example.com');
    }

    public function test_warns_when_mailer_does_not_deliver(): void
    {
        $superAdmin = $this->userWithRole('super-admin');

        config(['mail.default' => 'log']);
        $this->actingAs($superAdmin)->get(route('mail-logs.index'))->assertSee('tidak benar-benar dikirim');

        config(['mail.default' => 'smtp']);
        $this->actingAs($superAdmin)->get(route('mail-logs.index'))->assertDontSee('tidak benar-benar dikirim');
    }

    public function test_sent_mail_is_recorded_without_content(): void
    {
        Mail::to('warga@example.com')->send(new AspirasiMail([
            'nomor_tiket' => 'MARIMOI-ASP-20261018-0001',
            'nama_pengirim' => 'Citra',
            'isi_aspirasi' => 'Isi rahasia warga',
        ], AspirasiMail::TYPE_PENERIMAAN));

        $log = MailLog::sole();
        $this->assertSame(MailLog::STATUS_SENT, $log->status);
        $this->assertSame('warga@example.com', $log->recipients);
        $this->assertSame(AspirasiMail::class, $log->mailable);
        $this->assertStringContainsString('MARIMOI-ASP-20261018-0001', $log->subject);
        $this->assertNotNull($log->message_id);
        $this->assertDatabaseMissing('mail_logs', ['subject' => 'Isi rahasia warga']);
    }

    public function test_permanently_failed_queued_mail_is_recorded_with_error(): void
    {
        $mailable = (new TanggapanMail(['kode' => 'MARIMOI-FDB-20261018-0042'], 'selesai'))->to('warga@example.com');
        $job = Mockery::mock(Job::class);
        $job->shouldReceive('payload')->andReturn([
            'data' => [
                'commandName' => SendQueuedMailable::class,
                'command' => serialize(new SendQueuedMailable($mailable)),
            ],
        ]);

        event(new JobFailed('database', $job, new RuntimeException('Expected response code 250 but got 550')));

        $log = MailLog::sole();
        $this->assertSame(MailLog::STATUS_FAILED, $log->status);
        $this->assertSame('warga@example.com', $log->recipients);
        $this->assertSame('Tindak Lanjut Tanggapan Anda Telah Selesai (MARIMOI-FDB-20261018-0042)', $log->subject);
        $this->assertSame('Expected response code 250 but got 550', $log->error);
    }

    public function test_failed_non_mail_jobs_are_ignored(): void
    {
        $job = Mockery::mock(Job::class);
        $job->shouldReceive('payload')->andReturn(['data' => ['commandName' => 'App\\Jobs\\Lainnya', 'command' => 'O:8:"stdClass":0:{}']]);

        event(new JobFailed('database', $job, new RuntimeException('boom')));

        $this->assertDatabaseCount('mail_logs', 0);
    }

    public function test_old_logs_are_pruned(): void
    {
        $old = MailLog::factory()->create(['created_at' => now()->subDays(MailLog::RETENTION_DAYS + 1)]);
        $recent = MailLog::factory()->create(['created_at' => now()->subDays(MailLog::RETENTION_DAYS - 1)]);

        $this->artisan('model:prune', ['--model' => [MailLog::class]])->assertSuccessful();

        $this->assertModelMissing($old);
        $this->assertModelExists($recent);
    }
}
