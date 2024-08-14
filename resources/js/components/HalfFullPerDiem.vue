<template>

    <i class="fa fa-star-o ms-3" aria-hidden="true" v-if="perDiem == null" @click="hfPerDiem('h')"></i>
    <i class="fa fa-star-half cursor-pointer ms-3" v-if="perDiem == 'h'" @click="hfPerDiem('f')"></i>
    <i class="fa fa-star cursor-pointer ms-3" v-if="perDiem == 'f'" @click="hfPerDiem(null)"></i>
    
    </template>
    
    <script setup>
    import axios from 'axios';
    import { ref } from 'vue'

    import {useLoading} from '../composables/useLoading'

    const props = defineProps({
        timesheetId: [Number, Array],
        perDiem: String
    })

    const emit = defineEmits(['hf-per-diem-done'])

    const { isLoading, setLoading } = useLoading()

    const hfPerDiem = (perDiem) => {

        setLoading(true)

        axios.post('/hf-per-diem', {
        'timesheetId': props.timesheetId,
        'perDiem': perDiem,
    })
        .then(res => emit('hf-per-diem-done', perDiem))
        .catch(err => console.log(err))
        .finally(() => setLoading(false))
    }
    
    </script>
    
    <style>
    </style>