import { $api } from '@/utils/api'
import { $endpoint } from '../utils/endpoints'

/**
 * Upload files
 */

export const uploadFiles = async data => {
    const response = await $api($endpoint('UPLOAD_FILES'), {
        method: 'POST',
        data
    })

    return response.data
}

/**
 * Upload temp files
 */
export const uploadTempFiles = async data => {
    const response = await $api($endpoint('UPLOAD_TEMP_FILES'), {
        method: 'POST',
        data
    })

    return response.data
}

/**
 * Remove temp file
 */
export const removeTempFile = async id => {
    const response = await $api($endpoint('REMOVE_TEMP_FILE'), {
        method: 'DELETE',
        body: { file_id: id }
    })

    return response.data
}

// Export default object for easier importing
export default {
    uploadFiles,
    uploadTempFiles,
    removeTempFile,
}
