<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;

class PaymentsController extends Controller
{
    public function success(): Response
    {
        return Inertia::render('Payments/Success');
    }

    public function cancel(): Response
    {
        return Inertia::render('Payments/Cancel');
    }
}
