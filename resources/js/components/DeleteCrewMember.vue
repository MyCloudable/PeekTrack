<template>

    <div>
        <i class="fa fa-trash cursor-pointer ms-3" aria-hidden="true" @click="deleteCrewMember"></i>
    </div>
    
    </template>
    
    <script setup>
    import axios from 'axios';
    import { ref } from 'vue'

    const props = defineProps({
        crewId: Number,
        crewMemberId: Number
    })

    const emit = defineEmits(['crew-member-deleted'])
    
    
    const deleteCrewMember = () => {
        axios.post('/delete-crew-members', {
        'crewId': props.crewId,
        'crewMemberId': props.crewMemberId,
    })
        .then(res => emit('crew-member-deleted'))
        .catch(err => console.log(err))
    }
    
    </script>
    
    <style>
    </style>