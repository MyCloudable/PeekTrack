<template>

    <LoadingOverlay />

    <a href="javascript:;" class="nav-link" data-bs-toggle="modal" data-bs-target="#clockin"
        @click="() => { getCrewMembers(); checkOrientation(); }">
        <span class="sidenav-normal  ms-2  ps-1">
            <h2> <i class="fas fa-business-time"></i> </h2>
        </span>
    </a>


    <div class="modal fade" id="clockin" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
        aria-hidden="true">
        <div v-if="isPortrait" class="rotate-overlay">
            <div class="rotate-content">
                <i class="fas fa-sync fa-spin fa-3x mb-3"></i>
                <p>Please rotate your device to landscape mode</p>
            </div>
        </div>


        <div class="modal-dialog modal-dialog-centered modal-xl" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="row header">

                        <!-- Late Entry Time -->
                        <div class="row text-dark mb-3 align-items-center">
                            <div class="col-1">
                                <i class="fa fa-clock-o" aria-hidden="true" @click="toggleLateEntryTime"></i>
                            </div>
                            <div class="col-3">
                                <VueDatePicker v-model="lateEntryTime" :enable-time="true" :formate="dateTimeFormat"
                                    class="responsive-datepicker" v-if="isLateEntryTimeVisible"></VueDatePicker>
                            </div>
                            <div class="col-8"></div>
                        </div>
                        <!-- Late Entry Time Ends -->

                        <i class="fas fa-yen-sign"></i>
                        <div class="col-md-6 text-dark"><span class="badge badge-info me-2">Status: </span> {{ status }}
                            <select class="d-inline w-50 ms-2 mt-3" v-if="!isAlreadyVerified || enableCrewTypeId"
                                v-model="crewTypeId">
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
                                :crewTypeId="crewTypeId" :key="departKey" @track-time-done="trackTimeDone"
                                @is-mobilization="enableCrewTypeId = !enableCrewTypeId"
                                :is-late-entry-time-visible="isLateEntryTimeVisible"
                                :late-entry-time="lateEntryTime ? format(lateEntryTime, dateTimeFormat) : lateEntryTime"
                                @last-entry-time-done="lastEntryTimeDone" />

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
                            <thead>
                                <tr>
                                    <th v-if="!isAlreadyVerified" title="Check All">✔️</th>
                                    <th title="Crew Member Name">👤</th>
                                    <th v-if="isAlreadyClockedin || isAlreadyClockedout" title="Status">Status</th>
                                    <th v-if="isAlreadyClockedin" title="Time In">Punch Time</th>
                                    <th v-if="isMenualClockinout" title="Time Out">Punch Time</th>
                                    <th v-if="isAlreadyClockedin || isAlreadyClockedout" title="Time">Total</th>
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
                                            :formate="dateTimeFormat" class="responsive-datepicker"></VueDatePicker>
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
                                        <div>{{ member.name }} ({{ member.total_time_all }})</div>
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
                                            <VueDatePicker v-model="member.clockin_time_edit" :enable-time="true"
                                                :formate="dateTimeFormat" :auto-apply="true" :placement="'top'"
                                                @update:model-value="menualClockinout($event, member.timesheet_id, 'clockin')"
                                                class="responsive-datepicker"></VueDatePicker>
                                        </div>
                                    </td>

                                    <td v-if="member.isMenualClockinout">
                                        <div>
                                            <!-- <input type="datetime-local" class="form-controll datetime"
                                                @change="menualClockinout($event, member.timesheet_id, 'clockout')"
                                                :value="(member.clockout_time) ? member.clockout_time : now"> -->
                                            <VueDatePicker v-model="member.clockout_time_edit" :enable-time="true"
                                                :formate="dateTimeFormat" :auto-apply="true" :placement="'top'"
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
                                            <i class="fa fa-pencil cursor-pointer"
                                                @click="openTimeEditModal(member)"></i>
                                            &nbsp&nbsp&nbsp&nbsp
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
    <!-- Edit Time Modal -->
    <div class="modal fade" id="editTimeModal" tabindex="-1" role="dialog" aria-labelledby="editTimeModalLabel"
        aria-hidden="true">
        <div v-if="isEditModalLandscape" class="rotate-notice rotate-portrait text-center">
            <div class="rotate-content">
                <i class="fas fa-sync fa-spin fa-3x mb-3"></i>
                <p>Please rotate your device to landscape mode</p>
            </div>
        </div>

        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content text-dark">
                <div class="modal-header">
                    <h5 class="modal-title" id="editTimeModalLabel">Edit Clock In/Out</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body d-flex flex-column gap-3">
                    <!-- Clock In -->
                    <div class="datepicker-wrapper">
                        <label>Clock In</label>
                        <VueDatePicker v-model="modalClockin" :enable-time="true" :auto-apply="true"
                            :formate="dateTimeFormat" class="form-control responsive-datepicker"
                            @update:model-value="saveMenualTime('clockin')" />
                    </div>

                    <!-- Clock Out -->
                    <div class="datepicker-wrapper">
                        <label>Clock Out</label>
                        <VueDatePicker v-model="modalClockout" :enable-time="true" :auto-apply="true"
                            :formate="dateTimeFormat" class="form-control responsive-datepicker"
                            @update:model-value="saveMenualTime('clockout')" />
                    </div>
                    <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button class="btn btn-warning" @click="applyManualTimes">Save</button>
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
import { useLoading } from '../composables/useLoading'


