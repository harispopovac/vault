<template>
    <VContainer fluid>
        <!-- Basic Colors Section -->
        <VCard class="mb-4">
            <VCardTitle class="d-flex align-center">
                <span>Basic Colors</span>
                <VChip color="info" variant="outlined" class="ml-2"
                >Predefined</VChip
                >
            </VCardTitle>
            <VCardText>
                <p class="text-body-2 text-medium-emphasis mb-4">
                    These are predefined colors that showcase the
                    ColorPickerForm component usage. They cannot be edited or
                    deleted.
                </p>
                <VDataTable
                    :headers="basicHeaders"
                    :items="basicColors"
                    :items-per-page="-1"
                    :sort-by="[]"
                    class="elevation-0 cursor-pointer"
                    hide-default-footer
                    @click:row="previewBasicColor"
                >
                    <!-- Color Preview Column -->
                    <template #item.preview="{ item }">
                        <div
                            class="color-preview-box theme-aware-preview"
                            :style="getThemeAwarePreviewStyle(item)"
                        >
                            <span>{{ item.name }}</span>
                        </div>
                    </template>

                    <!-- Name Column -->
                    <template #item.name="{ item }">
                        <span class="font-weight-medium">{{ item.name }}</span>
                    </template>
                </VDataTable>
            </VCardText>
        </VCard>

        <!-- Custom Colors Section -->
        <VCard>
            <VCardTitle class="d-flex align-center">
                <span>Custom Colors</span>
                <VChip color="success" variant="outlined" class="ml-2"
                >User Created</VChip
                >
                <VSpacer></VSpacer>
                <VBtn color="primary" @click="openCreateDialog">
                    Add Custom Color
                </VBtn>
            </VCardTitle>
            <VCardText>
                <p class="text-body-2 text-medium-emphasis mb-4">
                    Custom colors created using the advanced mode. These can be
                    edited and deleted.
                </p>
                <VDataTable
                    :headers="customHeaders"
                    :items="customColors"
                    :loading="loading"
                    :items-per-page="10"
                    class="elevation-0"
                >
                    <!-- Color Preview Column -->
                    <template #item.preview="{ item }">
                        <div class="d-flex align-center gap-2 py-2">
                            <div
                                class="color-preview-box"
                                :style="getCustomLightPreviewStyle(item)"
                            >
                                <span>Light</span>
                            </div>
                            <div
                                class="color-preview-box"
                                :style="getCustomDarkPreviewStyle(item)"
                            >
                                <span>Dark</span>
                            </div>
                        </div>
                    </template>

                    <!-- Name Column -->
                    <template #item.name="{ item }">
                        <span class="font-weight-medium">{{
                            item.name || "Unnamed"
                        }}</span>
                    </template>

                    <!-- UUID Column -->
                    <template #item.uuid="{ item }">
                        <code class="text-caption">{{ item.uuid }}</code>
                    </template>

                    <!-- Actions Column -->
                    <template #item.actions="{ item }">
                        <VBtn
                            icon
                            size="small"
                            variant="text"
                            title="Preview Color"
                            @click="previewCustomColor(item)"
                        >
                            <VIcon>tabler-eye</VIcon>
                        </VBtn>
                        <VBtn
                            icon
                            size="small"
                            variant="text"
                            title="Edit Color"
                            @click="openEditDialog(item)"
                        >
                            <VIcon>tabler-edit</VIcon>
                        </VBtn>
                        <VBtn
                            icon
                            size="small"
                            variant="text"
                            color="error"
                            title="Delete Color"
                            @click="confirmDelete(item)"
                        >
                            <VIcon>tabler-trash</VIcon>
                        </VBtn>
                    </template>
                </VDataTable>
            </VCardText>
        </VCard>

        <!-- Create/Edit Dialog -->
        <VDialog v-model="dialog" max-width="800px" persistent>
            <VCard>
                <VCardTitle>
                    <span class="text-h5">{{
                        editMode ? "Edit Color" : "New Color"
                    }}</span>
                </VCardTitle>
                <VCardText>
                    <VContainer>
                        <!-- Color Picker Integration -->
                        <VRow>
                            <VCol cols="12">
                                <ColorPickerForm
                                    ref="colorPickerRef"
                                    v-model="selectedColor"
                                    :advanced="true"
                                    @color-selected="handleColorSelected"
                                />
                            </VCol>
                        </VRow>
                    </VContainer>
                </VCardText>
                <VCardActions>
                    <VSpacer></VSpacer>
                    <VBtn variant="text" @click="closeDialog">Cancel</VBtn>
                    <VBtn color="primary" variant="elevated" @click="save"
                    >Save</VBtn
                    >
                </VCardActions>
            </VCard>
        </VDialog>

        <!-- Preview Dialog for Basic Colors -->
        <VDialog v-model="previewDialog" max-width="600px">
            <VCard v-if="previewedColor">
                <VCardTitle>
                    <span class="text-h5"
                    >{{ previewedColor.name }} Color Preview</span
                    >
                </VCardTitle>
                <VCardText>
                    <VContainer>
                        <VRow>
                            <VCol cols="12">
                                <div class="d-flex gap-4 justify-center">
                                    <!-- Light Theme Preview -->
                                    <VCard
                                        class="preview-card flex-grow-1"
                                        theme="light"
                                        variant="outlined"
                                        :style="{
                                            backgroundColor:
                                                'rgb(var(--v-theme-surface))',
                                        }"
                                    >
                                        <VCardText>
                                            <div class="text-subtitle-1 mb-2 text-center"
                                            >
                                                Light Mode Theme
                                            </div>
                                            <div class="pa-4 d-flex justify-center"
                                            >
                                                <VBtn
                                                    :color="
                                                        previewedColor.light
                                                            ?.background
                                                    "
                                                    :style="{
                                                        color:
                                                            previewedColor.light
                                                                ?.text +
                                                            ' !important',
                                                    }"
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
                                            backgroundColor:
                                                'rgb(var(--v-theme-surface))',
                                        }"
                                    >
                                        <VCardText>
                                            <div class="text-subtitle-1 mb-2 text-center"
                                            >
                                                Dark Mode Theme
                                            </div>
                                            <div class="pa-4 d-flex justify-center"
                                            >
                                                <VBtn
                                                    :color="
                                                        previewedColor.dark
                                                            ?.background
                                                    "
                                                    :style="{
                                                        color:
                                                            previewedColor.dark
                                                                ?.text +
                                                            ' !important',
                                                    }"
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
                    </VContainer>
                </VCardText>
                <VCardActions>
                    <VSpacer></VSpacer>
                    <VBtn variant="text" @click="previewDialog = false"
                    >Close</VBtn
                    >
                </VCardActions>
            </VCard>
        </VDialog>

        <!-- Delete Confirmation Dialog -->
        <VDialog v-model="deleteDialog" max-width="400px">
            <VCard>
                <VCardTitle>Confirm Delete</VCardTitle>
                <VCardText>
                    Are you sure you want to delete the color "{{
                        itemToDelete?.name
                    }}"?
                </VCardText>
                <VCardActions>
                    <VSpacer></VSpacer>
                    <VBtn variant="text" @click="deleteDialog = false"
                    >Cancel</VBtn
                    >
                    <VBtn color="error" variant="elevated" @click="deleteItem"
                    >Delete</VBtn
                    >
                </VCardActions>
            </VCard>
        </VDialog>

        <!-- Snackbar for notifications -->
        <VSnackbar v-model="snackbar" :color="snackbarColor" :timeout="3000">
            {{ snackbarText }}
        </VSnackbar>
    </VContainer>
