<script setup>
import { layoutConfig } from '@layouts'
import { can } from '@layouts/plugins/casl'
import { useLayoutConfigStore } from '@layouts/stores/config'
import { getDynamicI18nProps } from '@layouts/utils'

const props = defineProps({
  item: {
    type: null,
    required: true,
  },
})

const configStore = useLayoutConfigStore()
const shallRenderIcon = configStore.isVerticalNavMini()

const checkAdmin = (item, user) => {
    if (item.admin) {
        return user?.account_type === "sysadmin";
    }

    return true;
}

const checkAccess = item => {
    const user = useCookie("user").value

    if (item.admin) {
        return checkAdmin(item, user)
    }

    if (item.access) {
        if (item.access.includes('full_access')) {
            if (user?.full_access) {
                return true
            }
        }
        if (item.access.includes('personal_access')) {
            if (user?.personal_access) {
                return true
            }
        }
        if (item.access.includes('personal_plus_access')) {
            if (user?.personal_plus_access) {
                return true
            }
        }
        if (item.access.includes('business_access')) {
            if (user?.business_access) {
                return true
            }
        }
        if (item.access.includes('business_plus_access')) {
            if (user?.business_plus_access) {
                return true
            }
        }
        return false;
    }

    return true;
}
</script>

<template>
  <li
    v-if="can(item.action, item.subject) && checkAccess(item)"
    class="nav-section-title"
  >
    <div class="title-wrapper">
      <Transition
        name="vertical-nav-section-title"
        mode="out-in"
      >
        <Component
          :is="shallRenderIcon ? layoutConfig.app.iconRenderer : layoutConfig.app.i18n.enable ? 'i18n-t' : 'span'"
          :key="shallRenderIcon"
          :class="shallRenderIcon ? 'placeholder-icon' : 'title-text'"
          v-bind="{ ...layoutConfig.icons.sectionTitlePlaceholder, ...getDynamicI18nProps(item.heading, 'span') }"
        >
          {{ !shallRenderIcon ? item.heading : null }}
        </Component>
      </Transition>
    </div>
  </li>
</template>
