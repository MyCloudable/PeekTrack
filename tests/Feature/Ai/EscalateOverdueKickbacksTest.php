<?php

namespace Tests\Feature\Ai;

use App\Mail\KickbackEscalation;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Tests\TestCase;

class EscalateOverdueKickbacksTest extends TestCase
{
    // use RefreshDatabase;
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();

        // Enable escalation by default for tests; specific tests can override
        $this->setSetting('ai.manager_escalation_enabled', 'true');
        $this->setSetting('ai.kickback_escalation_days', '3');
    }

    public function test_escalates_overdue_card_with_manager(): void
    {
        $managerId = $this->createUser(['name' => 'Mgr Boss', 'email' => 'boss@example.com']);
        $superId   = $this->createUser(['name' => 'Sue Per', 'email' => 'sue@example.com', 'manager_id' => $managerId]);
        $link = $this->createKickedBackCard($superId, daysAgo: 5);

        $this->artisan('ai:escalate-kickbacks')->assertExitCode(0);

        Mail::assertSent(KickbackEscalation::class, function ($mail) {
            return $mail->hasTo('boss@example.com');
        });

        $row = DB::table('jobentries')->where('link', $link)->first();
        $this->assertNotNull($row->manager_escalated_at);
    }

    public function test_skips_card_under_threshold(): void
    {
        $managerId = $this->createUser(['email' => 'boss@example.com']);
        $superId   = $this->createUser(['email' => 'sue@example.com', 'manager_id' => $managerId]);
        $this->createKickedBackCard($superId, daysAgo: 1); // under 3-day default

        $this->artisan('ai:escalate-kickbacks')->assertExitCode(0);
        Mail::assertNothingSent();
    }

    public function test_skips_super_without_manager(): void
    {
        $superId = $this->createUser(['email' => 'sue@example.com', 'manager_id' => null]);
        $link = $this->createKickedBackCard($superId, daysAgo: 5);

        $this->artisan('ai:escalate-kickbacks')->assertExitCode(0);
        Mail::assertNothingSent();

        // manager_escalated_at must NOT be set — so it can retry once coverage is fixed
        $row = DB::table('jobentries')->where('link', $link)->first();
        $this->assertNull($row->manager_escalated_at);
    }

    public function test_skips_when_feature_disabled(): void
    {
        $this->setSetting('ai.manager_escalation_enabled', 'false');

        $managerId = $this->createUser(['email' => 'boss@example.com']);
        $superId   = $this->createUser(['email' => 'sue@example.com', 'manager_id' => $managerId]);
        $link = $this->createKickedBackCard($superId, daysAgo: 5);

        $this->artisan('ai:escalate-kickbacks')->assertExitCode(0);
        Mail::assertNothingSent();

        // Not marked escalated — will retry once feature is enabled
        $row = DB::table('jobentries')->where('link', $link)->first();
        $this->assertNull($row->manager_escalated_at);
    }

    public function test_does_not_re_escalate_already_escalated_card(): void
    {
        $managerId = $this->createUser(['email' => 'boss@example.com']);
        $superId   = $this->createUser(['email' => 'sue@example.com', 'manager_id' => $managerId]);
        $link = $this->createKickedBackCard($superId, daysAgo: 5);

        // Pre-mark as already escalated
        DB::table('jobentries')->where('link', $link)->update([
            'manager_escalated_at' => now()->subDay(),
        ]);

        $this->artisan('ai:escalate-kickbacks')->assertExitCode(0);
        Mail::assertNothingSent();
    }

    public function test_dry_run_does_not_send_or_mark(): void
    {
        $managerId = $this->createUser(['email' => 'boss@example.com']);
        $superId   = $this->createUser(['email' => 'sue@example.com', 'manager_id' => $managerId]);
        $link = $this->createKickedBackCard($superId, daysAgo: 5);

        $this->artisan('ai:escalate-kickbacks', ['--dry-run' => true])->assertExitCode(0);

        Mail::assertNothingSent();
        $row = DB::table('jobentries')->where('link', $link)->first();
        $this->assertNull($row->manager_escalated_at);
    }

    public function test_skips_resubmitted_card(): void
    {
        // Card was kicked back, then super fixed it. approved is now NULL (pending) or 1.
        $managerId = $this->createUser(['email' => 'boss@example.com']);
        $superId   = $this->createUser(['email' => 'sue@example.com', 'manager_id' => $managerId]);
        $link = $this->createKickedBackCard($superId, daysAgo: 5);

        DB::table('jobentries')->where('link', $link)->update(['approved' => null]);

        $this->artisan('ai:escalate-kickbacks')->assertExitCode(0);
        Mail::assertNothingSent();
    }

    // ── Helpers ──

    private function createUser(array $overrides = []): int
    {
        return DB::table('users')->insertGetId(array_merge([
            'name'     => 'Test User',
            'email'    => 'test' . uniqid() . '@example.com',
            'password' => bcrypt('x'),
            'role_id'  => 3,
            'active'   => 1,
            'is_clocked_in' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ], $overrides));
    }

    private function createKickedBackCard(int $userId, int $daysAgo): string
    {
        $link = (string) Str::uuid();
        DB::table('jobentries')->insert([
            'link'           => $link,
            'job_number'     => 'J-TEST',
            'name' => 'AI Test Job',
            'workdate'       => now()->toDateString(),
            'submitted'      => 1,
            'userId'         => $userId,
            'approved'       => 2,
            'kicked_back_at' => now()->subDays($daysAgo),
            'kickback_count' => 1,
            'created_at'     => now()->subDays($daysAgo),
            'updated_at'     => now()->subDays($daysAgo),
        ]);
        return $link;
    }

    private function setSetting(string $key, string $value): void
    {
        DB::table('settings')->updateOrInsert(
            ['key_name' => $key],
            ['value' => $value, 'value_type' => 'string', 'updated_at' => now()]
        );
    }
}
