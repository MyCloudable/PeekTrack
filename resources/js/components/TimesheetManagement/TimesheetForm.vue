<!-- TimesheetForm.vue -->
<template>

  <form @submit.prevent="submitForm">
    <div class="row">
      <div class="col-md-2">
      <label for="">Crew Type</label>
      <Select2 :options="crewtypes" v-model="formData.crewType" required />
    </div>
    <div class="col-md-2">
      <label for="">Crew Member</label>
      <Select2 :options="crewMembers" v-model="formData.crewMember" required />
    </div>
    <div class="col-md-2">
      <label for="">Super Intendent</label>
      <Select2 :options="superIntendents" v-model="formData.superIntendent" required />
    </div>
    <div class="col-md-2">
      <label for="">Job</label>
      <Select2 :options="props.jobs" v-model="formData.job" required />
    </div>
    <div class="col-md-2">
      <label for="">Time type</label>
      <Select2 :options="props.timetypes" v-model="formData.timetype" required />
    </div>
    <div class="col-md-2">
      <label for="">Clock in</label>
      <input type="date" class="form-control bg-white" v-model="formData.in" required>
    </div>
    <div class="col-md-2">
      <label for="">Clock out</label>
      <input type="date" class="form-control bg-white" v-model="formData.out" required>
    </div>

    <div class="col-md-12 mt-3">
      <button class="btn btn-success" type="submit">Submit</button>
    </div>
    </div>

  </form>


</template>

<script setup>
import { ref, onMounted } from 'vue'

const props = defineProps({
  users: Object,
  jobs: Object,
  timetypes: Object,
  crewtypes: Object
})

const emit = defineEmits(['submit-timesheet'])

let crewMembers = ref([])
let superIntendents = ref([])
let timetypes = ref([])

const formData = ref({
});

const submitForm = () => {
  emit('submit-timesheet', formData.value);
};

onMounted(() => {
  props.users.map(user => {
    (user.role_id == 3) ? superIntendents.value.push(user) : '';
    (user.role_id == 6) ? crewMembers.value.push(user) : '';

  })
})
</script>