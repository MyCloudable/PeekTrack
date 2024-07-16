<!-- TimesheetForm.vue -->
<template>

  <div class="col-md-6" v-if="!showForm">
    <button class="btn btn-info" @click="showForm = true">Create Record</button>
  </div>

  <form @submit.prevent="createTimesheet" v-else>
    <div class="row">
      <div class="col-md-2">
        <label for="">Crew Type</label>
        <Select2 :options="crewtypes" v-model="formData.crew_type_id" required />
      </div>
      <div class="col-md-2">
        <label for="">Crew Member</label>
        <Select2 :options="crewMembers" v-model="formData.user_id" required />
      </div>
      <div class="col-md-2">
        <label for="">Super Intendent</label>
        <Select2 :options="props.uniqueSuperintendents" v-model="formData.superintendentId" required />
      </div>
      <div class="col-md-2">
        <label for="">Job</label>
        <Select2 :options="props.jobs" v-model="formData.job_id" required />
      </div>
      <div class="col-md-2">
        <label for="">Time type</label>
        <Select2 :options="props.timetypes" v-model="formData.time_type_id" required />
      </div>
      <div class="col-md-2"></div>
      <div class="col-md-2 mt-2">
        <label for="">Clock in</label>
        <input type="datetime-local" class="form-control bg-white" v-model="formData.clockin_time" required>
      </div>
      <div class="col-md-2 mt-2">
        <label for="">Clock out</label>
        <input type="datetime-local" class="form-control bg-white" v-model="formData.clockout_time" required>
      </div>

      <div class="col-md-4" style="margin-top:40px;">
        <label></label>
        <button class="btn btn-success" type="submit">Submit</button>
        <button class="btn btn-danger ms-2" @click="back">Back</button>
      </div>
    </div>

  </form>


</template>

<script setup>
import { ref, onMounted } from 'vue'

import { useToast } from "vue-toastification";

const props = defineProps({
  users: Object,
  jobs: Object,
  timetypes: Object,
  crewtypes: Object,
  uniqueSuperintendents: Object
})

const emit = defineEmits(['create-timesheet'])

const toast = useToast();


let showForm = ref(false)

let crewMembers = ref([])
let timetypes = ref([])

const formData = ref({
});

const submitForm = () => {

};

const createTimesheet = async () => {
  try {
    const response = await axios.post(`timesheet-management/store`, {
      formData: formData.value
    })

    if (response.data.success) {
      toast.success(response.data.message)
      back()
      emit('create-timesheet')
    } else {
      toast.error('Failed to create timesheet entry')
    }
  } catch (error) {
    let errorMessage = error.response.data.message

    errorMessage ? toast.error(errorMessage) : 'Something went wrong'
  }
}

const back = () => {

  showForm.value = false
  formData.value = {}

}

onMounted(() => {
  props.users.map(user => {
    // (user.role_id == 3) ? superIntendents.value.push(user) : '';
    (user.role_id == 6) ? crewMembers.value.push(user) : '';

  })
})
</script>