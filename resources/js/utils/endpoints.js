export const $endpoint = (ENDPOINT, PARAMS = null) => {
    return PARAMS ? handleParams(ENDPOINT, PARAMS) : $endpoints[ENDPOINT]
}

const handleParams = (endpoint, params) => {
    let url = $endpoints[endpoint]
    for (const param in params) {
        url = url.replace("{" + param + "}", params[param])
    }

    return url
}

const $endpoints = {
    // Authentication
    LOGIN: "login",
    REGISTER: "register",
    LOGOUT: "logout",
    REQUEST_RESET_PASSWORD: "forgot-password",
    RESET_PASSWORD: "password-reset",
    VERIFY_USER: "verify-user",

    // Google Auth
    AUTH_GOOGLE_CALLBACK: "auth/google/callback",

    // UNLINK_GOOGLE: "auth/google/unlink",

    // Me
    ME: "me",
    UPDATE_ME: "me",
    UPLOAD_MY_PHOTO: "me/photo",
    CHANGE_PASSWORD: "change-password",

    // User Info
    ENROLL_USER_INFO: "user-info-enroll",
    VERIFY_USER_INFO: "user-info-verify",

    // Timezones
    GET_TIMEZONES: "timezones",

    // Repositories
    GET_REPOSITORIES: "repositories",
    STORE_REPOSITORY: "repositories",
    GET_REPOSITORY: "repositories/{id}",
    UPDATE_REPOSITORY: "repositories/{id}",
    DELETE_REPOSITORY: "repositories/{id}",

    // Prompt Templates
    INDEX_PROMPT_TEMPLATES: "prompt-templates",
    STORE_PROMPT_TEMPLATE: "prompt-templates",
    SHOW_PROMPT_TEMPLATE: "prompt-templates/{id}",
    UPDATE_PROMPT_TEMPLATE: "prompt-templates/{id}",
    DELETE_PROMPT_TEMPLATE: "prompt-templates/{id}",
    USE_PROMPT_TEMPLATE: "prompt-templates/{id}/use",

    // Triggers
    INDEX_TRIGGERS: "triggers",
    STORE_TRIGGER: "triggers",
    SHOW_TRIGGER: "triggers/{id}",
    UPDATE_TRIGGER: "triggers/{id}",
    DELETE_TRIGGER: "triggers/{id}",
    TOGGLE_TRIGGER: "triggers/{id}/toggle",

    // Prompts
    INDEX_PROMPTS: "prompts",
    STORE_PROMPT: "prompts",
    SHOW_PROMPT: "prompts/{id}",
    UPDATE_PROMPT: "prompts/{id}",
    DELETE_PROMPT: "prompts/{id}",

    // Developers
    INDEX_DEVELOPERS: "developers",
    SYNC_DEVELOPERS: "developers/sync",
    SYNC_REPOSITORY_DEVELOPERS: "developers/sync/{id}",
    UPDATE_DEVELOPER_ROLE: "developers/{id}/role",
    GET_DEVELOPER_ROLES: "developers/roles",
    GET_DEVELOPER_REPOSITORIES: "developers/repositories",

    // Roles
    INDEX_ROLES: "roles",
    STORE_ROLE: "roles",
    SHOW_ROLE: "roles/{id}",
    UPDATE_ROLE: "roles/{id}",
    DELETE_ROLE: "roles/{id}",
}
