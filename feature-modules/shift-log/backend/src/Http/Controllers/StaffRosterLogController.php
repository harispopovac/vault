<?php

namespace ShiftLog\ShiftLogModule\Http\Controllers;

use ShiftLog\ShiftLogModule\Http\Requests\CheckInRequest;
use ShiftLog\ShiftLogModule\Http\Requests\CheckOutRequest;
use ShiftLog\ShiftLogModule\Models\StaffRosterLog;
use ShiftLog\ShiftLogModule\Repositories\StaffRosterLogRepository;
use ShiftLog\ShiftLogModule\Transformers\StaffRosterLogTransformer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Exception;

class StaffRosterLogController extends Controller
{
    protected StaffRosterLogRepository $staffRosterLogRepository;

    public function __construct(StaffRosterLogRepository $staffRosterLogRepository)
    {
        $this->staffRosterLogRepository = $staffRosterLogRepository;

        // Add middleware for authentication if needed
        // $this->middleware('auth:staff_api');
    }

    /**
     * Get shift log configuration
     */
    public function config(): JsonResponse
    {
        try {
            return response()->json([
                'success' => true,
                'data' => config('shift-log')
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch configuration',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display a listing of shift logs with combined roster data
     * This is the main endpoint that combines planned shifts with actual logs
     */
    public function index(Request $request): JsonResponse
    {
        try {
            // Authorization check would go here
            // $this->authorize('see_shift_log', [StaffRosterLog::class, ['site_id' => $request->query('site_id')]]);

            $filters = [
                'site_id' => $request->query('site_id') ? (int)$request->query('site_id') : null,
                'staff_id' => $request->query('staff_id') ? (int)$request->query('staff_id') : null,
                'start' => $request->query('start', date('Y-m-d')),
                'end' => $request->query('end'),
                'search' => $request->query('search'),
                'show_past_shifts' => $request->query('show_past_shifts'),
                'all' => $request->query('all', false),
                'page' => $request->query('page', 1),
                'limit' => $request->query('limit', 100),
            ];

            $result = $this->staffRosterLogRepository->index($filters);

            return response()->json($result);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch shift logs',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Check in to a shift (create a new shift log entry)
     */
    public function store(CheckInRequest $request): JsonResponse
    {
        try {
            // Authorization check would go here
            // $this->authorize('manage_shift_log', StaffRosterLog::class);

            $validatedData = $request->getValidatedData();

            // Check if staff member already has an open shift
            $staffField = config('shift-log.staff_field', 'staff_id');
            $staffId = $validatedData[$staffField];

            $existingOpenShift = $this->staffRosterLogRepository->getOpenShift($staffId);
            if ($existingOpenShift) {
                return response()->json([
                    'success' => false,
                    'message' => 'Staff member already has an open shift',
                    'error' => 'You must check out of your current shift before checking in to a new one.',
                    'open_shift' => $existingOpenShift
                ], 422);
            }

            $staffRosterLog = $this->staffRosterLogRepository->store($validatedData);

            return response()->json([
                'success' => true,
                'message' => 'Checked in successfully',
                'data' => $staffRosterLog
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to check in',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified shift log
     */
    public function show(StaffRosterLog $staffRosterLog): JsonResponse
    {
        try {
            // Authorization check would go here
            // $this->authorize('see_shift_log', $staffRosterLog);

            $staffRosterLog->load(['staff', 'site', 'breaks']);

            return response()->json([
                'success' => true,
                'data' => $staffRosterLog
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch shift log',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Check out from a shift (update existing shift log entry)
     */
    public function update(CheckOutRequest $request, StaffRosterLog $staffRosterLog): JsonResponse
    {
        try {
            // Authorization check would go here
            // $this->authorize('manage_shift_log', $staffRosterLog);

            // Ensure the shift is currently open
            if ($staffRosterLog->actual_end !== null) {
                return response()->json([
                    'success' => false,
                    'message' => 'Shift is already completed',
                    'error' => 'This shift has already been checked out.'
                ], 422);
            }

            $validatedData = $request->getValidatedData();

            $updatedStaffRosterLog = $this->staffRosterLogRepository->update($staffRosterLog, $validatedData);

            return response()->json([
                'success' => true,
                'message' => 'Checked out successfully',
                'data' => $updatedStaffRosterLog
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to check out',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified shift log
     */
    public function destroy(StaffRosterLog $staffRosterLog): JsonResponse
    {
        try {
            // Authorization check would go here
            // $this->authorize('manage_shift_log', $staffRosterLog);

            $this->staffRosterLogRepository->destroy($staffRosterLog);

            return response()->json([
                'success' => true,
                'message' => 'Shift log deleted successfully'
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete shift log',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get the current open shift for a staff member
     */
    public function getOpenShift(Request $request): JsonResponse
    {
        try {
            $staffId = $request->query('staff_id');

            if (!$staffId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Staff ID is required'
                ], 422);
            }

            $openShift = $this->staffRosterLogRepository->getOpenShift((int)$staffId);

            return response()->json([
                'success' => true,
                'data' => $openShift
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch open shift',
                'error' => $e->getMessage()
            ], 500);
        }
    }

        /**
     * My Shifts - Get shifts for the authenticated staff member
     */
    public function myShifts(Request $request): JsonResponse
    {
        try {
            // Get authenticated user
            $user = auth()->user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Authentication required'
                ], 401);
            }

            // For "My Shifts", use the authenticated user's ID
            // This assumes the users table is the same as staff table
            // Or adjust this logic based on your User-Staff relationship
            $staffId = $user->id;

            $filters = [
                'staff_id' => (int)$staffId,
                'start' => $request->query('start', date('Y-m-d')),
                'end' => $request->query('end'),
                'show_past_shifts' => $request->query('show_past_shifts'),
                'all' => $request->query('all', false),
                'page' => $request->query('page', 1),
                'limit' => $request->query('limit', 100),
            ];

            $result = $this->staffRosterLogRepository->myShifts($filters);

            return response()->json($result);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch my shifts',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Handle break operations (when breaks are enabled)
     */
    public function manageBreak(Request $request, StaffRosterLog $staffRosterLog): JsonResponse
    {
        if (!config('shift-log.enable_breaks', false)) {
            return response()->json([
                'success' => false,
                'message' => 'Break functionality is disabled'
            ], 403);
        }

        try {
            $action = $request->input('action'); // 'start' or 'end'

            // Break management logic would go here
            // This is a placeholder for break functionality

            return response()->json([
                'success' => true,
                'message' => 'Break ' . $action . ' recorded successfully'
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to manage break',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * TODO: Move this to a dedicated StaffController later
     * Get staff list for shift log assignment
     */
    public function getStaffList(Request $request): JsonResponse
    {
        try {
            $search = $request->query('search', '');
            $all = $request->query('all', false);

            $query = \App\Models\Staff::query();

            if ($search) {
                $query->where(function($q) use ($search) {
                    $q->where('fname', 'like', '%' . $search . '%')
                      ->orWhere('sname', 'like', '%' . $search . '%')
                      ->orWhere('email', 'like', '%' . $search . '%');
                });
            }

            // Add fullname attribute for frontend compatibility
            $query->selectRaw("*, CONCAT(fname, ' ', sname) as fullname");

            if ($all) {
                $staff = $query->get();
            } else {
                $staff = $query->paginate(50);
            }

            return response()->json([
                'success' => true,
                'data' => $staff,
                'staff' => $staff // For compatibility with existing frontend code
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch staff list',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
