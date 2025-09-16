<template>
    <VRow
        :class="{
            'widget border mx-0 my-0': props.widget,
        }"
    >
        <VCol cols="12" md="4">
            <div class="d-flex gap-6 h-full">
                <div
                    id="dialog-drozpone"
                    class="file-dropzone pa-2"
                    :class="{
                        'w-full': $vuetify.display.mdAndDown,
                        'cursor-pointer': progress === 0,
                        'inner-widget-dropzone': props.widget,
                    }"
                    @click="startUploadPhoto"
                >
                    <VIcon color="primary"> tabler-file-upload </VIcon>
                    <div>Upload</div>
                    <div
                        v-if="progress > 0"
                        style="height: 22.5px; width: 80%"
                        class="d-flex align-center justiy-center"
                    >
                        <VProgressLinear v-model="progress" class="w-full" color="secondary" />
                    </div>
                </div>
                <div v-if="$vuetify.display.mdAndUp" class="file-dialog-divider h-full"></div>
            </div>
        </VCol>
        <VCol cols="12" md="8">
            <div class="w-full overflow-hidden">
                <div class="py-2 text-xl">Files:</div>
                <PerfectScrollbar
                    v-if="props.files.length > 0"
                    class="file-upload-scrollbar"
                    :options="{
                        wheelPropagation: false,
                        suppressScrollX: true,
                        maxScrollbarWidth: 1,
                    }"
                    style="width: 100%; overflow: hidden; height: 195px"
                >
                    <div
                        v-for="(file, index) in props.files"
                        :key="index"
                        class="d-flex align-center justify-space-between gap-1 mb-1"
                    >
                        <div class="d-flex align-center gap-4 file-name-wrapper">
                            <VIcon color="primary"> tabler-file-text </VIcon>
                            <a
                                class="file-name"
                                :href="file.original_url"
                                target="_blank"
                                rel="noopener noreferrer"
                            >
                                {{ file.name }}
                            </a>
                        </div>
                        <div class="d-flex gap-2">
                            {{ (file.size / (1024 * 1024)).toFixed(2) }}MB
                            <VIcon
                                v-if="!props.readonly"
                                size="20"
                                color="error"
                                @click="
                                    selectedFile = file;
                                    isRemoveConfirmDialogVisible = true;
                                "
                                >tabler-x</VIcon
                            >
                        </div>
                    </div>
                </PerfectScrollbar>
                <div v-if="!props.files.length">No files...</div>
            </div>
        </VCol>
        <VCol v-if="props.widget" cols="12" class="py-0">
            <div>
                <div>Total Files: {{ props.files.length }}</div>
                <div>
                    Total Size:
                    {{
                        (
                            props.files.reduce((acc, file) => acc + file.size, 0) /
                            (1024 * 1024)
                        ).toFixed(2)
                    }}MB
                </div>
            </div>
        </VCol>
    </VRow>
    <ConfirmDialog
        :is-dialog-visible="isRemoveConfirmDialogVisible"
        :question="`Remove this file: ${selectedFile?.name}?`"
        @confirm="removeFIle"
        @close="isRemoveConfirmDialogVisible = false"
    />
</template>

<script setup>
import { PerfectScrollbar } from "vue3-perfect-scrollbar";
import { removeTempFile } from "../services/fileServices";

const props = defineProps({
    files: {
        type: Array,
        default: () => [],
    },
    widget: {
        type: Boolean,
        default: false,
    },
    readonly: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(["update:files"]);

const files = computed({
    get: () => props.files,
    set: (newValue) => emit("update:files", newValue),
});

watch(props.files, (value) => {
    files.value = value;
});

const initializeDropzone = inject("initializeDropzone");
const progress = inject("progress");
const isRemoveConfirmDialogVisible = ref(false);
const selectedFile = ref(null);

onMounted(() => {
    initializeDropzone("dialog-drozpone");
});

const startUploadPhoto = () => {
    if (progress.value > 0) return;
    document.querySelector("#file-input").click();
};

const removeFIle = async () => {
    const fileIndex = files.value.findIndex((file) => file.id === selectedFile.value.id);

    try {
        files.value.splice(fileIndex, 1);
        await removeTempFile(selectedFile.value.id);
    } catch (err) {
        files.value.splice(fileIndex, 0, selectedFile.value);
        console.log(err);
    }

    selectedFile.value = null;
    isRemoveConfirmDialogVisible.value = false;
};
</script>

<style lang="scss">
.file-dropzone {
    border: 1.5px dashed #ccc;
    border-radius: 10px;
    display: flex;
    flex-direction: column;
    gap: 4px;
    justify-content: center;
    align-items: center;
    width: 250px;
    height: 250px;
}

.file-dialog-divider {
    width: 1px;
    display: flex;
    flex-direction: column;
    height: 250px;
    background: rgba(var(--v-border-color), var(--v-border-opacity)) !important;
}
.hover-dropzone {
    border: 1.5px dashed rgb(var(--v-theme-primary)) !important;
    background: rgba(var(--v-theme-primary), 0.1) !important;
    position: relative;

    &:before {
        content: "Drop Here";
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        color: rgb(var(--v-theme-primary));
        font-size: 0.8rem;
    }

    * {
        display: none;
    }
}
.file-name-wrapper {
    width: 100%;
    max-width: 65%;

    .file-name {
        font-weight: 500;
        width: 100%;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        text-decoration: underline;
        color: rgb(var(--v-theme-primary)) !important;
    }
}

.widget {
    border-radius: 6px;
    padding: 8px;

    .inner-widget-dropzone {
        height: auto;
        min-height: 100px;
    }
}

.file-upload-scrollbar {
    .ps__rail-x,
    .ps__rail-y {
        display: none;
    }
}
</style>
