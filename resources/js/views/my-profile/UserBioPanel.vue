<template>
    <VRow>
        <!-- SECTION User Details -->
        <VCol cols="12">
            <VCard v-if="props.userData">
                <VCardText class="text-center pt-12 pb-4">
                    <!-- 👉 Avatar -->
                    <VAvatar
                        rounded
                        :size="100"
                        color="primary"
                        variant="tonal"
                    >
                        <VImg
                            v-if="props.userData.photo"
                            :src="props.userData.photo"
                        />
                        <span v-else class="text-5xl font-weight-medium">
                            {{ getUserInitials(props.userData.fullName) }}
                        </span>

                        <UploadPhoto
                            :model-id="props.userData.id"
                            endpoint="UPLOAD_MY_PHOTO"
                            @update="emit('getUserData')"
                            @update-data="updateUserData"
                        />
                    </VAvatar>

                    <!-- 👉 User fullName -->
                    <h5 class="text-h5 mt-4">
                        {{ props.userData.fullName }}
                    </h5>

                    <!-- 👉 Teams -->
                    <div class="d-flex flex-wrap gap-2 mt-4 mb09">
                        <VChip
                            v-for="team in props.userData.teams"
                            :key="team.id"
                            color="primary"
                            size="small"
                            label
                        >
                            {{ team.name }}
                        </VChip>
                    </div>
                </VCardText>

                <VCardText>
                    <VDivider class="mb-4 mt-0" />

                    <!-- 👉 User Details list -->
                    <VList class="card-list mt-2">
                        <VListItem>
                            <VListItemTitle>
                                <h6 class="text-h6">
                                    Date of Birth:
                                    <div
                                        v-if="props.userData.dob"
                                        class="d-inline-block text-capitalize text-body-1"
                                    >
                                        {{
                                            moment(props.userData.dob).format(
                                                "D MMM YYYY",
                                            )
                                        }}
                                    </div>
                                </h6>
                            </VListItemTitle>
                        </VListItem>

                        <VListItem>
                            <VListItemTitle>
                                <h6 class="text-h6">
                                    Gender:
                                    <div class="d-inline-block text-capitalize text-body-1"
                                    >
                                        <VChip
                                            v-if="props.userData.gender === 'M'"
                                            size="small"
                                        >Male</VChip
                                        >
                                        <VChip
                                            v-if="props.userData.gender === 'F'"
                                            size="small"
                                        >Female</VChip
                                        >
                                    </div>
                                </h6>
                            </VListItemTitle>
                        </VListItem>

                        <VListItem>
                            <VListItemTitle>
                                <span class="text-h6"> Email: </span>
                                <span class="text-body-1">
                                    {{ props.userData.email }}
                                </span>
                            </VListItemTitle>
                        </VListItem>

                        <VListItem>
                            <VListItemTitle>
                                <h6 class="text-h6">
                                    Phone:
                                    <div class="d-inline-block text-body-1 text-capitalize"
                                    >
                                        {{ props.userData.phone }}
                                    </div>
                                </h6>
                            </VListItemTitle>
                        </VListItem>

                        <VListItem>
                            <VListItemTitle>
                                <h6 class="text-h6">
                                    Timezone:
                                    <div class="d-inline-block text-body-1 text-capitalize"
                                    >
                                        {{
                                            formattedTimezone(
                                                props.userData?.timezone,
                                            )
                                        }}
                                    </div>
                                </h6>
                            </VListItemTitle>
                        </VListItem>
                    </VList>
                </VCardText>

                <!-- 👉 Edit button -->
                <VCardText class="d-flex justify-center gap-x-4">
                    <StoreUpdateUserDialog
                        :me="me"
                        :data="{ ...props.userData }"
                        activator-type="edit-button"
                        @get-user="emit('getUserData')"
                    />
                </VCardText>
            </VCard>
        </VCol>
        <!-- !SECTION -->
    </VRow>
</template>

<script setup>
import StoreUpdateUserDialog from "@/views/my-profile/dialogs/StoreUpdateUserDialog.vue"

import moment from "moment"

const props = defineProps({
    userData: {
        type: Object,
        required: true,
    },
    me: {
        type: Boolean,
        default: false,
    },
})

const emit = defineEmits(["getUserData"])

const updateUserData = data => {
    if (data.photo) {
        useAuthStore().setUserAvatar(data.photo)
    }

    const me = useCookie("user").value
    if (me.id === data.id) {
        useCookie("user").value = JSON.stringify(data)
    }
}

const formattedTimezone = timezone => {
    if (!timezone) return ""

    return timezone
        .replace(/_/g, " ") // Replace underscores with spaces
        .replace(/\//g, " / ") // Add spaces around the slash
}

const getUserInitials = fullName => {
    if (!fullName) return ""
    const [firstName = "", lastName = ""] = fullName.split(" ")
    
    return firstName.charAt(0) + lastName.charAt(0)
}
</script>

<style lang="scss" scoped>
.card-list {
    --v-card-list-gap: 0.5rem;
}

.text-capitalize {
    text-transform: capitalize !important;
}
</style>
