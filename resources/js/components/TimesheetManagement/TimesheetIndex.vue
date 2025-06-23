<template>

    <LoadingOverlay />

    <div class="row">
        <div class="card card-frame">
            <div class="card-body">
                <div class="card-title">Create Timesheet</div>
                <timesheet-form :users="props.users" :jobs="props.jobs" :timetypes="props.timetypes"
                    :crewtypes="props.crewtypes" :uniqueSuperintendents="props.uniqueSuperintendents"
                    @create-timesheet="TimesheetCreated" :authuser="authuser" />
            </div>
        </div>
    </div>

    <div class="row mt-5">
        <div class="card card-frame">
            <div class="card-body">
                <div class="card-title">Advance Filter</div>
                <timesheet-filter :users="props.users" :jobs="props.jobs" :authuser="authuser" @filter="handleFilter" />
            </div>
        </div>

    </div>

    <div class="row mt-5">
        <div class="col-md-12">
            <button class="btn btn-secondary mb-5" v-if="editingRows.size > 0" @click="updateAllRows(null)">Update
                All</button>
            <DataTable :options="tableOptions" ref="dataTableRef" class="table custom-hover table-hover" />

            <span id="totalTimeFooter">Total time: {{ totalTimeFooter }}</span>
        </div>
    </div>

</template>

<script setup>
import axios from 'axios'
import { ref, reactive, onMounted, watch, nextTick } from 'vue'
import TimesheetFilter from './Filter'
import TimesheetForm from './TimesheetForm'
import TimeConvert from '../../composables/TimeConvert'

import DataTable from 'datatables.net-vue3';
import DataTablesCore from 'datatables.net-bs5';

import { useToast } from "vue-toastification";

import LoadingOverlay from '../shared/LoadingOverlay.vue'
import { useLoading } from '../../composables/useLoading'

import { useRecovery } from "../../composables/useRecovery"


import { createApp } from 'vue';
import { result } from 'lodash';

const toast = useToast();

const { isLoading, setLoading } = useLoading()

const { recover } = useRecovery()

DataTable.use(DataTablesCore);

const props = defineProps({
    users: Object,
    jobs: Object,
    timetypes: Object,
    // roleid: Number,
    authuser: Object,
    crewtypes: Object,
    uniqueSuperintendents: Object
})


const filterData = ref('')
const dataTableRef = ref(null)
const editingRows = reactive(new Set())

const selectAllApproval = ref(false)

const totalTimeFooter = ref('')
axios.defaults.headers.common['X-CSRF-TOKEN'] = document.querySelector('meta[name="csrf-token"]').getAttribute('content');



