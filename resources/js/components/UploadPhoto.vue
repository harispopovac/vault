<template>
    <div
        class="photo-upload-wrapper"
        @mouseenter="showPhotoUploadIcon = true"
        @mouseleave="showPhotoUploadIcon = false"
        @click="startUpload"
    >
        <div class="photo-upload-background">
            <VIcon color="white" icon="tabler-edit" size="24" />
        </div>
        <VFileInput
            id="photo-input"
            class="d-none"
            accept="image/*"
            @change="uploadPhoto"
        />
    </div>
</template>

<script setup>
const props = defineProps({
    modelId: {
        type: [String, Number],
        required: true,
        default: "",
    },
    endpoint: {
        type: String,
        required: true,
        default: null,
    },
})

const emit = defineEmits(["update", "updateData"])

const showPhotoUploadIcon = ref(false)

const startUpload = () => {
    document.querySelector("#photo-input").click()
}

const uploadPhoto = async file => {
    const data = new FormData()

    data.append("photo", file.target.files[0])

    try {
        const response = await $api(
            $endpoint(props.endpoint, {
                id: props.modelId,
            }),
            {
                method: "POST",
                body: data,
            },
        )

        emit("update")
        emit("updateData", response.model)
    } catch (e) {
        console.log(e)
    }
}
</script>

<style lang="scss">
.photo-upload-wrapper {
    position: absolute;
    width: 100%;
    height: 100%;

    .photo-upload-background {
        position: absolute;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.5);
        display: flex;
        justify-content: center;
        align-items: center;
        cursor: pointer;
        opacity: 0;
        transition: opacity 0.3s ease-in-out;
    }

    .photo-upload-background:hover {
        opacity: 1;
    }
}
</style>
