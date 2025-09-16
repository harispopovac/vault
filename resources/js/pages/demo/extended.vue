<template>
    <VContainer fluid>
        <VRow>
            <VCol cols="12">
                <VCard>
                    <VCardText class="d-flex align-center justify-space-between"
                    >
                        <div>
                            <h4 class="text-h4 mb-2">
                                🔧 Extended Demo Module
                            </h4>
                            <p class="text-body-1 mb-0">
                                This page demonstrates how to extend the base
                                demo module with custom functionality,
                                additional UI elements, and project-specific
                                features using slots, props, and events.
                            </p>
                        </div>

                        <VChip color="primary" variant="tonal" size="large">
                            <VIcon icon="tabler-puzzle" start />
                            Extended
                        </VChip>
                    </VCardText>
                </VCard>
            </VCol>
        </VRow>

        <VRow class="mt-6">
            <VCol cols="12">
                <!-- Extended Demo Table with customizations -->
                <ExtendedDemoTable />
            </VCol>
        </VRow>

        <VRow class="mt-6">
            <VCol cols="12" md="6">
                <VCard>
                    <VCardText>
                        <h5 class="text-h5 mb-3">✨ Extension Features</h5>
                        <VList lines="two">
                            <VListItem
                                prepend-icon="tabler-layout-cards"
                                title="Custom UI Slots"
                                subtitle="Title, actions, and row-actions customization"
                            />
                            <VListItem
                                prepend-icon="tabler-settings"
                                title="Configurable Props"
                                subtitle="Custom headers, API endpoints, and toggleable features"
                            />
                            <VListItem
                                prepend-icon="tabler-bolt"
                                title="Event Handling"
                                subtitle="Custom user actions and interactions"
                            />
                            <VListItem
                                prepend-icon="tabler-api"
                                title="Extended API"
                                subtitle="Additional endpoints for create, delete, export"
                            />
                        </VList>
                    </VCardText>
                </VCard>
            </VCol>

            <VCol cols="12" md="6">
                <VCard>
                    <VCardText>
                        <h5 class="text-h5 mb-3">🛠️ Implementation Patterns</h5>
                        <VList lines="two">
                            <VListItem
                                prepend-icon="tabler-code"
                                title="Component Extension"
                                subtitle="ExtendedDemoTable.vue extends base functionality"
                            />
                            <VListItem
                                prepend-icon="tabler-server"
                                title="Controller Inheritance"
                                subtitle="ExtendedDemoController extends DemoController"
                            />
                            <VListItem
                                prepend-icon="tabler-route"
                                title="Custom Routes"
                                subtitle="demo-extended.php adds project-specific endpoints"
                            />
                            <VListItem
                                prepend-icon="tabler-database"
                                title="Service Extension"
                                subtitle="extendedDemoService.js with additional methods"
                            />
                        </VList>
                    </VCardText>
                </VCard>
            </VCol>
        </VRow>

        <VRow class="mt-6">
            <VCol cols="12">
                <VCard>
                    <VCardText>
                        <h5 class="text-h5 mb-3">📚 Documentation</h5>
                        <p class="text-body-1 mb-4">
                            For detailed information on creating and extending
                            modules, check out our comprehensive guides:
                        </p>

                        <div class="d-flex flex-wrap gap-4">
                            <VBtn
                                variant="tonal"
                                color="primary"
                                prepend-icon="tabler-book"
                                href="/docs/MODULE_CREATION_GUIDE.md"
                                target="_blank"
                            >
                                Module Creation Guide
                            </VBtn>

                            <VBtn
                                variant="tonal"
                                color="secondary"
                                prepend-icon="tabler-puzzle"
                                href="/docs/MODULE_EXTENSION_GUIDE.md"
                                target="_blank"
                            >
                                Extension Guide
                            </VBtn>

                            <VBtn
                                variant="outlined"
                                prepend-icon="tabler-brand-github"
                                href="https://github.com"
                                target="_blank"
                            >
                                View Source
                            </VBtn>
                        </div>
                    </VCardText>
                </VCard>
            </VCol>
        </VRow>
    </VContainer>
</template>

<script setup>
import ExtendedDemoTable from "@/components/ExtendedDemoTable.vue"

definePage({
    name: "ExtendedDemo",
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
