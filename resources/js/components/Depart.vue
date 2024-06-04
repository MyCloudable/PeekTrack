<template>
    <div ref="departWrapper">
        
        <button type="button" class="btn btn-info p-3" @click="getAllJobs" 
        v-if="(!isDepart && travelTime == null) || 
        (!isDepart && travelTime && travelTime.type == 'depart_for_job' && travelTime.arrive)">
        MOBILIZATION</button>

        <button type="button" class="btn btn-secondary ms-5 p-3" 
        v-if="!isDepart && travelTime && travelTime.type == 'depart_for_job' && travelTime.arrive" @click="depart">
        Back to office</button>
        
        <div class="d-flex align-items-center" v-if="isDepart">
            <!-- <select class="form-control w-50" v-model="departForm.jobId">
                <option value="">Select your job</option>
                <option v-for="(job, index) in jobs" :key="job.id" :value="job.id">{{ job.job_number }}</option>
            </select> -->
            <div class="text-dark">
                <Select2 v-model="departForm.jobId" :options="jobs" 
            :settings="{ width: '250px', dropdownParent: departWrapper }"  />
            </div>
            <button type="button" class="btn btn-secondary btn-sm mt-3 ms-1" @click="depart">Depart</button>
        </div>

        <div class="d-flex align-items-center" v-if="travelTime && travelTime.type == 'depart_for_job' && !travelTime.arrive">
            <button type="button" class="btn btn-secondary btn-sm mt-3 ms-1" @click="depart">Arrive at job location</button>
        </div>

        <div class="d-flex align-items-center" v-if="travelTime && travelTime.type == 'depart_for_office' && !travelTime.arrive">
            <button type="button" class="btn btn-secondary btn-sm mt-3 ms-1" @click="depart">Arrive at office</button>
        </div>

    </div>
</template>

<script setup>
import axios from 'axios';
import { ref, onMounted } from 'vue';

const props = defineProps({
        crewId: Number,
        travelTime: Object,
    })

const departWrapper = ref(null)

let travelTime = ref(props.travelTime)
let isDepart = ref(false)
let jobs = ref([])

let departForm = ref({
    'crewId': props.crewId,
    'jobId': '',
    'type': ''
})

onMounted(() => {

})

const getAllJobs = () => {
    axios.get('/getjobs-for-depart')
        .then(res => {
            jobs.value = res.data;
            isDepart.value = true
        })
        .catch(err => console.log(err))
}

const depart = () => {

    setType()

    axios.post('/track-time-travel', {
        departForm: departForm.value
    })
        .then(res => {
            travelTime.value = res.data
            isDepart.value = false
        })
        .catch(err => console.log(err))
}

const setType = () => {
    if(travelTime.value == null && isDepart.value){
        departForm.value.type = 'depart_for_job'
    }
    else if(travelTime.value && travelTime.value.type == 'depart_for_job' && !travelTime.value.arrive){
        departForm.value.type = 'arrive_for_job'
        departForm.value.travelTimeId = travelTime.value.id
    }
    else if(isDepart.value && travelTime.value && travelTime.value.type == 'depart_for_job' && travelTime.value.arrive){
        departForm.value.type = 'depart_for_job'
        departForm.value.travelTimeId = travelTime.value.id
    }
    else if(!isDepart.value && travelTime.value && travelTime.value.type == 'depart_for_job' && travelTime.value.arrive){
        departForm.value.type = 'depart_for_office'
    }
    else if(travelTime.value && travelTime.value.type == 'depart_for_office' && !travelTime.value.arrive){
        departForm.value.type = 'arrive_for_office'
        departForm.value.travelTimeId = travelTime.value.id
    }
}

</script>

<style scoped>

/* .select2-container--default .select2-results>.select2-results__options{
    color: black !important;
} */

/* .select2-container--bootstrap .select2-results__option--highlighted[aria-selected] {
    background-color: green;
    color: black;
    display: table-row;
} */


/* body.dark-version{
    color: lightgrey !important;
} */

/* .form-control{
color: #949494 !important;
}
.form-control:focus{
border-color: #1d8cf8 !important;
} */

</style>