const isPortrait = ref(false)

const checkOrientation = () => {
    const isPortraitMode =
        (window.screen.orientation && window.screen.orientation.type.startsWith('portrait')) ||
        window.orientation === 0 || // fallback for older iOS
        window.innerHeight > window.innerWidth

    isPortrait.value = isPortraitMode
}

const checkEditModalOrientation = () => {
    const isLandscape =
        (window.screen.orientation && window.screen.orientation.type.startsWith('landscape')) ||
        window.orientation === 90 || window.orientation === -90 ||
        window.innerWidth > window.innerHeight

    isEditModalLandscape.value = isLandscape
}


onMounted(() => {
    checkOrientation()
    checkEditModalOrientation()

    window.addEventListener('resize', () => {
        checkOrientation()
        checkEditModalOrientation()
    })
    window.addEventListener('orientationchange', () => {
        checkOrientation()
        checkEditModalOrientation()
    })

    const editModal = document.getElementById('editTimeModal')
    if (editModal) {
        editModal.addEventListener('shown.bs.modal', checkEditModalOrientation)
    }
})

onUnmounted(() => {
    window.removeEventListener('resize', checkOrientation)
    window.removeEventListener('resize', checkEditModalOrientation)
    window.removeEventListener('orientationchange', checkOrientation)
    window.removeEventListener('orientationchange', checkEditModalOrientation)

    const editModal = document.getElementById('editTimeModal')
    if (editModal) {
        editModal.removeEventListener('shown.bs.modal', checkEditModalOrientation)
    }
})

const forceReflow = () => {
    document.body.offsetHeight // triggers reflow
}
const isEditModalLandscape = ref(false)

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

