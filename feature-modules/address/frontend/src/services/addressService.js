const API_BASE = "/api/v1"

const apiCall = async (url, options = {}) => {
    const response = await fetch(`${API_BASE}/${url}`, {
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
    async getAddresses() {
        return await apiCall("addresses", { method: "GET" })
    },

    async storeAddress(data) {
        return await apiCall("addresses", {
            method: "POST",
            body: data,
        })
    },

    async updateAddress(id, data) {
        return await apiCall(`addresses/${id}`, {
            method: "PUT",
            body: data,
        })
    },

    async getAddress(id) {
        return await apiCall(`addresses/${id}`, { method: "GET" })
    },

    async deleteAddress(id) {
        return await apiCall(`addresses/${id}`, { method: "DELETE" })
    },
}
