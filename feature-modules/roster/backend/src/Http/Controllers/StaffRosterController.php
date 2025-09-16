<?php

namespace StaffRoster\StaffRosterModule\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use StaffRoster\StaffRosterModule\Http\Requests\CheckConflictsRequest;
use StaffRoster\StaffRosterModule\Http\Requests\ClearRosterRequest;
use StaffRoster\StaffRosterModule\Http\Requests\CopyDayRequest;
use StaffRoster\StaffRosterModule\Http\Requests\CopyRosterRequest;
use StaffRoster\StaffRosterModule\Http\Requests\StoreStaffRosterRequest;
use StaffRoster\StaffRosterModule\Http\Requests\UpdateStaffRosterRequest;
use StaffRoster\StaffRosterModule\Models\StaffRoster;
use StaffRoster\StaffRosterModule\Repositories\StaffRosterRepository;
use Exception;

class StaffRosterController extends Controller
{
    protected StaffRosterRepository $staffRosterRepository;
    protected StaffRoster $staffRoster;

    public function __construct(StaffRosterRepository $staffRosterRepository, StaffRoster $staffRoster)
    {
        $this->staffRosterRepository = $staffRosterRepository;
        $this->staffRoster = $staffRoster;
    }

    /**
     * Display a listing of the rosters.
     */
    public function index(Request $request): JsonResponse
    {
        $filters = [
            'site_id' => (int)$request->query('site_id'),
            'start' => $request->query('start'),
            'end' => $request->query('end'),
            'search' => $request->query('search'),
            'staff_ids' => $request->query('staff_ids'),
            'include_overlapping' => $request->query('include_overlapping', false),
            'upcoming_soon' => $request->query('upcoming_soon'),
            'isToday' => $request->query('isToday'),
        ];

        try {
            $rosters = $this->staffRosterRepository->index($filters);
            return response()->json($rosters);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch rosters',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created roster in storage.
     */
    public function store(StoreStaffRosterRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();

            // Check for conflicts
            foreach ($validated['staff_ids'] as $staffId) {
                $data = $validated;
                $data['staff_id'] = $staffId;
                $conflicts = $this->staffRosterRepository->checkConflicts($data);

                if (!empty($conflicts['data'])) {
                    $staff = \App\Models\Staff::find($staffId);
                    return response()->json([
                        'success' => false,
                        'message' => 'Roster conflict detected',
                        'errors' => [
                            'staff' => ($staff ? $staff->fname . ' ' . $staff->sname : 'Staff member') . ' has a conflicting roster'
                        ]
                    ], 422);
                }
            }

            $rosters = $this->staffRosterRepository->store($validated);

            return response()->json([
                'success' => true,
                'message' => 'Staff roster added successfully',
                'data' => $rosters
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create roster',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified roster.
     */
    public function show(StaffRoster $roster): JsonResponse
    {
        try {
            $rosterData = $this->staffRosterRepository->show($roster);
            return response()->json($rosterData);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch roster',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified roster in storage.
     */
    public function update(UpdateStaffRosterRequest $request, StaffRoster $roster): JsonResponse
    {
        try {
            $validated = $request->validated();
            $validated['id'] = $roster->id;

            // Check for conflicts (excluding current roster)
            $conflicts = $this->staffRosterRepository->checkConflicts($validated);

            if (!empty($conflicts['data'])) {
                $staff = \App\Models\Staff::find($validated['staff_id']);
                return response()->json([
                    'success' => false,
                    'message' => 'Roster conflict detected',
                    'errors' => [
                        'staff' => ($staff ? $staff->fname . ' ' . $staff->sname : 'Staff member') . ' has a conflicting roster'
                    ]
                ], 422);
            }

            $updatedRoster = $this->staffRosterRepository->update($roster, $validated);

            return response()->json([
                'success' => true,
                'message' => 'Staff roster updated successfully',
                'data' => $updatedRoster
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update roster',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified roster from storage.
     */
    public function destroy($id): JsonResponse
    {
        try {
            $roster = $this->staffRoster->find($id);

            if (!$roster) {
                return response()->json([
                    'success' => false,
                    'message' => 'Roster not found',
                ], 404);
            }

            $deletedRoster = $this->staffRosterRepository->destroy($roster);

            return response()->json([
                'success' => true,
                'message' => 'Roster deleted successfully',
                'data' => $deletedRoster,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete roster',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Copy rosters from one week to another.
     */
    public function copy(CopyRosterRequest $request): JsonResponse
    {
        try {
            $result = $this->staffRosterRepository->copy($request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Rosters copied successfully',
                'data' => $result
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to copy rosters',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Clear rosters for a specific period.
     */
    public function clear(ClearRosterRequest $request): JsonResponse
    {
        try {
            $result = $this->staffRosterRepository->clear($request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Rosters cleared successfully',
                'data' => $result
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to clear rosters',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Clear rosters for a specific day.
     */
    public function clearDay(Request $request): JsonResponse
    {
        $request->validate([
            'site_id' => 'nullable|integer',
            'day' => 'required|date',
        ]);

        try {
            $result = $this->staffRosterRepository->clear($request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Day rosters cleared successfully',
                'data' => $result
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to clear day rosters',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Copy rosters from one day to other days.
     */
    public function copyDay(CopyDayRequest $request): JsonResponse
    {
        try {
            $result = $this->staffRosterRepository->copyDay($request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Day rosters copied successfully',
                'data' => $result
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to copy day rosters',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * TODO: Move this to a dedicated StaffController later
     * Get staff list for roster assignment
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

    /**
     * Get future rosters.
     */
    public function futureRosters(Request $request): JsonResponse
    {
        $filters = [
            'site_id' => $request->query('site_id'),
            'staff_ids' => $request->query('staff_ids'),
            'start_date' => now()->toDateString(),
        ];

        try {
            $rosters = $this->staffRosterRepository->futureRosters($filters);
            return response()->json($rosters);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch future rosters',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get upcoming rosters (within next hour).
     */
    public function upcomingRosters(Request $request): JsonResponse
    {
        try {
            $rosters = $this->staffRosterRepository->index([
                'site_id' => $request->query('site_id'),
                'staff_ids' => $request->query('staff_ids'),
                'upcoming_soon' => true,
            ]);

            return response()->json($rosters);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch upcoming rosters',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Check for roster conflicts.
     */
    public function checkConflicts(CheckConflictsRequest $request): JsonResponse
    {
        try {
            $conflicts = $this->staffRosterRepository->checkConflicts($request->validated());

            return response()->json([
                'success' => true,
                'conflicts' => $conflicts,
                'has_conflicts' => !empty($conflicts['data'])
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to check conflicts',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
