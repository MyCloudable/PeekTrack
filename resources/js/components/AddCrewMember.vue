<template>

<span class="badge badge-primary ms-1">
    <i class="fa fa-plus-circle cursor-pointer" aria-hidden="true" @click="getAllUsers()"></i>
</span>

</template>

<script setup>
import { onUnmounted } from 'vue'
const emit = defineEmits(['get-all-users', 'unmount-component'])

// onUnmounted(() => emit('unmount-component'))

const getAllUsers = () => {
    axios.get('/getusers-for-crewentry')
        .then(res => {
            emit('get-all-users', res.data)
        })
        .catch(err => console.log(err))
}

</script>

<style>
</style>