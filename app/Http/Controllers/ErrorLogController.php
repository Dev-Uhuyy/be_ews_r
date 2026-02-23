<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ErrorLog;

class ErrorLogController extends Controller
{
    public function index(Request $request)
    {
        if ($request->isMethod('post')) {
            if ($request->password === 'sti-api-123') {
                $logs = ErrorLog::orderBy('created_at', 'desc')->get();
                return view('logs.index', [
                    'authorized' => true,
                    'logs' => $logs,
                ]);
            } else {
                return view('logs.index', [
                    'authorized' => false,
                    'error' => 'Password salah',
                ]);
            }
        }

        // Jika GET langsung, maka tidak authorized
        return view('logs.index', [
            'authorized' => false,
        ]);
    }

}