const tableOptions = ref({
    processing: true,
    serverSide: true,
    ajax: {
        url: '/timesheet-management/getall',
        data: function (params) {
            // Merge DataTables parameters with custom parameters
            return {
                ...params,
                filterData: filterData.value,
            };
        },
        type: 'GET',
        dataType: 'json',
        beforeSend: function (request) {
            // Optionally, modify AJAX request headers or add extra parameters
        },
        error: function (xhr, textStatus, error) {
            console.error('Ajax error:', error);
        },
        complete: function (response) {

            // Optionally, handle after AJAX request completion
            nextTick(() => {

                document.querySelectorAll('.edit-icon').forEach((el) => {
                    el.addEventListener('click', handleEditClick)
                });

                // const selectAllCheckbox = document.querySelector('.select-all-approval')
                // if (selectAllCheckbox) {
                //     console.log('checkbox exist ' + selectAllCheckbox)
                //     selectAllCheckbox.addEventListener('change', handleSelectAllApproval)
                // }

                document.querySelectorAll('.select-all-approval')
                    .forEach((checkbox) => {
                        checkbox.addEventListener('change', handleSelectAllApproval)
                    })

                document.querySelectorAll('.payroll-approval-checkbox, .crew-member-approval-checkbox, .reviewer-approval-checkbox, .weekend-out-approval-checkbox')
                    .forEach((checkbox) => {
                        checkbox.addEventListener('change', handleCheckboxChange)
                    })

                document.querySelectorAll('.delete-icon').forEach((el) => {
                    el.addEventListener('click', handleDeleteClick)
                });

                document.querySelectorAll('.recover-icon').forEach((el) => {
                    el.addEventListener('click', handleRecoverDelete)
                });

            });

        }
    },

    drawCallback: function (settings) {

        // event listeners for custom search on job number dropdown

        document.querySelectorAll('.search-input').forEach((el) => {
            const timesheetId = el.getAttribute('data-id')
            el.addEventListener('keyup', (event) => handleFilterOptions(event, timesheetId))
        });

        document.querySelectorAll('.dropdown-item').forEach(item => {
            const timesheetId = item.getAttribute('data-id')
            item.addEventListener('click', (event) => selectOption(event, timesheetId))
        });
    },

    columns: [
        {
            data: 'created_by',
            title: 'Created By',
            render: function (data, type, row, meta) {
                return `<small><small><small>${data}</small></small></small>`;
            }
        },
        {
            data: 'timesheet_id', title: 'ID', render: function (data, type, row, meta) {
                return `<small><small><small>${data}</small></small></small>`;
            }
        },
        { data: 'crewmember_name', title: 'Crew Member' },
        {
            data: 'superintendent_name', title: 'Superintendent', render: function (data, type, row, meta) {
                return `<small><small><small>${data}</small></small></small>`;
            }
        },

        {
            data: 'job_number_county',
            title: 'Job #(County)',
            render: function (data, type, row) {
                if (editingRows.has(row.timesheet_id)) {

                    // const options = props.jobs.map(job =>
                    //     `<option value="${job.id}" ${job.id === row.job_id ? 'selected' : ''}>${job.text}</option>`
                    // ).join('');
                    // return `<select class="form-control bg-white job-number-select" data-id="${row.timesheet_id}">${options}</select>`;

                    const options = props.jobs.map(job =>
                        `<li class="dropdown-item" data-value="${job.id}" ${job.id === row.job_id ? 'selected' : ''} data-id="${row.timesheet_id}" >${job.text}</li>`
                    ).join('')

                    return `
                    <div class="searchable-dropdown position-relative">
                        <input type="text" class="form-control job-number-select search-input bg-white" placeholder="Search Job Number..." 
                            data-id="${row.timesheet_id}" data-value="${row.job_id}" value="${data}" />
                        <ul class="custom-dropdown-list" id="dropdown-list-${row.timesheet_id}">
                            ${options}
                        </ul>
                    </div>`;
                }

                return `${data}`;
            }
        },

        {
            data: 'time_type_name',
            title: 'Type',
            render: function (data, type, row, meta) {
                if (editingRows.has(row.timesheet_id)) {
                    const options = props.timetypes.map(time_type =>
                        `<option value="${time_type.id}" ${time_type.id === row.time_type_id ? 'selected' : ''}>${time_type.name}</option>`
                    ).join('');
                    return `<select class="form-control bg-white time-type-select" data-id="${row.timesheet_id}">${options}</select>`
                }
                return data ?? 'Production'
            }
        },
        {
            data: 'clockin_time',
            title: 'In',
            render: function (data, type, row) {
                if (editingRows.has(row.timesheet_id)) {
                    return `<input type="datetime-local" class="form-control bg-white clockin-time-input" value="${data}" data-id="${row.timesheet_id}" />`
                }
                return data
            }
        },
        {
            data: 'clockout_time',
            title: 'Out',
            render: function (data, type, row) {
                if (editingRows.has(row.timesheet_id)) {
                    return `<input type="datetime-local" class="form-control bg-white clockout-time-input" value="${data}" data-id="${row.timesheet_id}" />`
                }
                return data
            }
        },

        {
            data: 'total_time',
            title: 'Total',
            render: function (data, type, row, meta) {
                return TimeConvert(data)
            }
        },
        {
            data: 'per_diem',
            title: 'PD',
            orderable: true,
            render: function (data, type, row) {
                if (editingRows.has(row.timesheet_id)) {
                    const options = `
                    <option value="">Select per diem</option>
                    <option value="h" ${'h' === row.per_diem ? 'selected' : ''}>h</option>
                    <option value="f" ${'f' === row.per_diem ? 'selected' : ''}>f</option>`

                    return `<select class="form-control bg-white per-diem-select" data-id="${row.timesheet_id}">${options}</select>`
                }
                return data
            }
        },
        {
            data: 'weekend_out',
            title: 'WEO',
            orderable: true,
            render: function (data, type, row) {
                let isDisabled = false

                //if (props.authuser.role_id >= 3) {
                if (row.crewmember_role == 6) { // if time entry user role is crew member then make WEO disable
                    isDisabled = true
                }
                return `<input type="checkbox" class="form-check-input weekend-out-approval-checkbox" data-id="${row.timesheet_id}" data-type="weekend_out" ${data ? 'checked' : ''} ${isDisabled ? 'disabled' : ''} />`
            }
        },
        {
            data: 'crew_member_approval',
            title: 'CMA',
            orderable: true,
            render: function (data, type, row) {
                return `<input type="checkbox" class="form-check-input crew-member-approval-checkbox" data-id="${row.timesheet_id}" data-type="crew_member_approval" ${data ? 'checked' : ''} disabled />`
            }
        },
        {
            data: 'reviewer_approval',
            title: '<input type="checkbox" class="form-check-input select-all-approval" data-type="reviewer_approval" data-el="reviewer-approval-checkbox" /> RA',
            orderable: true,
            render: function (data, type, row) {
                return `<input type="checkbox" class="form-check-input reviewer-approval-checkbox" data-id="${row.timesheet_id}" data-type="reviewer_approval" ${data ? 'checked' : ''} ${props.authuser.role_id <= 3 ? '' : 'disabled'} />`
            }
        },

        {
            data: 'payroll_approval',
            title: '<input type="checkbox" class="form-check-input select-all-approval" data-type="payroll_approval" data-el="payroll-approval-checkbox" /> PA',
            orderable: false,
            render: function (data, type, row) {
                return `<input type="checkbox" class="form-check-input payroll-approval-checkbox" data-id="${row.timesheet_id}" data-type="payroll_approval" ${data ? 'checked' : ''} ${props.authuser.role_id == 5 ? '' : 'disabled'} />`
            }
        },
        {
            data: 'action',
            title: 'Action',
            orderable: false,
            render: function (data, type, row) {
                // Function to check if the current user can edit based on roles and approvals
                const canEdit = () => {
                    // Role checks
                    const isCrewMember = false
                    const isSuperintendent = props.authuser.role_id === 3
                    const isReviewer = props.authuser.role_id === 2
                    const isPayrollAdmin = props.authuser.role_id === 5
                    const isAdmin = props.authuser.role_id === 1
                    const isManager = props.authuser.role_id === 7 // allow managers also to edit


                    // Approval statuses
                    const isCmaApproved = row.crew_member_approval === 1
                    const isReviewerApproved = row.reviewer_approval === 1
                    const isPayrollApproved = row.payroll_approval === 1

                    // response to send
                    // let res = true

                    // if (!isCmaApproved && !isReviewerApproved && !isPayrollApproved) {
                    //     if (isSuperintendent || isReviewer || isPayrollAdmin) {
                    //         res = true
                    //     } else {
                    //         res = false
                    //     }
                    // }
                    // if (isCmaApproved) {
                    //     if (isReviewer || isPayrollAdmin) {
                    //         res = true
                    //     } else {
                    //         res = false
                    //     }
                    // }
                    // if (!isCmaApproved) {
                    //     if (isSuperintendent || isReviewer || isPayrollAdmin) {
                    //         res = true
                    //     } else {
                    //         res = false
                    //     }
                    // }
                    // if (isReviewerApproved) {
                    //     if (isReviewer || isPayrollAdmin) {
                    //         res = true
                    //     } else {
                    //         res = false
                    //     }
                    // }
                    // if (isPayrollApproved) {

                    //     res = false
                    // }

                    // return res



                    // // Default to false if any approval is done by CMA
                    // if (isCmaApproved) {
                    //     return isReviewer || isPayrollAdmin;
                    // }

                    // // Allow editing if no approvals and user is Superintendent, Reviewer, or Payroll Admin
                    // return !isCmaApproved && !isReviewerApproved && !isPayrollApproved &&
                    //     (isSuperintendent || isReviewer || isPayrollAdmin);


                    if (isCmaApproved) { // only reviewer and payroll admin can interact
                        if ((isReviewer || isPayrollAdmin || isAdmin || isManager) && (!isReviewerApproved && !isPayrollApproved)) {
                            return true
                        } else {
                            return false
                        }
                    } else { // all users, superintendent , reviewer and payroll admin can interact
                        if ((isSuperintendent || isReviewer || isPayrollAdmin || isAdmin || isManager) && (!isReviewerApproved && !isPayrollApproved)) {
                            return true
                        } else {
                            return false
                        }
                    }



                };

                // Determine if the pencil icon should be shown based on edit permissions
                const showPencilIcon = canEdit();

                // const showTrashIcon = (props.authuser.role_id === 3 || props.authuser.role_id === 2 || props.authuser.role_id === 5 || props.authuser.role_id === 1) && !row.payroll_approval;
                const showTrashIcon = ([1, 2, 3, 5, 7].includes(props.authuser.role_id)) && !row.payroll_approval; // allow manager also to delete


                let actionButtons = '';

                if (showPencilIcon) {
                    actionButtons += `<i class="fa fa-pencil cursor-pointer edit-icon" data-id="${row.timesheet_id}" aria-hidden="true"></i>`;
                } else {
                    actionButtons += `<i class="fa fa-times disabled-icon" aria-hidden="true"></i>`;
                }

                // if (showTrashIcon) {
                if (showPencilIcon) {
                    actionButtons += `<i class="fa fa-trash cursor-pointer delete-icon" data-id="${row.timesheet_id}" aria-hidden="true" style="margin-left: 10px;"></i>`;
                }

                // if user is Admin, give recover option for deleted items
                console.log('give admin recover icon')
                if (props.authuser.role_id == 11) {
                    if (row.deleted_at) {
                        actionButtons += `<i class="fas fa-trash-restore cursor-pointer recover-icon" data-id="${row.timesheet_id}" style="margin-left:10px;"></i>`
                    }
                }

                return actionButtons;
            }
        },
    ],
    order: [[7, 'asc']],
    footerCallback: function (tfoot, data, start, end, display) {
        totalTimeFooter.value = TimeConvert(data.reduce((acc, row) => acc + parseFloat(row.total_time), 0)) // Calculate total_time sum
    },

});


