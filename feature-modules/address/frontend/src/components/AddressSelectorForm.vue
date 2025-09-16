<template>
    <VRow>
        <VCol cols="12">
            <AppAutocomplete
                v-model="selectedAddress"
                label="Address Search"
                placeholder="Search for an address"
                :items="addresses"
                item-title="sla"
                return-object
                :loading="loadingAddresses"
                hide-no-data
                clearable
                :custom-filter="() => true"
                @update:search="handleAddressSearch"
            >
                <template #prepend-inner>
                    <VIcon icon="tabler-map-search" />
                </template>
            </AppAutocomplete>
        </VCol>
        <VCol cols="12">
            <AppTextField
                v-model="formData.address_1"
                label="Address Line 1"
                placeholder="Address Line 1"
                :error="Boolean(errors.address_1?.length)"
                :error-messages="errors.address_1"
            />
        </VCol>
        <VCol cols="12">
            <AppTextField
                v-model="formData.address_2"
                label="Address Line 2"
                placeholder="Address Line 2"
            />
        </VCol>
        <VCol cols="12">
            <AppTextField
                v-model="formData.suburbcity"
                label="Suburb / Town"
                placeholder="Suburb / Town"
                :error="Boolean(errors.suburbcity?.length)"
                :error-messages="errors.suburbcity"
            />
        </VCol>
        <VCol cols="12" sm="6">
            <AppTextField
                v-model="formData.postcode"
                label="Postcode"
                placeholder="Postcode"
                :error="Boolean(errors.postcode?.length)"
                :error-messages="errors.postcode"
            />
        </VCol>
        <VCol cols="12" sm="6">
            <AppSelect
                v-model="formData.stateprov"
                :items="states"
                label="State"
                placeholder="Select State"
                :error="Boolean(errors.stateprov?.length)"
                :error-messages="errors.stateprov"
            />
        </VCol>
        <VCol cols="12">
            <AddressMap
                v-if="formData.lat && formData.lng"
                :lat="formData.lat"
                :lng="formData.lng"
                :height="$vuetify.display.mdAndUp ? '224px' : '100px'"
            />
        </VCol>
    </VRow>
</template>

<script setup>
import debounce from "lodash/debounce"
import { AddressMap } from "maps-module-frontend"
import { ref, watch } from "vue"
import endpoints from "../utils/endpoints.js"

const props = defineProps({
    modelValue: { type: Object, default: () => ({}) },
})

const emit = defineEmits(["update:modelValue"])

const formData = ref({
    country: "AU",
    address_1: "",
    address_2: "",
    suburbcity: "",
    postcode: "",
    stateprov: "",
    lat: null,
    lng: null,
})

const selectedAddress = ref(null)
const loadingAddresses = ref(false)
const addresses = ref([])
const errors = ref({})

const states = [
    { title: "New South Wales", value: "NSW" },
    { title: "Queensland", value: "QLD" },
    { title: "Victoria", value: "VIC" },
    { title: "South Australia", value: "SA" },
    { title: "Western Australia", value: "WA" },
    { title: "Tasmania", value: "TAS" },
    { title: "Northern Territory", value: "NT" },
    { title: "Australian Capital Territory", value: "ACT" },
]

watch(
    () => formData.value,
    newValue => {
        emit("update:modelValue", newValue)
    },
    { deep: true },
)

watch(
    () => props.modelValue,
    newValue => {
        if (newValue) {
            formData.value = { ...formData.value, ...newValue }
        }
    },
    { deep: true },
)

const handleAddressSearch = async q => {
    await debouncedAddressSearch(q)
}

const debouncedAddressSearch = debounce(query => {
    if (query.length > 2) {
        loadingAddresses.value = true
        searchAddress(query)
    }
}, 300)

const $api = async (url, options = {}) => {
    const response = await fetch(`/api/v1/${url}`, {
        method: options.method || "GET",
    })

    if (!response.ok) return []
    
    return await response.json()
}

const $endpoint = (key, params = {}) => {
    let path = endpoints[key]
    if (!path) return ""
    Object.keys(params).forEach(k => {
        path = path.replace(`{${k}}`, encodeURIComponent(params[k] ?? ""))
    })
    
    return path
}

const searchAddress = async q => {
    try {
        addresses.value = await $api($endpoint("PROXY_GET_ADDRESSES", { q }), {
            method: "GET",
        })
    } catch (e) {
        console.error(e)
    }
    loadingAddresses.value = false
}

watch(selectedAddress, async newValue => {
    if (newValue) {
        await selectAddress()
    }
})

const selectAddress = async () => {
    try {
        const response = await $api(
            $endpoint("PROXY_GET_ADDRESS_BY_ID", {
                id: selectedAddress.value.id,
            }),
            { method: "GET" },
        )

        if (response) {
            formData.value = {
                ...formData.value,
                address_1: capitalizeWords(response.address_1),
                address_2: response.address_2
                    ? capitalizeWords(response.address_2)
                    : null,
                suburbcity: capitalizeWords(response.suburbcity),
                stateprov: response.stateprov,
                postcode: response.postcode,
                lat: response.lat,
                lng: response.lng,
            }
            emit("update:modelValue", formData.value)
        }
    } catch (e) {
        console.error(e)
    }
}

const capitalizeWords = str =>
    str.toLowerCase().replace(/\b\w/g, char => char.toUpperCase())
</script>
