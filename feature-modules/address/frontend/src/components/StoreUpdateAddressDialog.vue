<template>
    <VDialog
        v-model="isDialogVisible"
        persistent
        no-click-animation
        :max-width="
            formData.country === 'AU' && formData.lat && formData.lng
                ? 1000
                : 600
        "
    >
        <template #activator="{ props }">
            <template v-if="activatorType === 'button'">
                <VBtn color="primary" v-bind="props">
                    {{ isNewAddress ? "Add Address" : "Edit Address" }}
                </VBtn>
            </template>
            <template v-else-if="activatorType === 'icon'">
                <VIcon
                    icon="tabler-edit"
                    color="primary"
                    size="16"
                    v-bind="props"
                />
            </template>
            <VCard v-else variant="outlined" v-bind="props" class="d-flex">
                <div class="flex-grow-1 pa-4">
                    <div v-if="isNewAddress" class="text-center">
                        Add Address
                    </div>
                    <div v-else>
                        <div>
                            {{ propsData?.address_1 ?? "" }}
                            {{ propsData?.address_2 ?? "" }}
                        </div>
                        <div>
                            {{ propsData?.suburbcity ?? "" }}
                            {{ propsData?.stateprov ?? "" }}
                            {{ propsData?.postcode ?? "" }}
                        </div>
                        <div>
                            {{ propsData?.country ?? "" }}
                        </div>
                    </div>
                </div>
            </VCard>
        </template>
        <DialogCloseBtn @click="closeDialog" />
        <VCard title="Address">
            <VCardText>
                <VRow>
                    <VCol
                        cols="12"
                        :md="
                            formData.country === 'AU' &&
                                formData.lat &&
                                formData.lng
                                ? 6
                                : 12
                        "
                    >
                        <VRow>
                            <VCol cols="12">
                                <AppAutocomplete
                                    v-model="selectedAddress"
                                    label="Address Search"
                                    placeholder="Address Search"
                                    :items="addresses"
                                    item-title="sla"
                                    return-object
                                    :loading="loadingAddresses"
                                    hide-no-data
                                    clearable
                                    :disabled="disabled"
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
                                    :disabled="disabled"
                                    :error="Boolean(errors.address_1?.length)"
                                    :error-messages="errors.address_1"
                                />
                            </VCol>
                            <VCol cols="12">
                                <AppTextField
                                    v-model="formData.address_2"
                                    label="Address Line 2"
                                    placeholder="Address Line 2"
                                    :disabled="disabled"
                                    :error="Boolean(errors.address_2?.length)"
                                    :error-messages="errors.address_2"
                                />
                            </VCol>
                            <VCol cols="12">
                                <AppTextField
                                    v-model="formData.suburbcity"
                                    label="Suburb / Town"
                                    :disabled="disabled"
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
                                    :disabled="disabled"
                                    :error="Boolean(errors.postcode?.length)"
                                    :error-messages="errors.postcode"
                                />
                            </VCol>
                            <VCol cols="12" sm="6">
                                <AppSelect
                                    v-model="formData.stateprov"
                                    :disabled="disabled"
                                    :items="states"
                                    label="State"
                                    placeholder="State"
                                    :error="Boolean(errors.stateprov?.length)"
                                    :error-messages="errors.stateprov"
                                />
                            </VCol>
                        </VRow>
                    </VCol>
                    <VCol cols="12" md="6">
                        <VRow>
                            <VCol
                                v-if="
                                    formData.country === 'AU' &&
                                        formData.lat &&
                                        formData.lng
                                "
                                cols="12"
                            >
                                <AddressMap
                                    :lat="formData.lat"
                                    :lng="formData.lng"
                                    :height="
                                        $vuetify.display.mdAndUp
                                            ? '464px'
                                            : '300px'
                                    "
                                    style="margin-top: 22px"
                                />
                            </VCol>
                        </VRow>
                    </VCol>
                </VRow>
            </VCardText>
            <VCardText class="d-flex justify-end flex-wrap gap-3">
                <VBtn variant="tonal" color="secondary" @click="closeDialog">
                    Close</VBtn
                >
                <VBtn :disabled="disabled" @click="submit"> Save</VBtn>
            </VCardText>
        </VCard>
    </VDialog>
</template>

<script setup>
import { debounce } from "lodash"
import { AddressMap } from "maps-module-frontend"
import { onBeforeUnmount, onMounted, ref, watch } from "vue"
import addressService from "../services/addressService.js"
import endpoints from "../utils/endpoints.js"

