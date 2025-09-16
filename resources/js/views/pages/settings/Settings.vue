<template>
  <VContainer fluid>
    <!-- Header Section -->
    <VRow>
      <VCol cols="12">
        <VCard>
          <VCardText class="d-flex align-center justify-space-between">
            <div>
              <h4 class="text-h4 mb-1">⚙️ Settings</h4>
              <p class="text-body-2 mb-0 text-medium-emphasis">
                Manage your organization settings and configurations
              </p>
            </div>
          </VCardText>
        </VCard>
      </VCol>
    </VRow>

    <!-- Settings Tabs -->
    <VRow class="mt-6">
      <VCol cols="12">
        <VTabs v-model="currentTab" class="v-tabs-pill">
          <VTab v-for="tab in tabs" :key="tab.value" :value="tab.value">
            <VIcon :size="18" :icon="tab.icon" class="me-1" />
            <span>{{ tab.title }}</span>
          </VTab>
        </VTabs>

        <VWindow
          v-model="currentTab"
          class="mt-6 disable-tab-transition"
          :touch="false"
        >
          <!-- Roles Tab -->
          <VWindowItem value="roles">
            <VCard>
              <VCardText>
                <Roles />
              </VCardText>
            </VCard>
          </VWindowItem>

          <!-- GitHub Integration Tab -->
          <VWindowItem value="github">
            <VCard>
              <VCardText>
                <div class="text-center py-12">
                  <VIcon
                    icon="tabler-brand-github"
                    size="80"
                    color="grey-lighten-1"
                    class="mb-4"
                  />
                  <h5 class="text-h5 text-grey mb-2">GitHub Integration</h5>
                  <p class="text-body-1 text-grey mb-4">
                    Manage your GitHub access tokens and webhook configurations
                  </p>
                  <VBtn color="primary" variant="outlined">
                    Coming Soon
                  </VBtn>
                </div>
              </VCardText>
            </VCard>
          </VWindowItem>

          <!-- Notifications Tab -->
          <VWindowItem value="notifications">
            <VCard>
              <VCardText>
                <div class="text-center py-12">
                  <VIcon
                    icon="tabler-bell"
                    size="80"
                    color="grey-lighten-1"
                    class="mb-4"
                  />
                  <h5 class="text-h5 text-grey mb-2">Notification Settings</h5>
                  <p class="text-body-1 text-grey mb-4">
                    Configure email notifications and alert preferences
                  </p>
                  <VBtn color="primary" variant="outlined">
                    Coming Soon
                  </VBtn>
                </div>
              </VCardText>
            </VCard>
          </VWindowItem>
        </VWindow>
      </VCol>
    </VRow>
  </VContainer>
</template>

<script setup>
import { ref } from 'vue'
import Roles from './roles/Roles.vue'

// Component state
const currentTab = ref('roles')

// Tabs configuration
const tabs = [
  {
    value: 'roles',
    title: 'Roles',
    icon: 'tabler-users-group',
  },
  {
    value: 'github',
    title: 'GitHub Integration',
    icon: 'tabler-brand-github',
  },
  {
    value: 'notifications',
    title: 'Notifications',
    icon: 'tabler-bell',
  },
]
</script>

<style scoped>
.v-container {
  max-width: 1400px;
}
</style>