const handleFilter = (filter) => {
    console.log('handleFilter')
    filterData.value = filter; // Update filterData ref with new filter data
    console.log(filterData.value)

    if (dataTableRef.value && dataTableRef.value.dt) {
        console.log('11')
        dataTableRef.value.dt.ajax.reload(); // Trigger DataTable reload
    }
};


const handleCheckboxChange = async (event) => {

    setLoading(true)

    const checkbox = event.target
    if (checkbox.classList.contains('form-check-input')) {
        const id = checkbox.dataset.id
        const isChecked = checkbox.checked
        const type = checkbox.dataset.type

        try {


            const response = await axios.post('/timesheet-management/update-checkbox-approval', {
                id: id,
                approved: isChecked,
                type: type
            })

            if (response.data.success) {
                if (dataTableRef.value && dataTableRef.value.dt) {
                    console.log('12')
                    dataTableRef.value.dt.ajax.reload(null, false) // Reload table data without resetting pagination
                }
                toast.success('Approval status updated successfully')
            } else {

                toast.error('Failed to update approval status')
            }
        } catch (error) {

            toast.error('An error occurred while updating approval status')
        } finally {
            setLoading(false)
        }
    }
}


const handleSelectAllApproval = async (event) => {

    console.log('handleSelectAllApproval')

    setLoading(true)

    const checkbox = event.target
    const checkboxes = document.querySelectorAll(`.${checkbox.dataset.el}`)
    const selectedIds = []

    selectAllApproval.value = checkbox.checked
    const type = checkbox.dataset.type

    checkboxes.forEach(checkbox => {
        if (!checkbox.disabled) { // Check if the checkbox is not disabled
            checkbox.checked = selectAllApproval.value
            selectedIds.push({
                id: checkbox.dataset.id,
            })
        }
    })

    try {
        const response = await axios.post('/timesheet-management/update-checkbox-approval-bulk', {
            selectedIds: selectedIds,
            approved: selectAllApproval.value,
            type: type
        })

        if (response.data.success) {
            if (dataTableRef.value && dataTableRef.value.dt) {
                console.log('13')
                dataTableRef.value.dt.ajax.reload(null, false)
                // dataTableRef.value.reload()
            }
            toast.success('Approval status updated successfully')
        } else {
            toast.error('Failed to update approval status')
        }
    } catch (error) {
        toast.error('An error occurred while updating approval status')
    } finally {
        setLoading(false)
    }
}


