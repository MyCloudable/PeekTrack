<template>

    <LoadingOverlay />

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
                        <i class="fas fa-yen-sign"></i>
                        <div class="col-md-6 text-dark"><span class="badge badge-info me-2">Status: </span> {{ status }}
                            <select class="d-inline w-50 ms-2 mt-3" v-if="!isAlreadyVerified || enableCrewTypeId" v-model="crewTypeId">
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
                            <depart v-if="isAlreadyClockedin" :crewId="crewId" :travelTime="travelTime" :crewTypeId="crewTypeId"
                                 :key="departKey"
                                @track-time-done="trackTimeDone" @is-mobilization="enableCrewTypeId = !enableCrewTypeId" />

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
                                            <Select2 v-model="createNewCrewForm[0].crew_member_id" :options="allUsers"
                                                :settings="select2Settings" />
                                        </div>
                                    </td>
                                    <td>
                                        <!-- <input type="datetime-local" class="form-controll datetime"
                                            v-model="createNewCrewForm[0].clockin_time"> -->
                                            <VueDatePicker v-model="createNewCrewForm[0].clockin_time" :enable-time="true"
                                            :formate="dateTimeFormat"
                                            class="responsive-datepicker"
                                            ></VueDatePicker>
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

                                    <td v-if="isAlreadyClockedin || isAlreadyClockedout">
                                        {{ member.status }}
                                    </td>

                                    <td v-if="isAlreadyClockedin || isAlreadyClockedout">
                                        <div v-if="!member.isMenualClockinout">
                                            {{ member.clockout_time ? member.clockout_time : member.clockin_time }}
                                        </div>
                                        <div v-else>
                                            <!-- <input type="datetime-local" class="form-controll datetime"
                                                @change="menualClockinout($event, member.timesheet_id, 'clockin')"
                                                :value="member.clockin_time"> -->
                                                <VueDatePicker v-model="member.clockin_time_edit"
                                                :enable-time="true"
                                                :formate="dateTimeFormat"
                                                @update:model-value="menualClockinout($event, member.timesheet_id, 'clockin')"
                                                class="responsive-datepicker"></VueDatePicker>
                                        </div>
                                    </td>

                                    <td v-if="member.isMenualClockinout">
                                        <div>
                                            <!-- <input type="datetime-local" class="form-controll datetime"
                                                @change="menualClockinout($event, member.timesheet_id, 'clockout')"
                                                :value="(member.clockout_time) ? member.clockout_time : now"> -->
                                                <VueDatePicker v-model="member.clockout_time_edit"
                                                :enable-time="true"
                                                :formate="dateTimeFormat"
                                                @update:model-value="menualClockinout($event, member.timesheet_id, 'clockout')"
                                                class="responsive-datepicker"></VueDatePicker>
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

import { format, parse } from 'date-fns'

import LoadingOverlay from './shared/LoadingOverlay.vue'
import {useLoading} from '../composables/useLoading'

const toast = useToast()

const { isLoading, setLoading } = useLoading()

const departWrapper = ref(null)
let select2Settings = ref({
    'width': '250px',
    'dropdownParent': departWrapper,
})

const dateTimeFormat = "yyyy-MM-dd'T'HH:mm:ss";

let now = ref('')

let iconVisible = ref(true)
const scrollThreshold = 50 // Adjust the threshold as needed

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
let enableCrewTypeId = ref(false)

let departKey = ref(0)


// things to manage multi tabs issue
let initialLoad = true // Flag to handle initial load

const setLocalStorageFlag = () => {
        localStorage.setItem('crewMembersUpdated', Date.now())
        getCrewMembers()
    }

window.addEventListener('storage', (event) => {
    if (event.key === 'crewMembersUpdated' && !initialLoad) {
        // Call getCrewMembers to update UI ( in other tabs if user open ) 
        getCrewMembers()
    }
});
// things to manage multi tabs issue end

onMounted(() => {
    window.addEventListener('scroll', handleScroll)

    setCurrentDateTime()

    // Reset initial load flag after mounted hook completes
    initialLoad = false

})
onBeforeUnmount(() => {
    window.removeEventListener('scroll', handleScroll)
})

