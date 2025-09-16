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
    // Files Management
    UPLOAD_FILES: "files/upload",
    UPLOAD_TEMP_FILES: "temp-files/upload",
    REMOVE_TEMP_FILE: "temp-files/remove",
}

export { $endpoints }
