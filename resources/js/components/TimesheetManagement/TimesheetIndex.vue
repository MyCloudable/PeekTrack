<template>

    <div class="row">
        <timesheet-filter :users="props.users" :jobs="props.jobs" :authuser="authuser" @filter="handleFilter" />

    </div>

    <div class="row mt-3">
        <div class="col-md-12">
            <button class="btn btn-info w-25" @click="showForm = true">Create Record</button>
        </div>
        <timesheet-form :users="props.users" :jobs="props.jobs" :timetypes="props.timetypes" :crewtypes="props.crewtypes" 
        v-if="showForm" @submit-timesheet="createTimesheet" />
    </div>

    <div class="row mt-5">
        <div class="col-md-12">
            <!-- <DataTable :options="tableOptions" ref="dataTableRef" @change="handleCheckboxChange" /> -->
            <DataTable :options="tableOptions" ref="dataTableRef" />
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

// import toast from '../../plugins/toast' // Import toast instance

import { useToast } from "vue-toastification";

// import Select2 from 'vue3-select2-component'; // Ensure this is correctly imported


import { createApp } from 'vue';
import { result } from 'lodash';

const toast = useToast();

DataTable.use(DataTablesCore);

const props = defineProps({
    users: Object,
    jobs: Object,
    timetypes: Object,
    // roleid: Number,
    authuser: Object,
    crewtypes: Object
})

const showForm = ref(false)

const filterData = ref('')
const dataTableRef = ref(null)
const editingRows = reactive(new Set())

const selectAllPayrollApproval = ref(false)

const handleSelectAllPayrollApproval = async (event) => {
    const checkbox = event.target
    const checkboxes = document.querySelectorAll('.payroll-approval-checkbox')
    const selectedIds = []

    selectAllPayrollApproval.value = checkbox.checked
    const type = checkbox.dataset.type

    checkboxes.forEach(checkbox => {
        if (!checkbox.disabled) { // Check if the checkbox is not disabled
            checkbox.checked = selectAllPayrollApproval.value
            selectedIds.push({
                id: checkbox.dataset.id,
            })
        }
    })

    try {
        const response = await axios.post('/timesheet-management/update-checkbox-approval-bulk', {
            selectedIds: selectedIds,
            approved: selectAllPayrollApproval.value,
            type: type
        })

        if (response.data.success) {
            if (dataTableRef.value && dataTableRef.value.dt) {
                dataTableRef.value.dt.ajax.reload(null, false)
            }
            this.$toast.success('Approval status updated successfully')
        } else {
            this.$toast.error('Failed to update approval status')
        }
    } catch (error) {
        this.$toast.error('An error occurred while updating approval status')
    }
}


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
                    el.addEventListener('click', handleEditClick);
                });

                const selectAllCheckbox = document.getElementById('select-all-payroll-approval')
                if (selectAllCheckbox) {
                    selectAllCheckbox.addEventListener('change', handleSelectAllPayrollApproval)
                }

                document.querySelectorAll('.payroll-approval-checkbox, .crew-member-approval-checkbox, .reviewer-approval-checkbox, .weekend-out-approval-checkbox')
                    .forEach((checkbox) => {
                        checkbox.addEventListener('change', handleCheckboxChange)
                    })

                document.querySelectorAll('.delete-icon').forEach((el) => {
                    el.addEventListener('click', handleDeleteClick);
                });

            });

        }
    },

    columns: [
        { data: 'timesheet_id', title: 'Test id' },
        { data: 'crewmember_name', title: 'Crew member' },
        { data: 'superintendent_name', title: 'Superintendent' },

        {
            data: 'job_number_county',
            title: 'Job Number(County)',
            render: function (data, type, row) {
                if (editingRows.has(row.timesheet_id)) {
                    const options = props.jobs.map(job =>
                        `<option value="${job.id}" ${job.id === row.job_id ? 'selected' : ''}>${job.text}</option>`
                    ).join('');
                    return `<select class="form-control bg-white job-number-select" data-id="${row.timesheet_id}">${options}</select>`;
                }

                return data;
            }
        },

        // { data: 'job_number_county', title: 'Job Number(County)', render: renderJobSelect },
        // { data: 'job_number_county', title: 'Job Number(County)', render: renderJobColumn },

        {
            data: 'time_type_name',
            title: 'Time Type',
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



        // { data: 'crew_type_name', title: 'Crew type Name' },
        // { data: 'per_diem', title: 'Per Diem' },
        {
            data: 'total_time',
            title: 'Total',
            render: function (data, type, row, meta) {
                return TimeConvert(data)
            }
        },
        { data: 'per_diem', title: 'Per Diem' },
        {
            data: 'weekend_out',
            title: 'WO',
            orderable: true,
            render: function (data, type, row) {
                return `<input type="checkbox" class="form-check-input weekend-out-approval-checkbox" data-id="${row.timesheet_id}" data-type="weekend_out" ${data ? 'checked' : ''} ${props.authuser.role_id == 2 ? '' : 'disabled'} '' />`
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
            title: 'RA',
            orderable: true,
            render: function (data, type, row) {
                return `<input type="checkbox" class="form-check-input reviewer-approval-checkbox" data-id="${row.timesheet_id}" data-type="reviewer_approval" ${data ? 'checked' : ''} ${props.authuser.role_id == 2 ? '' : 'disabled'} />`
            }
        },

        {
            data: 'payroll_approval',
            title: '<input type="checkbox" class="form-check-input" id="select-all-payroll-approval" data-type="payroll_approval" /> PA',
            orderable: true,
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
                    const isPayrollAdmin = props. authuser.role_id === 5

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



                    // Default to false if any approval is done by CMA
                    if (isCmaApproved) {
                        return isReviewer || isPayrollAdmin;
                    }

                    // Allow editing if no approvals and user is Superintendent, Reviewer, or Payroll Admin
                    return !isCmaApproved && !isReviewerApproved && !isPayrollApproved &&
                        (isSuperintendent || isReviewer || isPayrollAdmin);
                };

                // Determine if the pencil icon should be shown based on edit permissions
                const showPencilIcon = canEdit();

                const showTrashIcon = (props.authuser.role_id === 3 || props.authuser.role_id === 2 || props.authuser.role_id === 5) && !row.payroll_approval;

                let actionButtons = '';

                if (showPencilIcon) {
                    actionButtons += `<i class="fa fa-pencil cursor-pointer edit-icon" data-id="${row.timesheet_id}" aria-hidden="true"></i>`;
                } else {
                    actionButtons += `<i class="fa fa-times disabled-icon" aria-hidden="true"></i>`;
                }

                if (showTrashIcon) {
                    actionButtons += `<i class="fa fa-trash cursor-pointer delete-icon" data-id="${row.timesheet_id}" aria-hidden="true" style="margin-left: 10px;"></i>`;
                }

                return actionButtons;
            }
        },
    ]
});


