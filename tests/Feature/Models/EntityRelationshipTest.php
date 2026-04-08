<?php

declare(strict_types=1);

namespace Tests\Feature\Models;

use App\Enums\RelationshipDirection;
use App\Enums\RelationshipType;
use App\Models\DiaryEntry;
use App\Models\EntityRelationship;
use App\Models\Note;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EntityRelationshipTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_entity_relationship(): void
    {
        $user = User::factory()->create();
        $entry = DiaryEntry::factory()->create(['user_id' => $user->id]);
        $note = Note::factory()->create(['user_id' => $user->id]);

        $relationship = EntityRelationship::create([
            'entity_a_id' => $entry->id,
            'entity_a_type' => 'diary_entry',
            'entity_b_id' => $note->id,
            'entity_b_type' => 'note',
            'relationship_type' => RelationshipType::Sibling,
            'direction' => RelationshipDirection::AToB,
        ]);

        $this->assertNotNull($relationship->id);
        $this->assertEquals(RelationshipType::Sibling, $relationship->relationship_type);
        $this->assertEquals(RelationshipDirection::AToB, $relationship->direction);
    }

    public function test_morph_map_resolves_correctly(): void
    {
        $map = Relation::morphMap();

        $this->assertArrayHasKey('diary_entry', $map);
        $this->assertArrayHasKey('note', $map);
        $this->assertArrayHasKey('postit', $map);
        $this->assertArrayHasKey('image', $map);
        $this->assertArrayHasKey('tag', $map);
        $this->assertArrayHasKey('important_date', $map);
    }

    public function test_morph_relations_resolve_entities(): void
    {
        $user = User::factory()->create();
        $entry = DiaryEntry::factory()->create(['user_id' => $user->id]);
        $note = Note::factory()->create(['user_id' => $user->id]);

        $relationship = EntityRelationship::create([
            'entity_a_id' => $entry->id,
            'entity_a_type' => 'diary_entry',
            'entity_b_id' => $note->id,
            'entity_b_type' => 'note',
            'relationship_type' => RelationshipType::ParentChild,
        ]);

        $this->assertInstanceOf(DiaryEntry::class, $relationship->entityA);
        $this->assertInstanceOf(Note::class, $relationship->entityB);
    }

    public function test_unique_constraint_prevents_duplicates(): void
    {
        $user = User::factory()->create();
        $entry = DiaryEntry::factory()->create(['user_id' => $user->id]);
        $note = Note::factory()->create(['user_id' => $user->id]);

        $attrs = [
            'entity_a_id' => $entry->id,
            'entity_a_type' => 'diary_entry',
            'entity_b_id' => $note->id,
            'entity_b_type' => 'note',
            'relationship_type' => RelationshipType::Sibling,
        ];

        EntityRelationship::create($attrs);

        $this->expectException(QueryException::class);
        EntityRelationship::create($attrs);
    }
}
