<?php

namespace ShiftLog\ShiftLogModule\Repositories;

use ShiftLog\ShiftLogModule\Models\StaffRosterLog;
use StaffRoster\StaffRosterModule\Models\StaffRoster;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Builder;
use Exception;

class StaffRosterLogRepository
{
    protected StaffRosterLog $staffRosterLog;
    protected StaffRoster $staffRoster;

    public function __construct(StaffRosterLog $staffRosterLog, StaffRoster $staffRoster)
    {
        $this->staffRosterLog = $staffRosterLog;
        $this->staffRoster = $staffRoster;
    }

    /**
     * Get shifts for a specific staff member (My Shifts functionality)
     * Uses Eloquent relationships instead of raw SQL for better reliability
     */
    public function myShifts($data = []): array
    {
        try {
            $staffId = $data['staff_id'] ?? null;
            $page = $data['page'] ?? 1;
            $limit = $data['limit'] ?? 100;
            $showPastShifts = $data['show_past_shifts'] ?? false;
            $showFutureShifts = $data['show_future_shifts'] ?? false;

            if (!$staffId) {
                return [
                    'success' => false,
                    'message' => 'Staff ID is required',
                    'data' => []
                ];
            }

            // Get shift logs for the staff member through staff_roster relationship
            $query = $this->staffRosterLog->newQuery()
                ->with(['staffRoster' => function($query) {
                    $query->select('id', 'staff_id', 'site_id', 'rostered_start', 'rostered_end', 'created_at', 'updated_at');

                    // Include site data if sites are enabled
                    if (config('shift-log.enable_sites', true)) {
                        $query->with(['site:id,name,timezone']);
                    }
                }])
                ->whereHas('staffRoster', function($query) use ($staffId) {
                    $query->where('staff_id', $staffId);
                });

            // Apply date filtering
            if ($showPastShifts) {
                $query->where(function($q) {
                    $q->where('claimed_start', '<=', now())
                      ->orWhere('actual_start', '<=', now());
                });
            } elseif ($showFutureShifts) {
                $query->where(function($q) {
                    $q->where('claimed_start', '>=', now())
                      ->orWhere('actual_start', '>=', now());
                });
            } else {
                // Default: show today's shifts
                $query->whereDate('claimed_start', today());
            }

            // Apply pagination
            $offset = ($page - 1) * $limit;
            $total = $query->count();
            $shifts = $query->skip($offset)->take($limit)->get();

            return [
                'success' => true,
                'data' => $shifts->map(function($shift) {
                    return $this->formatShiftData($shift);
                }),
                'pagination' => [
                    'total' => $total,
                    'page' => $page,
                    'limit' => $limit,
                    'pages' => ceil($total / $limit)
                ]
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Error fetching shifts: ' . $e->getMessage(),
                'data' => []
            ];
        }
    }

    /**
     * Format shift data for consistent output
     */
    private function formatShiftData($shift)
    {
        $roster = $shift->staffRoster;

        return [
            'id' => $shift->id,
            'staff_roster_id' => $shift->staff_roster_id,
            'staff_id' => $roster->staff_id ?? null,
            'site_id' => $roster->site_id ?? null,
            'site_name' => $roster->site->name ?? null,
            'rostered_start' => $roster->rostered_start ?? null,
            'rostered_end' => $roster->rostered_end ?? null,
            'claimed_start' => $shift->claimed_start,
            'claimed_end' => $shift->claimed_end,
            'actual_start' => $shift->actual_start,
            'actual_end' => $shift->actual_end,
            'checkin_comments' => $shift->checkin_comments,
            'checkout_comments' => $shift->checkout_comments,
            'check_in_photo' => $shift->check_in_photo,
            'check_out_photo' => $shift->check_out_photo,
            'ot_claim' => $shift->ot_claim,
            'ot_reason' => $shift->ot_reason,
            'ot_response' => $shift->ot_response,
            'created_at' => $shift->created_at,
            'updated_at' => $shift->updated_at,
        ];
    }

