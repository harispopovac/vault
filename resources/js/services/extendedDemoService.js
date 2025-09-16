import axios from "axios"

// Helper function to get cookie value
const getCookie = name => {
    const value = `; ${document.cookie}`
    const parts = value.split(`; ${name}=`)
    if (parts.length === 2) return parts.pop().split(";").shift()
    
    return null
}

// Create extended API instance
const extendedApi = axios.create({
    baseURL: "https://template.test",
    headers: {
        "Content-Type": "application/json",
        Accept: "application/json",
    },
})

// Add request interceptor for authentication
extendedApi.interceptors.request.use(
    config => {
        const accessToken = getCookie("accessToken")
        if (accessToken) {
            config.headers.Authorization = `Bearer ${accessToken}`
        }
        
        return config
    },
    error => {
        return Promise.reject(error)
    },
)

/**
 * Extended Demo Service
 * Extends the base demo module with project-specific functionality
 */
export class ExtendedDemoService {
    // Base functionality (using extended endpoint with more data)
    static async fetchUsers() {
        const response = await extendedApi.get("/api/demo-extended/users")
        
        return response.data
    }

    // Project-specific: Create new user
    static async createUser(userData) {
        const response = await extendedApi.post(
            "/api/demo-extended/users",
            userData,
        )

        
        return response.data
    }

    // Project-specific: Delete user
    static async deleteUser(userId) {
        const response = await extendedApi.delete(
            `/api/demo-extended/users/${userId}`,
        )

        
        return response.data
    }

    // Project-specific: Export users
    static async exportUsers() {
        const response = await extendedApi.get(
            "/api/demo-extended/users/export",
            {
                responseType: "blob", // For file download
            },
        )

        // Create download link
        const url = window.URL.createObjectURL(new Blob([response.data]))
        const link = document.createElement("a")

        link.href = url
        link.setAttribute(
            "download",
            `users_export_${new Date().toISOString().split("T")[0]}.csv`,
        )
        document.body.appendChild(link)
        link.click()
        link.remove()
        window.URL.revokeObjectURL(url)

        return { success: true, message: "Export completed" }
    }

    // Project-specific: Send email to user
    static async sendEmailToUser(userId, emailData) {
        const response = await extendedApi.post(
            `/api/demo-extended/users/${userId}/send-email`,
            emailData,
        )

        
        return response.data
    }

    // Project-specific: Get user statistics
    static async getUserStats() {
        const response = await extendedApi.get(
            "/api/demo-extended/users/stats",
        )

        
        return response.data
    }

    // Project-specific: Bulk import users
    static async bulkImportUsers(file) {
        const formData = new FormData()

        formData.append("file", file)

        const response = await extendedApi.post(
            "/api/demo-extended/users/bulk-import",
            formData,
            {
                headers: {
                    "Content-Type": "multipart/form-data",
                },
            },
        )

        
        return response.data
    }

    // Project-specific: Search users with filters
    static async searchUsers(filters = {}) {
        const params = new URLSearchParams(filters)

        const response = await extendedApi.get(
            `/api/demo-extended/users?${params}`,
        )

        
        return response.data
    }

    // Project-specific: Get user by ID with full details
    static async getUserById(userId) {
        const response = await extendedApi.get(
            `/api/demo-extended/users/${userId}`,
        )

        
        return response.data
    }

    // Project-specific: Update user
    static async updateUser(userId, userData) {
        const response = await extendedApi.put(
            `/api/demo-extended/users/${userId}`,
            userData,
        )

        
        return response.data
    }
}

// Export individual functions for convenience
export const {
    fetchUsers,
    createUser,
    deleteUser,
    exportUsers,
    sendEmailToUser,
    getUserStats,
    bulkImportUsers,
    searchUsers,
    getUserById,
    updateUser,
} = ExtendedDemoService

// Default export
export default ExtendedDemoService
