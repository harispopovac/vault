import DemoUsersTable from "./src/components/DemoUsersTable.vue"

export default {
    install(app) {
        app.component("DemoUsersTable", DemoUsersTable)
    },
}

// Also export components individually
export { DemoUsersTable }

// Keep backwards compatibility
export { DemoUsersTable as DemoPostsTable }
