<template>
    <DataTable :options="tableOptions" ref="dataTableRef">

        <thead>
            <tr>
                <th>Action</th>
                <th>Job #</th>
                <th>Description</th>
                <th>County</th>
                <th>Contractor</th>
                <th>Branch</th>
            </tr>
        </thead>

    </DataTable>
</template>

<script setup>

import { ref, reactive, onMounted, watch, nextTick } from 'vue'
import DataTable from 'datatables.net-vue3';
import DataTablesCore from 'datatables.net-bs5';

const props = defineProps({
    branchName: String,
})

DataTable.use(DataTablesCore);

const dataTableRef = ref(null)

const tableOptions = ref({
    processing: true,
    serverSide: true,
    ajax: {
        url: '/jobs',
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

        }
    },

    columns: [
        { data: 'action', title: 'Action', orderable: false },
        { data: 'job_number', title: 'Job #' },
        { data: 'description', title: 'Description' },
        { data: 'county', title: 'County' },
        { data: 'contractor', title: 'Contractor' },
        { data: 'branch', title: 'Branch' },
    ],

    initComplete: function () {
        const debounceDelay = 300; // Delay in milliseconds

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
                column.header().replaceChildren(input);

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


                // Set initial search value for the Branch column
                if (title === 'Branch' && props.branchName) {
                    input.value = props.branchName;
                    column.search(props.branchName).draw();
                }

            });
    }

})

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





onMounted(async () => {

    console.log('branchName ' + props.branchName)

    try {
        dataTableRef.value = new DataTable(dataTableRef.value.$el, tableOptions.value);
    } catch (error) {
        console.error('Error initializing DataTable:', error);
    }

});

</script>


<style></style>