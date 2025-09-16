<template>
    <VContainer>
        <VCard>
            <VCardTitle class="d-flex align-center">
                <span>Addresses</span>
                <VSpacer />
                <StoreUpdateAddressDialog
                    activator-type="button"
                    :data="newAddressData"
                    @update:address="onSaved"
                />
            </VCardTitle>
            <VCardText>
                <VDataTable
                    :headers="headers"
                    :items="items"
                    :loading="loading"
                >
                    <template #item.actions="{ item }">
                        <StoreUpdateAddressDialog
                            activator-type="icon"
                            :data="item"
                            @update:address="onSaved"
                        />
                        <VBtn
                            icon
                            size="small"
                            variant="text"
                            color="error"
                            @click="showConfirmDialog(item)"
                        >
                            <VIcon icon="tabler-trash" />
                        </VBtn>
                    </template>
                </VDataTable>
            </VCardText>
        </VCard>
        <ConfirmDialog
            :is-dialog-visible="isConfirmVisible"
            question="Are you sure you want to delete this address?"
            @confirm="performDelete"
            @close="isConfirmVisible = false"
        />
    </VContainer>
</template>

<script setup>
import { onMounted, ref } from "vue"
import addressService from "../services/addressService.js"
import StoreUpdateAddressDialog from "./StoreUpdateAddressDialog.vue"

const items = ref([])
const loading = ref(false)
const newAddressData = ref({ id: null })
const isConfirmVisible = ref(false)
const itemPendingDelete = ref(null)

const headers = [
    { title: "ID", key: "id" },
    { title: "Address 1", key: "address_1" },
    { title: "Address 2", key: "address_2" },
    { title: "Suburb", key: "suburbcity" },
    { title: "State", key: "stateprov" },
    { title: "Postcode", key: "postcode" },
    { title: "Country", key: "country" },
    { title: "Actions", key: "actions", sortable: false, align: "end" },
]

const fetchItems = async () => {
    loading.value = true
    try {
        const res = await addressService.getAddresses()

        items.value = res ?? []
    } finally {
        loading.value = false
    }
}

// Row-level dialogs handle their own open state via activator icons

const onSaved = async () => {
    await fetchItems()
}

const showConfirmDialog = item => {
    itemPendingDelete.value = { ...item }
    isConfirmVisible.value = true
}

const performDelete = async () => {
    console.log(itemPendingDelete.value)
    if (!itemPendingDelete.value) return
    try {
        await addressService.deleteAddress(itemPendingDelete.value.id)
        await fetchItems()
    } finally {
        isConfirmVisible.value = false
        itemPendingDelete.value = null
    }
}

onMounted(fetchItems)
</script>
