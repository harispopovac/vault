<template>
    <AppTextField
        v-model="displayTime"
        :placeholder="placeholder"
        :label="label"
        type="text"
        class="time-input"
        @input="handleInput"
        @keydown="handleKeyDown"
        @click="selectPart"
        @focus="focusField"
        @blur="formatTime"
    />
</template>

<script setup>
import { ref, watch } from "vue"

const props = defineProps({
    modelValue: {
        type: String,
        default: "",
    },
    label: {
        type: String,
        default: "Time",
    },
    placeholder: {
        type: String,
        default: "00:00:00",
    },
    duration: {
        type: Boolean,
        default: false, // Default to "time of day" mode
    },
})

const emit = defineEmits(["update:modelValue"])

const internalTime = ref(props.modelValue || "00:00:00")
const displayTime = ref(internalTime.value)
let selectedPart = "hours"

watch(
    () => props.modelValue,
    newVal => {
        internalTime.value = newVal || "00:00:00"
        displayTime.value = internalTime.value
    },
)

function handleInput(event) {
    const input = event.target
    const cursorPosition = input.selectionStart
    const parts = displayTime.value.split(":")

    if (parts.length !== 3) {
        parts[0] = "00"
        parts[1] = "00"
        parts[2] = "00"
    }

    // Update the correct part based on selection
    if (selectedPart === "hours") {
        parts[0] = updatePart(
            parts[0],
            input.value.slice(0, cursorPosition),
            props.duration ? Infinity : 23,
        )
    } else if (selectedPart === "minutes") {
        parts[1] = updatePart(
            parts[1],
            input.value.slice(parts[0].length + 1, cursorPosition),
            59,
        )
    } else if (selectedPart === "seconds") {
        parts[2] = updatePart(
            parts[2],
            input.value.slice(
                parts[0].length + parts[1].length + 2,
                cursorPosition,
            ),
            59,
        )
    }

    displayTime.value = parts.join(":")

    // Adjust cursor to be at the end of the typed part
    const adjustedCursorPosition = adjustCursor(input, selectedPart, parts)

    setTimeout(
        () =>
            input.setSelectionRange(
                adjustedCursorPosition,
                adjustedCursorPosition,
            ),
        0,
    )
}

function updatePart(currentValue, newValue, maxValue) {
    const numericValue = parseInt(newValue.replace(/\D/g, ""), 10) || 0

    return Math.min(numericValue, maxValue).toString().padStart(2, "0")
}

function adjustCursor(input, part, parts) {
    if (part === "hours") {
        return parts[0].length
    } else if (part === "minutes") {
        return parts[0].length + 1 + parts[1].length
    } else if (part === "seconds") {
        return parts[0].length + parts[1].length + 2 + parts[2].length
    }

    return input.selectionStart
}

function handleKeyDown(event) {
    const input = event.target

    // Handle Shift + Tab for backward navigation
    if (event.key === "Tab" && event.shiftKey) {
        event.preventDefault()
        if (selectedPart === "seconds") {
            moveToPart(input, "minutes")
        } else if (selectedPart === "minutes") {
            moveToPart(input, "hours")
        } else if (selectedPart === "hours") {
            input.blur() // Move focus back to the previous field
        }
    }

    // Handle Tab for forward navigation
    else if (event.key === "Tab") {
        event.preventDefault()
        if (selectedPart === "hours") {
            moveToPart(input, "minutes")
        } else if (selectedPart === "minutes") {
            moveToPart(input, "seconds")
        } else if (selectedPart === "seconds") {
            input.blur() // Move focus to the next field
        }
    }

    // Handle Arrow navigation
    else if (event.key === "ArrowRight") {
        if (selectedPart === "hours") {
            moveToPart(input, "minutes")
        } else if (selectedPart === "minutes") {
            moveToPart(input, "seconds")
        }
        event.preventDefault()
    } else if (event.key === "ArrowLeft") {
        if (selectedPart === "seconds") {
            moveToPart(input, "minutes")
        } else if (selectedPart === "minutes") {
            moveToPart(input, "hours")
        }
        event.preventDefault()
    }
}

function selectPart(event) {
    const input = event.target
    const cursorPosition = input.selectionStart

    const colonPositions = [
        displayTime.value.indexOf(":"),
        displayTime.value.lastIndexOf(":"),
    ]

    if (cursorPosition <= colonPositions[0]) {
        moveToPart(input, "hours")
    } else if (
        cursorPosition > colonPositions[0] &&
        cursorPosition <= colonPositions[1]
    ) {
        moveToPart(input, "minutes")
    } else {
        moveToPart(input, "seconds")
    }
}

function focusField(event) {
    const input = event.target

    moveToPart(input, "hours") // Always select hours when field gains focus
}

function moveToPart(input, part) {
    const colonPositions = [
        displayTime.value.indexOf(":"),
        displayTime.value.lastIndexOf(":"),
    ]

    selectedPart = part
    if (part === "hours") {
        input.setSelectionRange(0, colonPositions[0])
    } else if (part === "minutes") {
        input.setSelectionRange(colonPositions[0] + 1, colonPositions[1])
    } else if (part === "seconds") {
        input.setSelectionRange(
            colonPositions[1] + 1,
            displayTime.value.length,
        )
    }
}

function formatTime() {
    const parts = displayTime.value.split(":")

    parts[0] = parts[0].padStart(2, "0") // Hours
    parts[1] = parts[1].padStart(2, "0") // Minutes
    parts[2] = parts[2].padStart(2, "0") // Seconds

    if (!props.duration) {
        parts[0] = Math.min(parseInt(parts[0], 10), 23)
            .toString()
            .padStart(2, "0") // Clamp hours to 23 if duration is false
    }

    internalTime.value = parts.join(":")
    displayTime.value = internalTime.value
    emit("update:modelValue", internalTime.value)
}
</script>

<style lang="scss">
.time-input .v-field__input {
    padding-right: 30px; /* Adjust padding for custom styles */
}
</style>
