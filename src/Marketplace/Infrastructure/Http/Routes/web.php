<?php
use Illuminate\Support\Facades\Route;
use Src\Marketplace\Infrastructure\Http\Controller\ViewHomePageCentralGETController;

Route::get("/", [ViewHomePageCentralGETController::class, 'index'])->name('central.home');

?>
