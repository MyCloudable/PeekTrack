<template>

    <i class="fas fa-trash-restore cursor-pointer recover-icon" @click="handleRecoverDelete()"></i>

</template>


<script setup>

import { useToast } from "vue-toastification"
import { useLoading } from '../../composables/useLoading'
import {useRecovery} from '../../composables/useRecovery'

const toast = useToast()
const { isLoading, setLoading } = useLoading()
const { recover } = useRecovery()

const props = defineProps({
    crewId: Number,
})

const handleRecoverDelete = () => {
    if (confirm(`Are you sure you want to recover this record?`)) {
    const res = recover('crew', props.crewId)
    if (res) {
        toast.success("Record have been recoverd successfully")
        setTimeout(() => {
            window.location.reload();
        }, 1000);
    }else{
        toast.error("Something went wrong")
    }
  }

}


// Export the method so it can be accessed globally
// window.handleRecoverDelete = handleRecoverDelete
</script>