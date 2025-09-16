<template>
    <div>
        <!-- Basic Mode -->
        <div v-if="!isAdvancedMode" class="color-grid">
            <div
                v-for="(color, index) in colorPalette"
                :key="index"
                class="color-item"
                :class="{
                    selected: selectedColorIndex === index,
                }"
                @click="selectPaletteColor(index)"
            >
                <div
                    class="color-preview"
                    :style="{
                        backgroundColor: isDark
                            ? color.dark.background
                            : color.light.background,
                    }"
                >
                    <span
                        class="color-name"
                        :style="{
                            color: isDark ? color.dark.text : color.light.text,
                        }"
                    >
                        {{ color.name }}
                    </span>
                </div>
            </div>
        </div>

        <!-- Advanced Mode -->
        <div v-else class="mb-4 px-4">
            <VRow>
                <!-- Radio Buttons Column -->
                <VCol cols="6">
                    <div class="text-subtitle-2 mb-2">Color</div>
                    <VRadioGroup
                        v-model="selectedColorMode"
                        class="d-flex flex-column gap-3"
                    >
                        <!-- Background Light -->
                        <div>
                            <VRadio
                                value="bg_light"
                                density="comfortable"
                                hide-details
                            >
                                <template #label>
                                    <span>Background (Light Mode)</span>
                                </template>
                            </VRadio>
                            <div class="text-caption text-medium-emphasis ml-7">
                                {{ formData.bg_light }} /
                                {{ rgbaValues.bg_light }}
                            </div>
                        </div>

                        <!-- Text Light -->
                        <div>
                            <VRadio
                                value="text_light"
                                density="comfortable"
                                hide-details
                            >
                                <template #label>
                                    <span>Text (Light Mode)</span>
                                </template>
                            </VRadio>
                            <div class="text-caption text-medium-emphasis ml-7">
                                {{ formData.text_light }} /
                                {{ rgbaValues.text_light }}
                            </div>
                        </div>

                        <!-- Border Light -->
                        <div>
                            <VRadio
                                value="border_light"
                                density="comfortable"
                                hide-details
                            >
                                <template #label>
                                    <span>Border (Light Mode)</span>
                                </template>
                            </VRadio>
                            <div class="text-caption text-medium-emphasis ml-7">
                                {{ formData.border_light }} /
                                {{ rgbaValues.border_light }}
                            </div>
                        </div>

                        <!-- Background Dark -->
                        <div>
                            <VRadio
                                value="bg_dark"
                                density="comfortable"
                                hide-details
                            >
                                <template #label>
                                    <span>Background (Dark Mode)</span>
                                </template>
                            </VRadio>
                            <div class="text-caption text-medium-emphasis ml-7">
                                {{ formData.bg_dark }} /
                                {{ rgbaValues.bg_dark }}
                            </div>
                        </div>

                        <!-- Text Dark -->
                        <div>
                            <VRadio
                                value="text_dark"
                                density="comfortable"
                                hide-details
                            >
                                <template #label>
                                    <span>Text (Dark Mode)</span>
                                </template>
                            </VRadio>
                            <div class="text-caption text-medium-emphasis ml-7">
                                {{ formData.text_dark }} /
                                {{ rgbaValues.text_dark }}
                            </div>
                        </div>

                        <div>
                            <VRadio
                                value="border_dark"
                                density="comfortable"
                                hide-details
                            >
                                <template #label>
                                    <span>Border (Dark Mode)</span>
                                </template>
                            </VRadio>
                            <div class="text-caption text-medium-emphasis ml-7">
                                {{ formData.border_dark }} /
                                {{ rgbaValues.border_dark }}
                            </div>
                        </div>
                    </VRadioGroup>
                </VCol>

                <!-- Color Picker Column -->
                <VCol cols="6">
                    <VColorPicker
                        v-model="currentColor"
                        mode="hexa"
                        :modes="['rgba', 'hexa']"
                        :show-swatches="false"
                        class="flex-grow-1"
                        @update:model-value="updateSelectedColor"
                    ></VColorPicker>
                </VCol>
            </VRow>

            <!-- Preview -->
            <VRow class="mt-4">
                <VCol cols="12">
                    <div class="text-subtitle-2 mb-4 d-flex align-center">
                        Preview
                        <VProgressCircular
                            v-if="isLoading"
                            indeterminate
                            size="20"
                            width="2"
                            color="primary"
                            class="ml-2"
                        />
                    </div>
                    <div class="d-flex gap-4">
                        <!-- Light Theme Preview -->
                        <VCard
                            class="preview-card flex-grow-1"
                            theme="light"
                            variant="outlined"
                            :style="{
                                backgroundColor: 'rgb(var(--v-theme-surface))',
                            }"
                        >
                            <VCardText>
                                <div class="text-subtitle-1 mb-2 text-center">
                                    Light Mode Theme
                                </div>
                                <div class="pa-4 d-flex justify-center">
                                    <VBtn
                                        :color="formData.bg_light"
                                        :style="{ color: formData.text_light }"
                                        class="px-6"
                                    >
                                        Button
                                    </VBtn>
                                </div>
                            </VCardText>
                        </VCard>

                        <!-- Dark Theme Preview -->
                        <VCard
                            class="preview-card flex-grow-1"
                            theme="dark"
                            variant="outlined"
                            :style="{
                                backgroundColor: 'rgb(var(--v-theme-surface))',
                            }"
                        >
                            <VCardText>
                                <div class="text-subtitle-1 mb-2 text-center">
                                    Dark Mode Theme
                                </div>
                                <div class="pa-4 d-flex justify-center">
                                    <VBtn
                                        :color="formData.bg_dark"
                                        :style="{ color: formData.text_dark }"
                                        class="px-6"
                                    >
                                        Button
                                    </VBtn>
                                </div>
                            </VCardText>
                        </VCard>
                    </div>
                </VCol>
            </VRow>
        </div>
        <!-- Mode Toggle -->
        <VRow v-if="advanced" class="d-flex px-5">
            <VCol cols="12">
                <VBtn
                    variant="text"
                    class="w-100"
                    :loading="isLoading"
                    :disabled="isLoading"
                    @click="toggleMode"
                >
                    {{ isAdvancedMode ? "Basic" : "Advanced" }}
                </VBtn>
            </VCol>
        </VRow>
    </div>
