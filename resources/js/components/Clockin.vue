<template>

    <a href="javascript:;" class="nav-link text-body p-0" data-bs-toggle="modal" data-bs-target="#clockin"
        @click="getCrewMembers" v-if="iconVisible">
        <span class="sidenav-normal  ms-2  ps-1">
            <h2> <i class="fas fa-business-time"></i> </h2>
        </span>
    </a>

    <div class="modal fade" id="clockin" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="row header">
                        <div class="col-md-6 text-dark"><span class="badge badge-info me-2">Status: </span> {{ status }}
                            <select class="d-inline w-50 ms-2" v-if="!isAlreadyVerified" v-model="crewTypeId">
                                <option v-for="(crewType, index) in crewTypes" :value="crewType.id">
                                    {{ crewType.name }}
                                </option>
                            </select>
                        </div>
                        <div class="col-md-6 d-flex justify-content-end">
                            <span class="badge badge-success me-2" v-if="isAlreadyClockedout">Crew Clocked Out</span>
                            <span class="badge badge-success" v-if="isAlreadyVerified">Crew Verified</span>
                            <span class="badge badge-danger" v-else>Crew Not Verified</span>

                            <add-crew-member @get-all-users="GetAllUsers" v-if="isAlreadyClockedin" />

                        </div>
                    </div>
                    <div class="row actions mt-3 mb-3">
                        <div class="col-md-8">
                            <depart v-if="isAlreadyClockedin" :crewId="crewId" :travelTime="travelTime"
                                :key="departKey"
                                @track-time-done="trackTimeDone" />

                            <button type="button" class="btn btn-secondary p-3" @click="weatherEntry"
                                v-if="isAlreadyVerified && !isAlreadyClockedin">Weather</button>

                        </div>
                        <div class="col-md-3">

                            <button type="button" class="btn btn-primary p-3" @click="verifyTeam"
                                v-if="!isAlreadyVerified">Verify Crew</button>
                            <button type="button" class="btn btn-success p-3" @click="clockinout('clockin')"
                                v-if="isAlreadyVerified && !isAlreadyClockedin">Clock in</button>
                            <button type="button" class="btn btn-danger p-3" @click="clockinout('clockout')"
                                v-if="isAlreadyClockedin && !isAlreadyClockedout">Clock out
                            </button>
                            <button type="button" class="btn btn-secondary p-3" @click="readyForVerification"
                                v-if="isAlreadyClockedout">Ready for verification</button>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-flush table-striped verify-crew-members">
                            <thead class="">
                                <tr>
                                    <th v-if="!isAlreadyVerified">
                                        <div>Check</div>
                                    </th>
                                    <th>
                                        <div>Name</div>
                                    </th>
                                    <!-- <th>
                                        <div>Email</div>
                                    </th> -->
                                    <!-- <th v-if="isAlreadyClockedin">
                                        <div>In</div>
                                    </th>
                                    <th v-if="isAlreadyClockedin">
                                        <div>Out</div>
                                    </th> -->
                                    <th v-if="isAlreadyClockedin || isAlreadyClockedout">
                                        <div>Status</div>
                                    </th>
                                    <th v-if="isAlreadyClockedin">
                                        <div>Time</div>
                                    </th>
                                    <th v-if="isMenualClockinout">
                                        <div>Time Out</div>
                                    </th>
                                    <th v-if="isAlreadyClockedin || isAlreadyClockedout">
                                        <div>Total</div>
                                    </th>
                                    <!-- <th v-if="isAlreadyVerified">
                                        <div></div>
                                    </th> -->
                                    <th v-if="isAlreadyClockedin">
                                        <div><half-full-per-diem :timesheetId="allPerDiemTimesheetIds"
                                                :perDiem="allPerDiemStatus" @hf-per-diem-done="hfPerDiemDone" /></div>
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-if="!isAlreadyVerified">
                                    <td>
                                        <div><input type="checkbox" class="form-check-input" v-model="isCheckAll"
                                                @click="toggleCheckboxes"></div>
                                    </td>
                                    <td></td>
                                    <td></td>
                                </tr>

                                <tr v-if="allUsers.length > 0" ref="departWrapper">
                                    <td>
                                        <div class="input-group input-group-outline">
                                            <!-- <select class="form-control clr-light"
                                                v-model="createNewCrewForm[0].crew_member_id">
                                                <option value="">Select superintendent</option>
                                                <option v-for="(user, index) in allUsers" :key="user.id"
                                                    :value="user.id">{{ user.name }}
                                                </option>
                                            </select> -->
                                            <Select2 v-model="createNewCrewForm[0].crew_member_id" :options="allUsers" :settings="select2Settings" />
                                        </div>
                                    </td>
                                    <td>
                                        <input type="datetime-local" class="form-controll datetime"
                                            v-model="createNewCrewForm[0].clockin_time">
                                    </td>
                                    <td>
                                        <button class="btn btn-success" @click="addNewCrew"
                                            :disabled="!createNewCrewForm[0].crew_member_id || !createNewCrewForm[0].clockin_time">Create</button>
                                    </td>
                                </tr>

                                <tr v-for="(member, index) in CrewMembersTobeVerified" :key="member.id">
                                    <td v-if="!isAlreadyVerified">
                                        <div><input type="checkbox" class="form-check-input" :checked="member.isChecked"
                                                @click="toggleSingleCheckbox(index)"></div>
                                    </td>
                                    <td>
                                        <div>{{ member.name }}</div>
                                    </td>
                                    <!-- <td>
                                        <div>{{ member.email }}</div>
                                    </td> -->
                                    <!-- <td v-if="isAlreadyClockedin">
                                        <div v-if="!member.isMenualClockinout">
                                            {{ member.clockin_time }}
                                        </div>
                                        <div v-else><input type="datetime-local" class="form-control datetime"
                                                @change="menualClockinout($event, member.timesheet_id, 'clockin')"
                                                :value="now">
                                        </div>
                                    </td>
                                    <td v-if="isAlreadyClockedin">
                                        <div v-if="!member.isMenualClockinout">
                                            {{ member.clockout_time }}
                                        </div>
                                        <div v-else><input type="datetime-local" class="form-control datetime"
                                                @change="menualClockinout($event, member.timesheet_id, 'clockout')"
                                                :value="now">
                                        </div>
                                    </td> -->

                                    <td v-if="isAlreadyClockedin || isAlreadyClockedout">
                                        {{ member.status }}
                                    </td>

                                    <td v-if="isAlreadyClockedin || isAlreadyClockedout">
                                        <div v-if="!member.isMenualClockinout">
                                            {{ member.clockout_time ? member.clockout_time : member.clockin_time }}
                                        </div>
                                        <div v-else><input type="datetime-local" class="form-controll datetime"
                                                @change="menualClockinout($event, member.timesheet_id, 'clockin')"
                                                :value="now">
                                        </div>
                                    </td>

                                    <td v-if="member.isMenualClockinout">
                                        <div><input type="datetime-local" class="form-controll datetime"
                                                @change="menualClockinout($event, member.timesheet_id, 'clockout')"
                                                :value="now">
                                        </div>
                                    </td>
                                    <td v-if="isMenualClockinout && !member.isMenualClockinout"></td>

                                    <td v-if="isAlreadyClockedin || isAlreadyClockedout">
                                        {{ member.total_time }}
                                    </td>

                                    <td class="d-flex">
                                        <div v-if="isAlreadyClockedin">
                                            <i class="fa fa-pencil cursor-pointer" aria-hidden="true"
                                                @click="enableMenualClock(member.id)"></i>&nbsp&nbsp&nbsp&nbsp
                                            <half-full-per-diem :timesheetId="member.timesheet_id"
                                                :perDiem="member.per_diem"
                                                @hf-per-diem-done="hfPerDiemDone" />&nbsp&nbsp&nbsp&nbsp

                                        </div>
                                        <!-- <delete-crew-member :crewId="crewId" :crewMemberId="member.id"
                                        @crew-member-deleted="crewMemberDeleted"
                                            v-if="isAlreadyVerified" /> -->
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>


                </div>
                <div class="modal-footer">
                    <button type="button" class="btn bg-gradient-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

