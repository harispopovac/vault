// Base API URL - adjust this to match your backend setup
const API_BASE = "/api/v1"

// Helper function to make API calls
const apiCall = async (url, options = {}) => {
    const response = await fetch(`${API_BASE}${url}`, {
        headers: {
            "Content-Type": "application/json",
            Accept: "application/json",
            ...options.headers,
        },
        ...options,
        body: options.body ? JSON.stringify(options.body) : undefined,
    })

    if (!response.ok) {
        throw new Error(
            `API call failed: ${response.status} ${response.statusText}`,
        )
    }

    return await response.json()
}

export default {
    // Get all colors
    async getColors() {
        return await apiCall("/colors", {
            method: "GET",
        })
    },

    // Get a specific color by ID
    async getColor(id) {
        return await apiCall(`/colors/${id}`, {
            method: "GET",
        })
    },

    // Get a specific color by UUID
    async getColorByUuid(uuid) {
        if (!uuid) {
            throw new Error("UUID is required")
        }
        
        return await apiCall(`/colors/uuid/${uuid}`, {
            method: "GET",
        })
    },

    // Create a new color
    async createColor(data) {
        return await apiCall("/colors", {
            method: "POST",
            body: data,
        })
    },

    // Update an existing color by ID
    async updateColor(id, data) {
        return await apiCall(`/colors/${id}`, {
            method: "PUT",
            body: data,
        })
    },

    // Update an existing color by UUID
    async updateColorByUuid(uuid, data) {
        if (!uuid) {
            throw new Error("UUID is required")
        }
        
        return await apiCall(`/colors/uuid/${uuid}`, {
            method: "PUT",
            body: data,
        })
    },

    // Delete a color
    async deleteColor(id) {
        return await apiCall(`/colors/${id}`, {
            method: "DELETE",
        })
    },
}
