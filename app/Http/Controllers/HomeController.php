<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class HomeController extends Controller
{
    public function index(): View
    {
        return view('index', [
            'application' => 'Community Services Management System',
            'sprint' => 'Sprint 0 - Developer Onboarding',
            'technology' => 'PHP with Laravel',
            'version' => '0.1.0',
        ]);
    }
}