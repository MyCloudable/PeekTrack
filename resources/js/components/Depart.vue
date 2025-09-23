<template>
    <div ref="departWrapper">

        <!-- <LoadingOverlay /> -->

        <!-- <button type="button" class="btn btn-info p-3" @click="getAllJobs" v-if="(!isDepart && travelTime == null) ||
            (!isDepart && travelTime && travelTime.type == 'depart_for_job' && travelTime.arrive)">
            MOBILIZATION</button> -->

        <!-- show MOB button all the time even before reaching to job / office -->
        <button type="button" class="btn btn-info p-3" @click="getAllJobs" v-if="canMobilize" :disabled="isBusy">
            MOBILIZATION</button>

        <button type="button" class="btn btn-secondary ms-5 p-3"
            v-if="!isDepart && travelTime && travelTime.type == 'depart_for_job' && travelTime.arrive" @click="depart"
            :disabled="isBusy">
            END PRODUCTION</button>

        <div class="d-flex align-items-center" v-if="isDepart">
            <i class="fas fa-undo text-dark cursor-pointer me-1" :class="{ 'pe-none opacity-50': isBusy }"
                @click="isDepart = false; $emit('is-mobilization')"></i>
            <div class="text-dark" :class="{ 'pe-none opacity-50': isBusy }">
                <Select2 v-model="departForm.jobId" :options="jobs" :settings="select2Settings" class="foo-bar" />
            </div>
            <button type="button" class="btn btn-secondary btn-sm mt-3 ms-1" @click="depart(true)"
                :disabled="isBusy">Depart</button>
        </div>

        <div class="d-inline-flex align-items-center ms-3"
            v-if="travelTime && travelTime.type == 'depart_for_job' && !travelTime.arrive && !isDepart">
            <button type="button" class="btn btn-secondary btn-sm mt-3 ms-1" @click="depart" :disabled="isBusy">Arrive
                at job
                location</button>
        </div>

        <div class="d-inline-flex align-items-center gap-3 flex-column flex-md-row mt-2 ms-3"
            v-if="travelTime && travelTime.type == 'depart_for_office' && !travelTime.arrive && !isDepart">

            <!-- Show time types dropdown when Arrive at Office-->
            <label class="text-dark me-1">Time type</label>
            <select v-model="arriveOfficeTypeId" style="width:200px;" class="bg-white" :disabled="isBusy">
                <option :value="null" disabled>Select time type…</option>
                <option v-for="t in props.timeTypes" :key="t.id" :value="t.id">
                    {{ t.display_name }}
                </option>
            </select>

            <button type="button" class="btn btn-secondary btn-sm mt-3 ms-1" @click="depart"
                :disabled="isBusy">Arrive</button>
        </div>

        <!-- Switch time type AFTER arriving at office (loop until clock out) -->
        <div class="d-inline-flex align-items-center gap-3 flex-column flex-md-row mt-2 ms-3" v-if="canSwitchTypesHere">
            <label class="text-dark me-1">Switch time type</label>
            <select v-model="selectedSwitchTypeId" style="width:200px;" class="bg-white" :disabled="isBusy">
                <option :value="null" disabled>Select time type…</option>
                <option v-for="t in props.timeTypes" :key="t.id" :value="t.id">
                    {{ t.display_name }}
                </option>
            </select>
            <button type="button" class="btn btn-outline-info btn-sm mt-3 ms-1" @click="switchTimeType"
                :disabled="isBusy">
                Apply
            </button>
        </div>


    </div>
</template>

<script setup>
import axios from 'axios';
import { ref, onMounted, computed, watch } from 'vue'

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
const isBusy = isLoading


const departWrapper = ref(null)
let select2Settings = ref({
    'width': '250px',
    'dropdownParent': departWrapper,
    'dropdownCssClass': ':all'
})

let travelTime = ref(props.travelTime)
// Keep local travelTime in sync with prop (defensive) -> To be extra safe if the parent ever stops re-keying
watch(() => props.travelTime, v => { travelTime.value = v })


let isDepart = ref(false)
let jobs = ref([])

let departForm = ref({
    'crewId': props.crewId,
    'jobId': '',
    'type': '',
    prevTravelTimeId: null, // NEW, if we are switching jobs mid-leg
})

const arriveOfficeTypeId = ref(null) // to select time type from dropdown


const selectedSwitchTypeId = ref(null) // for switch time type dropdown
const shopTypeId = ref(null)   // cache Shop’s id for reuse

// show the switcher only when we’re back from the job and at office (indirect time)
// AND the MOB picker is NOT open
const canSwitchTypesHere = computed(() => {
    const tt = travelTime.value
    return !!(tt && tt.type === 'depart_for_office' && tt.arrive && !isDepart.value)
})

// show MOB button all the time even before reaching to job / office
const canMobilize = computed(() => {
    return (!isDepart.value && (travelTime.value == null || travelTime.value))
})


onMounted(() => {
    // default to “Shop” if present (switch time type dropdown)
    const shop = (props.timeTypes || []).find(t => t.name?.toLowerCase().includes('shop'))
    if (shop) {
        selectedSwitchTypeId.value = shop.id
        shopTypeId.value = shop.id
    }
})

