<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Src\Review\Infrastructure\Http\Controller\ViewReviewIndexGETController;

Route::get('/backoffice/{user_uuid}/module', [ViewReviewIndexGETController::class, 'index'])->name('tenant.review.index');
