import FileUploadDialog from './src/components/FileUploadDialog.vue'
import FileUploadDialogContent from './src/components/FileUploadDialogContent.vue'
import FileUploadWidget from './src/components/FileUploadWidget.vue'

// Import and re-export services
export * from './src/services/fileServices.js'

export default {
  install(app) {
    app.component('FileUploadWidget', FileUploadWidget)
    app.component('FileUploadDialog', FileUploadDialog)
    app.component('FileUploadDialogContent', FileUploadDialogContent)
  }
}

// Export file upload components
export { FileUploadDialog, FileUploadDialogContent, FileUploadWidget }

