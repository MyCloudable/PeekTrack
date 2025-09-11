<template>
    <div>
        <DataTable :options="options" ref="dtRef" id="overflow-approval-table" class="table table-hover table-striped">
            <thead>
                <tr>
                    <th>Action</th>
                    <th>Submitted On</th>
                    <th>Job #</th>
                    <th>Branch</th>
                    <th>Approved By</th>
                    <th>Phase</th>
                    <th>Start Date</th>
                    <th>Timeout Date</th>
                    <th>Notes</th>
                </tr>
            </thead>
        </DataTable>

        <!-- Approval Modal -->
        <div class="modal fade" id="oaModal" tabindex="-1" aria-hidden="true" ref="modalEl">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Approve or Reject</h5>
                        <button type="button" class="btn-close" @click="hideModal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label"><strong>Decision</strong></label>
                            <select class="form-control bg-white" v-model="form.decision">
                                <option value="">Select...</option>
                                <option value="approved">Approve</option>
                                <option value="rejected">Reject</option>
                            </select>
                        </div>
                        <div class="mb-3" v-if="form.decision === 'rejected'">
                            <label class="form-label"><strong>Rejection Note</strong></label>
                            <textarea class="form-control bg-white" rows="3" v-model="form.note"
                                placeholder="Provide a reason..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-warning" :disabled="submitting" @click="submitDecision">
                            {{ submitting ? 'Submitting...' : 'Submit' }}
                        </button>
                        <button class="btn btn-secondary" :disabled="submitting" @click="hideModal">Cancel</button>
                    </div>
                </div>
            </div>
        </div>

    </div>
</template>

<script setup>
import { ref, onMounted, nextTick } from 'vue'
import axios from 'axios'
import DataTable from 'datatables.net-vue3'
import DataTablesCore from 'datatables.net-bs5'
// import 'datatables.net-bs5/css/dataTables.bootstrap5.css'

import { useToast } from "vue-toastification"
const toast = useToast()

DataTable.use(DataTablesCore)

const dtRef = ref(null)
const modalEl = ref(null)
let bsModal = null

const form = ref({
    overflow_id: null,
    decision: '',
    note: ''
})
const submitting = ref(false)

const options = ref({
    processing: true,
    serverSide: true,
    stateSave: true,          // remembers search/sort/page length
    ajax: {
        url: '/jobs/overflowapproval/data',
        type: 'GET',
        dataType: 'json'
    },
    columns: [
        { data: 'action', name: 'action', orderable: false, searchable: false },
        { data: 'completion_date', name: 'overflow_items.completion_date', title: 'Submitted On' },
        { data: 'job_number', name: 'jobs.job_number', title: 'Job #' },
        { data: 'branch', name: 'branch.description', title: 'Branch' },
        { data: 'approved_by', name: 'users.name', title: 'Approved By' },
        { data: 'phase', name: 'crew_types.name', title: 'Phase' },
        { data: 'timein_date', name: 'overflow_items.timein_date', title: 'Start Date' },
        { data: 'timeout_date', name: 'overflow_items.timeout_date', title: 'Timeout Date' },
        {
            data: 'notes_list', name: 'notes_list', title: 'Notes',
            orderable: false, searchable: false,
            render: (data) => data && String(data).trim()
                ? `<i class="fas fa-sticky-note text-success" title="${String(data).replace(/"/g, '&quot;')}"></i>`
                : `<i class="fas fa-sticky-note text-primary"></i>`
        },
    ],
    createdRow: function (row, data) {
        // useful for event delegation; store id on row
        row.dataset.id = data.id
    },
    drawCallback: function () {
        // activate bootstrap tooltips if you use them
        if (window.bootstrap) {
            const tooltips = [].slice.call(document.querySelectorAll('#overflow-approval-table [title]'))
            tooltips.forEach(el => new bootstrap.Tooltip(el))
        }
    }
})

function showModal() {
    if (!bsModal && window.bootstrap) {
        bsModal = new bootstrap.Modal(modalEl.value)
    }
    bsModal?.show()
}
function hideModal() {
    bsModal?.hide()
    form.value = { overflow_id: null, decision: '', note: '' }
}

async function submitDecision() {
    if (!form.value.overflow_id || !form.value.decision) return
    submitting.value = true
    try {
        const res = await axios.post('/scheduling/overflow/approval', {
            overflow_id: form.value.overflow_id,
            decision: form.value.decision,
            note: form.value.note
        }, { headers: { 'Accept': 'application/json' } })

        if (res.data?.ok) {
            hideModal()

            // dt may be a function (older builds) or a property (newer builds)
            const maybe = dtRef.value?.dt
            const api = (typeof maybe === 'function') ? maybe() : maybe

            // Fallback (only if needed): use jQuery to grab the existing instance
            const finalApi = api || (window.jQuery ? window.jQuery('#overflow-approval-table').DataTable() : null)

            finalApi?.ajax.reload(null, false)

            toast.success('Decision recorded.')
        } else {
            toast.error('Unexpected response. Please try again.')
        }
    } catch (e) {
        toast.error('Failed to submit: ' + (e?.response?.data?.message || e.message || 'Unknown error'))
    } finally {
        submitting.value = false
    }
}

onMounted(async () => {
    await nextTick()
    // delegate click for the "Open" buttons rendered by server
    $('#overflow-approval-table').on('click', '.js-open-approval', function () {
        const id = this.getAttribute('data-id')
        form.value.overflow_id = id
        form.value.decision = ''
        form.value.note = ''
        showModal()
    })
})
</script>
