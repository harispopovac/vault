<?php

namespace StaffRoster\StaffRosterModule\Repositories;

use StaffRoster\StaffRosterModule\Models\StaffRoster;
use StaffRoster\StaffRosterModule\Transformers\StaffRosterTransformer;
use Illuminate\Support\Facades\DB;
use Exception;

class StaffRosterRepository
{
    protected StaffRoster $staffRoster;

    public function __construct(StaffRoster $staffRoster)
    {
        $this->staffRoster = $staffRoster;
    }

    public function index($data): ?array
    {
        $query = $this->staffRoster->newQuery();

        // Include relationships
        $with = ['staff'];
        if (config('roster.enable_sites')) {
            $with[] = config('roster.site_relationship');
        }
        $query->with($with);

        // Filter by site_id if provided and sites are enabled
        if (config('roster.enable_sites') && isset($data['site_id']) && $data['site_id'] !== 0) {
            $query->forSite($data['site_id']);
        }

        // Filter by staff_ids if provided
        if (isset($data['staff_ids'])) {
            $staffIds = is_array($data['staff_ids']) ? $data['staff_ids'] : explode(',', $data['staff_ids']);
            $query->whereIn('staff_id', $staffIds);
            $query->whereHas('staff', function ($q) {
                $q->where('status', '!=', 'retired');
            });
        }

        // Date range filtering
        if (isset($data['start']) && isset($data['end'])) {
            if ($data['include_overlapping'] ?? false) {
                $query->overlapping($data['start'], $data['end']);
            } else {
                $query->forPeriod($data['start'], $data['end']);
            }
        }

        // Today's roster filter
        if (isset($data['isToday']) && $data['isToday']) {
            $query->where('rostered_start', '>=', now()->startOfDay())
                  ->where('rostered_end', '<=', now()->endOfDay());
        }

        // Upcoming shifts filter (within next hour)
        if (isset($data['upcoming_soon']) && $data['upcoming_soon']) {
            $currentTime = now();
            $oneHourAhead = $currentTime->copy()->addHour();

            $query->where('rostered_start', '<=', $oneHourAhead)
                  ->where('rostered_end', '>=', $currentTime);
        }

        // Execute the query
        $rosters = $query->get();

        // Sort by start time, then by staff name
        $rosters = $rosters->sortBy(function ($roster) {
            return [
                $roster->rostered_start,
                $roster->staff->fname ?? '',
                $roster->staff->sname ?? ''
            ];
        });

        return fractal($rosters, new StaffRosterTransformer())->toArray();
    }

