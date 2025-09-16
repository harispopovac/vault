import axios from "axios"

// Helper function to get cookie value
const getCookie = name => {
    const value = `; ${document.cookie}`
    const parts = value.split(`; ${name}=`)
    if (parts.length === 2) return parts.pop().split(";").shift()
    
    return null
}

// Create axios instance with dynamic base URL
const api = axios.create({
    baseURL: import.meta.env.VITE_API_BASE_URL || window.location.origin,
    headers: {
        "Content-Type": "application/json",
        Accept: "application/json",
    },
})

// Add request interceptor to include access token
api.interceptors.request.use(
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

export const fetchUsers = async () => {
    const response = await api.get("/api/demo/users")
    
    return response.data
}
