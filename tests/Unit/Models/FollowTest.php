<?php

use App\Models\Follow;
use App\Models\HtmlEntity;
use App\Models\Sport;
use Illuminate\Support\HtmlString;

describe('sport_display', function () {
    test('returns all sports html entities when follow is not sport scoped', function () {
        $follow = new Follow(['sport' => null]);

        expect($follow->sport_display)
            ->toBeInstanceOf(HtmlString::class)
            ->and($follow->sport_display->toHtml())->toBe(HtmlEntity::BASKETBALL->entity().' '.HtmlEntity::FOOTBALL->entity());
    });

    test('returns sport html entity when follow is sport scoped', function () {
        $follow = new Follow(['sport' => Sport::FOOTBALL]);

        expect($follow->sport_display)
            ->toBeInstanceOf(HtmlString::class)
            ->and($follow->sport_display->toHtml())->toBe(HtmlEntity::FOOTBALL->entity());
    });
});