let lateEntryTime = ref('')
let isLateEntryTimeVisible = ref(false)


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

            const totalTimes = new Map(); // to store total time all per user_id

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

                // Aggregate total time all for each user_id
                if (!totalTimes.has(time.user_id)) {
                    totalTimes.set(time.user_id, 0)
                }
                totalTimes.set(time.user_id, totalTimes.get(time.user_id) + time.total_time)

            })

            departKey.value++ //this will causes Vue to recreate the depart component

            // After processing all timesheets, assign the total time all to each member
            CrewMembersTobeVerified.value.forEach(member => {
                if (totalTimes.has(member.id)) {
                    member.total_time_all = TimeConvert(totalTimes.get(member.id));
                } else {
                    member.total_time_all = '0'; // or any default value if no time is found
                }
            });

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

    if (!confirm('Are you sure you want to verify the crew?')) return

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

    // if late entry time is Visible then late entry time field should be filled
    if (isLateEntryTimeVisible.value && !lateEntryTime.value) {
        toast.error('Please select the late entry time if its visible, otherwise toggle it to disable')
        return
    }

    if (!confirm(`Are you sure you want to ${type} ?`)) return

    setLoading(true)

    const response = axios.post('/clockinout-crew-members', {
        'crewId': crewId.value,
        'type': type,
        'isMenual': false,
        'lateEntryTime': lateEntryTime.value ? format(lateEntryTime.value, dateTimeFormat) : lateEntryTime.value
    })
        .then(res => {
            setLocalStorageFlag()
            lastEntryTimeDone()
        })
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
    allUsers.value = users
        .filter(user => user.role_id === 6)  // Only add crew members
        .map(user => ({
            id: user.id,
            text: `${user.id} - ${user.name}`,  // Format as "ID - Name"
        }));

    // Set default values in the form
    createNewCrewForm.value[0].clockin_time = now.value;
};

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

    if (!confirm('Are you sure you are ready for verification?')) return

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

    if (!confirm('Are you sure you want to add weather time for this crew?')) return

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

const toggleLateEntryTime = () => {
    isLateEntryTimeVisible.value = !isLateEntryTimeVisible.value
    lateEntryTime.value = ''
}

const lastEntryTimeDone = () => {
    isLateEntryTimeVisible.value = false
    lateEntryTime.value = ''
}



const handleScroll = (() => {
    if (window.scrollY > scrollThreshold) {
        iconVisible.value = false;
    } else {
        iconVisible.value = true;
    }

})

const modalClockin = ref('')
const modalClockout = ref('')
const activeMemberId = ref(null)

const openTimeEditModal = (member) => {
    modalClockin.value = member.clockin_time_edit
    modalClockout.value = member.clockout_time_edit
    activeMemberId.value = member.id
    const modal = new bootstrap.Modal(document.getElementById('editTimeModal'))
    modal.show()
}

const applyManualTimes = () => {
    const member = CrewMembersTobeVerified.value.find(m => m.id === activeMemberId.value)
    if (member) {
        member.clockin_time_edit = modalClockin.value
        member.clockout_time_edit = modalClockout.value
        menualClockinout(modalClockin.value, member.timesheet_id, 'clockin')
        menualClockinout(modalClockout.value, member.timesheet_id, 'clockout')
    }
    bootstrap.Modal.getInstance(document.getElementById('editTimeModal')).hide()
}


</script>

<style scoped>
/* General Modal & Content Styling */
#clockin .modal,
#clockin .modal-content,
#clockin .modal-body {
    overflow: visible !important;
    position: relative !important;
    z-index: 99999;
}

/* Full width & height modal aligned to top-left */
#clockin .modal-dialog {
    position: fixed !important;
    top: 0 !important;
    left: 0 !important;
    width: 100vw !important;
    height: 100vh !important;
    max-width: 100vw !important;
    margin: 0 !important;
    padding: 0 !important;
}

/* Full height modal content, no border radius */
#clockin .modal-content {
    width: 100vw !important;
    height: 100vh !important;
    border-radius: 0 !important;
    font-size: 1rem;
}

/* Modal body scrollable */
#clockin .modal-body {
    height: calc(100vh - 120px) !important;
    /* adjust if you have header/footer */
    overflow-y: auto !important;
    overflow-x: visible !important;
    -webkit-overflow-scrolling: touch;
    padding-bottom: 5rem;
}

#clockin .modal-content {
    width: auto !important;
    height: auto !important;
    z-index: 9999 !important;
    /* Bootstrap default */
    border-radius: 0.5rem !important;

}


/* Table layout */
.table-responsive {
    overflow-x: auto !important;
}

.table {

    table-layout: fixed !important;
    width: 100% !important;
}

