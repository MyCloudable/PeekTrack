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
        <button class="btn btn-warning btn-md mt-4" @click="submit">Update</button>
    </div>

</template>

<script setup>
import { ref, onMounted } from 'vue'

import { useToast } from "vue-toastification"

const props = defineProps({
    users: Object,
    crewTypes: Object,
    crew: Object,
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


let formData = ref({
    crew_members: []
})

onMounted(() => {

    let crew_members = props.crew.crew_members.map(member => parseInt(member, 10))

    props.crewTypes.map(crewType => {
        (props.crew.crew_type_id == crewType.id) ? formData.value.crew_type_id = crewType.id : ''
    })

    props.users.map(user => {
        if(user.role_id == 3){
            superIntendents.value.push(user);
            (props.crew.superintendentId == user.id) ? formData.value.superintendentId = user.id : ''

        }

        if(user.role_id == 6){
            crewMembers.value.push(user);
            (crew_members.includes(user.id)) ? formData.value.crew_members.push(user.id) : ''
        }

    })
})

const submit = async () => {
  try {
    const response = await axios.put(`/crews/${props.crew.id}`, {
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

<style></style>