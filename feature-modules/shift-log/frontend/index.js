// Import components
import MyShifts from "./src/components/MyShifts.vue"
import ShiftLog from "./src/components/ShiftLog.vue"
import MyShiftDetailsDialog from "./src/components/dialogs/MyShiftDetailsDialog.vue"
import ShiftLogDetailsDialog from "./src/components/dialogs/ShiftLogDetailsDialog.vue"
import StoreUpdateShiftLogDialog from "./src/components/dialogs/StoreUpdateShiftLogDialog.vue"

// Import services
import shiftLogService from "./src/services/shiftLogService.js"

// Vue 3 plugin for registering components globally
const ShiftLogPlugin = {
    install(app, options = {}) {
        // Register components globally
        app.component("ShiftLog", ShiftLog)
        app.component("MyShifts", MyShifts)
        app.component("StoreUpdateShiftLogDialog", StoreUpdateShiftLogDialog)
        app.component("ShiftLogDetailsDialog", ShiftLogDetailsDialog)
        app.component("MyShiftDetailsDialog", MyShiftDetailsDialog)

        // Provide shift log service globally
        app.provide("shiftLogService", shiftLogService)

        // Optional: Add configuration
        if (options.config) {
            app.provide("shiftLogConfig", options.config)
        }
    },
}

// Individual component exports
export {
    MyShiftDetailsDialog,
    MyShifts,
    ShiftLog,
    ShiftLogDetailsDialog,
    StoreUpdateShiftLogDialog,
    shiftLogService,
}

// Default export for the plugin
export default ShiftLogPlugin

// Named export for the service
export { shiftLogService as service }