</template>

<script setup>
import { computed, onMounted, ref, watch } from "vue"
import { useTheme } from "vuetify"
import colorsService from "../services/colorsService.js"
import { colorsPalette } from "../utils/colors.js"
import { updateColorCache } from "../utils/useColorPallete.js"

const props = defineProps({
    modelValue: {
        type: String,
        default: "Purple",
    },
    advanced: {
        type: Boolean,
        default: false,
    },
})

const emit = defineEmits(["update:modelValue", "save", "colorSelected"])

const theme = useTheme()
const isDark = computed(() => theme.global.current.value.dark)

const selectedColorIndex = ref(0)
const colorPalette = ref(colorsPalette)
const isAdvancedMode = ref(false)
const isLoading = ref(false)
const isDirty = ref(false)

// Advanced mode state
const selectedColorMode = ref("bg_light")
const currentColor = ref(null)

// Initialize with empty values - will be populated based on modelValue
const formData = ref({
    id: null,
    uuid: null,
    bg_light: "",
    text_light: "",
    bg_dark: "",
    text_dark: "",
    border_light: "",
    border_dark: "",
})

const initializeFormData = () => {
    if (!props.modelValue) {
        // Default to a neutral color if no modelValue is provided
        formData.value = {
            id: null,
            uuid: null,
            bg_light: "#9933FF1F",
            text_light: "#8844FFCC",
            bg_dark: "#9933FF38",
            text_dark: "#8844FFFF",
            border_light: "#9933FF1F",
            border_dark: "#9933FF38",
        }

        return
    }

    if (isUuid(props.modelValue)) {
        // If it's a UUID, fetch the custom color
        fetchCustomColor(props.modelValue)
    } else {
        // If it's a color name, find it in the palette
        const colorIndex = colorsPalette.findIndex(
            color => color.name === props.modelValue,
        )

        if (colorIndex >= 0) {
            selectedColorIndex.value = colorIndex

            const selectedColor = colorsPalette[colorIndex]

            formData.value = {
                id: null,
                uuid: null,
                name: selectedColor.name,
                bg_light: convertRgbaToHex(selectedColor.light.background),
                text_light: convertRgbaToHex(selectedColor.light.text),
                bg_dark: convertRgbaToHex(selectedColor.dark.background),
                text_dark: convertRgbaToHex(selectedColor.dark.text),
                border_light: convertRgbaToHex(selectedColor.light.border),
                border_dark: convertRgbaToHex(selectedColor.dark.border),
            }
        }
    }
}

// Initialize on component creation
onMounted(() => {
    initializeFormData()
    if (formData.value && selectedColorMode.value) {
        currentColor.value = formData.value[selectedColorMode.value]
    }
})

