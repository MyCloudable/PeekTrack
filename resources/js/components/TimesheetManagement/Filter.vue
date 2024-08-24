<template>

    <div class="row" v-if="!showFilter">
        <div class="col-md-6">
            <button class="btn btn-info" @click="showFilter = true">Show Filter</button>
        </div>
    </div>

    <div class="row" v-else>

        <div class="col-md-2">
            <div class="form-group">
                <label for="">Crew Member</label>
                <Select2 :options="crewMembers" v-model="filterData.crewMember" />
            </div>
            <i class="fas fa-times cursor-pointer mt-1" @click="clearField('crewMember')"></i>
        </div>

        <div class="col-md-2">
            <div class="form-group">
                <label for="">Super Intendent</label>
                <Select2 :options="superIntendents" v-model="filterData.superIntendent"
                    :disabled="superintendentDisabled" />
            </div>
            <i class="fas fa-times cursor-pointer mt-1" v-if="authuser.role_id !== 3" @click="clearField('superIntendent')"></i>
        </div>
        <div class="col-md-2">
            <div class="form-group">
                <label for="">Job</label>
                <Select2 :options="props.jobs" v-model="filterData.job" />
            </div>
            <i class="fas fa-times cursor-pointer mt-1" @click="clearField('job')"></i>
        </div>

        <div class="col-md-2">
            <div class="form-group">
                <label for="">Location</label>
                <Select2 :options="locations" v-model="filterData.location" />
            </div>
            <i class="fas fa-times cursor-pointer mt-1" @click="clearField('location')"></i>
        </div>

        <div class="col-md-2">
            <div class="form-group">
                <label for="">From Period</label>
                <input type="date" class="form-control bg-white" v-model="filterData.from">
            </div>
            <i class="fas fa-times cursor-pointer mt-1" @click="clearField('from')"></i>
        </div>
        <div class="col-md-2">
            <div class="form-group">
                <label for="">To Period</label>
                <input type="date" class="form-control bg-white" v-model="filterData.to">
            </div>
            <i class="fas fa-times cursor-pointer mt-1" @click="clearField('to')"></i>
        </div>



        <div class="col-md-12">
            <button class="btn btn-warning btn-md mt-4" @click="$emit('filter', filterData)">Submit</button>
            <button class="btn btn-danger btn-md mt-4 ms-3" @click="clear()">Clear</button>
            <button class="btn btn-info btn-md mt-4 ms-3" @click="showFilter = false">Hide Filter</button>
        </div>

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

let showFilter = ref(false)

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
        if (props.authuser.role_id == 3 && props.authuser.id == user.id) {
            filterData.value.superIntendent = user.id
            superintendentDisabled = true
            emit('filter', filterData.value)
        }


        (user.role_id == 6 || user.role_id == 3) ? crewMembers.value.push(user) : '';


        let el = locations.value.filter(el => {
            return el.text == user.location
        })


        if (!el.length) {
            locations.value.push({
                'id': user.location,
                'text': user.location
            })
        }

    })
})

const clear = () => {
    const superIntendentValue = filterData.value.superIntendent
    filterData.value = {}

    if (props.authuser.role_id == 3) // don't clear this if user is superintendent. clear this if user is not superintendent
        filterData.value.superIntendent = superIntendentValue

    emit('filter', filterData.value)
}

const clearField = (field) => {

    delete filterData.value[field];
    
}


</script>

<style></style>