const handleFilter = (filter) => {
    console.log('handleFilter')
    filterData.value = filter; // Update filterData ref with new filter data
    console.log(filterData.value)

    if (dataTableRef.value && dataTableRef.value.dt) {
        dataTableRef.value.dt.ajax.reload(); // Trigger DataTable reload
    }
};


const handleCheckboxChange = async (event) => {
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
                    dataTableRef.value.dt.ajax.reload(null, false) // Reload table data without resetting pagination
                }
                this.$toast.success('Approval status updated successfully')
            } else {
                this.$toast.error('Failed to update approval status')
            }
        } catch (error) {
            this.$toast.error('An error occurred while updating approval status')
        }
    }
}

const handleEditClick = (event) => {

    const id = event.target.getAttribute('data-id')
    if (editingRows.has(parseInt(id))) {
        editingRows.delete(parseInt(id))
        saveRow(id)
    } else {
        editingRows.add(parseInt(id))
    }
    if (dataTableRef.value && dataTableRef.value.dt) {
        dataTableRef.value.dt.ajax.reload(null, false)
    }
}


const saveRow = async (id) => {

    const clockinInput = document.querySelector(`.clockin-time-input[data-id="${id}"]`)
    const clockoutInput = document.querySelector(`.clockout-time-input[data-id="${id}"]`)
    const jobNumberSelect = document.querySelector(`.job-number-select[data-id="${id}"]`);
    const timeTypeSelect = document.querySelector(`.time-type-select[data-id="${id}"]`);

    const clockinTime = clockinInput ? clockinInput.value : null;
    const clockoutTime = clockoutInput ? clockoutInput.value : null;
    const jobNumber = jobNumberSelect ? jobNumberSelect.value : null;
    const timeType = timeTypeSelect ? timeTypeSelect.value : null;

    try {
        const response = await axios.post('/timesheet-management/update-times', {
            id: id,
            clockin_time: clockinTime,
            clockout_time: clockoutTime,
            job_number: jobNumber,
            time_type: timeType
        })

        if (response.data.success) {
            if (dataTableRef.value && dataTableRef.value.dt) {
                dataTableRef.value.dt.ajax.reload(null, false); // Reload table data without resetting pagination
            }
            this.$toast.success('Timesheet entry updated successfully')
        } else {
            this.$toast.error('Failed to updated timesheet entry')
        }
        alert(response.data.message)

    } catch (error) {
        // alert('here coming error')
        alert(`Error: ${error.response.data.message}`);
        this.$toast.error('An error occurred while updating the timesheet entry')
    }
}