// Basic mode color selection
const selectPaletteColor = index => {
    selectedColorIndex.value = index

    const selectedColor = colorsPalette[index]

    emit("update:modelValue", selectedColor.name)
    isDirty.value = true

    if (selectedColor) {
        formData.value = {
            id: null,
            uuid: null,
            name: selectedColor.name,
            bg_light: convertRgbaToHex(selectedColor.light.background),
            text_light: convertRgbaToHex(selectedColor.light.text),
            bg_dark: convertRgbaToHex(selectedColor.dark.background),
            text_dark: convertRgbaToHex(selectedColor.dark.text),
            border_light: convertRgbaToHex(selectedColor.light.border),
            border_dark: convertRgbaToHex(selectedColor.dark.border),
        }
        currentColor.value = formData.value[selectedColorMode.value]

        // Emit the selected color for basic mode
        emit("colorSelected", {
            name: selectedColor.name,
            color: selectedColor,
        })
    }
}

// Advanced mode functions
const toggleMode = () => {
    isAdvancedMode.value = !isAdvancedMode.value
}

const rgbaValues = computed(() => ({
    bg_light: hexToRgba(formData.value.bg_light),
    text_light: hexToRgba(formData.value.text_light),
    bg_dark: hexToRgba(formData.value.bg_dark),
    text_dark: hexToRgba(formData.value.text_dark),
    border_light: hexToRgba(formData.value.border_light),
    border_dark: hexToRgba(formData.value.border_dark),
}))

const updateTimeout = ref(null)

const updateSelectedColor = color => {
    // Don't update if the color is black and we already have a valid color
    if (
        color === "#000000FF" &&
        formData.value[selectedColorMode.value] &&
        formData.value[selectedColorMode.value] !== "#000000FF"
    ) {
        return
    }

    // Ensure the color is trimmed before storing it
    formData.value[selectedColorMode.value] = color.trim()
    isDirty.value = true

    // Debounce the API call to avoid too many requests
    if (isAdvancedMode.value) {
        clearTimeout(updateTimeout.value)
        updateTimeout.value = setTimeout(() => {
            saveCustomColor()
        }, 500)
    }
}

// Generate auto name for custom colors
const generateColorName = () => {
    const timestamp = Date.now()

    return `Custom-${timestamp.toString().slice(-6)}`
}

// Save custom color to the database
const saveCustomColor = async () => {
    // In basic mode, just emit the selected color and return
    if (!isAdvancedMode.value) {
        const selectedColor = colorsPalette[selectedColorIndex.value]
        if (selectedColor) {
            emit("colorSelected", {
                name: selectedColor.name,
                color: selectedColor,
            })
        }

        return
    }

    // Advanced mode - save to database
    if (isLoading.value || !isDirty.value) return

    isLoading.value = true
    try {
        const payload = {
            name: formData.value.name || generateColorName(),
            light_bg: stripHash(formData.value.bg_light),
            light_text: stripHash(formData.value.text_light),
            light_border: stripHash(formData.value.border_light),
            dark_bg: stripHash(formData.value.bg_dark),
            dark_text: stripHash(formData.value.text_dark),
            dark_border: stripHash(formData.value.border_dark),
        }

        let response
        if (formData.value.uuid) {
            response = await colorsService.updateColorByUuid(
                formData.value.uuid,
                payload,
            )
            if (response.data) {
                updateColorCache(formData.value.uuid, { ...response.data })
            }
        } else {
            response = await colorsService.createColor(payload)
            if (response.data?.uuid) {
                formData.value.id = response.data.id // Store ID for future updates
                formData.value.uuid = response.data.uuid
                emit("update:modelValue", response.data.uuid)
                updateColorCache(response.data.uuid, { ...response.data })
            }
        }
        isDirty.value = false // Reset dirty state after save

        // Emit the UUID for advanced mode
        if (response.data?.uuid) {
            emit("colorSelected", {
                uuid: response.data.uuid,
                color: response.data,
            })
        }
    } catch (error) {
        console.error("Error saving custom color:", error)
    } finally {
        isLoading.value = false
    }
}

// Expose save method to be called from parent
defineExpose({ save: saveCustomColor })

// Color conversion utilities
const convertRgbaToHex = rgba => {
    if (rgba && rgba.startsWith("#")) return rgba
    const values = rgba?.match(/[\d.]+/g)
    if (!values || values.length < 3) return "#000000FF"
    const r = Math.round(parseFloat(values[0]))
    const g = Math.round(parseFloat(values[1]))
    const b = Math.round(parseFloat(values[2]))
    const a = values[3] ? Math.round(parseFloat(values[3]) * 255) : 255

    return `#${r.toString(16).padStart(2, "0")}${g.toString(16).padStart(2, "0")}${b.toString(16).padStart(2, "0")}${a.toString(16).padStart(2, "0")}`
}