const props = defineProps({
    activatorType: { type: String, default: "card" },
    data: { type: Object, default: () => ({}) },
    disabled: { type: Boolean, default: false },
    activatorIconAdd: { type: String, default: "tabler-plus" },
    activatorIconEdit: { type: String, default: "tabler-edit" },
    activatorColor: { type: String, default: "primary" },
    activatorSize: { type: [String, Number], default: 16 },
})

const emit = defineEmits(["update:address"])

const isDialogVisible = ref(false)
const isNewAddress = ref(true)
const activatorIsNew = computed(() => !props.data?.id)
const addresses = ref([])
const selectedAddress = ref(null)
const loadingAddresses = ref(false)
const errors = ref({})
const propsData = ref(null)

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

const emptyForm = {
    id: null,
    address_1: "",
    address_2: "",
    suburbcity: "",
    stateprov: null,
    postcode: "",
    country: "AU",
    lat: null,
    lng: null,
}

const formData = ref({ ...emptyForm })

watch(isDialogVisible, val => {
    if (val && props.data?.id) {
        formData.value = { ...props.data }
    }
})

watch(selectedAddress, val => {
    if (val) selectAddress()
})

watch(
    () => props.data,
    val => {
        if (val) {
            formData.value = { ...emptyForm, ...val }
            propsData.value = { ...val }
            isNewAddress.value = !(val?.id || val?.address_1?.length)
        }
    },
)

const validate = () => {
    errors.value = {}
    if (!formData.value.address_1)
        errors.value.address_1 = ["Address field is required"]
    if (!formData.value.suburbcity)
        errors.value.suburbcity = ["Suburb field is required"]
    if (!formData.value.postcode)
        errors.value.postcode = ["Postcode field is required"]
    if (!formData.value.stateprov)
        errors.value.stateprov = ["State field is required"]
    
    return !Object.keys(errors.value).length
}

const submit = async () => {
    if (!validate()) return
    try {
        let response
        if (formData.value.id) {
            response = await addressService.updateAddress(
                formData.value.id,
                formData.value,
            )
        } else {
            response = await addressService.storeAddress(formData.value)
        }
        if (response) {
            emit("update:address", response.id || response.data?.id || null)
            closeDialog()
        }
    } catch (e) {
        console.error(e)
    }
}

const $api = async (url, options = {}) => {
    const response = await fetch(`/api/v1/${url}`, {
        method: options.method || "GET",
    })

    if (!response.ok) return []
    
    return await response.json()
}

const $endpoint = (key, params = {}) => {
    let path = endpoints[key]
    Object.keys(params).forEach(k => {
        path = path.replace(`{${k}}`, encodeURIComponent(params[k] ?? ""))
    })
    
    return path
}

const handleAddressSearch = q => {
    debouncedAddressSearch(q)
}

const debouncedAddressSearch = debounce(query => {
    if (query.length > 2) {
        loadingAddresses.value = true
        searchAddress(query)
    }
}, 300)

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

const selectAddress = async () => {
    try {
        const response = await $api(
            $endpoint("PROXY_GET_ADDRESS_BY_ID", {
                id: selectedAddress.value.id,
            }),
            { method: "GET" },
        )

        if (response) {
            formData.value.address_1 = response.address_1
                ? capitalizeWords(response.address_1)
                : null
            formData.value.address_2 = response.address_2
                ? capitalizeWords(response.address_2)
                : null
            formData.value.suburbcity = response.suburbcity
                ? capitalizeWords(response.suburbcity)
                : null
            formData.value.stateprov = response.stateprov
            formData.value.postcode = response.postcode
            formData.value.lat = response.lat
            formData.value.lng = response.lng
        }
    } catch (e) {
        console.error(e)
    }
}

const capitalizeWords = str =>
    str.toLowerCase().replace(/\b\w/g, char => char.toUpperCase())

const closeDialog = () => {
    isDialogVisible.value = false
    formData.value = { ...emptyForm }
    selectedAddress.value = null
    loadingAddresses.value = false
    errors.value = {}
}

onMounted(() => {
    if (props.data?.id) {
        formData.value = { ...props.data }
    }
    propsData.value = { ...props.data }
    isNewAddress.value = !(props.data?.id || props.data?.address_1?.length)
    window.addEventListener("keydown", e => {
        if (e.key === "Escape") closeDialog()
    })
})

onBeforeUnmount(() => {
    formData.value = { ...emptyForm }
    window.removeEventListener("keydown", e => {
        if (e.key === "Escape") closeDialog()
    })
})

defineExpose({ isDialogVisible })
</script>
