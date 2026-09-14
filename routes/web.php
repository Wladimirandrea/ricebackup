<?php

use Illuminate\Support\Facades\Route;

Route::get('/{any}', function () {
    return view('app');
})->where('any', '.*');
Route::get('/baby-shower/guest/{guestId}', function () {
    return view('app'); // Asegúrate de colocar el nombre de tu vista Blade donde se monta Vue (#app)
});