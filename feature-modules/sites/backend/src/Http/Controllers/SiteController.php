<?php

namespace Sites\SitesModule\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Sites\SitesModule\Http\Requests\StoreSiteRequest;
use Sites\SitesModule\Http\Requests\UpdateSiteRequest;
use Sites\SitesModule\Models\Site;
use Sites\SitesModule\Repositories\SiteRepository;
use Exception;

class SiteController extends Controller
{
    protected SiteRepository $siteRepository;

    public function __construct(SiteRepository $siteRepository)
    {
        $this->siteRepository = $siteRepository;
    }

    /**
     * Display a listing of the sites.
     */
    public function index(Request $request): JsonResponse
    {
        $filters = [
            'organisation_id' => $request->query('organisation_id'),
            'status' => $request->query('status'),
            'active_only' => $request->query('active_only', false),
            'search' => $request->query('search'),
        ];

        try {
            $sites = $this->siteRepository->index($filters);
            return response()->json($sites);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch sites',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created site in storage.
     */
    public function store(StoreSiteRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();
            $site = $this->siteRepository->store($validated);

            return response()->json([
                'success' => true,
                'message' => 'Site created successfully',
                'data' => $site
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create site',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified site.
     */
    public function show(Site $site): JsonResponse
    {
        try {
            $siteData = $this->siteRepository->show($site);
            return response()->json($siteData);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch site',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified site in storage.
     */
    public function update(UpdateSiteRequest $request, Site $site): JsonResponse
    {
        try {
            $validated = $request->validated();
            $updatedSite = $this->siteRepository->update($site, $validated);

            return response()->json([
                'success' => true,
                'message' => 'Site updated successfully',
                'data' => $updatedSite
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update site',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified site from storage.
     */
    public function destroy(Site $site): JsonResponse
    {
        try {
            $this->siteRepository->destroy($site);

            return response()->json([
                'success' => true,
                'message' => 'Site deleted successfully'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete site',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get simple list of sites for dropdowns.
     */
    public function getSimpleList(Request $request): JsonResponse
    {
        $filters = [
            'organisation_id' => $request->query('organisation_id'),
            'active_only' => $request->query('active_only', true),
        ];

        try {
            $sites = $this->siteRepository->getSimpleList($filters);
            return response()->json([
                'success' => true,
                'data' => $sites
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch sites list',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
