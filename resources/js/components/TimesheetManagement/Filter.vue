<template>

    <div class="col-md-2">
        <div class="form-group">
            <label for="">Crew Member</label>
            <!-- <select class="form-control bg-white p-1">
                <option value="">Select crew member</option>
                <option v-for="(user, index) in users" :value="user.id">{{ user.name }}</option>
            </select> -->
            <Select2 :options="crewMembers" v-model="filterData.crewMember" />
        </div>
    </div>

    <div class="col-md-2">
        <div class="form-group">
            <label for="">Super Intendent</label>
            <Select2 :options="superIntendents" v-model="filterData.superIntendent" :disabled="superintendentDisabled" />
        </div>
    </div>
    <div class="col-md-2">
        <div class="form-group">
            <label for="">Job</label>
            <Select2 :options="props.jobs" v-model="filterData.job" />
        </div>
    </div>

    <div class="col-md-2">
        <div class="form-group">
            <label for="">Location</label>
            <Select2 :options="locations" v-model="filterData.location" />
        </div>
    </div>

    <div class="col-md-2">
        <div class="form-group">
            <label for="">From Period</label>
            <input type="date" class="form-control bg-white" v-model="filterData.from">
        </div>
    </div>
    <div class="col-md-2">
        <div class="form-group">
            <label for="">To Period</label>
            <input type="date" class="form-control bg-white" v-model="filterData.to">
        </div>
    </div>



    <div class="col-md-12">
        <button class="btn btn-primary btn-md mt-4" @click="$emit('filter', filterData)">Submit</button>
        <button class="btn btn-danger btn-md mt-4 ms-3" @click="filterData = {};$emit('filter', filterData)">Clear</button>
    </div>

</template>

<script setup>
import { ref, onMounted } from 'vue'

const props = defineProps({
        users: Object,
        jobs: Object,
        authuser: Object
    })

    const emit = defineEmits(['filter'])

// let users = ref(props.users)
// let jobs = ref(props.jobs)

let crewMembers = ref([])
let superIntendents = ref([])
let locations = ref([])

let superintendentDisabled = false; // Initially not disabled

// let filter = ref({
//     'crewMember': '',
//     'superIntendent': '',
//     'job': '',
//     'location': '',
//     'from': '',
//     'to': ''
// })

let filterData = ref({})

onMounted(() => {
    console.log('authuser is ' + props.authuser.email)
    props.users.map(user => {
        (user.role_id == 3) ? superIntendents.value.push(user) : '';

        // if logged in user is superintendent then auto select logged in superintentend and make this dropdown disabled, so only see his records
        if(props.authuser.role_id == 3 && props.authuser.id == user.id){
            filterData.value.superIntendent = user.id
            superintendentDisabled = true
            emit('filter', filterData.value)
        }


        (user.role_id == 6) ? crewMembers.value.push(user) : '';


        let el = locations.value.filter(el => {
            return el.text == user.location
        })


        if(!el.length){
            locations.value.push({
                'id': user.location,
                'text': user.location
            })
        }

    })
})


</script>

<style></style>