const handleEditClick = (event) => {

    const id = event.target.getAttribute('data-id')
    if (editingRows.has(parseInt(id))) {
        // editingRows.delete(parseInt(id))
        // saveRow(id)
        updateAllRows(id)
    } else {
        editingRows.add(parseInt(id))
    }

    if (dataTableRef.value && dataTableRef.value.dt) {
        console.log('14')
        dataTableRef.value.dt.ajax.reload(null, false)
    }

}


const saveRow = async (id) => {

    setLoading(true)

    const clockinInput = document.querySelector(`.clockin-time-input[data-id="${id}"]`)
    const clockoutInput = document.querySelector(`.clockout-time-input[data-id="${id}"]`)
    const jobNumberSelect = document.querySelector(`.job-number-select[data-id="${id}"]`);
    const timeTypeSelect = document.querySelector(`.time-type-select[data-id="${id}"]`);
    const perDiemSelect = document.querySelector(`.per-diem-select[data-id="${id}"]`);

    const clockinTime = clockinInput ? clockinInput.value : null;
    const clockoutTime = clockoutInput ? clockoutInput.value : null;
    const jobNumber = jobNumberSelect ? jobNumberSelect.getAttribute('data-value') : null;
    const timeType = timeTypeSelect ? timeTypeSelect.value : null;
    const perDiem = perDiemSelect ? perDiemSelect.value : null;

    try {
        const response = await axios.post('/timesheet-management/update-times', {
            id: id,
            clockin_time: clockinTime,
            clockout_time: clockoutTime,
            job_number: jobNumber,
            time_type: timeType,
            per_diem: perDiem
        })

        if (response.data.success) {
            if (dataTableRef.value && dataTableRef.value.dt) {
                console.log('15')
                dataTableRef.value.dt.ajax.reload(null, false); // Reload table data without resetting pagination
            }
            toast.success('Timesheet entry updated successfully')
        } else {
            toast.error('Failed to updated timesheet entry')
        }
        alert(response.data.message)

    } catch (error) {
        // alert('here coming error')
        alert(`Error: ${error.response.data.message}`);
        toast.error('An error occurred while updating the timesheet entry')
    } finally {
        setLoading(false)
    }
}