    public function store($data): ?array
    {
        DB::beginTransaction();

        try {
            $staffRosters = [];

            // Support multiple staff IDs
            $staffIds = $data['staff_ids'] ?? [$data['staff_id']];

            foreach ($staffIds as $staffId) {
                $rosterData = $data;
                $rosterData['staff_id'] = $staffId;
                unset($rosterData['staff_ids']); // Remove array to avoid issues

                $staffRoster = $this->staffRoster->create($rosterData);
                $staffRosters[] = $staffRoster;
            }

            DB::commit();

            return fractal($staffRosters, new StaffRosterTransformer())->toArray();
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    public function show(StaffRoster $staffRoster): ?array
    {
        $staffRoster->load(['staff', 'site']);
        return fractal($staffRoster, new StaffRosterTransformer())->toArray();
    }

    public function update(StaffRoster $staffRoster, $data): ?array
    {
        DB::beginTransaction();

        try {
            // Handle absence logic
            if (isset($data['absent']) && $data['absent']) {
                $data['absent_notice'] = $staffRoster->absent_notice ?: now();
                $data['absence_noted_by'] = $staffRoster->absence_noted_by ?: ($data['noted_by'] ?? null);
            } else {
                $data['absent_notice'] = null;
                $data['absence_noted_by'] = null;
                $data['absent_notes'] = null;
            }

            $staffRoster->update($data);

            DB::commit();

            return fractal($staffRoster, new StaffRosterTransformer())->toArray();
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    public function destroy(StaffRoster $staffRoster): array
    {
        DB::beginTransaction();

        try {
            // Transform the model BEFORE soft deleting it
            $transformedData = fractal($staffRoster, new StaffRosterTransformer())->toArray()['data'];

            // Now soft delete the model
            $staffRoster->delete();

            DB::commit();

            return $transformedData;
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    public function copy($data): bool
    {
        DB::beginTransaction();

        try {
            // Get current week rosters (use overlapping to include rosters that span week boundaries)
            $currentWeek = $this->index([
                'site_id' => $data['site_id'],
                'start' => $data['current_week']['start'],
                'end' => $data['current_week']['end'],
                'include_overlapping' => true,
            ]);

            if (empty($currentWeek['data'])) {
                DB::commit();
                return true;
            }

            // Clear target weeks first
            foreach ($data['copy_to'] as $week) {
                $this->clear([
                    'site_id' => $data['site_id'],
                    'start' => $week['start'],
                    'end' => $week['end'],
                ]);
            }

            // Copy rosters to target weeks
            $allRosterData = [];
            $currentWeekStart = new \DateTime($data['current_week']['start']);

            foreach ($data['copy_to'] as $week) {
                $targetWeekStart = new \DateTime($week['start']);
                $daysDifference = $targetWeekStart->diff($currentWeekStart)->days;

                foreach ($currentWeek['data'] as $roster) {
                    $roster = (object)$roster;

                    $newStart = \Carbon\Carbon::parse($roster->rostered_start)->addDays($daysDifference);
                    $newEnd = \Carbon\Carbon::parse($roster->rostered_end)->addDays($daysDifference);

                    $allRosterData[] = [
                        'staff_id' => $roster->staff_id,
                        'site_id' => $roster->site_id,
                        'rostered_start' => $newStart,
                        'rostered_end' => $newEnd,
                        'hourlyrate' => $roster->hourlyrate,
                        'shiftrate' => $roster->shiftrate,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
            }

            // Bulk insert
            if (!empty($allRosterData)) {
                DB::table('staff_roster')->insert($allRosterData);
            }

            DB::commit();
            return true;
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    public function clear($data): bool
    {
        DB::beginTransaction();

        try {
            // Use raw DB queries for hard delete (like qobox/tdtplanner)
            if (isset($data['day'])) {
                // Clear specific day
                $query = DB::table('staff_roster')
                    ->whereDate('rostered_start', $data['day']);

                if (isset($data['site_id']) && $data['site_id'] !== null) {
                    $query->where('site_id', $data['site_id']);
                } else {
                    $query->whereNull('site_id');
                }

                $query->delete();
            } else {
                // Clear date range
                $query = DB::table('staff_roster')
                    ->whereBetween('rostered_start', [$data['start'] . ' 00:00:00', $data['end'] . ' 23:59:59']);

                if (isset($data['site_id']) && $data['site_id'] !== null) {
                    $query->where('site_id', $data['site_id']);
                } else {
                    $query->whereNull('site_id');
            }

            $query->delete();
            }

            DB::commit();
            return true;
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    public function copyDay($data): bool
    {
        DB::beginTransaction();

        try {
            $weekStart = \Carbon\Carbon::parse($data['week_start']);
            $sourceDayIndex = $data['source_day_index'];
            $copyToDayIndices = $data['copy_to'];

            // Get the source day's roster data
            $sourceDayDate = $weekStart->copy()->addDays($sourceDayIndex)->toDateString();
            $sourceQuery = $this->staffRoster->newQuery()
                ->whereDate('rostered_start', $sourceDayDate);

            if (isset($data['site_id']) && $data['site_id'] !== null) {
                $sourceQuery->where('site_id', $data['site_id']);
            } else {
                $sourceQuery->whereNull('site_id');
            }

            $sourceRosters = $sourceQuery->get();

            // Copy to each target day
            foreach ($copyToDayIndices as $targetDayIndex) {
                if ($targetDayIndex === $sourceDayIndex) {
                    continue; // Skip copying to the same day
                }

                $targetDayDate = $weekStart->copy()->addDays($targetDayIndex)->toDateString();

                // Clear existing roster for the target day (hard delete)
                $clearQuery = DB::table('staff_roster')
                    ->whereDate('rostered_start', $targetDayDate);

                if (isset($data['site_id']) && $data['site_id'] !== null) {
                    $clearQuery->where('site_id', $data['site_id']);
                } else {
                    $clearQuery->whereNull('site_id');
                }

                $clearQuery->delete();

                // Copy rosters to the target day
                foreach ($sourceRosters as $sourceRoster) {
                    $daysDifference = $targetDayIndex - $sourceDayIndex;
                    $newStart = \Carbon\Carbon::parse($sourceRoster->rostered_start)->addDays($daysDifference);
                    $newEnd = \Carbon\Carbon::parse($sourceRoster->rostered_end)->addDays($daysDifference);

                    $this->staffRoster->create([
                        'staff_id' => $sourceRoster->staff_id,
                        'site_id' => $sourceRoster->site_id,
                        'rostered_start' => $newStart,
                        'rostered_end' => $newEnd,
                        'hourlyrate' => $sourceRoster->hourlyrate,
                        'shiftrate' => $sourceRoster->shiftrate,
                    ]);
                }
            }

            DB::commit();
            return true;
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    public function futureRosters($data): array
    {
        $query = $this->staffRoster->newQuery()
            ->with(['staff'])
            ->where('rostered_start', '>=', $data['start_date']);

        if (isset($data['site_id']) && $data['site_id']) {
            $query->where('site_id', $data['site_id']);
        }

        if (isset($data['staff_ids']) && $data['staff_ids']) {
            $staffIds = is_array($data['staff_ids']) ? $data['staff_ids'] : explode(',', $data['staff_ids']);
            $query->whereIn('staff_id', $staffIds);
        }

        $rosters = $query->orderBy('rostered_start')->get();

        return fractal($rosters, new StaffRosterTransformer())->toArray();
    }

    public function checkConflicts($data): array
    {
        $conflicts = $this->staffRoster->newQuery()
            ->where('staff_id', $data['staff_id'])
            ->when(isset($data['id']), function ($query) use ($data) {
                return $query->where('id', '<>', $data['id']);
            })
            ->when(isset($data['site_id']), function ($query) use ($data) {
                if ($data['site_id'] === null) {
                    return $query->whereNull('site_id');
                }
                return $query->where('site_id', $data['site_id']);
            }, function ($query) {
                return $query->whereNull('site_id');
            })
            ->overlapping($data['rostered_start'], $data['rostered_end'])
            ->get();

        return fractal($conflicts, new StaffRosterTransformer())->toArray();
    }
}
