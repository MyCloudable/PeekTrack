<template>
    <div>
        <button type="button" class="btn btn-info p-3" @click="getAllJobs" v-if="!isDepart">MOBILIZATION</button>
        <div class="d-flex align-items-center" v-else>
            <select class="form-control w-50" v-model="departForm.job_id" @change="setType('depart_for_job')">
                <option value="">Select your job</option>
                <option v-for="(job, index) in jobs" :key="job.id" :value="job.id">{{ job.job_number }}</option>
            </select>
            <button type="button" class="btn btn-secondary btn-sm mt-3 ms-1" @click="depart">Submit</button>
        </div>
    </div>
</template>

<script setup>
import axios from 'axios';
import { ref, onMounted } from 'vue';

let isDepart = ref(false)
let jobs = ref([])

let departForm = ref({
    'job_id': '',
    'type': ''
})

onMounted(() => {
    // getAllJobs()
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
    axios.post('/track-time-travel', {
        departForm: departForm.value
    })
        .then(res => {
            console.log(res.data);
        })
        .catch(err => console.log(err))
}

const setType = (type) => departForm.value.type = type

</script>

<style scoped>

select{
    border: 1px solid #d2d6da !important;
}

</style>