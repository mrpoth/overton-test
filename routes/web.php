<?php

use App\Http\Controllers\ParserController;
use Illuminate\Support\Facades\Route;

Route::get('/', [ParserController::class, 'index']);
