<?php

declare(strict_types=1);

namespace Tests\Feature\Services;

use App\Models\DiaryEntry;
use App\Models\EntityRelationship;
use App\Models\Note;
use App\Models\Tag;
use App\Models\User;
use App\Services\ConstellationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConstellationServiceTest extends TestCase
{
    use RefreshDatabase;

    private ConstellationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ConstellationService;
    }

    public function test_build_graph_returns_nodes_and_edges_keys(): void
    {
        $user = User::factory()->create();

        $graph = $this->service->buildGraph($user);

        $this->assertArrayHasKey('nodes', $graph);
        $this->assertArrayHasKey('edges', $graph);
        $this->assertIsArray($graph['nodes']);
        $this->assertIsArray($graph['edges']);
    }

    public function test_build_graph_with_no_entities_returns_empty_arrays(): void
    {
        $user = User::factory()->create();

        $graph = $this->service->buildGraph($user);

        $this->assertEmpty($graph['nodes']);
        $this->assertEmpty($graph['edges']);
    }

    public function test_build_graph_includes_diary_entry_as_node(): void
    {
        $user = User::factory()->create();
        $entry = DiaryEntry::factory()->create(['user_id' => $user->id, 'title' => 'Test Entry']);

        $graph = $this->service->buildGraph($user);

        $node = collect($graph['nodes'])->firstWhere('id', $entry->id);
        $this->assertNotNull($node);
        $this->assertSame('diary', $node['type']);
        $this->assertSame('Test Entry', $node['title']);
    }

    public function test_build_graph_includes_note_as_node(): void
    {
        $user = User::factory()->create();
        $note = Note::factory()->create(['user_id' => $user->id]);

        $graph = $this->service->buildGraph($user);

        $node = collect($graph['nodes'])->firstWhere('id', $note->id);
        $this->assertNotNull($node);
        $this->assertSame('note', $node['type']);
    }

    public function test_build_graph_type_filter_limits_nodes(): void
    {
        $user = User::factory()->create();
        DiaryEntry::factory()->create(['user_id' => $user->id]);
        Note::factory()->create(['user_id' => $user->id]);

        $graph = $this->service->buildGraph($user, ['type' => 'diary']);

        $types = collect($graph['nodes'])->pluck('type')->unique()->values()->all();
        $this->assertSame(['diary'], $types);
    }

    public function test_build_graph_excludes_other_users_entities(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        DiaryEntry::factory()->create(['user_id' => $other->id]);

        $graph = $this->service->buildGraph($user);

        $this->assertEmpty($graph['nodes']);
    }

    public function test_build_graph_creates_tag_edge_for_shared_tags(): void
    {
        $user = User::factory()->create();
        $tag = Tag::factory()->create(['user_id' => $user->id]);
        $entry1 = DiaryEntry::factory()->create(['user_id' => $user->id]);
        $entry2 = DiaryEntry::factory()->create(['user_id' => $user->id]);
        $entry1->tags()->attach($tag);
        $entry2->tags()->attach($tag);

        $graph = $this->service->buildGraph($user);

        $tagEdge = collect($graph['edges'])->firstWhere('type', 'tag');
        $this->assertNotNull($tagEdge);
    }

    public function test_build_graph_creates_date_edge_for_same_day_entities(): void
    {
        $user = User::factory()->create();
        $date = now()->setTime(10, 0);
        $entry1 = DiaryEntry::factory()->create(['user_id' => $user->id, 'created_at' => $date]);
        $entry2 = DiaryEntry::factory()->create(['user_id' => $user->id, 'created_at' => $date->copy()->addHour()]);

        $graph = $this->service->buildGraph($user);

        $dateEdge = collect($graph['edges'])->firstWhere('type', 'date');
        $this->assertNotNull($dateEdge);
    }

    public function test_build_graph_includes_explicit_relationship_edge(): void
    {
        $user = User::factory()->create();
        $entry = DiaryEntry::factory()->create(['user_id' => $user->id]);
        $note = Note::factory()->create(['user_id' => $user->id]);

        EntityRelationship::create([
            'entity_a_id' => $entry->id,
            'entity_a_type' => 'diary_entry',
            'entity_b_id' => $note->id,
            'entity_b_type' => 'note',
            'relationship_type' => 'sibling',
        ]);

        $graph = $this->service->buildGraph($user);

        $siblingEdge = collect($graph['edges'])->firstWhere('type', 'sibling');
        $this->assertNotNull($siblingEdge);
    }

    public function test_node_has_expected_fields(): void
    {
        $user = User::factory()->create();
        DiaryEntry::factory()->create(['user_id' => $user->id]);

        $graph = $this->service->buildGraph($user);
        $node = $graph['nodes'][0];

        foreach (['id', 'type', 'title', 'preview', 'mood', 'tags', 'created_at'] as $field) {
            $this->assertArrayHasKey($field, $node);
        }
    }
}
