<template>
    <VDialog :model-value="fileUploadDialogOpen" :width="1000" @update:model-value="onReset">
        <!-- 👉 Dialog close btn -->
        <DialogCloseBtn @click="onReset" />

        <VCard class="pa-sm-4 pa-2">
            <VCardText>
                <FileUploadDialogContent
                    :files="props.files"
                    :file-id-to-replace="props.fileIdToReplace"
                    @update:files="(files) => emit('update:files', files)"
                />
            </VCardText>

            <!-- 👉 Actions button -->
            <VFooter class="d-flex align-center justify-end">
                <div class="d-flex gap-8 align-center">
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
                    <VBtn color="secondary" variant="tonal" @click="onReset"> Save </VBtn>
                </div>
            </VFooter>
        </VCard>
    </VDialog>
</template>

<script setup>
import { FileUploadDialogContent } from 'files-module-frontend'

const props = defineProps({
    files: {
        type: Array,
        default: () => [],
    },
    fileIdToReplace: {
        type: [Number, String],
        required: false,
        default: null,
    },
    endpoint: {
        type: String,
        required: false,
        default: null,
    },
    modelId: {
        type: Number,
        required: false,
        default: null,
    },
});

const emit = defineEmits(["update:files"]);
const fileUploadDialogOpen = inject("fileUploadDialogOpen");

const onReset = () => {
    fileUploadDialogOpen.value = false;
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
</style>
