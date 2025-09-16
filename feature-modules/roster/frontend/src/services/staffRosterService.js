import { $api } from "@/utils/api"
import { $endpoint } from "../utils/endpoints"

/**
 * Fetch rosters with optional filters
 */
export const fetchRosters = async (params = {}) => {
    const response = await $api($endpoint("GET_STAFF_ROSTER"), {
        method: "GET",
        params,
    })

    return response.data
}

/**
 * Store a new roster
 */
export const storeRoster = async data => {
    const response = await $api($endpoint("STORE_STAFF_ROSTER"), {
        method: "POST",
        data,
    })

    return response.data
}

/**
 * Get a specific roster by ID
 */
export const fetchRoster = async id => {
    const response = await $api($endpoint("GET_STAFF_ROSTER"), {
        method: "GET",
    })

    return response.data
}

/**
 * Update a roster
 */
export const updateRoster = async (id, data) => {
    const response = await $api($endpoint("UPDATE_STAFF_ROSTER", { id }), {
        method: "PUT",
        data,
    })

    return response.data
}

/**
 * Delete a roster
 */
export const deleteRoster = async id => {
    const response = await $api($endpoint("DELETE_STAFF_ROSTER", { id }), {
        method: "DELETE",
    })

    return response.data
}

/**
 * Copy rosters between weeks
 */
export const copyRosters = async data => {
    const response = await $api($endpoint("COPY_STAFF_ROSTER"), {
        method: "POST",
        data,
    })

    return response.data
}

/**
 * Copy rosters for a specific day
 */
export const copyDayRosters = async data => {
    const response = await $api($endpoint("COPY_DAY_ROSTER"), {
        method: "POST",
        data,
    })

    return response.data
}

/**
 * Clear rosters for a period
 */
export const clearRosters = async data => {
    const response = await $api($endpoint("GET_STAFF_ROSTER"), {
        method: "POST",
        data,
    })

    return response.data
}

/**
 * Clear rosters for a specific day
 */
export const clearDayRosters = async data => {
    const response = await $api($endpoint("CLEAR_DAY_ROSTER"), {
        method: "POST",
        data,
    })

    return response.data
}

/**
 * Get future rosters
 */
export const fetchFutureRosters = async params => {
    const response = await $api($endpoint("GET_STAFF_ROSTER"), {
        method: "GET",
        params,
    })

    return response.data
}

/**
 * Get upcoming rosters (within next hour)
 */
export const fetchUpcomingRosters = async params => {
    const response = await $api($endpoint("GET_STAFF_ROSTER"), {
        method: "GET",
        params,
    })

    return response.data
}

/**
 * Check for roster conflicts
 */
export const checkRosterConflicts = async data => {
    const response = await $api($endpoint("GET_STAFF_ROSTER"), {
        method: "GET",
        params: data,
    })

    return response.data
}

/**
 * Fetch staff list with optional search
 */
export const fetchStaffList = async (params = {}) => {
    const response = await $api($endpoint("GET_STAFF_LIST"), {
        method: "GET",
        params,
    })

    return response.data
}

// Export default object for easier importing
export default {
    fetchRosters,
    storeRoster,
    fetchRoster,
    updateRoster,
    deleteRoster,
    copyRosters,
    copyDayRosters,
    clearRosters,
    clearDayRosters,
    fetchFutureRosters,
    fetchUpcomingRosters,
    checkRosterConflicts,
    fetchStaffList,
}
