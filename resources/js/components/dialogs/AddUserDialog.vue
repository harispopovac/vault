<template>
    <VDialog
        :model-value="modelValue"
        max-width="600"
        persistent
        @update:model-value="$emit('update:modelValue', $event)"
    >
        <VCard>
            <VCardText class="d-flex align-center justify-space-between">
                <div>
                    <h4 class="text-h4 mb-1">Add New User</h4>
                    <p class="text-body-2 mb-0">
                        Create a new user account in the system
                    </p>
                </div>

                <VBtn icon variant="text" @click="closeDialog">
                    <VIcon icon="tabler-x" />
                </VBtn>
            </VCardText>

            <VDivider />

            <VCardText>
                <VForm
                    ref="formRef"
                    v-model="isFormValid"
                    @submit.prevent="handleSubmit"
                >
                    <VRow>
                        <!-- First Name -->
                        <VCol cols="12" md="6">
                            <VTextField
                                v-model="formData.firstName"
                                label="First Name"
                                placeholder="Enter first name"
                                :rules="[requiredValidator]"
                                prepend-inner-icon="tabler-user"
                                :disabled="isLoading"
                            />
                        </VCol>

                        <!-- Last Name -->
                        <VCol cols="12" md="6">
                            <VTextField
                                v-model="formData.lastName"
                                label="Last Name"
                                placeholder="Enter last name"
                                :rules="[requiredValidator]"
                                prepend-inner-icon="tabler-user"
                                :disabled="isLoading"
                            />
                        </VCol>

                        <!-- Email -->
                        <VCol cols="12">
                            <VTextField
                                v-model="formData.email"
                                label="Email Address"
                                placeholder="Enter email address"
                                type="email"
                                :rules="[requiredValidator, emailValidator]"
                                prepend-inner-icon="tabler-mail"
                                :disabled="isLoading"
                            />
                        </VCol>

                        <!-- Role -->
                        <VCol cols="12" md="6">
                            <VSelect
                                v-model="formData.role"
                                label="Role"
                                placeholder="Select user role"
                                :items="roleOptions"
                                :rules="[requiredValidator]"
                                prepend-inner-icon="tabler-shield"
                                :disabled="isLoading"
                            />
                        </VCol>

                        <!-- Department -->
                        <VCol cols="12" md="6">
                            <VSelect
                                v-model="formData.department"
                                label="Department"
                                placeholder="Select department"
                                :items="departmentOptions"
                                :rules="[requiredValidator]"
                                prepend-inner-icon="tabler-building"
                                :disabled="isLoading"
                            />
                        </VCol>

                        <!-- Password -->
                        <VCol cols="12" md="6">
                            <VTextField
                                v-model="formData.password"
                                label="Password"
                                placeholder="Enter password"
                                :type="showPassword ? 'text' : 'password'"
                                :rules="[requiredValidator, passwordValidator]"
                                prepend-inner-icon="tabler-lock"
                                :append-inner-icon="
                                    showPassword
                                        ? 'tabler-eye-off'
                                        : 'tabler-eye'
                                "
                                :disabled="isLoading"
                                @click:append-inner="
                                    showPassword = !showPassword
                                "
                            />
                        </VCol>

                        <!-- Confirm Password -->
                        <VCol cols="12" md="6">
                            <VTextField
                                v-model="formData.confirmPassword"
                                label="Confirm Password"
                                placeholder="Confirm password"
                                :type="
                                    showConfirmPassword ? 'text' : 'password'
                                "
                                :rules="[
                                    requiredValidator,
                                    confirmPasswordValidator,
                                ]"
                                prepend-inner-icon="tabler-lock"
                                :append-inner-icon="
                                    showConfirmPassword
                                        ? 'tabler-eye-off'
                                        : 'tabler-eye'
                                "
                                :disabled="isLoading"
                                @click:append-inner="
                                    showConfirmPassword = !showConfirmPassword
                                "
                            />
                        </VCol>

                        <!-- Send Welcome Email -->
                        <VCol cols="12">
                            <VCheckbox
                                v-model="formData.sendWelcomeEmail"
                                label="Send welcome email to the user"
                                :disabled="isLoading"
                            />
                        </VCol>
                    </VRow>
                </VForm>

                <!-- Error Alert -->
                <VAlert
                    v-if="errorMessage"
                    type="error"
                    class="mt-4"
                    closable
                    @click:close="errorMessage = ''"
                >
                    {{ errorMessage }}
                </VAlert>

                <!-- Success Alert -->
                <VAlert
                    v-if="successMessage"
                    type="success"
                    class="mt-4"
                    closable
                    @click:close="successMessage = ''"
                >
                    {{ successMessage }}
                </VAlert>
            </VCardText>

            <VDivider />

            <VCardActions class="pa-4">
                <VSpacer />

                <VBtn
                    variant="outlined"
                    :disabled="isLoading"
                    @click="closeDialog"
                >
                    Cancel
                </VBtn>

                <VBtn
                    color="primary"
                    :loading="isLoading"
                    :disabled="!isFormValid"
                    @click="handleSubmit"
                >
                    <VIcon icon="tabler-plus" start />
                    Add User
                </VBtn>
            </VCardActions>
        </VCard>
    </VDialog>
