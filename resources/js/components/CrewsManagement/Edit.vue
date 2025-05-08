<template>
    <div class="input-group-outline mt-4">
        <label for="">Crew Type</label>
        <Select2 :options="formattedCrewTypes" :settings="select2Settings" v-model="formData.crew_type_id" />
    </div>
    <div class="input-group-outline mt-4">
        <label for="">Select Superintendent</label>
        <Select2 :options="formattedSuperIntendents" :settings="select2Settings" v-model="formData.superintendentId" />
    </div>
    <div class="input-group-outline mt-4">
        <label for="">Select Crew Members</label>
        <Select2 :options="formattedCrewMembers" :settings="select2SettingsCrews" v-model="formData.crew_members" />
    </div>
    <div class="input-group-outline mt-4">
        <label for="">Select Manager</label>
        <Select2 :options="formattedManagers" :settings="select2Settings" v-model="formData.managerId" />
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

const toast = useToast()

let select2Settings = ref({
    'width': '100%',
})
let select2SettingsCrews = ref({
    'width': '100%',
    multiple: true
})

// Formatted options for Select2
let formattedCrewTypes = ref([])
let formattedSuperIntendents = ref([])
let formattedCrewMembers = ref([])
let formattedManagers = ref([])

let formData = ref({
    crew_members: []
})

onMounted(() => {
    // Format crew types with ID and Name
    formattedCrewTypes.value = props.crewTypes.map(crewType => ({
        id: crewType.id,
        text: `${crewType.name}` // Combine ID and Name
    }))

    // Format users for superintendents
formattedSuperIntendents.value = props.users
    .filter(user => user.role_id === 3 || user.role_id === 7)
    .map(user => ({
        id: user.id,
        text: `${user.id} - ${user.name}`
    }))

    // Format users for crew members
    formattedCrewMembers.value = props.users
        .filter(user => user.role_id === 6) // Filter for crew members
        .map(user => ({
            id: user.id,
            text: `${user.id} - ${user.name}` // Combine ID and Name
        }))

    // Format users for managers
    formattedManagers.value = props.users
        .filter(user => user.role_id === 7) // Filter for managers
        .map(user => ({
            id: user.id,
            text: `${user.id} - ${user.name}` // Combine ID and Name
        }))

    // Initialize form data
    formData.value.crew_type_id = props.crew.crew_type_id || null
    formData.value.superintendentId = props.crew.superintendentId || null
    formData.value.crew_members = props.crew.crew_members.map(id => parseInt(id, 10)) || []

    // **for managerId
    const superintendent = props.users.find(u => u.id === formData.value.superintendentId);
    console.log(`superintendent: ${superintendent}`)
    formData.value.managerId = superintendent ? superintendent.manager_id : null;


})

const submit = async () => {
    try {
        const response = await axios.put(`/crews/${props.crew.id}`, {
            crew_type_id: formData.value.crew_type_id,
            superintendentId: formData.value.superintendentId,
            crew_members: formData.value.crew_members,
            managerId: formData.value.managerId,
        })

        if (response.data.success) {
            toast.success(response.data.message)
            setTimeout(() => {
                window.location.href = response.data.redirect
            }, 3000)
        } else {
            toast.error('Failed to update crew')
        }
    } catch (error) {
        let errorMessage = error.response?.data?.message || 'Something went wrong'
        toast.error(errorMessage)
    }
}
</script>
