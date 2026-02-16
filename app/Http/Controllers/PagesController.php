<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

class PagesController extends Controller
{
    public function index()
    {
        return Inertia::render('Guest/Home');
    }

    public function awards()
    {
        return Inertia::render('Guest/Awards');
    }

    public function services()
    {
        return Inertia::render('Guest/Services');
    }
}
