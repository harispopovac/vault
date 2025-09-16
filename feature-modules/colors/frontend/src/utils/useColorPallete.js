import { ref } from "vue"
import { colorsPalette } from "./colors.js"
import colorsService from "../services/colorsService.js"

// Cache for colors and pending promises
const colorCache = ref({})
const pendingPromises = {}

// Helper function to ensure color values have hash prefixes
const ensureHashPrefix = color => {
    if (!color) return color

    return color.startsWith("#") ? color : `#${color}`
}

// Format color data to ensure consistent structure with hash prefixes
const formatColorData = colorData => {
    if (!colorData) return null

    // If it's already in the right format, just ensure hash prefixes
    if (colorData.light && colorData.dark) {
        return {
            name: colorData.name,
            light: {
                background: ensureHashPrefix(colorData.light.background),
                border: ensureHashPrefix(colorData.light.border),
                text: ensureHashPrefix(colorData.light.text),
            },
            dark: {
                background: ensureHashPrefix(colorData.dark.background),
                border: ensureHashPrefix(colorData.dark.border),
                text: ensureHashPrefix(colorData.dark.text),
            },
        }
    }

    // If it's in a different format (like from API), convert it
    return {
        name: colorData.name,
        light: {
            background: ensureHashPrefix(
                colorData.light_bg || colorData.light?.background,
            ),
            border: ensureHashPrefix(
                colorData.light_border || colorData.light?.border,
            ),
            text: ensureHashPrefix(
                colorData.light_text || colorData.light?.text,
            ),
        },
        dark: {
            background: ensureHashPrefix(
                colorData.dark_bg || colorData.dark?.background,
            ),
            border: ensureHashPrefix(
                colorData.dark_border || colorData.dark?.border,
            ),
            text: ensureHashPrefix(colorData.dark_text || colorData.dark?.text),
        },
    }
}

// Event for notifying components when colors are updated
const createColorUpdatedEvent = (uuid, color) => {
    window.dispatchEvent(
        new CustomEvent("color-updated", {
            detail: { uuid, color },
        }),
    )
}

/**
 * Update the color cache when a color is modified
 * This should be called after a successful PUT request to update a color
 */
export const updateColorCache = (uuid, colorData) => {
    if (!uuid || !colorData) return

    // Format the color data
    const formattedColor = formatColorData(colorData)

    // Update the cache
    colorCache.value[uuid] = formattedColor

    // Notify components that the color is updated
    createColorUpdatedEvent(uuid, formattedColor)

    return formattedColor
}

/**
 * Get a color from the palette or API
 * This function always returns a synchronous color object
 * If the color needs to be fetched, it returns a default color and updates the cache when ready
 */
export const getColorFromPalette = nameOrUuid => {
    // If no input, return Steel color
    if (!nameOrUuid) {
        return colorsPalette.find(c => c.name === "Steel")
    }

    // Check cache first
    if (colorCache.value[nameOrUuid]) {
        return colorCache.value[nameOrUuid]
    }

    // If it's a name (less than 16 characters), look up in colorsPalette
    if (nameOrUuid.length < 16) {
        const color = colorsPalette.find(c => c.name === nameOrUuid)
        if (color) {
            const formattedColor = formatColorData(color)

            // Cache the color
            colorCache.value[nameOrUuid] = formattedColor

            return formattedColor
        }
    }

    // If it's a UUID, fetch from API
    else if (nameOrUuid.length >= 16) {
        // If we're already fetching this color, don't start another request
        if (!pendingPromises[nameOrUuid]) {
            // Start the API request
            pendingPromises[nameOrUuid] = true

            colorsService
                .getColorByUuid(nameOrUuid)
                .then(response => {
                    if (response && response.data) {
                        // Format and cache the color
                        const formattedColor = formatColorData(response.data)

                        colorCache.value[nameOrUuid] = formattedColor

                        // Notify components that the color is updated
                        createColorUpdatedEvent(nameOrUuid, formattedColor)
                    }
                })
                .catch(error => {
                    console.error("Error fetching color:", error)
                })
                .finally(() => {
                    // Remove from pending promises
                    delete pendingPromises[nameOrUuid]
                })
        }

        // Return a neutral color while loading
        return {
            name: "Loading",
            light: {
                background: "#f0f0f080",
                border: "#e0e0e0",
                text: "#333333",
            },
            dark: {
                background: "#38383880",
                border: "#484848",
                text: "#ffffff",
            },
        }
    }

    // Fallback to Steel color
    const steelColor = colorsPalette.find(c => c.name === "Steel")
    const formattedSteelColor = formatColorData(steelColor)

    colorCache.value[nameOrUuid] = formattedSteelColor

    return formattedSteelColor
}

/**
 * Preload colors for a list of color names or UUIDs
 */
export const preloadColors = (colorIds = []) => {
    if (!colorIds || !colorIds.length) return

    colorIds.forEach(id => {
        if (id) getColorFromPalette(id)
    })
}

/**
 * Listen for color updates
 */
export const useColorUpdates = callback => {
    const handleColorUpdate = event => {
        if (event.detail && event.detail.color) {
            callback(event.detail.uuid, event.detail.color)
        }
    }

    // Setup event listener
    window.addEventListener("color-updated", handleColorUpdate)

    // Return cleanup function
    return () => {
        window.removeEventListener("color-updated", handleColorUpdate)
    }
}