</template>

<script setup>
import { createUser } from "@/services/extendedDemoService.js"
import { reactive, ref, watch } from "vue"

// Props & Emits
const props = defineProps({
    modelValue: {
        type: Boolean,
        required: true,
    },
})

const emit = defineEmits(["update:modelValue", "user-added"])

// Form data
const formRef = ref()
const isFormValid = ref(false)
const isLoading = ref(false)
const showPassword = ref(false)
const showConfirmPassword = ref(false)
const errorMessage = ref("")
const successMessage = ref("")

const formData = reactive({
    firstName: "",
    lastName: "",
    email: "",
    role: "",
    department: "",
    password: "",
    confirmPassword: "",
    sendWelcomeEmail: true,
})

// Options for dropdowns
const roleOptions = [
    { title: "Administrator", value: "admin" },
    { title: "Manager", value: "manager" },
    { title: "User", value: "user" },
    { title: "Guest", value: "guest" },
]

const departmentOptions = [
    { title: "Information Technology", value: "it" },
    { title: "Human Resources", value: "hr" },
    { title: "Finance", value: "finance" },
    { title: "Marketing", value: "marketing" },
    { title: "Sales", value: "sales" },
    { title: "Operations", value: "operations" },
]

// Validation rules
const requiredValidator = value => !!value || "This field is required"

const emailValidator = value => {
    const emailPattern = /^[^\s@]+@[^\s@][^\s.@]*\.[^\s@]+$/
    
    return emailPattern.test(value) || "Please enter a valid email address"
}

const passwordValidator = value => {
    if (!value) return "Password is required"
    if (value.length < 8) return "Password must be at least 8 characters"
    
    return true
}

const confirmPasswordValidator = value => {
    if (!value) return "Please confirm your password"
    if (value !== formData.password) return "Passwords do not match"
    
    return true
}

// Methods
const resetForm = () => {
    if (formRef.value) {
        formRef.value.reset()
    }

    // Reset form data
    Object.assign(formData, {
        firstName: "",
        lastName: "",
        email: "",
        role: "",
        department: "",
        password: "",
        confirmPassword: "",
        sendWelcomeEmail: true,
    })

    // Reset state
    errorMessage.value = ""
    successMessage.value = ""
    isLoading.value = false
    showPassword.value = false
    showConfirmPassword.value = false
}

const closeDialog = () => {
    emit("update:modelValue", false)

    // Reset form after a short delay to avoid animation issues
    setTimeout(resetForm, 300)
}

const handleSubmit = async () => {
    if (!formRef.value) return

    const { valid } = await formRef.value.validate()
    if (!valid) return

    isLoading.value = true
    errorMessage.value = ""
    successMessage.value = ""

    try {
        // Prepare user data
        const userData = {
            fname: formData.firstName,
            sname: formData.lastName,
            email: formData.email,
            role: formData.role,
            department: formData.department,
            password: formData.password,
            password_confirmation: formData.confirmPassword,
            send_welcome_email: formData.sendWelcomeEmail,
        }

        // Call the API to create user
        const newUser = await createUser(userData)

        successMessage.value = "User created successfully!"

        // Emit the event with the new user data
        emit("user-added", newUser)

        // Close dialog after a short delay to show success message
        setTimeout(() => {
            closeDialog()
        }, 1500)
    } catch (error) {
        console.error("Error creating user:", error)

        if (error.response?.data?.message) {
            errorMessage.value = error.response.data.message
        } else if (error.response?.data?.errors) {
            // Handle validation errors
            const errors = Object.values(error.response.data.errors).flat()

            errorMessage.value = errors.join(", ")
        } else {
            errorMessage.value = "Failed to create user. Please try again."
        }
    } finally {
        isLoading.value = false
    }
}

// Watch for dialog open/close to reset form
watch(
    () => props.modelValue,
    newValue => {
        if (!newValue) {
            // Dialog is closing, reset form
            setTimeout(resetForm, 300)
        }
    },
)
</script>

<style scoped>
.v-card {
    overflow: visible;
}
</style>
