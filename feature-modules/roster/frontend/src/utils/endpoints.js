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
    // Staff Roster
    STORE_STAFF_ROSTER: "staff-roster",
    UPDATE_STAFF_ROSTER: "staff-roster/{id}",
    DELETE_STAFF_ROSTER: "staff-roster/{id}",
    GET_STAFF_ROSTER: "staff-roster",
    GET_STAFF_LIST: "staff", // This would need to be implemented
    COPY_STAFF_ROSTER: "staff-roster/copy",
    COPY_DAY_ROSTER: "staff-roster/copy-day",
    CLEAR_DAY_ROSTER: "staff-roster/clear-day",
}

export { $endpoints }