const updateAllRows = async (singleRowId) => {

    setLoading(true)

    const rowsData = []

    // Determine which rows to process
    const rowIds = singleRowId !== null ? [singleRowId] : Array.from(editingRows);

    console.log('oh')
    console.log(rowIds)

    rowIds.forEach(id => {

        const clockinInput = document.querySelector(`.clockin-time-input[data-id="${id}"]`)
        const clockoutInput = document.querySelector(`.clockout-time-input[data-id="${id}"]`)
        const jobNumberSelect = document.querySelector(`.job-number-select[data-id="${id}"]`)
        const timeTypeSelect = document.querySelector(`.time-type-select[data-id="${id}"]`)
        const perDiemSelect = document.querySelector(`.per-diem-select[data-id="${id}"]`)

        rowsData.push({
            id: id,
            clockin_time: clockinInput ? clockinInput.value : null,
            clockout_time: clockoutInput ? clockoutInput.value : null,
            job_number: jobNumberSelect ? jobNumberSelect.getAttribute('data-value') : null,
            time_type: timeTypeSelect ? timeTypeSelect.value : null,
            per_diem: perDiemSelect ? perDiemSelect.value : null
        })

    })

    console.table(rowsData)

    // return


    try {
        const response = await axios.post('/timesheet-management/update-times', {
            rows: rowsData
        })

        if (response.data.success) {
            if (dataTableRef.value && dataTableRef.value.dt) {
                console.log('15')
                dataTableRef.value.dt.ajax.reload(null, false); // Reload table data without resetting pagination
            }
            toast.success('Timesheet entry updated successfully')
            editingRows.clear()
        } else {
            toast.error('Failed to updated timesheet entry')
        }
        // alert(response.data.message)

    } catch (error) {

        (error.response.data.message) ? toast.error(error.response.data.message) : ''

        // toast.error('An error occurred while updating the timesheet entry')
        console.table(error.response.data.errors)

        const errorIds = new Set();

        error.response.data.errors.forEach((error) => {
            toast.error(error.message)
            errorIds.add(error.id);
        })

        console.log('errorIds')
        console.table(errorIds)

        // Filter the editingRows Set to keep only those with errors
        editingRows.forEach(id => {
            if (!errorIds.has(id)) {
                editingRows.delete(id);
            }
        })

    } finally {

        // (singleRowId) ? editingRows.delete(parseInt(singleRowId)) : editingRows.clear()

        // (singleRowId) ? console.log('remove single') : console.log('remove all')
        // if(singleRowId){
        //     console.log('if condition ' + singleRowId)
        // }else{
        //     console.log('else condition ' + singleRowId)
        // }
        setLoading(false)

        console.log('finally block')
        if (dataTableRef.value && dataTableRef.value.dt) {
            console.log('16')
            dataTableRef.value.dt.ajax.reload(null, false)
        }

    }




}




