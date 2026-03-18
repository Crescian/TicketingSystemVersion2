<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class EmployeeDashboardController extends Controller
{
    public function index()
    {
        return view('dashboard.employee');
    }

    // Add this method for the JSON API endpoint
    
}
