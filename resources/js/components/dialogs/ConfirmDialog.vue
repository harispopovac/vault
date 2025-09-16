<template>
    <!-- 👉 Confirm Dialog -->
    <VDialog
        max-width="500"
        :model-value="props.isDialogVisible"
        @update:model-value="onCancel"
    >
        <VCard class="text-center px-10 py-6">
            <VCardText>
                <VIcon class="mb-4" size="50" :icon="icon" :color="color" />

                <h6 class="text-lg font-weight-medium">
                    {{ props.question }}
                </h6>
            </VCardText>

            <VCardText class="align-center justify-center">
                <VRow>
                    <VCol :cols="includesReject ? 4 : 6">
                        <VBtn
                            block
                            color="secondary"
                            variant="tonal"
                            @click="onCancel"
                        >
                            {{ cancelLabel }}
                        </VBtn>
                    </VCol>
                    <VCol v-if="includesReject" :cols="includesReject ? 4 : 6">
                        <VBtn
                            block
                            color="error"
                            variant="elevated"
                            @click="onReject"
                        >
                            {{ rejectLabel }}
                        </VBtn>
                    </VCol>
                    <VCol :cols="includesReject ? 4 : 6">
                        <VBtn
                            block
                            :color="confirmColor"
                            variant="elevated"
                            @click="onConfirmation"
                        >
                            {{ confirmLabel }}
                        </VBtn>
                    </VCol>
                </VRow>
            </VCardText>
        </VCard>
    </VDialog>
</template>

<script setup>
const props = defineProps({
    question: {
        type: String,
        required: true,
    },
    isDialogVisible: {
        type: Boolean,
        required: true,
    },
    icon: {
        type: String,
        default: "tabler-alert-triangle",
    },
    color: {
        type: String,
        default: "warning",
    },
    includesReject: {
        type: Boolean,
        default: false,
    },
    rejectLabel: {
        type: String,
        default: "Reject",
    },
    confirmLabel: {
        type: String,
        default: "Confirm",
    },
    cancelLabel: {
        type: String,
        default: "Cancel",
    },
    confirmColor: {
        type: String,
        default: "primary",
    },
})

const emit = defineEmits(["confirm", "reject", "close"])

const onConfirmation = () => {
    emit("confirm")
    emit("close")
}

const onReject = () => {
    emit("reject")
    emit("close")
}

const onCancel = () => {
    emit("close")
}
</script>
