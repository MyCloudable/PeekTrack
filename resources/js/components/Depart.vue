<template>
    <div ref="departWrapper">

        <button type="button" class="btn btn-info p-3" @click="getAllJobs" v-if="(!isDepart && travelTime == null) ||
            (!isDepart && travelTime && travelTime.type == 'depart_for_job' && travelTime.arrive)">
            MOBILIZATION</button>

        <button type="button" class="btn btn-secondary ms-5 p-3"
            v-if="!isDepart && travelTime && travelTime.type == 'depart_for_job' && travelTime.arrive" @click="depart">
            END PRODUCTION</button>

        <div class="d-flex align-items-center" v-if="isDepart">
            <i class="fas fa-undo text-dark cursor-pointer me-1" @click="isDepart = false"></i>
            <div class="text-dark">
                <Select2 v-model="departForm.jobId" :options="jobs" :settings="select2Settings" class="foo-bar" />
            </div>
            <button type="button" class="btn btn-secondary btn-sm mt-3 ms-1" @click="depart(true)">Depart</button>
        </div>

        <div class="d-flex align-items-center"
            v-if="travelTime && travelTime.type == 'depart_for_job' && !travelTime.arrive">
            <button type="button" class="btn btn-secondary btn-sm mt-3 ms-1" @click="depart">Arrive at job
                location</button>
        </div>

        <div class="d-flex align-items-center"
            v-if="travelTime && travelTime.type == 'depart_for_office' && !travelTime.arrive">
            <button type="button" class="btn btn-secondary btn-sm mt-3 ms-1" @click="depart">Arrive</button>
        </div>

    </div>
</template>

<script setup>
import axios from 'axios';
import { ref, onMounted } from 'vue'

import { useToast } from "vue-toastification"

const props = defineProps({
    crewId: Number,
    travelTime: Object,
})

const emit = defineEmits(['track-time-done'])

const toast = useToast()

const departWrapper = ref(null)
let select2Settings = ref({
    'width': '250px',
    'dropdownParent': departWrapper,
    'dropdownCssClass': ':all'
})

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

const depart = (eventOrValidation = false) => {

     // Determine if we should validate the job ID
     const shouldValidateJobId = eventOrValidation === true;

    // job id should be required
    if(shouldValidateJobId && departForm.value.jobId == ''){
        toast.error('Please select the job first')
        return
    }

    setType()

    axios.post('/track-time-travel', {
        departForm: departForm.value
    })
        .then(res => {
            travelTime.value = res.data
            isDepart.value = false
            emit('track-time-done')
        })
        .catch(err => console.log(err))
}

const setType = () => {
    if (travelTime.value == null && isDepart.value) {
        departForm.value.type = 'depart_for_job'
    }
    else if (travelTime.value && travelTime.value.type == 'depart_for_job' && !travelTime.value.arrive) {
        departForm.value.type = 'arrive_for_job'
        departForm.value.travelTimeId = travelTime.value.id
    }
    else if (isDepart.value && travelTime.value && travelTime.value.type == 'depart_for_job' && travelTime.value.arrive) {
        departForm.value.type = 'depart_for_job'
        departForm.value.travelTimeId = travelTime.value.id
    }
    else if (!isDepart.value && travelTime.value && travelTime.value.type == 'depart_for_job' && travelTime.value.arrive) {
        departForm.value.type = 'depart_for_office'
    }
    else if (travelTime.value && travelTime.value.type == 'depart_for_office' && !travelTime.value.arrive) {
        departForm.value.type = 'arrive_for_office'
        departForm.value.travelTimeId = travelTime.value.id
    }
}

</script>

<style scoped></style>