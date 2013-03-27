<?php

Route::get('/captcha', function()
{
	return Captcha::create();
});