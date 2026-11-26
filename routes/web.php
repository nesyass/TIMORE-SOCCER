<?php

use Illuminate\Support\Facades\Route;

// The public website can replace this local landing route.
Route::redirect('/', '/admin');

require __DIR__.'/admin.php';
