<template>
    <VDialog v-model="isDialogVisible" max-width="600">
        <VCard>
            <VCardTitle>Capture Photo</VCardTitle>
            <VCardText>
                <div v-if="!photoCaptured">
                    <video
                        ref="videoRef"
                        autoplay
                        playsinline
                        style="width: 100%; transform: scaleX(-1)"
                    ></video>
                </div>
                <div v-else>
                    <canvas
                        ref="canvasRef"
                        style="width: 100%; transform: scaleX(-1)"
                    ></canvas>
                </div>
            </VCardText>
            <VCardActions class="d-flex gap-3 justify-end pa-4">
                <VBtn
                    v-if="!photoCaptured"
                    color="primary"
                    @click="capturePhoto"
                >
                    Capture Photo
                </VBtn>
                <VBtn v-else color="primary" @click="retakePhoto">
                    Retake Photo
                </VBtn>
                <VBtn
                    v-if="photoCaptured"
                    color="success"
                    @click="confirmPhoto"
                >
                    Confirm Photo
                </VBtn>
                <VBtn color="secondary" @click="closeDialog"> Close </VBtn>
            </VCardActions>
        </VCard>
    </VDialog>
</template>

<script setup>
import {
    defineEmits,
    defineExpose,
    nextTick,
    onBeforeUnmount,
    ref,
    watch,
} from "vue"

const emit = defineEmits(["photoCaptured", "cameraError"])
const isDialogVisible = ref(false)
const photoCaptured = ref(false)
const videoRef = ref(null)
const canvasRef = ref(null)
const currentStream = ref(null)

const stopStream = () => {
    try {
        if (currentStream.value) {
            currentStream.value.getTracks().forEach(track => track.stop())
        }
        if (videoRef.value && videoRef.value.srcObject) {
            videoRef.value.srcObject = null
        }
    } catch (e) {
        // swallow
    } finally {
        currentStream.value = null
    }
}

const openDialog = async () => {
    isDialogVisible.value = true
    photoCaptured.value = false
    await nextTick() // Ensure DOM is updated

    try {
        // Ensure any previous stream is stopped before starting a new one
        stopStream()

        const stream = await navigator.mediaDevices.getUserMedia({
            video: true,
        })

        if (videoRef.value) {
            currentStream.value = stream
            videoRef.value.srcObject = stream
        } else {
            console.error("Video element is not available")
            emit("cameraError", new Error("Video element is not available"))
        }
    } catch (error) {
        console.error("Failed to access camera:", error)
        emit("cameraError", error)
        isDialogVisible.value = false
    }
}

const capturePhoto = async () => {
    const video = videoRef.value

    photoCaptured.value = true
    await nextTick() // Wait for the DOM to update

    const canvas = canvasRef.value
    if (!canvas) {
        console.error("Canvas element is not available")
        emit("cameraError", new Error("Canvas element is not available"))
        
        return
    }

    const context = canvas.getContext("2d")
    if (!context) {
        console.error("Failed to get canvas context")
        emit("cameraError", new Error("Failed to get canvas context"))
        
        return
    }

    canvas.width = video.videoWidth
    canvas.height = video.videoHeight
    context.drawImage(video, 0, 0, canvas.width, canvas.height)

    // Stop the camera once we've captured the image
    stopStream()
}

const retakePhoto = async () => {
    photoCaptured.value = false
    await nextTick() // Ensure DOM is updated

    try {
        // Stop any existing stream before starting a new one
        stopStream()

        const stream = await navigator.mediaDevices.getUserMedia({
            video: true,
        })

        if (videoRef.value) {
            currentStream.value = stream
            videoRef.value.srcObject = stream
        } else {
            console.error("Video element is not available")
            emit("cameraError", new Error("Video element is not available"))
        }
    } catch (error) {
        console.error("Failed to access camera:", error)
        emit("cameraError", error)
    }
}

const confirmPhoto = () => {
    const canvas = canvasRef.value
    const photoData = canvas.toDataURL("image/png")

    emit("photoCaptured", photoData)
    closeDialog()
}

const closeDialog = () => {
    isDialogVisible.value = false
    stopStream()
}

// Expose the openDialog method
defineExpose({ openDialog })

// Stop the stream when dialog is closed externally
watch(
    () => isDialogVisible.value,
    visible => {
        if (!visible) {
            stopStream()
        }
    },
)

onBeforeUnmount(() => {
    stopStream()
})
</script>

<style scoped>
video,
canvas {
    border-radius: 8px;
    background-color: #000;
}
</style>
