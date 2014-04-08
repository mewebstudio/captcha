<?php

Route::get('/captcha', function()
{
	ob_start();
	$id = Captcha::create();
	$content = ob_get_clean();
	return Response::make($content)
		->header('Cache-Control', 'no-cache, no-store, max-age=0, must-revalidate')
		->header('Pragma', 'no-cache')
		->header('Content-Type', 'image/jpg')
		->header('Content-Disposition', 'filename=' . $id . '.jpg');
});