const setCurrentDateTime = () => {
    const noww = new Date();
    const year = noww.getFullYear();
    const month = String(noww.getMonth() + 1).padStart(2, '0');
    const day = String(noww.getDate()).padStart(2, '0');
    const hours = String(noww.getHours()).padStart(2, '0');
    const minutes = String(noww.getMinutes()).padStart(2, '0');
    //   const seconds = String(noww.getSeconds()).padStart(2, '0');

    now.value = `${year}-${month}-${day}T${hours}:${minutes}`;
}

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

                        // add these two extra keys so that can be used while initilization of date picker, and for update as well
                        member.clockin_time_edit = parse(time.clockin_time, 'yyyy-MM-dd HH:mm', new Date())
                        member.clockout_time_edit = (time.clockout_time) ? parse(time.clockout_time, 'yyyy-MM-dd HH:mm', new Date()) : now.value
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

    setLoading(true)

    submitCrewMembersToVerify.value = []
    CrewMembersTobeVerified.value.map(member => (member.hasOwnProperty('isChecked') && member.isChecked) ?
        submitCrewMembersToVerify.value.push(member.id) : '')

    axios.post('/verify-crew-members', {
        'crewId': crewId.value,
        'crewMembers': submitCrewMembersToVerify.value,
        'crewTypeId': crewTypeId.value
    })
        // .then(res => getCrewMembers()) call setLocalStorageFlag instead of getCrewMembers everywhere in then block
        .then(res => setLocalStorageFlag())
        .catch(err => console.log(err))
        .finally(() => setLoading(false))
}

const clockinout = (type) => {

    setLoading(true)

    const response = axios.post('/clockinout-crew-members', {
        'crewId': crewId.value,
        'type': type,
        'isMenual': false,
    })
        .then(res => setLocalStorageFlag())
        .catch(error => {
            console.log('bs kr')
            let errorMessage = error.response.data.message
            errorMessage ? toast.error(errorMessage) : 'Something went wrong'
        })
        .finally(() => setLoading(false))


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

    setLoading(true)

    const formatedDateTime = format(event, dateTimeFormat) // to adjust formate of date picker

    axios.post('/clockinout-crew-members', {
        'crewId': crewId.value,
        'type': type,
        'isMenual': true,
        'timesheetId': timesheetId,
        // 'time': event.target.value
        'time': formatedDateTime
    })
        .then(res => setLocalStorageFlag())
        .catch(error => {
            let errorMessage = error.response.data.message
            errorMessage ? toast.error(errorMessage) : 'Something went wrong'
        })
        .finally(() => setLoading(false))
}


// add new crew member
const GetAllUsers = (users) => {

    users.map(user => {
        (user.role_id == 6) ? allUsers.value.push(user) : ''; // if crew member
    })

    createNewCrewForm.value[0].clockin_time = now.value
}
const addNewCrew = () => {

    setLoading(true)

    createNewCrewForm.value[0].clockin_time = format(createNewCrewForm.value[0].clockin_time, dateTimeFormat) // adjust for date picker formate

    axios.post('/add-new-crew-members', {
        'crewId': crewId.value,
        'createNewCrewForm': createNewCrewForm.value[0],
    })
        .then(res => {
            allUsers.value = []
            setLocalStorageFlag()
        })
        .catch(error => {
            let errorMessage = error.response.data.message
            errorMessage ? toast.error(errorMessage) : 'Something went wrong'
        })
        .finally(() => setLoading(false))
}

const crewMemberDeleted = () => setLocalStorageFlag()

const hfPerDiemDone = (status) => {
    allPerDiemStatus.value = status
    setLocalStorageFlag()
}

const trackTimeDone = () => setLocalStorageFlag()

const readyForVerification = () => {

    setLoading(true)

    axios.post('/ready-for-verification', {
        'crewId': crewId.value,
    })
        .then(res => {
            setLocalStorageFlag()
        })
        .catch(err => console.log(err))
        .finally(() => setLoading(false))
}

const weatherEntry = () => {

    setLoading(true)

    axios.post('/wather-entry', {
        'crewId': crewId.value,
    })
        .then(res => {
            setLocalStorageFlag()
        })
        .catch(err => console.log(err))
        .finally(() => setLoading(false))
}



const handleScroll = (() => {
    if (window.scrollY > scrollThreshold) {
        iconVisible.value = false;
    } else {
        iconVisible.value = true;
    }

})

</script>

<style>
.verify-crew-members {
    font-size: 14px;
    background: #1A2035 !important;
    min-height: 300px; /* to fit date picker in all screens */
}

.verify-crew-members thead{
    height: 100px; /* to fit date picker in all screens */
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

/* to manage date picker  */

.dp__pointer.dp__input_readonly{
    min-width: 210px !important;
}
.dp__main{
    position: static !important;

}
.dp__outer_menu_wrap.dp--menu-wrapper{
    top: 0 !important;
    left: 35% !important;
}
/* to manage date picker ends  */


/* to fit datepicker on mobile devices */
@media (max-width: 767px) {
  .modal-dialog {
    max-width: 100%;
    margin: 0;
  }

  .modal-content {
    border-radius: 0;
  }

  .responsive-datepicker {
    width: 100%;
  }
  .dp__outer_menu_wrap.dp--menu-wrapper{
    left: 10% !important;
    top: 30% !important;
}
.dp--menu-wrapper{
    z-index: 9999999999 !important;
}
.dp__menu_inner{
    position: relative;
    z-index: 1200;
}
}
/* to fit datepicker on mobile devices ends */




@media only screen and (min-device-width: 768px) and (max-device-width: 1024px) {
    .modal-lg{
        max-width: 100% !important;
    }
}


</style>