</template>

<script setup>
import { computed, onMounted, ref } from "vue"
import { useTheme } from "vuetify"
import colorsService from "../services/colorsService"
import { colorsPalette } from "../utils/colors.js"
import ColorPickerForm from "./ColorPickerForm.vue"

// Theme management
const theme = useTheme()
const isDark = computed(() => theme.global.current.value.dark)

// Data properties
const customColors = ref([])
const loading = ref(false)
const dialog = ref(false)
const deleteDialog = ref(false)
const previewDialog = ref(false)
const editMode = ref(false)
const selectedColor = ref("")
const itemToDelete = ref(null)
const colorPickerRef = ref(null)
const previewedColor = ref(null)

// Snackbar properties
const snackbar = ref(false)
const snackbarText = ref("")
const snackbarColor = ref("success")

// Basic colors from the predefined palette
const basicColors = computed(() => {
    return colorsPalette.map(color => ({
        name: color.name,
        light: color.light,
        dark: color.dark,
        type: "basic",
    }))
})

// Table headers for basic colors (removed type column)
const basicHeaders = [
    { title: "Preview", key: "preview", sortable: false },
    { title: "Name", key: "name", sortable: false },
]

// Table headers for custom colors (removed type column)
const customHeaders = [
    { title: "Preview", key: "preview", sortable: false },
    { title: "Name", key: "name" },
    { title: "UUID", key: "uuid" },
    { title: "Actions", key: "actions", sortable: false, align: "end" },
]

// Default item structure
const defaultItem = {
    name: "",
    dark_text: "",
    dark_bg: "",
    dark_border: "",
    light_text: "",
    light_bg: "",
    light_border: "",
}

const editedItem = ref({ ...defaultItem })
const editedIndex = ref(-1)

// Methods
const fetchCustomColors = async () => {
    loading.value = true
    try {
        const response = await colorsService.getColors()

        customColors.value = response.data || []
    } catch (error) {
        console.error("Error fetching colors:", error)
        showNotification("Error fetching colors", "error")
    } finally {
        loading.value = false
    }
}

const openCreateDialog = () => {
    editMode.value = false
    editedItem.value = { ...defaultItem }
    editedIndex.value = -1
    selectedColor.value = "" // Start with empty for new colors
    dialog.value = true
}

const openEditDialog = item => {
    editMode.value = true
    editedIndex.value = customColors.value.indexOf(item)
    editedItem.value = { ...item }

    // For custom colors, always use UUID since this is the custom colors table
    selectedColor.value = item.uuid
    dialog.value = true
}