const hexToRgba = hex => {
    if (!hex) return "rgba(0,0,0,0)"
    const r = parseInt(hex.slice(1, 3), 16)
    const g = parseInt(hex.slice(3, 5), 16)
    const b = parseInt(hex.slice(5, 7), 16)
    let a = 1
    if (hex.length === 9) {
        a = parseInt(hex.slice(7, 9), 16) / 255
    }

    return `rgba(${r},${g},${b},${a.toFixed(2)})`
}

const stripHash = color => (color ? color.replace("#", "").trim() : "")

const addHash = color =>
    color ? (color.startsWith("#") ? color : `#${color}`) : "#000000FF"

const isUuid = value =>
    /^[\da-f]{8}-[\da-f]{4}-[\da-f]{4}-[\da-f]{4}-[\da-f]{12}$/i.test(value)

// Fetch custom color by UUID
const fetchCustomColor = async uuid => {
    isLoading.value = true
    try {
        const response = await colorsService.getColorByUuid(uuid)

        if (response.data && response.data.uuid) {
            // Validate that we have actual color data, not empty data
            const hasValidColors =
                response.data.light?.background &&
                response.data.dark?.background

            if (hasValidColors) {
                formData.value = {
                    id: response.data.id,
                    uuid: response.data.uuid,
                    name: response.data.name,
                    bg_light: addHash(response.data.light?.background),
                    text_light: addHash(response.data.light?.text),
                    bg_dark: addHash(response.data.dark?.background),
                    text_dark: addHash(response.data.dark?.text),
                    border_light: addHash(response.data.light?.border),
                    border_dark: addHash(response.data.dark?.border),
                }
                isAdvancedMode.value = true

                // Update current color to match the selected color mode
                if (
                    selectedColorMode.value &&
                    formData.value[selectedColorMode.value]
                ) {
                    currentColor.value =
                        formData.value[selectedColorMode.value]
                }
            } else {
                console.error("Received color data with empty color values")
            }
        } else {
            console.error("No valid color data received")
        }
    } catch (error) {
        console.error("Error fetching custom color:", error)
    } finally {
        isLoading.value = false
    }
}

watch(selectedColorMode, newMode => {
    if (formData.value && formData.value[newMode]) {
        currentColor.value = formData.value[newMode]
    }
})

watch(
    formData,
    newValue => {
        if (newValue) {
            // Ensure all color values are trimmed
            Object.keys(newValue).forEach(key => {
                if (typeof newValue[key] === "string" && key.includes("_")) {
                    newValue[key] = newValue[key].trim()
                }
            })
            currentColor.value = newValue[selectedColorMode.value]
        }
    },
    { deep: true, immediate: true },
)

watch(
    () => props.modelValue,
    newValue => {
        if (newValue) {
            if (isUuid(newValue)) {
                if (formData.value.uuid !== newValue) {
                    fetchCustomColor(newValue)
                }
            } else {
                const colorIndex = colorsPalette.findIndex(
                    c => c.name === newValue,
                )

                if (
                    colorIndex !== -1 &&
                    selectedColorIndex.value !== colorIndex
                ) {
                    selectPaletteColor(colorIndex)
                }
            }
        }
    },
    { immediate: true },
)
</script>

<style scoped>
.color-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
    gap: 8px;
    padding: 22px;
}

.color-item {
    cursor: pointer;
    border-radius: 8px;
    overflow: hidden;
    border: 2px solid transparent;
}

.color-item.selected {
    border-color: rgb(var(--v-theme-primary));
}

.color-preview {
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 8px;
}

.color-name {
    font-size: 0.875rem;
    font-weight: 500;
}

.preview-card {
    min-width: 250px;
}

:deep(.v-color-picker) {
    max-width: 100%;
}

:deep(.v-color-picker-edit__input input) {
    border: thin solid rgba(var(--v-theme-on-surface), 0.38);
    background: transparent;
    color: rgba(var(--v-theme-on-surface), 0.87);
    transition: border-color 0.15s cubic-bezier(0.4, 0, 0.2, 1);
}

:deep(.v-color-picker-edit__input input:hover) {
    border-color: rgba(var(--v-theme-on-surface), 0.86);
}

:deep(.v-color-picker-edit__input input:focus) {
    border-color: rgb(var(--v-theme-primary));
    border-width: 2px;
    outline: none;
}
</style>
