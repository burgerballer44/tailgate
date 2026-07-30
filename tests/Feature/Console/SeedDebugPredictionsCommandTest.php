<?php

use App\Models\Player;
use App\Models\Prediction;
use Illuminate\Support\Facades\Artisan;

test('debug prediction seed command always seeds the owner demo player with predictions', function () {
    Artisan::call('tailgate:seed-debug-predictions', [
        '--seed' => 20260712,
    ]);

    $player = Player::query()
        ->where('player_name', 'Test Player DEV')
        ->firstOrFail();

    $predictionCount = Prediction::query()
        ->where('player_id', $player->id)
        ->count();

    expect($predictionCount)->toBeGreaterThanOrEqual(3);
});
