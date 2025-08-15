<template>
    <div ref="departWrapper">

        <!-- <LoadingOverlay /> -->

        <button type="button" class="btn btn-info p-3" @click="getAllJobs" v-if="(!isDepart && travelTime == null) ||
            (!isDepart && travelTime && travelTime.type == 'depart_for_job' && travelTime.arrive)">
            MOBILIZATION</button>

        <button type="button" class="btn btn-secondary ms-5 p-3"
            v-if="!isDepart && travelTime && travelTime.type == 'depart_for_job' && travelTime.arrive" @click="depart">
            END PRODUCTION</button>

        <div class="d-flex align-items-center" v-if="isDepart">
            <i class="fas fa-undo text-dark cursor-pointer me-1"
                @click="isDepart = false; $emit('is-mobilization')"></i>
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

        <div class="d-flex align-items-center gap-3 flex-column flex-md-row mt-2"
            v-if="travelTime && travelTime.type == 'depart_for_office' && !travelTime.arrive">

            <!-- Show time types dropdown when Arrive at Office-->
            <label class="text-dark me-1">Time type</label>
            <select v-model="arriveOfficeTypeId" style="width:200px;" class="bg-white">
                <option :value="null" disabled>Select time type…</option>
                <option v-for="t in props.timeTypes" :key="t.id" :value="t.id">
                    {{ t.display_name }}
                </option>
            </select>

            <button type="button" class="btn btn-secondary btn-sm mt-3 ms-1" @click="depart">Arrive</button>
        </div>

    </div>
</template>

<script setup>
import axios from 'axios';
import { ref, onMounted } from 'vue'

import { useToast } from "vue-toastification"
// import LoadingOverlay from './shared/LoadingOverlay.vue'
import { useLoading } from '../composables/useLoading'
import { toInteger } from 'lodash';

const props = defineProps({
    crewId: Number,
    travelTime: Object,
    crewTypeId: Number,
    isLateEntryTimeVisible: Boolean,
    lateEntryTime: String,
    timeTypes: Array,
})

const emit = defineEmits(['track-time-done', 'is-mobilization', 'last-entry-time-done'])

const toast = useToast()

const { isLoading, setLoading } = useLoading()

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

const arriveOfficeTypeId = ref(null) // to select time type from dropdown


onMounted(() => {

})

const getAllJobs = () => {
    axios.get('/getjobs-for-depart')
        .then(res => {
            jobs.value = res.data;
            isDepart.value = true
            emit('is-mobilization')
        })
        .catch(err => console.log(err))
}

const depart = (eventOrValidation = false) => {



    // Determine if we should validate the job ID
    const shouldValidateJobId = eventOrValidation === true;

    // job id should be required
    if (shouldValidateJobId && departForm.value.jobId == '') {
        toast.error('Please select the job first')
        return
    }

    // if late entry time is Visible then late entry time field should be filled
    if (props.isLateEntryTimeVisible && !props.lateEntryTime) {
        toast.error('Please select the late entry time if its visible, otherwise toggle it to disable')
        return
    }

    departForm.value.lateEntryTime = props.lateEntryTime // add lateEntryTime value in form (null if not visible, otherwise filled value)

    //set crewTypeId to departForm if depart for a job as crewTypeId dropdown is active at this time (in Clockin.vue)
    if (shouldValidateJobId)
        departForm.value.crewTypeId = props.crewTypeId

    setType()

    // include selected time type when arriving at office
    if (departForm.value.type === 'arrive_for_office') {
        if (!arriveOfficeTypeId.value) {
            toast.error('Please select a time type')
            return
        }
        departForm.value.time_type_id = arriveOfficeTypeId.value
    } else {
        // make sure we don't leak an old value on other actions
        delete departForm.value.time_type_id
    }

    if (!confirm(`Are you sure you want to ${departForm.value.type.split('_').join(' ')} ${getSelectedJobContent()}?`)) return

    setLoading(true) // Enable loading

    axios.post('/track-time-travel', {
        departForm: departForm.value,
    })
        .then(res => {
            travelTime.value = res.data
            isDepart.value = false
            emit('track-time-done')

            if (shouldValidateJobId)
                emit('is-mobilization')

            emit('last-entry-time-done')

        })
        .catch(err => {
            toast.error(err.response.data.message)
        })
        .finally(() => setLoading(false)) // Disable loading


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

const getSelectedJobContent = () => {

    let jobContent = ''
    if (departForm.value.type == 'depart_for_job') {

        jobs.value.map(job => {
            if (job.id === toInteger(departForm.value.jobId)) jobContent = job.text
        })
    }

    return jobContent

}

</script>

<style></style>
