<template>
    <AppAutocomplete
        v-model="value"
        :items="timezones"
        item-title="name_formatted"
        item-value="name"
        placeholder="Select Timezone"
        label="Timezone"
        prepend-inner-icon="tabler-timezone"
        :error="Boolean(errors?.length)"
        :error-messages="errors"
        :density="density"
    />
</template>

<script setup>
defineProps({
    errors: {
        type: Array,
        default: () => [],
    },
    density: {
        type: String,
        default: "comfortable",
    },
})

const emit = defineEmits(["update:modelValue"])

const value = ref("")
const timezones = ref([])

watch(
    () => general.timezones,
    () => {
        timezones.value = general.timezones
    },
)

onMounted(() => {
    timezones.value = general.timezones

    getTimezones()
})

const getTimezones = async () => {
    timezones.value = await $api($endpoint("GET_TIMEZONES"), {
        method: "GET",
    })

    general.setTimezones(timezones.value)
}

watch(value, () => {
    emit("update:modelValue", value.value)
})
</script>
