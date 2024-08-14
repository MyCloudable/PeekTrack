<!-- TimesheetForm.vue -->
<template>

  <div class="col-md-6" v-if="!showForm">
    <button class="btn btn-info" @click="showForm = true">Create Record</button>
  </div>

  <form @submit.prevent="createTimesheet" v-else>
    <div class="row">
      <div class="col-md-3">
        <label for="">Crew Type</label>
        <Select2 :options="crewtypes" v-model="formData.crew_type_id" required />
      </div>
      
      <div class="col-md-3">
        <label for="">Job</label>
        <Select2 :options="props.jobs" v-model="formData.job_id" required />
      </div>

      <div class="col-md-3">
        <label for="">Time type</label>
        <Select2 :options="props.timetypes" v-model="formData.time_type_id" required />
      </div>

      <div class="col-md-3">
        <label for="">Per diem</label>
        <Select2 :options="diem" v-model="formData.per_diem" />
      </div>
      
      <div class="col-md-3 mt-2">
        <label for="">Clock in</label>
        <input type="datetime-local" class="form-control bg-white" v-model="formData.clockin_time" required>
      </div>
      <div class="col-md-3 mt-2">
        <label for="">Clock out</label>
        <input type="datetime-local" class="form-control bg-white" v-model="formData.clockout_time" required>
      </div>

      <div class="col-md-3 mt-2">
        <label for="">Super Intendent</label>
        <Select2 :options="props.uniqueSuperintendents" v-model="formData.superintendentId" :disabled="superintendentDisabled" required />
      </div>

      <div class="col-md-9 mt-2">
        <label for="">Crew Member</label>
        <Select2 :options="crewMembers" :settings="select2SettingsCrews" v-model="formData.user_id" required />
      </div>

      <div class="col-md-3" style="margin-top:40px;">
        <label></label>
        <button class="btn btn-success" type="submit">Submit</button>
        <button class="btn btn-danger ms-2" @click="back">Back</button>
      </div>
    </div>

  </form>


</template>

<script setup>
import { ref, watch, onMounted, nextTick } from 'vue'

import { useToast } from "vue-toastification";

import {useLoading} from '../../composables/useLoading'

const props = defineProps({
  users: Object,
  jobs: Object,
  timetypes: Object,
  crewtypes: Object,
  uniqueSuperintendents: Object,
  authuser: Object
})

const emit = defineEmits(['create-timesheet'])

let select2SettingsCrews = ref({
    'width': '100%',
    multiple: true
})

const toast = useToast()

const { isLoading, setLoading } = useLoading()


let showForm = ref(false)

let crewMembers = ref([])
let timetypes = ref([])

let diem = ref([{ id: 'h', text: 'h' }, { id: 'f', text: 'f' }])

let superintendentDisabled = false; // Initially not disabled

const formData = ref({
});


const createTimesheet = async () => {

  if (!validateTimesheetData()) {
    return; // Exit if validation fails
  }

  try {

    setLoading(true)

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
  } finally {
    setLoading(false)
  }
}


const validateTimesheetData = () => {
  if (formData.value.time_type_id && formData.value.job_id) {
    const selectedTimeType = props.timetypes.find(timetype => timetype.id == formData.value.time_type_id);
    const selectedJob = props.jobs.find(job => job.id == formData.value.job_id);

    if (selectedTimeType && selectedJob && selectedTimeType.text === 'Production' && selectedJob.text === '9-99-9998 ()') {
      toast.error('Cannot create timesheet with Production time type for job 9-99-9998');
      return false; // Validation failed
    }
  }
  return true; // Validation passed
}


const back = () => {

  showForm.value = false
  formData.value = {}

}

onMounted(() => {
  props.users.map(user => {
    // (user.role_id == 3) ? superIntendents.value.push(user) : '';
    (user.role_id == 6 || user.role_id == 3) ? crewMembers.value.push(user) : '';

  })


  // if logged in user is superintendent then auto select logged in superintentend
  props.uniqueSuperintendents.map(user => {
    if (props.authuser.role_id == 3 && props.authuser.id == user.id) {
      formData.value.superintendentId = user.id
      superintendentDisabled = true
    }
  })

  setDefaultToFormData()


})

const setDefaultToFormData = () => {

  const setJobId = () => {
    props.jobs?.forEach(job => {
      if (job.text === '9-99-9998 ()') {
        formData.value.job_id = job.id;
      }
    });
  };

  const setTimeTypeId = () => {
    props.timetypes?.forEach(timetype => {
      if (timetype.text === 'Shop') {
        formData.value.time_type_id = timetype.id;
      }
    });
  };

  setJobId();
  setTimeTypeId();
};


</script>