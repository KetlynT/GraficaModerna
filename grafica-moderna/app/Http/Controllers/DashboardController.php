<?php

namespace App\Http\Controllers;

use App\Services\DashboardService;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    protected $service;

    public function __construct(DashboardService $service)
    {
        $this->service = $service;
    }

    public function index(Request $request)
    {
        // Verifica se é Admin (Middleware deve tratar isso também)
        if (!$request->user() || $request->user()->role !== 'Admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $range = $request->query('range', '7d');
        return response()->json($this->service->getAnalytics($range));
    }
}