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
    // Shift Log - Admin endpoints
    SHIFT_LOG_CONFIG: "shift-log/config",
    SHIFT_LOG_INDEX: "shift-log",
    SHIFT_LOG_STORE: "shift-log",
    SHIFT_LOG_SHOW: "shift-log/{id}",
    SHIFT_LOG_UPDATE: "shift-log/{id}",
    SHIFT_LOG_DELETE: "shift-log/{id}",
    SHIFT_LOG_OPEN_SHIFT: "shift-log/open-shift/staff",

    // Shift Log - Staff My Shifts endpoints
    MY_SHIFTS_INDEX: "my-shifts",
    MY_SHIFTS_CHECK_IN: "my-shifts/check-in",
    MY_SHIFTS_CHECK_OUT: "my-shifts/check-out/{id}",
    MY_SHIFTS_OPEN_SHIFT: "my-shifts/open-shift",

    // Break management (when enabled)
    SHIFT_LOG_BREAKS_MANAGE: "shift-log/{id}/breaks",
    MY_SHIFTS_BREAKS_MANAGE: "my-shifts/{id}/breaks",

    // Photo upload
    SHIFT_LOG_UPLOAD_PHOTO: "shift-log/upload-photo",

    // Sites (if not already exists)
    GET_SITES: "sites",

    // Staff list
    GET_STAFF_LIST: "staff",
}

export { $endpoints }
