<?php

namespace Sites\SitesModule\Repositories;

use Sites\SitesModule\Models\Site;
use Sites\SitesModule\Transformers\SiteTransformer;
use Illuminate\Support\Facades\DB;
use Exception;

class SiteRepository
{
    protected Site $site;

    public function __construct(Site $site)
    {
        $this->site = $site;
    }

    public function index($data = []): ?array
    {
        $query = $this->site->newQuery();

        // Include relationships
        $query->with(['address', 'organisation', 'timezone', 'managers']);

        // Filter by organisation if provided
        if (isset($data['organisation_id']) && $data['organisation_id']) {
            $query->forOrganisation($data['organisation_id']);
        }

        // Filter by status
        if (isset($data['status']) && $data['status']) {
            $query->where('status', $data['status']);
        }

        // Only active sites
        if (isset($data['active_only']) && $data['active_only']) {
            $query->active();
        }

        // Search functionality
        if (isset($data['search']) && !empty($data['search'])) {
            $searchTerm = $data['search'];
            $query->where(function ($q) use ($searchTerm) {
                $q->where('name', 'like', "%{$searchTerm}%")
                  ->orWhere('label', 'like', "%{$searchTerm}%")
                  ->orWhere('email', 'like', "%{$searchTerm}%")
                  ->orWhere('phone', 'like', "%{$searchTerm}%");
            });
        }

        $sites = $query->orderBy('name')->get();

        return fractal($sites, new SiteTransformer())->toArray();
    }

    public function store($data): ?array
    {
        DB::beginTransaction();

        try {
            // Set default values
            $data['status'] = $data['status'] ?? 'open';
            $data['timezone'] = $data['timezone'] ?? 'UTC';
            $data['currency'] = $data['currency'] ?? 'AUD';

            // Create the site
            $site = $this->site->create($data);

            // Handle address creation if provided
            if (isset($data['address']) && is_array($data['address'])) {
                $address = \App\Models\Address::create($data['address']);
                $site->address_id = $address->id;
                $site->save();
            }

            // Handle managers assignment if provided
            if (isset($data['manager_ids']) && is_array($data['manager_ids'])) {
                $site->managers()->sync($data['manager_ids']);
            }

            DB::commit();

            return fractal($site->load(['address', 'organisation', 'timezone', 'managers']), new SiteTransformer())->toArray();
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    public function show(Site $site): ?array
    {
        $site->load(['address', 'organisation', 'timezone', 'managers', 'buildings', 'rooms']);
        return fractal($site, new SiteTransformer())->toArray();
    }

    public function update(Site $site, $data): ?array
    {
        DB::beginTransaction();

        try {
            // Update the site
            $site->update($data);

            // Handle address update if provided
            if (isset($data['address']) && is_array($data['address'])) {
                if ($site->address_id) {
                    $site->address()->update($data['address']);
                } else {
                    $address = \App\Models\Address::create($data['address']);
                    $site->address_id = $address->id;
                    $site->save();
                }
            }

            // Handle managers assignment if provided
            if (isset($data['manager_ids']) && is_array($data['manager_ids'])) {
                $site->managers()->sync($data['manager_ids']);
            }

            DB::commit();

            return fractal($site->load(['address', 'organisation', 'timezone', 'managers']), new SiteTransformer())->toArray();
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    public function destroy(Site $site): bool
    {
        DB::beginTransaction();

        try {
            // Check if site has any dependencies
            if ($site->buildings()->exists() || $site->rooms()->exists()) {
                throw new Exception('Cannot delete site with existing buildings or rooms');
            }

            // Detach managers
            $site->managers()->detach();

            // Soft delete the site
            $site->delete();

            DB::commit();

            return true;
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    public function getSimpleList($data = []): array
    {
        $query = $this->site->newQuery();

        // Basic filters
        if (isset($data['organisation_id']) && $data['organisation_id']) {
            $query->forOrganisation($data['organisation_id']);
        }

        if (isset($data['active_only']) && $data['active_only']) {
            $query->active();
        }

        $sites = $query->select('id', 'name', 'label', 'timezone')
                       ->orderBy('name')
                       ->get();

        return $sites->map(function ($site) {
            return [
                'id' => $site->id,
                'name' => $site->display_name,
                'label' => $site->display_name,
                'timezone' => $site->timezone,
            ];
        })->toArray();
    }
}