</template>

<script setup>
import axios from 'axios';
import { ref, onMounted, onUnmounted, onBeforeUnmount } from 'vue'

import { useToast } from "vue-toastification"

import AddCrewMember from './AddCrewMember'
import DeleteCrewMember from './DeleteCrewMember'
import HalfFullPerDiem from './HalfFullPerDiem'
import TimeConvert from '../composables/TimeConvert'
import Depart from './Depart'

const toast = useToast();

const departWrapper = ref(null)
let select2Settings = ref({
    'width': '250px',
    'dropdownParent': departWrapper,
})

let now = ref('')

let iconVisible = ref(true)

let isAlreadyVerified = ref(false)
let crewId = ref('')
let CrewMembersTobeVerified = ref([])
let isCheckAll = ref(false)
let toggleSingleCrewMember = ref('')
let submitCrewMembersToVerify = ref([])

let isAlreadyClockedin = ref(false)
let isAlreadyClockedout = ref(false)
let timesheet = ref([])
let travelTime = ref('')

let allUsers = ref([])
let createNewCrewForm = ref([{
    'crew_member_id': '',
    'clockin_time': ''
}])

let isMenualClockinout = ref(false)

let allPerDiemTimesheetIds = ref([])
let allPerDiemStatus = ref(null)

let status = ref('')

