<?php

namespace App\Http\Controllers;

class HomeController extends Controller
{
    public function index()
    {
        $products = \App\Models\Product::where('is_featured', true)->take(8)->get();
        return view('home', compact('products'));
    }
}