const handleDeleteClick = async (event) => {
    const id = event.target.getAttribute('data-id');

    if (confirm('Are you sure you want to delete this timesheet?')) {
        try {

            setLoading(true)

            const response = await axios.delete(`/timesheets/${id}`);

            if (response.data.success) {
                if (dataTableRef.value && dataTableRef.value.dt) {
                    console.log('16')
                    dataTableRef.value.dt.ajax.reload(null, false);
                }
                toast.success('Timesheet entry deleted successfully')
                alert(response.data.message)
            } else {
                toast.error('Failed to delete timesheet entry')
                alert(response.data.message)
            }
        } catch (error) {
            let errorMessage = error.response.data.message
            errorMessage ? toast.error(errorMessage) : 'An error occurred while deleting the timesheet entry'
        } finally {
            setLoading(false)
        }
    }
};

const TimesheetCreated = () => {
    console.log('coming here')
    if (dataTableRef.value && dataTableRef.value.dt) {
        console.log('17')
        dataTableRef.value.dt.ajax.reload(null, false);
    }
}


const handleRecoverDelete = (event) => {
    const id = event.target.getAttribute('data-id')
    if (confirm(`Are you sure you want to recover this record?`)) {
        const res = recover('timesheet', id)
        if (res) {
            toast.success("Record have been recoverd successfully")
            if (dataTableRef.value && dataTableRef.value.dt) {
                setTimeout(() => {
                    dataTableRef.value.dt.ajax.reload(null, false)
                }, 500);
            }
        } else {
            toast.error("Something went wrong")
        }
    }

}




onMounted(async () => {

    try {
        console.log('18')
        dataTableRef.value = new DataTable(dataTableRef.value.$el, tableOptions.value);
    } catch (error) {
        console.error('Error initializing DataTable:', error);
    }

});




// custom search on job number dropdown in a datatable

function handleFilterOptions(event, timesheetId) {

    const inputElement = event.target
    let filter = inputElement.value.toUpperCase()
    let dropdownList = document.getElementById(`dropdown-list-${timesheetId}`)
    let options = dropdownList.getElementsByTagName("li")

    // Show the dropdown list as the user types
    dropdownList.style.display = "block"

    for (let i = 0; i < options.length; i++) {
        let optionText = options[i].textContent || options[i].innerText;
        if (optionText.toUpperCase().indexOf(filter) > -1) {
            options[i].style.display = ""
        } else {
            options[i].style.display = "none"
        }
    }
}

// Function to handle option selection
function selectOption(event, timesheetId) {

    let inputElement = document.querySelector(`input[data-id="${timesheetId}"]`)
    let dropdownList = document.getElementById(`dropdown-list-${timesheetId}`)

    // Set the input value to the selected option's text
    inputElement.value = event.target.innerText

    // Store the selected value for backend update
    let selectedValue = event.target.getAttribute('data-value')
    inputElement.setAttribute('data-value', selectedValue)

    // Hide the dropdown list
    dropdownList.style.display = "none"
}




</script>

<style>
.page-item .page-link,
.page-item span {
    background: black !important;
}

.page-item.active .page-link,
.page-item span {
    background: orange !important;
}

.dt-search input,
.dt-search input:focus {
    background: white;
}

body.dark-version table.dataTable tbody tr:nth-child(even) {
    background-color: #333;

    /* Darker shade for dark theme */

}

.dark-version .table tbody tr td {
    color: #fff !important;

}

.custom-dropdown-list {
    display: none;
    position: absolute;
    top: 100%;
    left: 0;
    max-height: 200px;
    overflow: auto;
    z-index: 1000;
    background-color: white;
    border: 1px solid #ccc;
    width: 100%;
    padding-left: 5px;
    cursor: pointer;
}


.dropdown-item {
    padding-left: 0;
    font-size: 12px;
}
</style>