let crewTypes = ref([])
let crewTypeId = ref('')

let departKey = ref(0)

onMounted(() => {
    window.addEventListener('scroll', handleScroll)

    const date = new Date();
    let year = date.getFullYear();
    let month = date.getMonth() + 1;
    let day = date.getDate();

    if (month.toString().length === 1) {
        console.log('come here');
    }

    (month.toString().length === 1) ? month = '0' + month : ''
        (day.toString().length === 1) ? day = '0' + day : ''
    now.value = `${year}-${month}-${day}T12:00`

})
onBeforeUnmount(() => {
    window.removeEventListener('scroll', handleScroll)
})

const getCrewMembers = () => {
    axios.get('/crew-members')
        .then(res => {
            isAlreadyVerified.value = res.data.isAlreadyVerified
            isAlreadyClockedin = res.data.isAlreadyClockedin
            isAlreadyClockedout = res.data.isAlreadyClockedout
            crewId.value = res.data.crewId
            CrewMembersTobeVerified.value = res.data.crewMembers
            timesheet.value = res.data.timesheet
            travelTime.value = res.data.travelTime
            status.value = res.data.status
            crewTypes.value = res.data.crewTypes
            crewTypeId.value = res.data.crewTypeId

            timesheet.value.map(time => {

                CrewMembersTobeVerified.value.map(member => {
                    if (member.id == time.user_id) {
                        member.clockin_time = time.clockin_time
                        member.clockout_time = time.clockout_time
                        member.timesheet_id = time.id
                        member.per_diem = time.per_diem
                        member.status = (time.clockout_time) ? 'Out' : 'In'
                        member.total_time = TimeConvert(time.total_time)
                    }
                })

                allPerDiemTimesheetIds.value.push(time.id)
            })

            departKey.value++ //this will causes Vue to recreate the depart component

        })
        .catch(err => console.log(err))
}

const toggleCheckboxes = () => {
    isCheckAll.value = !isCheckAll.value
    CrewMembersTobeVerified.value.map(member => member.isChecked = isCheckAll.value)
}

const toggleSingleCheckbox = (index) => {
    (!CrewMembersTobeVerified.value[index].hasOwnProperty('isChecked'))
        ? CrewMembersTobeVerified.value[index].isChecked = ''
        : ''
    CrewMembersTobeVerified.value[index].isChecked = !CrewMembersTobeVerified.value[index].isChecked
}

