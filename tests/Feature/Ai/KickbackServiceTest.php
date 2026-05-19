<?php

namespace Tests\Feature\Ai;

use App\Services\Ai\KickbackService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class KickbackServiceTest extends TestCase
{
    // use RefreshDatabase;
    use DatabaseTransactions;

    private KickbackService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new KickbackService();
    }

    public function test_kickback_sets_approved_to_2(): void
    {
        $link = $this->createCard(['approved' => null, 'kickback_count' => 0]);

        $this->service->kickback($link, 999);

        $row = DB::table('jobentries')->where('link', $link)->first();
        $this->assertEquals(2, $row->approved);
    }

    public function test_kickback_increments_kickback_count(): void
    {
        $link = $this->createCard(['kickback_count' => 2]);

        $this->service->kickback($link, 999);

        $row = DB::table('jobentries')->where('link', $link)->first();
        $this->assertEquals(3, $row->kickback_count);
    }

    public function test_kickback_sets_timestamps(): void
    {
        $link = $this->createCard();
        $this->service->kickback($link, 999);

        $row = DB::table('jobentries')->where('link', $link)->first();
        $this->assertNotNull($row->kicked_back_at);
        $this->assertNotNull($row->super_notified_at);
    }

    public function test_kickback_throws_when_card_missing(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->service->kickback('nonexistent-link', 999);
    }

    public function test_approve_sets_approved_to_1_with_ai_attribution(): void
    {
        $link = $this->createCard(['approved' => null]);

        $this->service->approve($link, 999);

        $row = DB::table('jobentries')->where('link', $link)->first();
        $this->assertEquals(1, $row->approved);
        $this->assertStringStartsWith('AI:', $row->approvedBy);
        $this->assertNotNull($row->approved_date);
    }

    public function test_repeated_kickback_continues_to_increment_count(): void
    {
        // Models resubmit-then-reject-again cycle.
        $link = $this->createCard(['kickback_count' => 0]);

        $this->service->kickback($link, 1);
        $this->service->kickback($link, 2);
        $this->service->kickback($link, 3);

        $row = DB::table('jobentries')->where('link', $link)->first();
        $this->assertEquals(3, $row->kickback_count);
    }

    private function createCard(array $overrides = []): string
    {
        $link = (string) Str::uuid();
        DB::table('jobentries')->insert(array_merge([
            'link'           => $link,
            'job_number'     => 'J-TEST',
            'name' => 'AI Test Job',
            'workdate'       => now()->toDateString(),
            'submitted'      => 1,
            'userId'         => 1,
            'kickback_count' => 0,
            'created_at'     => now(),
            'updated_at'     => now(),
        ], $overrides));
        return $link;
    }
}
