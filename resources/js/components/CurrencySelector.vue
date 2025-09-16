<template>
    <AppAutocomplete
        ref="currencySelector"
        v-model="value"
        :items="currencies"
        :item-title="itemTitle"
        item-value="code"
        :placeholder="placeholder"
        :label="label"
        :prepend-inner-icon="icon"
        :error="Boolean(errors?.length)"
        :error-messages="errors"
        :density="density"
        @focus="showSelectedValue = false"
        @blur="showSelectedValue = true"
    >
        <!-- Custom Dropdown Items -->
        <template #item="{ item, props }">
            <VListItem
                v-bind="props"
                :title="item.raw[selectionItem]"
                @click="blur"
            />
        </template>

        <!-- Custom Styled Selected Value -->
        <template #selection="{ item }">
            <span v-if="selectedItem === 'custom' && showSelectedValue"
            >{{ item.raw.symbol_native }}
                <span v-if="item.raw.code">({{ item.raw.code }})</span></span
            >
            <span v-else-if="showSelectedValue">{{
                item.raw[selectedItem]
            }}</span>
        </template>
    </AppAutocomplete>
</template>

<script setup>
import AppAutocomplete from "@core/components/app-form-elements/AppAutocomplete.vue"

defineProps({
    errors: {
        type: Array,
        default: () => [],
    },
    density: {
        type: String,
        default: "comfortable",
    },
    icon: {
        type: String,
        default: "tabler-currency-dollar",
    },
    label: {
        type: String,
        default: "Currency",
    },
    placeholder: {
        type: String,
        default: "Select Currency",
    },
    itemTitle: {
        type: String,
        default: "formatted_name",
    },
    selectionItem: {
        type: String,
        default: "formatted_name",
    },
    selectedItem: {
        type: String,
        default: "formatted_name",
    },
})

const emit = defineEmits(["update:modelValue", "setCurrencies"])

const value = ref("")
const currencies = inject("currencies", ref([]))
const showSelectedValue = ref(true)

const currencySelector = ref(null)

watch(value, () => {
    if (currencySelector.value.$el.querySelector("input")) {
        currencySelector.value.$el.querySelector("input").blur()
        showSelectedValue.value = true
    }
})

const blur = () => {
    if (currencySelector.value.$el.querySelector("input")) {
        currencySelector.value.$el.querySelector("input").blur()
        showSelectedValue.value = true
    }
}

watch(
    () => general.currencies,
    () => {
        currencies.value = general.currencies
    },
)

onMounted(() => {
    currencies.value = general.currencies

    getCurrencies()
})

const getCurrencies = async () => {
    currencies.value = await $api($endpoint("GET_CURRENCIES"), {
        method: "GET",
    })

    general.setCurrencies = currencies.value

    emit("setCurrencies", currencies.value)
}

watch(value, () => {
    emit("update:modelValue", value.value)
})
</script>
