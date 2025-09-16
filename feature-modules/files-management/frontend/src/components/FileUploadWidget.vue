<template>
    <FileUploadDialogContent
        v-if="size === 'xl'"
        widget
        :files="props.files"
        :readonly="props.readonly"
        @update:files="(files) => emit('update:files', files)"
    />
    <VRow
        v-else
        id="dropzone"
        class="file-upload-widget-wrapper mx-0 my-0"
        :class="{
            'small-widget': props.size === 's',
            'medium-widget': props.size === 'm',
            'large-widget': props.size === 'l',
            'justify-start': props.size === 'l' && props.files.length > 0,
            'justify-center align-content-center align-items-center': !props.files.length,
            'justify-start align-content-start align-items-start':
                props.files.length && props.size === 'l',
            'cursor-pointer': progress === 0,
        }"
        @click="progress > 0 ? null : (fileUploadDialogOpen = true)"
    >
        <VCol cols="12" style="height: fit-content">
            <div
                class="d-flex align-center gap-1"
                :class="{
                    'justify-start': ['l'].includes(props.size),
                    'justify-center': ['s', 'm'].includes(props.size) || !props.files.length,
                    'flex-column': ['m'].includes(props.size) || !props.files.length,
                    'flex-row': ['s', 'l'].includes(props.size),
                }"
            >
                <VIcon
                    v-if="!(progress > 0 && props.size === 's')"
                    color="primary"
                    class="upload-icon"
                >
                    tabler-file-upload
                </VIcon>
                <div v-if="progress === 0 && !props.files.length && props.size !== 's'">
                    File Upload
                </div>

                <div
                    v-if="progress > 0"
                    style="height: 22.5px"
                    class="d-flex align-center justiy-center"
                    :class="{
                        'small-widget-progress-bar': props.size === 's',
                        'medium-widget-progress-bar': props.size === 'm',
                        'large-widget-progress-bar': props.size === 'l',
                    }"
                >
                    <VProgressLinear v-model="progress" class="w-full" color="secondary" />
                </div>

                <div
                    v-else-if="files.length > 0 || ['l'].includes(props.size)"
                    v-if="!props.noText"
                    class="font-weight-bold text-center"
                    style="
                        white-space: nowrap;
                        text-overflow: ellipsis;
                        overflow: hidden;
                        height: 22.5px;
                    "
                >
                    {{ props.files.length }} files, {{ sizeOfFiles }} MB
                </div>
            </div>
        </VCol>
        <VCol
            v-if="['l'].includes(props.size) && props.files.length > 0"
            class="pt-0 flex-grow-1"
            cols="12"
        >
            <PerfectScrollbar
                v-if="props.files.length > 0"
                style="width: 100%; overflow: hidden; height: 70px"
            >
                <div
                    v-for="file in props.files"
                    :key="file.name"
                    class="d-flex align-center gap-1 overflow-hidden"
                    style="max-width: 100%"
                >
                    <VIcon color="primary"> tabler-file-text </VIcon>
                    <div style="white-space: nowrap; text-overflow: ellipsis; overflow: hidden">
                        {{ file.name }}
                    </div>
                </div>
            </PerfectScrollbar>
        </VCol>
    </VRow>
    <input
        id="file-input"
        :key="formKey"
        class="d-none"
        type="file"
        :multiple="props.multiple"
        :accept="props.onlyImages ? 'image/*' : null"
        :disabled="props.readonly"
        @change="handleFileSelect"
    />
    <FileUploadDialog
        v-if="fileUploadDialogOpen"
        :endpoint="props.endpoint"
        :model-id="props.modelId"
        :files="props.files"
        @update:files="(files) => emit('update:files', files)"
        @file-uploaded="fileUploadDialogOpen = false"
        @get-files="emit('getFiles')"
    />
</template>

<script setup>
import { FileUploadDialog } from 'files-module-frontend'
import { FileUploadDialogContent } from 'files-module-frontend'
import { $endpoint } from '../utils/endpoints'

import { PerfectScrollbar } from "vue3-perfect-scrollbar";
// import { useSnackbarStore } from "@/store/snackbar";

// const snackbar = useSnackbarStore();

const formKey = ref(0);
const props = defineProps({
    multiple: {
        type: Boolean,
        default: true,
    },
    onlyImages: {
        type: Boolean,
        default: false,
    },
    size: {
        // available sizes: s, m, l, xl
        type: String,
        default: "s",
    },
    endpoint: {
        type: String,
        required: false,
        default: "UPLOAD_FILES",
    },
    params: {
        type: Object,
        required: false,
        default: () => ({
            multiple: true,
        }),
    },
    modelId: {
        type: Number,
        required: false,
        default: null,
    },
    modelType: {
        type: String,
        required: false,
        default: null,
    },
    files: {
        type: Array,
        default: () => [],
    },
    collectionName: {
        type: String,
        required: false,
        default: null,
    },
    readonly: {
        type: Boolean,
        required: false,
        default: false,
    },
    maxFileSize: {
        type: Number,
        required: false,
        default: 30,
    },
    noText: {
        type: Boolean,
        required: false,
        default: false,
    },
    fileIdToReplace: {
        type: [Number, String],
        required: false,
        default: null,
    },
});

const emit = defineEmits(["getFiles", "update:files"]);
const fileUploadDialogOpen = ref(false);

const files = computed({
    get: () => props.files,
    set: (newValue) => emit("update:files", newValue),
});

provide("fileUploadDialogOpen", fileUploadDialogOpen);

const tempFileIds = inject("tempFileIds");

