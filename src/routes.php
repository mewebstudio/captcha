<?php

Route::get('/captcha', function () {
    return Captcha::create(Input::has('id') ? Input::get('id') : null);
});
