import { $api } from "@/utils/api"
import { $endpoint } from "../utils/endpoints"

/**
 * Shift Log API Service
 * Handles all API interactions for shift logging functionality
 */
class ShiftLogService {
    constructor() {
        // Cache for configuration to avoid multiple API calls
        this._configCache = null
        this._configPromise = null
    }

    /**
     * Get shift log configuration from backend (cached)
     * Note: Currently not used - components use hardcoded defaults instead
     * This method is available if you want to implement dynamic backend config
     */
    async getConfig() {
        // Return cached config if available
        if (this._configCache) {
            return this._configCache
        }

        // Return existing promise if already fetching
        if (this._configPromise) {
            return this._configPromise
        }

        // Create new promise and cache it
        this._configPromise = this._fetchConfig()

        try {
            this._configCache = await this._configPromise
            
            return this._configCache
        } finally {
            // Clear the promise after completion (success or failure)
            this._configPromise = null
        }
    }

    /**
     * Internal method to fetch config from API
     */
    async _fetchConfig() {
        try {
            return await $api($endpoint("SHIFT_LOG_CONFIG"), {
                method: "GET",
            })
        } catch (error) {
            console.error("Error fetching shift log config:", error)

            // Return default configuration if API fails
            return {
                success: true,
                data: {
                    enable_sites: true,
                    enable_breaks: false,
                    enable_overtime: false,
                    enable_photos: false,
                    enable_comments: true,
                    labels: {
                        shift: "Shift",
                        check_in: "Check In",
                        check_out: "Check Out",
                        overtime: "Overtime",
                    },
                },
            }
        }
    }

    /**
     * Get shift logs with combined roster data (admin view)
     */
    async getShiftLogs(params = {}) {
        try {
            return await $api($endpoint("SHIFT_LOG_INDEX"), {
                method: "GET",
                query: {
                    site_id: params.siteId,
                    staff_id: params.staffId,
                    start: params.start,
                    end: params.end,
                    search: params.search,
                    show_past_shifts: params.showPastShifts,
                    page: params.page || 1,
                    limit: params.limit || 100,
                    all: params.all || false,
                },
            })
        } catch (error) {
            console.error("Error fetching shift logs:", error)
            throw error
        }
    }

    /**
     * Get my shifts (staff view)
     */
    async getMyShifts(params = {}) {
        try {
            return await $api($endpoint("MY_SHIFTS_INDEX"), {
                method: "GET",
                query: {
                    start: params.start,
                    end: params.end,
                    search: params.search,
                    show_past_shifts: params.showPastShifts,
                    page: params.page || 1,
                    limit: params.limit || 100,
                    all: params.all || false,
                },
            })
        } catch (error) {
            console.error("Error fetching my shifts:", error)
            throw error
        }
    }

    /**
     * Check in to a shift
     */
    async checkIn(data, isMyShifts = false) {
        try {
            // Use FormData to handle both text data and photo uploads
            const formData = new FormData()

            // Add all form fields
            Object.keys(data).forEach(key => {
                if (data[key] !== null && data[key] !== undefined) {
                    formData.append(key, data[key])
                }
            })

            const endpoint = isMyShifts
                ? "MY_SHIFTS_CHECK_IN"
                : "SHIFT_LOG_STORE"

            return await $api($endpoint(endpoint), {
                method: "POST",
                body: formData,
            })
        } catch (error) {
            console.error("Error checking in:", error)
            throw error
        }
    }

    /**
     * Check out from a shift
     */
    async checkOut(shiftLogId, data, isMyShifts = false) {
        try {
            const endpoint = isMyShifts
                ? $endpoint("MY_SHIFTS_CHECK_OUT", { id: shiftLogId })
                : $endpoint("SHIFT_LOG_UPDATE", { id: shiftLogId })

            return await $api(endpoint, {
                method: "PUT",
                body: data,
            })
        } catch (error) {
            console.error("Error checking out:", error)
            throw error
        }
    }

    /**
     * Get specific shift log details
     */
    async getShiftLog(id) {
        try {
            return await $api($endpoint("SHIFT_LOG_SHOW", { id }), {
                method: "GET",
            })
        } catch (error) {
            console.error("Error fetching shift log:", error)
            throw error
        }
    }

    /**
     * Delete a shift log
     */
    async deleteShiftLog(id) {
        try {
            return await $api($endpoint("SHIFT_LOG_DELETE", { id }), {
                method: "DELETE",
            })
        } catch (error) {
            console.error("Error deleting shift log:", error)
            throw error
        }
    }

    /**
     * Get open shift for a staff member
     */
    async getOpenShift(staffId, isMyShifts = false) {
        try {
            const endpoint = isMyShifts
                ? "MY_SHIFTS_OPEN_SHIFT"
                : "SHIFT_LOG_OPEN_SHIFT"

            return await $api($endpoint(endpoint), {
                method: "GET",
                query: { staff_id: staffId },
            })
        } catch (error) {
            console.error("Error fetching open shift:", error)
            throw error
        }
    }

    /**
     * Manage break (start/end)
     */
    async manageBreak(shiftLogId, action, data = {}, isMyShifts = false) {
        try {
            const endpoint = isMyShifts
                ? $endpoint("MY_SHIFTS_BREAKS_MANAGE", { id: shiftLogId })
                : $endpoint("SHIFT_LOG_BREAKS_MANAGE", { id: shiftLogId })

            return await $api(endpoint, {
                method: "POST",
                body: { ...data, action },
            })
        } catch (error) {
            console.error("Error managing break:", error)
            throw error
        }
    }

    /**
     * Upload photo for shift log
     */
    async uploadPhoto(shiftLogId, photoBlob, type = "check_in") {
        try {
            const formData = new FormData()

            formData.append("id", shiftLogId)
            formData.append(`${type}_photo`, photoBlob, `${type}_photo.png`)

            return await $api($endpoint("SHIFT_LOG_UPLOAD_PHOTO"), {
                method: "POST",
                body: formData,
            })
        } catch (error) {
            console.error("Error uploading photo:", error)
            throw error
        }
    }

    /**
     * Get staff list for autocomplete
     */
    async getStaff(search = "") {
        try {
            return await $api($endpoint("GET_STAFF_LIST"), {
                method: "GET",
                query: {
                    all: true,
                    search: search,
                },
            })
        } catch (error) {
            console.error("Error fetching staff:", error)
            throw error
        }
    }

    /**
     * Get sites list
     */
    async getSites() {
        try {
            return await $api($endpoint("GET_SITES"), {
                method: "GET",
            })
        } catch (error) {
            console.error("Error fetching sites:", error)
            throw error
        }
    }
}

export default new ShiftLogService()
