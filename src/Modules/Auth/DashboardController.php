<?php

namespace App\Modules\Auth;

use App\Core\Auth;
use App\Core\BaseController;

class DashboardController extends BaseController
{
    public function index()
    {
        Auth::requireLogin();
        $this->view('admin/dashboard', [
            'title' => 'Dashboard - Control Center'
        ]);
    }
}