const getAllJobs = () => {
    if (isBusy.value) return
    setLoading(true)

    axios.get('/getjobs-for-depart')
        .then(res => {
            jobs.value = res.data;
            isDepart.value = true
            emit('is-mobilization')
        })
        .catch(err => {
            toast.error(err.response.data.message || 'Something went wrong')
        })
        .finally(() => setLoading(false))
}

const depart = (eventOrValidation = false) => {

    if (isBusy.value) return

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
            toast.error(err.response.data.message || 'Something went wrong')
        })
        .finally(() => setLoading(false)) // Disable loading


}

const setType = () => {

    // Always clear this; we only set it when we’re switching jobs mid-leg
    departForm.value.prevTravelTimeId = null

    // 1) First MOB of the day, from the shop:
    //    - there is no current travel leg (travelTime == null)
    //    - the user opened the MOB picker (isDepart == true)
    if (travelTime.value == null && isDepart.value) {
        departForm.value.type = 'depart_for_job'
    }

    // 2) Switching job while a leg is still open:
    //    - user opened the MOB picker (isDepart)
    //    - there IS a current travel leg (travelTime)
    //    - that leg has NOT been closed yet (!arrive)
    //    - and it could be either “to job” OR “to office”
    //    What we do:
    //      - close that open leg at the same timestamp as this new MOB
    //      - immediately start a new depart_for_job to the newly selected job

    else if (
        isDepart.value &&
        travelTime.value &&
        !travelTime.value.arrive &&
        (travelTime.value.type === 'depart_for_job' || travelTime.value.type === 'depart_for_office')
    ) {
        // switch to another job mid-leg
        departForm.value.type = 'depart_for_job'
        // backend will close this open leg first (arrive = MOB timestamp), then create the new one
        departForm.value.prevTravelTimeId = travelTime.value.id
    }

    // 3) Normal arrival at the job site:
    //    - current leg is “depart_for_job” and still open (!arrive)
    //    - user clicked “Arrive at job location” (not the MOB picker)
    //    What we do:
    //      - close this leg (arrive_for_job) by sending its travelTimeId
    else if (travelTime.value && travelTime.value.type == 'depart_for_job' && !travelTime.value.arrive) {
        departForm.value.type = 'arrive_for_job'
        departForm.value.travelTimeId = travelTime.value.id
    }

    // 4) Start another job AFTER you’re already at a job:
    //    - you’re at a job (type == depart_for_job && arrive == true)
    //    - user opened the MOB picker to go to a different job (isDepart)
    //    What we do:
    //      - start a fresh “depart_for_job” leg to the newly selected job
    //      - (setting travelTimeId isn’t required for creating the new leg, but harmless)
    else if (isDepart.value && travelTime.value && travelTime.value.type == 'depart_for_job' && travelTime.value.arrive) {
        departForm.value.type = 'depart_for_job'
        departForm.value.travelTimeId = travelTime.value.id
    }

    // 4b) Start a new job while already back at office:
    //     - last leg: depart_for_office AND it's closed (arrive == true)
    //     - user opened MOB to go to a new job
    //     -> start a fresh depart_for_job
    else if (
        isDepart.value &&
        travelTime.value &&
        travelTime.value.type === 'depart_for_office' &&
        travelTime.value.arrive
    ) {
        departForm.value.type = 'depart_for_job'
        // no prevTravelTimeId needed; that office leg is already closed
    }

    // 5) End Production (leave job -> head to office):
    //    - you’re at a job (type == depart_for_job && arrive == true)
    //    - user clicked “End Production” (NOT the MOB picker)
    //    What we do:
    //      - start the return leg: depart_for_office
    else if (!isDepart.value && travelTime.value && travelTime.value.type == 'depart_for_job' && travelTime.value.arrive) {
        departForm.value.type = 'depart_for_office'
    }

    // 6) Arrive at office:
    //    - current leg is “depart_for_office” and still open (!arrive)
    //    - user clicked “Arrive” (and we’ll also include the selected time type)
    //    What we do:
    //      - close the office leg (arrive_for_office) by sending its travelTimeId
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

// switch time type AFTER arriving at office (loop until clock out)
const switchTimeType = () => {

    if (isBusy.value) return

    if (!selectedSwitchTypeId.value) {
        toast.error('Please select a time type')
        return
    }
    // follow the same Late Entry rule as elsewhere
    if (props.isLateEntryTimeVisible && !props.lateEntryTime) {
        toast.error('Please select the late entry time or toggle it off')
        return
    }
    if (!confirm('Apply new time type now?')) return

    setLoading(true)
    axios.post('/switch-time-type', {
        crewId: props.crewId,
        timeTypeId: selectedSwitchTypeId.value,
        // parent already passes a formatted string or null
        lateEntryTime: props.lateEntryTime || null,
    })
        .then(() => {
            selectedSwitchTypeId.value = shopTypeId.value ?? null // reset the switch dropdown to Shop
            emit('track-time-done')       // refresh crew/times
            emit('last-entry-time-done')  // reset the Late Entry toggle in parent
        })
        .catch(err => {
            toast.error(err?.response?.data?.message || 'Something went wrong')
        })
        .finally(() => setLoading(false))
}

</script>

<style></style>
