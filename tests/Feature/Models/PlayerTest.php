<?php

use App\Models\Member;
use App\Models\Player;
use App\Models\Prediction;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Symfony\Component\Uid\Ulid;

describe('route binding and identifiers', function () {
    test('uses ulid as route key name', function () {
        expect((new Player)->getRouteKeyName())->toBe('ulid');
    });

    test('generates ulid on create', function () {
        $player = Player::factory()->create();

        expect($player->ulid)->not->toBeNull();
        expect($player->ulid)->toBeInstanceOf(Ulid::class);
        expect((string) $player->ulid)->toHaveLength(26);
    });
});

describe('relationships', function () {
    test('predictions returns has many relationship', function () {
        $relation = (new Player)->predictions();

        expect($relation)->toBeInstanceOf(HasMany::class);
        expect($relation->getRelated())->toBeInstanceOf(Prediction::class);
    });

    test('member returns belongs to relationship', function () {
        $relation = (new Player)->member();

        expect($relation)->toBeInstanceOf(BelongsTo::class);
        expect($relation->getRelated())->toBeInstanceOf(Member::class);
    });
});

describe('filter scope', function () {
    test('filters by member_id', function () {
        $member = Member::factory()->create();
        $otherMember = Member::factory()->create();

        $matching = Player::factory()->create([
            'member_id' => $member->id,
            'player_name' => 'Member Match',
        ]);

        $nonMatching = Player::factory()->create([
            'member_id' => $otherMember->id,
            'player_name' => 'Other Member',
        ]);

        $results = Player::query()->filter(['member_id' => $member->id])->get();

        expect($results->pluck('id')->all())->toContain($matching->id);
        expect($results->pluck('id')->all())->not->toContain($nonMatching->id);
    });

    test('filters by q with case insensitive matching', function () {
        $matching = Player::factory()->create(['player_name' => 'Taylor Swift']);
        $nonMatching = Player::factory()->create(['player_name' => 'Morgan Wallen']);

        $results = Player::query()->filter(['q' => 'tAyLoR'])->get();

        expect($results->pluck('id')->all())->toContain($matching->id);
        expect($results->pluck('id')->all())->not->toContain($nonMatching->id);
    });

    test('applies member_id and q filters together', function () {
        $member = Member::factory()->create();
        $otherMember = Member::factory()->create();

        $match = Player::factory()->create([
            'member_id' => $member->id,
            'player_name' => 'Combined Match',
        ]);

        $wrongName = Player::factory()->create([
            'member_id' => $member->id,
            'player_name' => 'Different Name',
        ]);

        $wrongMember = Player::factory()->create([
            'member_id' => $otherMember->id,
            'player_name' => 'Combined Match',
        ]);

        $results = Player::query()->filter([
            'member_id' => $member->id,
            'q' => 'combined',
        ])->get();

        expect($results->pluck('id')->all())->toContain($match->id);
        expect($results->pluck('id')->all())->not->toContain($wrongName->id);
        expect($results->pluck('id')->all())->not->toContain($wrongMember->id);
    });
});
