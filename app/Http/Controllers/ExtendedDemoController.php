<?php

namespace App\Http\Controllers;

use Demo\DemoModule\Http\Controllers\DemoController;
use App\Models\User;
use Illuminate\Http\Request;

/**
 * Example: Extending the Demo Module Controller
 * This shows how to add project-specific functionality
 * while keeping the base module intact
 */
class ExtendedDemoController extends DemoController
{
    /**
     * Get users with additional project-specific data
     * Overrides the base getUsers method
     */
    public function getUsers()
    {
        try {
            // Get base user data with additional fields
            $users = User::select('id', 'fname', 'sname', 'email', 'created_at', 'role', 'department')
                ->with(['teams', 'projects']) // Project-specific relationships
                ->latest()
                ->limit(10)
                ->get()
                ->map(function ($user) {
                    return [
                        'id' => $user->id,
                        'name' => trim($user->fname . ' ' . $user->sname),
                        'email' => $user->email,
                        'role' => $user->role ?? 'User', // Project-specific field
                        'department' => $user->department ?? 'N/A', // Project-specific field
                        'teams' => $user->teams ?? [], // Project-specific relationship
                        'projects_count' => $user->projects->count() ?? 0, // Project-specific data
                        'created_at' => $user->created_at,
                        'avatar' => $user->avatar_url ?? null, // Project-specific field
                    ];
                });
            
            return response()->json([
                'success' => true,
                'data' => $users,
                'meta' => [
                    'total' => User::count(),
                    'active' => User::whereNotNull('email_verified_at')->count()
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch users'
            ], 500);
        }
    }

    /**
     * Project-specific: Create new user
     */
    public function createUser(Request $request)
    {
        $request->validate([
            'fname' => 'required|string|max:255',
            'sname' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'role' => 'nullable|string|max:255',
            'department' => 'nullable|string|max:255',
            'send_welcome_email' => 'boolean',
        ]);

        try {
            $user = User::create([
                'fname' => $request->fname,
                'sname' => $request->sname,
                'email' => $request->email,
                'password' => bcrypt($request->password),
                'role' => $request->role,
                'department' => $request->department,
                'email_verified_at' => now(), // Auto-verify for admin created users
            ]);

            // Send welcome email if requested
            if ($request->send_welcome_email) {
                // Here you would implement your welcome email logic
                // Mail::to($user->email)->send(new WelcomeUserEmail($user, $request->password));
            }

            // Return user data in the expected format
            $userData = [
                'id' => $user->id,
                'name' => trim($user->fname . ' ' . $user->sname),
                'email' => $user->email,
                'role' => $user->role ?? 'User',
                'department' => $user->department ?? 'N/A',
                'created_at' => $user->created_at,
            ];

            return response()->json([
                'success' => true,
                'data' => $userData,
                'message' => 'User created successfully'
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create user: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Project-specific: Export users to CSV
     */
    public function exportUsers()
    {
        try {
            $users = User::select('id', 'fname', 'sname', 'email', 'role', 'department', 'created_at')
                ->get();

            $filename = 'users_export_' . date('Y-m-d_H-i-s') . '.csv';
            
            $headers = [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ];

            $callback = function() use ($users) {
                $file = fopen('php://output', 'w');
                fputcsv($file, ['ID', 'First Name', 'Last Name', 'Email', 'Role', 'Department', 'Created At']);
                
                foreach ($users as $user) {
                    fputcsv($file, [
                        $user->id,
                        $user->fname,
                        $user->sname,
                        $user->email,
                        $user->role ?? 'N/A',
                        $user->department ?? 'N/A',
                        $user->created_at->format('Y-m-d H:i:s')
                    ]);
                }
                fclose($file);
            };

            return response()->stream($callback, 200, $headers);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to export users'
            ], 500);
        }
    }

    /**
     * Project-specific: Send email to user
     */
    public function sendEmailToUser(Request $request, $userId)
    {
        $request->validate([
            'subject' => 'required|string|max:255',
            'message' => 'required|string',
        ]);

        try {
            $user = User::findOrFail($userId);
            
            // Here you would implement your email sending logic
            // Mail::to($user->email)->send(new CustomUserEmail($request->subject, $request->message));
            
            return response()->json([
                'success' => true,
                'message' => 'Email sent successfully to ' . $user->email
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send email'
            ], 500);
        }
    }

    /**
     * Project-specific: Delete user (soft delete)
     */
    public function deleteUser($userId)
    {
        try {
            $user = User::findOrFail($userId);
            $user->delete(); // Soft delete if you have SoftDeletes trait
            
            return response()->json([
                'success' => true,
                'message' => 'User deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete user'
            ], 500);
        }
    }
} 
