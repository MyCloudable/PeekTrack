<?php

namespace Tests\Unit\Ai;

use App\Jobs\Ai\RefreshAiFeatureRowJob;
use App\Observers\JobEntrySubmitObserver;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class JobEntrySubmitObserverTest extends TestCase
{
    private JobEntrySubmitObserver $observer;

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();
        $this->observer = new JobEntrySubmitObserver();
    }

    public function test_dispatches_on_initial_submit(): void
    {
        $card = $this->makeCard([
            'submitted' => 1,
            'changes'   => ['submitted' => 1],
            'original'  => ['submitted' => 0],
        ]);

        $this->observer->updated($card);

        Queue::assertPushed(RefreshAiFeatureRowJob::class);
    }

    public function test_dispatches_on_resubmit(): void
    {
        $card = $this->makeCard([
            'review_state' => 'resubmitted',
            'changes'      => ['review_state' => 'resubmitted'],
            'original'     => ['review_state' => 'kicked_back_to_super'],
        ]);

        $this->observer->updated($card);

        Queue::assertPushed(RefreshAiFeatureRowJob::class);
    }

    public function test_does_not_dispatch_on_unrelated_change(): void
    {
        $card = $this->makeCard([
            'notes'    => 'updated notes',
            'changes'  => ['notes' => 'updated notes'],
            'original' => ['notes' => 'old notes'],
        ]);

        $this->observer->updated($card);

        Queue::assertNothingPushed();
    }

    public function test_does_not_dispatch_when_already_submitted(): void
    {
        // Card was already submitted. Some other field changed but submitted itself didn't transition.
        $card = $this->makeCard([
            'submitted' => 1,
            'changes'   => ['updated_at' => '2026-04-01'],
            'original'  => ['submitted' => 1],
        ]);

        $this->observer->updated($card);

        Queue::assertNothingPushed();
    }

    /**
     * Build a stand-in for a JobEntry model instance with the
     * minimum surface area the observer uses.
     */
    private function makeCard(array $config)
    {
        $card = new class {
            public string $link;
            public ?int $submitted = null;
            public ?string $review_state = null;
            public array $_changes = [];
            public array $_original = [];

            public function getChanges(): array
            {
                return $this->_changes;
            }

            public function getOriginal(string $key = null)
            {
                if ($key === null) return $this->_original;
                return $this->_original[$key] ?? null;
            }
        };

        $card->link = 'test-link-' . uniqid();
        $card->submitted = $config['submitted'] ?? null;
        $card->review_state = $config['review_state'] ?? null;
        $card->_changes = $config['changes'] ?? [];
        $card->_original = $config['original'] ?? [];

        return $card;
    }
}
