// Import components
import RosterCalendar from "./components/RosterCalendar.vue"
import StaffRosterTable from "./components/StaffRosterTable.vue"
import CopyDayDialog from "./components/dialogs/CopyDayDialog.vue"
import CopyRosterDialog from "./components/dialogs/CopyRosterDialog.vue"
import StoreUpdateRosterDialog from "./components/dialogs/StoreUpdateRosterDialog.vue"

// Import services
import * as staffRosterService from "./services/staffRosterService"

// Vue plugin
const StaffRosterPlugin = {
    install(app, options = {}) {
        // Register components globally
        app.component("StaffRosterTable", StaffRosterTable)
        app.component("RosterCalendar", RosterCalendar)
        app.component("StoreUpdateRosterDialog", StoreUpdateRosterDialog)
        app.component("CopyRosterDialog", CopyRosterDialog)
        app.component("CopyDayDialog", CopyDayDialog)

        // Provide the service globally if needed
        app.provide("staffRosterService", staffRosterService)

        // You can also add global properties
        app.config.globalProperties.$staffRosterService = staffRosterService

        // Handle plugin options
        if (options.components) {
            // Allow selective component registration
            const componentMap = {
                StaffRosterTable: StaffRosterTable,
                StoreUpdateRosterDialog: StoreUpdateRosterDialog,
            }

            Object.keys(options.components).forEach(componentName => {
                if (
                    options.components[componentName] &&
                    componentMap[componentName]
                ) {
                    app.component(componentName, componentMap[componentName])
                }
            })
        }
    },
}

// Export the plugin as default
export default StaffRosterPlugin

// Auto-install when used via CDN or script tag
if (typeof window !== "undefined" && window.Vue) {
    window.Vue.use(StaffRosterPlugin)
}