.table th,
.table td {
    white-space: nowrap;
    text-align: center;
    vertical-align: middle;
    line-height: 10px;
}

.dark-version .table> :not(caption)>*>* {
    border-color: rgba(255, 255, 255, 0.4) !important;
    color: #fff !important;
}

.verify-crew-members th,
.verify-crew-members td {
    white-space: nowrap;
    text-align: center;
    font-size: 0.85rem;
    vertical-align: middle;
    font-weight: bold;
}

.verify-crew-members td.d-flex {
    gap: 6px;
    justify-content: center;
    align-items: center;
}

.verify-crew-members .responsive-datepicker {
    max-width: 160px !important;
}

/* Make sure the modal and table adjust on smaller screens */
@media (max-width: 1024px) {
    .verify-crew-members {
        font-size: 0.75rem;
    }

    .verify-crew-members .responsive-datepicker {
        max-width: 140px !important;
    }
}


.table td.text-truncate {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

/* Date picker wrapper fixes */
.dp__main {
    overflow: visible !important;
    position: relative !important;
}

.dp__outer_menu_wrap.dp--menu-wrapper {
    position: absolute !important;
    z-index: 999999999 !important;
    top: 200px !important;
    left: 35% !important;
}

.dp__pointer.dp__input_readonly {
    min-width: 210px !important;
}

/* Remove clear (X) button from date picker */
.dp--clear-btn {
    display: none !important;
}

/* Responsive fix for smaller mobile screens */
@media (max-width: 767px) {

    .modal-dialog,
    .modal-content {
        width: 100vw !important;
        height: 100vh !important;
    }

    .responsive-datepicker {
        width: 100%;
    }

    .dp__outer_menu_wrap.dp--menu-wrapper {
        left: 10% !important;
    }

    .dp--menu-wrapper {
        z-index: 9999999999 !important;
    }

    .dp__menu_inner {
        position: relative;
        z-index: 1200;
    }
}

/* Optional: Zoom out for iPads */
@media only screen and (min-device-width: 768px) and (max-device-width: 1024px) {

    .modal-dialog {
        transform: scale(0.9);
        transform-origin: top left;
    }

    .modal-content {
        font-size: 0.9rem;
    }
}

@media only screen and (min-device-width: 768px) and (max-device-width: 1024px) and (orientation: landscape) {

    .modal-dialog {
        transform: scale(0.85);
    }

    .modal-content {
        font-size: 0.85rem;
    }
}



body.modal-open {
    overflow: visible !important;
}


.datepicker-wrapper {
    position: relative;
}

/* Force the picker popup to go above the input */
.datepicker-wrapper .dp__outer_menu_wrap {
    position: absolute !important;
    top: 30% !important;
    bottom: 100% !important;
    left: 0 !important;
    z-index: 99999 !important;
    margin-bottom: 8px !important;
    width: max-content;
    max-width: 100%;
}

/* Optional: Prevent overflow in smaller screens */
@media (max-width: 768px) {
    .datepicker-wrapper .dp__outer_menu_wrap {
        left: 50% !important;
        transform: translateX(-50%);
        width: 90vw !important;
    }
}

.rotate-notice {
    background: #ffeeba;
    color: #856404;
    padding: 1rem;
    border-radius: 8px;
    border: 1px solid #ffeeba;
    margin-bottom: 1rem;
}

.rotate-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100vw;
    height: 100vh;
    background: rgba(255, 235, 59, 0.95);
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 999999;
    text-align: center;
    overflow: hidden;
}

body.modal-open .rotate-overlay {
    overflow: hidden;
}


.rotate-content {
    color: #333;
    font-size: 1.2rem;
}

.rotate-portrait {
    position: fixed;
    top: 0;
    left: 0;
    width: 100vw;
    height: 100vh;
    background: rgba(255, 235, 59, 0.95);
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 999999;
    text-align: center;
    overflow: hidden;
}

.navbar-vertical.navbar-expand-xs.fixed-start {
    left: 0;
    z-index: 5;
}
</style>