const initializeDropzone = (dropzoneId) => {
    const dropzone = document.getElementById(dropzoneId);

    if (dropzone) {
        dropzone.addEventListener("dragover", (event) => {
            if (progress.value > 0) return;
            event.preventDefault();
            dropzone.classList.add("hover-dropzone");
        });

        dropzone.addEventListener("dragleave", (event) => {
            if (dropzone.contains(event.target) && dropzone !== event.target) return;
            dropzone.classList.remove("hover-dropzone");
        });

        dropzone.addEventListener("drop", (event) => {
            if (progress.value > 0) return;
            event.preventDefault();
            dropzone.classList.remove("hover-dropzone");
            const files = Array.from(event.dataTransfer.files);
            files.forEach((file) => {
                if (file.size / (1024 * 1024) > props.maxFileSize) {
                    // snackbar.show("File greater than 50MB limit.");
                    console.log("File greater than 50MB limit.");
                }
            });
            handleFileSelect({ target: { files } });
        });
    }
};

provide("initializeDropzone", initializeDropzone);

onMounted(() => {
    initializeDropzone("dropzone");
});

const uploadFileWithProgress = async (endpoint, data, onProgress) => {
    const response = await new Promise((resolve, reject) => {
        const xhr = new XMLHttpRequest();
        const finalEndpoint = props.modelId ? endpoint : "UPLOAD_TEMP_FILES";
        const baseURL =
            import.meta.env.VITE_API_BASE_URL || "/api/" + (import.meta.env.VITE_API_VERSION ?? 'v1') + "/";

        const fullURL = $endpoint(finalEndpoint, props.params).startsWith("http")
            ? $endpoint(finalEndpoint, props.params)
            : baseURL + $endpoint(finalEndpoint, props.params);

        xhr.open("POST", fullURL, true);

        const accessToken = useCookie("accessToken").value;

        // Set headers
        xhr.setRequestHeader("Authorization", `Bearer ${accessToken}`);
        xhr.setRequestHeader("Accept", "application/json");

        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute("content");

        xhr.setRequestHeader("X-CSRF-TOKEN", csrfToken);

        // Track progress
        xhr.upload.onprogress = function (event) {
            if (event.lengthComputable) {
                const percentComplete = (event.loaded / event.total) * 100;
                onProgress(percentComplete); // Ensure this callback is correctly invoked
            }
        };

        xhr.onload = function () {
            if (xhr.status >= 200 && xhr.status < 300) {
                resolve(JSON.parse(xhr.responseText));
            } else {
                reject(new Error("Upload failed"));
            }
        };

        xhr.onerror = function () {
            reject(new Error("Network error"));
        };

        xhr.send(data);
    });
    if (response.file_ids) {
        tempFileIds.value.push(...response.file_ids);
    }
    if (response.temp_files) {
        if (props.multiple) {
            files.value.push(...response.temp_files);
        } else {
            files.value = response.temp_files;
        }
    }

    return response;
};

const progress = ref(0);
provide("progress", progress);
const sizeOfFiles = computed(() => {
    const totalSize = props.files.reduce((acc, file) => acc + file.size, 0);
    return (totalSize / (1024 * 1024)).toFixed(2);
});

const handleFileSelect = async (event) => {
    if (props.readonly) return;
    let newFiles = Array.from(event.target.files);
    const data = new FormData();

    if (newFiles.some((file) => file.size / (1024 * 1024) > props.maxFileSize)) {
        // snackbar.show(`File greater than ${props.maxFileSize}MB limit.`, "error");
        newFiles = newFiles.filter((file) => file.size / (1024 * 1024) <= props.maxFileSize);
    }

    data.append("id", props.modelId);
    data.append("model_id", props.modelId);
    data.append("collection_name", props.collectionName);

    // Add the file_id_to_replace if it exists
    if (props.fileIdToReplace) {
        data.append("file_id_to_replace", props.fileIdToReplace);
    }

    if (props.modelType) {
        data.append("model_type", props.modelType);
    }

    // If we're replacing a file, use 'file' instead of 'files[]'
    if (props.fileIdToReplace && newFiles.length > 0) {
        data.append("file", newFiles[0]);
    } else {
        // Otherwise use files[] for multiple files
        newFiles.forEach((file) => data.append("files[]", file));
    }

    if (!props.params.multiple) {
        data.append("single", true);
    }

    try {
        const response = await uploadFileWithProgress(props.endpoint, data, (percentComplete) => {
            progress.value = percentComplete;
        });
        progress.value = 0;

        if (response.files) {
            newFiles = response.files;
        } else if (response.temp_files) {
            newFiles = response.temp_files;
        }

        const updatedFiles = props.modelId ? [...props.files, ...newFiles] : props.files;
        emit("update:files", updatedFiles);

        if (props.modelId) {
            emit("getFiles");
        }
    } catch (error) {
        console.error(error);
        progress.value = 0;
    }
};
</script>

<style lang="scss">
.file-upload-widget-wrapper {
    border: 1.5px dashed #ccc;
    border-radius: 10px;
    width: fit-content;
    overflow: hidden;
}

.small-widget {
    width: 100%;
    max-width: 130px;
    height: 50px;

    .small-widget-progress-bar {
        width: 100px;
        max-width: 100%;
    }
}

.medium-widget {
    width: 100%;
    max-width: 150px;
    height: 80px;
    align-content: center;

    .medium-widget-progress-bar {
        width: 150px;
        max-width: 100%;
    }
}

.large-widget {
    width: 100%;
    max-width: 190px;
    height: 120px;
    overflow: hidden;

    .large-widget-progress-bar {
        width: 350px;
        max-width: 100%;
    }
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
</style>
