<?php

test('home page can be rendered', function () {
    $this->get('/')->assertOk();
});