const closeDialog = () => {
    dialog.value = false
    setTimeout(() => {
        editedItem.value = { ...defaultItem }
        editedIndex.value = -1
        selectedColor.value = ""
    }, 300)
}

const save = async () => {
    try {
        // Call the ColorPickerForm's save method if it's in advanced mode
        if (colorPickerRef.value && colorPickerRef.value.save) {
            await colorPickerRef.value.save()
        }

        // Close dialog and refresh the list
        dialog.value = false
        await fetchCustomColors()
        showNotification(
            editMode.value
                ? "Color updated successfully"
                : "Color created successfully",
            "success",
        )
        closeDialog()
    } catch (error) {
        console.error("Error saving color:", error)
        showNotification("Error saving color", "error")
    }
}

// Handle color selection events from ColorPickerForm
const handleColorSelected = event => {
    console.log("Color selected:", event)

    // For basic mode, the event contains { name, color }
    // For advanced mode, the event contains { uuid, color }
    // The ColorPickerForm handles the saving, we just listen for completion
}

const confirmDelete = item => {
    itemToDelete.value = item
    deleteDialog.value = true
}

const deleteItem = async () => {
    try {
        await colorsService.deleteColor(itemToDelete.value.id)
        await fetchCustomColors()
        showNotification("Color deleted successfully", "success")
    } catch (error) {
        console.error("Error deleting color:", error)
        showNotification("Error deleting color", "error")
    } finally {
        deleteDialog.value = false
        itemToDelete.value = null
    }
}

const showNotification = (text, color = "success") => {
    snackbarText.value = text
    snackbarColor.value = color
    snackbar.value = true
}

// Color formatting helper
const formatColorValue = colorValue => {
    if (!colorValue) return "#000000"

    // Remove extra spaces and ensure # prefix
    const cleaned = colorValue.trim()
    
    return cleaned.startsWith("#") ? cleaned : `#${cleaned}`
}

// Style helpers for basic colors (predefined palette)
const getLightPreviewStyle = item => {
    return {
        backgroundColor: item.light?.background || "#f0f0f0",
        color: item.light?.text || "#333",
        border: `2px solid ${item.light?.border || "#ddd"}`,
    }
}

const getDarkPreviewStyle = item => {
    return {
        backgroundColor: item.dark?.background || "#333",
        color: item.dark?.text || "#fff",
        border: `2px solid ${item.dark?.border || "#555"}`,
    }
}

// Style helpers for custom colors (from database)
const getCustomLightPreviewStyle = item => {
    return {
        backgroundColor: formatColorValue(item.light?.background) || "#f0f0f0",
        color: formatColorValue(item.light?.text) || "#333",
        border: `2px solid ${formatColorValue(item.light?.border) || "#ddd"}`,
    }
}

const getCustomDarkPreviewStyle = item => {
    return {
        backgroundColor: formatColorValue(item.dark?.background) || "#333",
        color: formatColorValue(item.dark?.text) || "#fff",
        border: `2px solid ${formatColorValue(item.dark?.border) || "#555"}`,
    }
}

// Theme-aware preview style for basic colors
const getThemeAwarePreviewStyle = item => {
    if (isDark.value) {
        return {
            backgroundColor: item.dark?.background || "#333",
            color: item.dark?.text || "#fff",
            border: `2px solid ${item.dark?.border || "#555"}`,
        }
    } else {
        return {
            backgroundColor: item.light?.background || "#f0f0f0",
            color: item.light?.text || "#333",
            border: `2px solid ${item.light?.border || "#ddd"}`,
        }
    }
}

// Preview basic color - open preview dialog
const previewBasicColor = (event, { item }) => {
    previewedColor.value = item
    previewDialog.value = true

    // Emit colorSelected event for parent component usage
    handleColorSelected({
        name: item.name,
        color: item,
    })
}

// Preview custom color - open preview dialog
const previewCustomColor = item => {
    // Format the custom color to match the expected structure
    const formattedColor = {
        name: item.name,
        light: {
            background: formatColorValue(item.light?.background),
            text: formatColorValue(item.light?.text),
            border: formatColorValue(item.light?.border),
        },
        dark: {
            background: formatColorValue(item.dark?.background),
            text: formatColorValue(item.dark?.text),
            border: formatColorValue(item.dark?.border),
        },
    }

    previewedColor.value = formattedColor
    previewDialog.value = true

    // Emit colorSelected event for parent component usage
    handleColorSelected({
        uuid: item.uuid,
        color: formattedColor,
    })
}

// Lifecycle
onMounted(() => {
    fetchCustomColors()
})
</script>

<style scoped>
.color-preview-box {
    width: 80px;
    height: 40px;
    border-radius: 4px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.color-preview-box span {
    font-size: 0.75rem;
    font-weight: 500;
}

.theme-aware-preview {
    transition: all 0.3s ease;
}

.cursor-pointer {
    cursor: pointer;
}

.cursor-pointer:hover .theme-aware-preview {
    transform: scale(1.05);
}

.preview-card {
    min-width: 250px;
}

:deep(.v-data-table__tr--clickable:hover) {
    background-color: rgba(var(--v-theme-on-surface), 0.04);
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