    /**
     * Combined query that matches the qobox functionality
     * Returns both planned shifts (rosters) and actual shift logs in a unified format
     */
    public function index($data = []): array
    {
        // Get configuration values
        $enableSites = config('shift-log.enable_sites', true);
        $siteTable = config('shift-log.site_table', 'sites');
        $siteField = config('shift-log.site_field', 'site_id');
        $staffTable = config('shift-log.staff_table', 'users');
        $staffField = config('shift-log.staff_field', 'staff_id');

        // Build query parameters
        $params = [];

        // Get filters
        $siteIdFilter = isset($data['site_id']) && $data['site_id'] ? (int)$data['site_id'] : null;
        $staffIdFilter = isset($data['staff_id']) && $data['staff_id'] ? (int)$data['staff_id'] : null;
        $dateFilter = isset($data['start']) && $data['start'] ? $data['start'] : date('Y-m-d');

        // Check show past/future shifts
        $showPastShiftsValue = null;
        if (array_key_exists('show_past_shifts', $data)) {
            $value = $data['show_past_shifts'];
            if ($value === true || $value === 'true' || $value === 1 || $value === '1') {
                $showPastShiftsValue = true;
            } elseif ($value === false || $value === 'false' || $value === 0 || $value === '0') {
                $showPastShiftsValue = false;
            }
        }

        $showPastShifts = $showPastShiftsValue === true;
        $showFutureShifts = $showPastShiftsValue === false;

        // Build the rosters CTE
        $sql = 'WITH todays_rosters AS (
            SELECT
                staff_roster.id AS roster_id,';

        // Conditionally include site fields
        if ($enableSites) {
            $sql .= '
                staff_roster.' . $siteField . ' AS roster_site_id,
                (staff_roster.rostered_start AT TIME ZONE roster_site.timezone)::DATE AS rostered_date,
                staff_roster.rostered_start AT TIME ZONE roster_site.timezone AS rostered_start,
                staff_roster.rostered_end AT TIME ZONE roster_site.timezone AS rostered_end';
        } else {
            $sql .= '
                NULL AS roster_site_id,
                staff_roster.rostered_start::DATE AS rostered_date,
                staff_roster.rostered_start AS rostered_start,
                staff_roster.rostered_end AS rostered_end';
        }

        $sql .= ',
                staff_roster.' . $staffField . ' AS roster_staff_id
            FROM
                staff_roster';

        // Conditionally JOIN with sites table
        if ($enableSites) {
            $sql .= ' JOIN ' . $siteTable . ' roster_site ON staff_roster.' . $siteField . ' = roster_site.id';
        }

        $sql .= ' WHERE staff_roster.deleted_at IS NULL';

        // Add date filtering
        if ($showPastShifts) {
            if ($enableSites) {
                $sql .= ' AND (staff_roster.rostered_start AT TIME ZONE roster_site.timezone)::DATE <= ?::DATE';
            } else {
                $sql .= ' AND staff_roster.rostered_start::DATE <= ?::DATE';
            }
            $params[] = $dateFilter;
        } elseif ($showFutureShifts) {
            if ($enableSites) {
                $sql .= ' AND (staff_roster.rostered_start AT TIME ZONE roster_site.timezone)::DATE >= ?::DATE';
            } else {
                $sql .= ' AND staff_roster.rostered_start::DATE >= ?::DATE';
            }
            $params[] = $dateFilter;
        } else {
            // Show only today's shifts
            $sql .= ' AND staff_roster.rostered_start >= ?::timestamp - INTERVAL \'24 hours\'
                AND staff_roster.rostered_start <= ?::timestamp + INTERVAL \'24 hours\'';

            if ($enableSites) {
                $sql .= ' AND (staff_roster.rostered_start AT TIME ZONE roster_site.timezone)::DATE = ?::DATE';
            } else {
                $sql .= ' AND staff_roster.rostered_start::DATE = ?::DATE';
            }

            $params[] = $dateFilter;
            $params[] = $dateFilter;
            $params[] = $dateFilter;
        }

        // Add filters
        if ($siteIdFilter && $enableSites) {
            $sql .= ' AND staff_roster.' . $siteField . ' = ?';
            $params[] = $siteIdFilter;
        }

        if ($staffIdFilter) {
            $sql .= ' AND staff_roster.' . $staffField . ' = ?';
            $params[] = $staffIdFilter;
        }

        // Build the logs CTE
        $sql .= '),
            todays_logs AS (
            SELECT
                staff_roster_log.id AS log_id,
                staff_roster_log.staff_roster_id,';

        // Conditionally include site fields for logs (get from staff_roster, not staff_roster_log)
        if ($enableSites) {
            $sql .= '
                log_roster.' . $siteField . ' AS log_site_id,
                (staff_roster_log.claimed_start AT TIME ZONE log_site.timezone)::DATE AS claimed_date,
                staff_roster_log.claimed_start AT TIME ZONE log_site.timezone AS claimed_start,
                staff_roster_log.claimed_end AT TIME ZONE log_site.timezone AS claimed_end,
                (staff_roster_log.actual_start AT TIME ZONE log_site.timezone)::DATE AS actual_date,
                staff_roster_log.actual_start AT TIME ZONE log_site.timezone AS actual_start,
                staff_roster_log.actual_end AT TIME ZONE log_site.timezone AS actual_end';
        } else {
            $sql .= '
                NULL AS log_site_id,
                staff_roster_log.claimed_start::DATE AS claimed_date,
                staff_roster_log.claimed_start AS claimed_start,
                staff_roster_log.claimed_end AS claimed_end,
                staff_roster_log.actual_start::DATE AS actual_date,
                staff_roster_log.actual_start AS actual_start,
                staff_roster_log.actual_end AS actual_end';
        }

        $sql .= ',
                log_roster.' . $staffField . ' AS log_staff_id';

        // Add optional overtime fields
        if (config('shift-log.enable_overtime', false)) {
            $sql .= ',
                staff_roster_log.ot_claim,
                staff_roster_log.ot_reason,
                staff_roster_log.ot_response';
        } else {
            $sql .= ',
                NULL AS ot_claim,
                NULL AS ot_reason,
                NULL AS ot_response';
        }

        $sql .= ' FROM staff_roster_log
            JOIN staff_roster log_roster ON staff_roster_log.staff_roster_id = log_roster.id';

        // Conditionally JOIN with sites table for logs (using log_roster, not staff_roster_log)
        if ($enableSites) {
            $sql .= ' JOIN ' . $siteTable . ' log_site ON log_roster.' . $siteField . ' = log_site.id';
        }

        $sql .= ' WHERE staff_roster_log.deleted_at IS NULL';

        // Add date filtering for logs
        if ($showPastShifts) {
            if ($enableSites) {
                $sql .= ' AND ((staff_roster_log.claimed_start AT TIME ZONE log_site.timezone)::DATE <= ?::DATE
                    OR (staff_roster_log.actual_start AT TIME ZONE log_site.timezone)::DATE <= ?::DATE)';
            } else {
                $sql .= ' AND (staff_roster_log.claimed_start::DATE <= ?::DATE
                    OR staff_roster_log.actual_start::DATE <= ?::DATE)';
            }
            $params[] = $dateFilter;
            $params[] = $dateFilter;
        } elseif ($showFutureShifts) {
            if ($enableSites) {
                $sql .= ' AND ((staff_roster_log.claimed_start AT TIME ZONE log_site.timezone)::DATE >= ?::DATE
                    OR (staff_roster_log.actual_start AT TIME ZONE log_site.timezone)::DATE >= ?::DATE)';
            } else {
                $sql .= ' AND (staff_roster_log.claimed_start::DATE >= ?::DATE
                    OR staff_roster_log.actual_start::DATE >= ?::DATE)';
            }
            $params[] = $dateFilter;
            $params[] = $dateFilter;
        } else {
            // Show only today's logs
            $sql .= ' AND staff_roster_log.claimed_start >= ?::timestamp - INTERVAL \'24 hours\'
                AND staff_roster_log.claimed_start <= ?::timestamp + INTERVAL \'24 hours\'';

            if ($enableSites) {
                $sql .= ' AND ((staff_roster_log.claimed_start AT TIME ZONE log_site.timezone)::DATE = ?::DATE
                    OR (staff_roster_log.actual_start AT TIME ZONE log_site.timezone)::DATE = ?::DATE)';
            } else {
                $sql .= ' AND (staff_roster_log.claimed_start::DATE = ?::DATE
                    OR staff_roster_log.actual_start::DATE = ?::DATE)';
            }

            $params[] = $dateFilter;
            $params[] = $dateFilter;
            $params[] = $dateFilter;
            $params[] = $dateFilter;
        }

        // Add filters for logs (using log_roster, not staff_roster_log)
        if ($siteIdFilter && $enableSites) {
            $sql .= ' AND log_roster.' . $siteField . ' = ?';
            $params[] = $siteIdFilter;
        }

        if ($staffIdFilter) {
            $sql .= ' AND log_roster.' . $staffField . ' = ?';
            $params[] = $staffIdFilter;
        }

        // Main query combining both CTEs
        $sql .= ')
            SELECT
                r.roster_id,
                r.roster_site_id,
                r.roster_staff_id,
                r.rostered_date,
                r.rostered_start,
                r.rostered_end,
                l.log_id,
                l.log_site_id,
                l.log_staff_id,
                l.claimed_date,
                l.claimed_start,
                l.claimed_end,
                l.actual_date,
                l.actual_start,
                l.actual_end,
                l.ot_claim,
                l.ot_reason,
                l.ot_response
            FROM
                todays_rosters r
                LEFT JOIN todays_logs l ON l.staff_roster_id = r.roster_id
            UNION ALL
            SELECT
                NULL AS roster_id,
                NULL AS roster_site_id,
                NULL AS roster_staff_id,
                NULL AS rostered_date,
                NULL AS rostered_start,
                NULL AS rostered_end,
                l.log_id,
                l.log_site_id,
                l.log_staff_id,
                l.claimed_date,
                l.claimed_start,
                l.claimed_end,
                l.actual_date,
                l.actual_start,
                l.actual_end,
                l.ot_claim,
                l.ot_reason,
                l.ot_response
            FROM
                todays_logs l
            WHERE
                l.staff_roster_id IS NULL
            ORDER BY claimed_start, rostered_start, actual_start';

        // Execute the query
        $results = DB::select($sql, $params);

        // Find open shift if staff_id is provided
        $openShift = null;
        if ($staffIdFilter) {
            $openShift = $this->staffRosterLog
                ->with(['staffRoster', 'breaks'])
                ->whereHas('staffRoster', function($query) use ($staffIdFilter) {
                    $query->where('staff_id', $staffIdFilter);
                })
                ->whereNotNull('actual_start')
                ->whereNull('actual_end')
                ->first();
        }

        // Transform and enrich results
        $transformedResults = $this->transformQueryResults($results, $data);

        return [
            'data' => $transformedResults,
            'open_shift' => $openShift,
            'meta' => [
                'total' => count($transformedResults),
                'filters' => $data
            ]
        ];
    }

    /**
     * Transform raw query results into the expected format
     */
    protected function transformQueryResults(array $results, array $filters = []): array
    {
        $transformed = [];
        $staffTable = config('shift-log.staff_table', 'users');
        $staffField = config('shift-log.staff_field', 'staff_id');

        foreach ($results as $result) {
            $item = [
                'roster_id' => $result->roster_id,
                'log_id' => $result->log_id,
                'staff_id' => $result->roster_staff_id ?? $result->log_staff_id,
                'site_id' => $result->roster_site_id ?? $result->log_site_id,

                // Planned shift data
                'rostered_start' => $result->rostered_start,
                'rostered_end' => $result->rostered_end,
                'rostered_date' => $result->rostered_date,

                // Actual shift data
                'claimed_start' => $result->claimed_start,
                'claimed_end' => $result->claimed_end,
                'actual_start' => $result->actual_start,
                'actual_end' => $result->actual_end,

                // Status indicators
                'is_planned_only' => $result->roster_id && !$result->log_id,
                'is_manual_log' => !$result->roster_id && $result->log_id,
                'is_logged_shift' => $result->roster_id && $result->log_id,
                'is_open' => $result->actual_start && !$result->actual_end,
                'is_completed' => $result->actual_start && $result->actual_end,
            ];

            // Add optional fields based on configuration
            if (config('shift-log.enable_overtime', false)) {
                $item['ot_claim'] = $result->ot_claim;
                $item['ot_reason'] = $result->ot_reason;
                $item['ot_response'] = $result->ot_response;
            }

            $transformed[] = $item;
        }

        // Apply search filtering if provided
        if (!empty($filters['search'])) {
            $transformed = $this->applySearchFilter($transformed, $filters['search']);
        }

        return $transformed;
    }

    /**
     * Apply search filtering to results
     */
    protected function applySearchFilter(array $results, $searchTerms): array
    {
        if (empty($searchTerms)) {
            return $results;
        }

        if (!is_array($searchTerms)) {
            $searchTerms = preg_split('/\s+/', $searchTerms, -1, PREG_SPLIT_NO_EMPTY);
        }

        return array_filter($results, function($item) use ($searchTerms) {
            // This is a simplified search - in a full implementation,
            // you'd join with staff table to search by name
            foreach ($searchTerms as $term) {
                $term = strtolower($term);
                // Add search logic here based on your requirements
                // For now, just return true to keep all items
                return true;
            }
            return false;
        });
    }

    /**
     * Store/Check-in functionality
     */
    public function store(array $data): StaffRosterLog
    {
        try {
            DB::beginTransaction();

            // Add IP tracking if enabled
            if (config('shift-log.enable_ip_tracking', false)) {
                $data['checkin_ip'] = request()->ip();
                $data['site_ip'] = $data['site_ip'] ?? request()->ip();
            }

            // Set actual start time to now
            $data['actual_start'] = now();

            $staffRosterLog = $this->staffRosterLog->create($data);

            DB::commit();

            return $staffRosterLog->load(['staff', 'site', 'breaks']);
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Update/Check-out functionality
     */
    public function update(StaffRosterLog $staffRosterLog, array $data): StaffRosterLog
    {
        try {
            DB::beginTransaction();

            // Add checkout IP if enabled and checking out
            if (config('shift-log.enable_ip_tracking', false) && isset($data['actual_end'])) {
                $data['checkout_ip'] = request()->ip();
            }

            $staffRosterLog->update($data);

            DB::commit();

            return $staffRosterLog->load(['staff', 'site', 'breaks']);
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Get open shift for a staff member
     */
    public function getOpenShift(int $staffId): ?StaffRosterLog
    {
        return $this->staffRosterLog
            ->with(['staffRoster', 'breaks'])
            ->whereHas('staffRoster', function($query) use ($staffId) {
                $query->where('staff_id', $staffId);
            })
            ->whereNotNull('actual_start')
            ->whereNull('actual_end')
            ->first();
    }

    /**
     * Delete a shift log
     */
    public function destroy(StaffRosterLog $staffRosterLog): bool
    {
        return $staffRosterLog->delete();
    }
}