const handleDeleteClick = async (event) => {
    const id = event.target.getAttribute('data-id');

    if (confirm('Are you sure you want to delete this timesheet?')) {
        try {
            const response = await axios.delete(`/timesheets/${id}`);

            if (response.data.success) {
                if (dataTableRef.value && dataTableRef.value.dt) {
                    dataTableRef.value.dt.ajax.reload(null, false);
                }
                toast.success('Timesheet entry deleted successfully')
                alert(response.data.message)
            } else {   
                toast.error('Failed to delete timesheet entry')
                alert(response.data.message)
            }
        } catch (error) {
            toast.error('An error occurred while deleting the timesheet entry')
            alert('An error occurred while deleting the timesheet entry')
        }
    }
};

const createTimesheet = async (formData) => {
    try {
            const response = await axios.post(`timesheet-management/store`, {
                formData: formData
            })

            if (response.data.success) {
                if (dataTableRef.value && dataTableRef.value.dt) {
                    dataTableRef.value.dt.ajax.reload(null, false);
                }
                toast.success('Timesheet entry created successfully')
                alert(response.data.message)
            } else {   
                toast.error('Failed to create timesheet entry')
                alert(response.data.message)
            }
        } catch (error) {
            toast.error('An error occurred while creating the timesheet entry')
            alert('An error occurred while creating the timesheet entry')
        }
}


onMounted(async () => {

    try {
        dataTableRef.value = new DataTable(dataTableRef.value.$el, tableOptions.value);
    } catch (error) {
        console.error('Error initializing DataTable:', error);
    }

});

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
    background-color: #333; /* Darker shade for dark theme */
}

/* table.dataTable thead > tr > th.dt-orderable-asc span.dt-column-order, table.dataTable thead > tr > th.dt-orderable-desc span.dt-column-order, table.dataTable thead > tr > th.dt-ordering-asc span.dt-column-order, table.dataTable thead > tr > th.dt-ordering-desc span.dt-column-order, table.dataTable thead > tr > td.dt-orderable-asc span.dt-column-order, table.dataTable thead > tr > td.dt-orderable-desc span.dt-column-order, table.dataTable thead > tr > td.dt-ordering-asc span.dt-column-order, table.dataTable thead > tr > td.dt-ordering-desc span.dt-column-order {
    position: absolute;
    right: 12px;
    top: 0;
    bottom: 0;
    width: 12px;
}

table.dataTable thead > tr > th.dt-orderable-asc, table.dataTable thead > tr > th.dt-orderable-desc, table.dataTable thead > tr > td.dt-orderable-asc, table.dataTable thead > tr > td.dt-orderable-desc {
    cursor: pointer;
}

table.dataTable thead > tr > th.dt-orderable-asc span.dt-column-order:before, table.dataTable thead > tr > th.dt-orderable-asc span.dt-column-order:after, table.dataTable thead > tr > th.dt-orderable-desc span.dt-column-order:before, table.dataTable thead > tr > th.dt-orderable-desc span.dt-column-order:after, table.dataTable thead > tr > th.dt-ordering-asc span.dt-column-order:before, table.dataTable thead > tr > th.dt-ordering-asc span.dt-column-order:after, table.dataTable thead > tr > th.dt-ordering-desc span.dt-column-order:before, table.dataTable thead > tr > th.dt-ordering-desc span.dt-column-order:after, table.dataTable thead > tr > td.dt-orderable-asc span.dt-column-order:before, table.dataTable thead > tr > td.dt-orderable-asc span.dt-column-order:after, table.dataTable thead > tr > td.dt-orderable-desc span.dt-column-order:before, table.dataTable thead > tr > td.dt-orderable-desc span.dt-column-order:after, table.dataTable thead > tr > td.dt-ordering-asc span.dt-column-order:before, table.dataTable thead > tr > td.dt-ordering-asc span.dt-column-order:after, table.dataTable thead > tr > td.dt-ordering-desc span.dt-column-order:before, table.dataTable thead > tr > td.dt-ordering-desc span.dt-column-order:after {
    left: 0;
    opacity: 0.125;
    line-height: 9px;
    font-size: 0.8em;
}

table.dataTable thead > tr > th.dt-orderable-asc span.dt-column-order:before, table.dataTable thead > tr > th.dt-ordering-asc span.dt-column-order:before, table.dataTable thead > tr > td.dt-orderable-asc span.dt-column-order:before, table.dataTable thead > tr > td.dt-ordering-asc span.dt-column-order:before {
    position: absolute;
    display: block;
    bottom: 50%;
    content: "▲";
    content: "▲" / "";
} */


</style>