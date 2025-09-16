// Export components
export { default as ColorPickerForm } from "./src/components/ColorPickerForm.vue"
export { default as ColorsTable } from "./src/components/ColorsTable.vue"

// Export services
export { default as colorsService } from "./src/services/colorsService.js"

// Export utilities
export { colorsPalette } from "./src/utils/colors.js"
export {
    getColorFromPalette,
    preloadColors,
    updateColorCache,
    useColorUpdates,
} from "./src/utils/useColorPallete.js"

// Export endpoints
export { default as colorsEndpoints } from "./src/utils/endpoints.js"
