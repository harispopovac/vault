<?php

namespace Demo\DemoModule\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use App\Models\User;

class DemoController extends Controller
{
    public function getUsers()
    {
        try {
            $users = User::select('id', 'fname', 'sname', 'email', 'created_at')
                ->latest()
                ->limit(10)
                ->get()
                ->map(function ($user) {
                    return [
                        'id' => $user->id,
                        'name' => trim($user->fname . ' ' . $user->sname),
                        'email' => $user->email,
                        'created_at' => $user->created_at
                    ];
                });
            
            return response()->json([
                'success' => true,
                'data' => $users
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch users'
            ], 500);
        }
    }
} 
