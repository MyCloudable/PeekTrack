<template>

    <div class="input-group-outline mt-4">
        <label for="">Crew Type</label>
        <Select2 :options="props.crewTypes" :settings="select2Settings" v-model="formData.crew_type_id" />
    </div>
    <div class="input-group-outline mt-4">
        <label for="">Select superintendent</label>
        <Select2 :options="superIntendents" :settings="select2Settings" v-model="formData.superintendentId" />
    </div>
    <div class="input-group-outline mt-4">
        <label for="">Select crew members</label>
        <Select2 :options="crewMembers" :settings="select2SettingsCrews" v-model="formData.crew_members" />
    </div>

    <div class="col-md-12">
        <button class="btn btn-warning btn-md mt-4" @click="submit">Submit</button>
    </div>

</template>

<script setup>
import { ref, onMounted } from 'vue'

import { useToast } from "vue-toastification"

const props = defineProps({
    users: Object,
    crewTypes: Object,
})

let select2Settings = ref({
    'width': '100%',
})
let select2SettingsCrews = ref({
    'width': '100%',
    multiple: true
})

const toast = useToast()


let crewMembers = ref([])
let superIntendents = ref([])


let formData = ref({})

onMounted(() => {

    props.users.map(user => {
        (user.role_id == 3) ? superIntendents.value.push(user) : '';

        (user.role_id == 6) ? crewMembers.value.push(user) : '';

    })
})

const submit = async () => {
  try {
    const response = await axios.post(`/crews`, {
        crew_type_id: formData.value.crew_type_id,
        superintendentId: formData.value.superintendentId,
        crew_members: formData.value.crew_members

    })

    if (response.data.success) {
      toast.success(response.data.message)
      setTimeout(() => {
        window.location.href = response.data.redirect
      }, 3000)
    } else {
      toast.error('Failed to create crew')
    }
  } catch (error) {
    let errorMessage = error.response.data.message

    errorMessage ? toast.error(errorMessage) : 'Something went wrong'
  }
}


</script>

<style>

.select2-container--default .select2-results__option[aria-selected=true]{
  color: black !important;
}
.select2-container--default .select2-selection--multiple .select2-selection__choice{
  color: black !important;
}

</style>