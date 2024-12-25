<?php

use App\Models\User;
use App\Models\UserStatus;

test('a user can be activated', function () {
    $user = new User(['status' => 'some-status']);
    expect($user->status)->not->toBe(UserStatus::ACTIVE);

    $user->activate();

    expect($user->status)->toBe(UserStatus::ACTIVE);
});