const verifyTeam = () => {
    submitCrewMembersToVerify.value = []
    CrewMembersTobeVerified.value.map(member => (member.hasOwnProperty('isChecked') && member.isChecked) ?
        submitCrewMembersToVerify.value.push(member.id) : '')

    axios.post('/verify-crew-members', {
        'crewId': crewId.value,
        'crewMembers': submitCrewMembersToVerify.value,
        'crewTypeId': crewTypeId.value
    })
        .then(res => getCrewMembers())
        .catch(err => console.log(err))
}

const clockinout = (type) => {
    
        console.log('Attempting to send request...');
        const response = axios.post('/clockinout-crew-members', {
            'crewId': crewId.value,
            'type': type,
            'isMenual': false,
        })
        .then(res => getCrewMembers())
        .catch(error => {
            console.log('bs kr')
            let errorMessage = error.response.data.message
            errorMessage ? toast.error(errorMessage) : 'Something went wrong'
        })
    
    
}

const enableMenualClock = (id) => {

    let isEnabled = false

    CrewMembersTobeVerified.value.map(member => {
        if (member.id == id) {
            if (!member.hasOwnProperty('isMenualClockinout')) {
                member.isMenualClockinout = true
            } else {
                member.isMenualClockinout = !member.isMenualClockinout
            }
        }


        if (member.hasOwnProperty('isMenualClockinout') && member.isMenualClockinout) {
            isEnabled = true
        }

        isMenualClockinout.value = isEnabled

    })
}

const menualClockinout = (event, timesheetId, type) => {
    axios.post('/clockinout-crew-members', {
        'crewId': crewId.value,
        'type': type,
        'isMenual': true,
        'timesheetId': timesheetId,
        'time': event.target.value
    })
        .then(res => getCrewMembers())
        .catch(error => {
            let errorMessage = error.response.data.message
            errorMessage ? toast.error(errorMessage) : 'Something went wrong'
        })
}


// add new crew member
const GetAllUsers = (users) => {
    // allUsers.value = users

    users.map(user => {
    (user.role_id == 6) ? allUsers.value.push(user) : ''; // if crew member
  })

    createNewCrewForm.value[0].clockin_time = now.value
}
const addNewCrew = () => {
    axios.post('/add-new-crew-members', {
        'crewId': crewId.value,
        'createNewCrewForm': createNewCrewForm.value[0],
    })
        .then(res => {
            allUsers.value = []
            getCrewMembers()
        })
        .catch(error => {
            let errorMessage = error.response.data.message
            errorMessage ? toast.error(errorMessage) : 'Something went wrong'
        })
}

const crewMemberDeleted = () => getCrewMembers()

const hfPerDiemDone = (status) => {
    allPerDiemStatus.value = status
    getCrewMembers()
}

const trackTimeDone = () => getCrewMembers()

const readyForVerification = () => {
    axios.post('/ready-for-verification', {
        'crewId': crewId.value,
    })
        .then(res => {
            getCrewMembers()
        })
        .catch(err => console.log(err))
}

const weatherEntry = () => {
    axios.post('/wather-entry', {
        'crewId': crewId.value,
    })
        .then(res => {
            getCrewMembers()
        })
        .catch(err => console.log(err))
}



const handleScroll = (() => {
    iconVisible.value = false
})

</script>

<style>
.verify-crew-members {
    font-size: 14px;
    background: #1A2035 !important;
}

.modal-backdrop {
    display: none;
    z-index: 1040 !important;
}

.modal-content {
    margin: 2px auto;
    z-index: 1100 !important;
}

.table td,
.table th {
    text-align: center;
}

.clr-light {
    color: rgba(255, 255, 255, 0.6) !important;
}

.dark-version .table tbody tr td {
    color: #fff !important;
}

.dark-version .table thead tr th {
    font-size: large !important;
}
</style>