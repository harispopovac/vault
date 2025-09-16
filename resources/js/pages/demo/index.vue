<template>
    <VContainer fluid>
        <VRow>
            <VCol cols="12">
                <VCard>
                    <VCardText class="d-flex align-center justify-space-between"
                    >
                        <div>
                            <h4 class="text-h4 mb-2">⚡ Base Demo Module</h4>
                            <p class="text-body-1 mb-0">
                                This page demonstrates the base demo module
                                functionality as a reusable package. It displays
                                user data with standard features like
                                pagination, sorting, and basic actions.
                            </p>
                        </div>

                        <VChip color="success" variant="tonal" size="large">
                            <VIcon icon="tabler-package" start />
                            Base Module
                        </VChip>
                    </VCardText>
                </VCard>
            </VCol>
        </VRow>

        <VRow class="mt-6">
            <VCol cols="12">
                <!-- Base Demo Module - Clean and Simple -->
                <DemoUsersTable />
            </VCol>
        </VRow>

        <VRow class="mt-6">
            <VCol cols="12" md="6">
                <VCard>
                    <VCardText>
                        <h5 class="text-h5 mb-3">📦 Base Features</h5>
                        <VList lines="two">
                            <VListItem
                                prepend-icon="tabler-table"
                                title="Data Display"
                                subtitle="Clean table with user information"
                            />
                            <VListItem
                                prepend-icon="tabler-refresh"
                                title="Data Refresh"
                                subtitle="Reload data with refresh button"
                            />
                            <VListItem
                                prepend-icon="tabler-eye"
                                title="Basic Actions"
                                subtitle="View and edit user actions"
                            />
                            <VListItem
                                prepend-icon="tabler-shield-check"
                                title="Authentication"
                                subtitle="Bearer token authentication integrated"
                            />
                        </VList>
                    </VCardText>
                </VCard>
            </VCol>

            <VCol cols="12" md="6">
                <VCard>
                    <VCardText>
                        <h5 class="text-h5 mb-3">🔧 Module Architecture</h5>
                        <VList lines="two">
                            <VListItem
                                prepend-icon="tabler-server-2"
                                title="Laravel Backend"
                                subtitle="DemoController with RESTful endpoints"
                            />
                            <VListItem
                                prepend-icon="tabler-brand-vue"
                                title="Vue Frontend"
                                subtitle="DemoUsersTable.vue with Vuexy theming"
                            />
                            <VListItem
                                prepend-icon="tabler-api"
                                title="API Service"
                                subtitle="demoService.js with axios integration"
                            />
                            <VListItem
                                prepend-icon="tabler-box"
                                title="Installable Package"
                                subtitle="Composer & NPM packages for easy installation"
                            />
                        </VList>
                    </VCardText>
                </VCard>
            </VCol>
        </VRow>

        <VRow class="mt-6">
            <VCol cols="12">
                <VCard variant="outlined" color="warning">
                    <VCardText>
                        <div class="d-flex align-center gap-3">
                            <VIcon
                                icon="tabler-bulb"
                                size="24"
                                color="warning"
                            />
                            <div>
                                <h6 class="text-h6">
                                    Want to see more advanced features?
                                </h6>
                                <p class="text-body-2 mb-0">
                                    Check out the Extended Demo to see how this
                                    base module can be customized with slots,
                                    props, events, and additional functionality
                                    for project-specific needs.
                                </p>
                            </div>
                            <VSpacer />
                            <VBtn
                                color="warning"
                                variant="tonal"
                                :to="{ name: 'ExtendedDemo' }"
                                append-icon="tabler-arrow-right"
                            >
                                View Extended Demo
                            </VBtn>
                        </div>
                    </VCardText>
                </VCard>
            </VCol>
        </VRow>
    </VContainer>
</template>

<script setup>
import { DemoUsersTable } from "demo-module-frontend"

definePage({
    name: "Demo",
    meta: {
        auth: true,
        layout: "default",
    },
})
</script>

<style scoped>
.v-container {
    max-width: 1200px;
}
</style>
