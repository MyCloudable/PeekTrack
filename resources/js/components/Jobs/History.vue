<template>

    <!-- Custom filter div -->
    <!-- <div id="custom-filters" class="custom-filters"></div> -->

    <!-- Clear All Button -->
    <!-- <button @click="clearAllFilters" class="btn btn-danger ms-2 mb-2">Clear All</button> -->


    <!-- Custom filter div -->
    <div class="card card-frame">
        <div class="card-body">
            <div class="card-title">Advance Filter</div>

            <button class="btn btn-info" @click="showFilter = true" v-show="!showFilter">Show Filter</button>
            <div v-show="showFilter">
                <div id="custom-filters" class="custom-filters"></div>
                <button @click="clearAllFilters" class="btn btn-danger btn-md  mb-2 mt-2">Clear All</button>
                <button class="btn btn-info btn-md ms-2 mb-2 mt-2" @click="showFilter = false">Hide Filter</button>
            </div>

        </div>
    </div>

    <DataTable :options="tableOptions" ref="dataTableRef" class="table table-hover table-striped">

        <thead>
            <tr>
                <th>Action</th>
                <th>Work date</th>
                <th>Job</th>
                <th>Name</th>
                <th>Branch</th>
                <th>Submission status</th>
                <th>Approval status</th>
                <th>Approval by</th>
            </tr>
        </thead>

    </DataTable>
</template>

<script setup>

import { ref, reactive, onMounted, watch, nextTick } from 'vue'
import DataTable from 'datatables.net-vue3';
import DataTablesCore from 'datatables.net-bs5';

import { useToast } from "vue-toastification";
const toast = useToast();

DataTable.use(DataTablesCore);

const dataTableRef = ref(null)

const filterInputs = ref([]); // Store references to input fields

let showFilter = ref(false)

const props = defineProps({
    authuser: Object,
})

const tableOptions = ref({
    processing: true,
    serverSide: true,
    ajax: {
        url: '/jobs/history',
        data: function (params) {
            // Merge DataTables parameters with custom parameters

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
            document.querySelectorAll('.billing-approval-checkbox').forEach((el) => {
                el.addEventListener('click', handleBillingApprovalCheckbox)
            });

        }
    },

    columns: [
        { data: 'action', title: 'Action', orderable: false },
        { data: 'workdate', title: 'Work date' },
        { data: 'job_number', title: 'Job' },
        { data: 'name', title: 'Name' },
        { data: 'branch', title: 'Branch' },
        {
            data: 'billing_approval',
            title: 'BA',
            orderable: true,
            render: function (data, type, row) {
                return `<input type="checkbox" class="form-check-input billing-approval-checkbox" data-id="${row.id}" data-type="billing_approval" ${data ? 'checked' : ''} ${props.authuser.role_id != 8 ? 'disabled' : ''} />`
            }
        },
        { data: 'billing_approval_by', title: 'BA by' },
        { data: 'submission_status', title: 'Submission status' },
        { data: 'approval_status', title: 'Approval status' },
        { data: 'approved_by', title: 'Approval by' },
    ],

    initComplete: function () {
        const debounceDelay = 300; // Delay in milliseconds

        const filtersDiv = document.getElementById('custom-filters')

        this.api()
            .columns()
            .every(function () {
                let column = this;
                let title = column.header().textContent;

                // Skip columns that should not have search inputs
                if (title && title === 'Action') {
                    return; // Skip this column
                }

                // Create input element
                let input = document.createElement('input');
                input.placeholder = title;

                // Add Bootstrap margin classes
                input.classList.add('me-3', 'mb-4');


                // Append input to custom div 
                filtersDiv.appendChild(input)

                // Store reference to the input field
                filterInputs.value.push({ input, column })

                // Event listener for user input
                input.addEventListener('keyup', () => {
                    if (column.search() !== this.value) {
                        column.search(input.value).draw();
                    }
                });

                // Debounced event listener for user input
                // const onInputChange = debounce(() => {
                //     console.log('Search triggered for column:', index, 'with value:', input.value); // Logging for debugging
                //     if (column.search() !== input.value) {
                //         column.search(input.value).draw();
                //     }
                // }, debounceDelay);

                // input.addEventListener('keyup', onInputChange);

            });
    }

})


const clearAllFilters = () => {
    filterInputs.value.forEach(({ input, column }) => {
        input.value = ''; // Clear input field
        column.search('').draw(); // Clear the search and redraw the table
    });
}

function debounce(func, wait) {
    let timeout;
    return function (...args) {
        console.log('Debounce called'); // Debugging log
        clearTimeout(timeout);
        timeout = setTimeout(() => {
            console.log('Executing function'); // Debugging log
            func.apply(this, args);
        }, wait);
    };
}


const handleBillingApprovalCheckbox = async (event) => {

    // setLoading(true)

    const checkbox = event.target
    const id = checkbox.dataset.id
    const isChecked = checkbox.checked

    try {
        const response = await axios.post('/jobs/history/update-billing-approval', {
            id: id,
            approved: isChecked,
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
        // setLoading(false)
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


<style></style>