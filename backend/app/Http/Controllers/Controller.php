<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

abstract class Controller
{
    // Menyediakan $this->authorize() untuk pemeriksaan Policy (KNF-01)
    use AuthorizesRequests;
}