<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class WebsiteController extends Controller
{
    public function home(): View
    {
        return view('website.home');
    }

    public function about(): View
    {
        return view('website.about');
    }

    public function contact(): View
    {
        return view('website.contact');
    }
}
