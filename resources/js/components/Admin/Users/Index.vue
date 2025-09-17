<template>

    <div class="row">
        <div class="card card-frame">
            <div class="card-body">
                <div class="card-title">Users</div>
                <DataTable :options="tableOptions" ref="dataTableRef" class="table custom-hover table-hover" />
            </div>
        </div>
    </div>

    <!-- Edit Modal -->
    <div class="modal fade" id="editUserModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h6 class="modal-title">Edit User</h6>
                    <button type="button" class="btn-close" @click="hideModal"></button>
                </div>

                <div class="modal-body">
                    <div class="mb-2">
                        <label class="form-label">Name</label>
                        <input class="form-control bg-white" v-model="editForm.name" />
                    </div>

                    <div class="mb-2">
                        <label class="form-label">Role</label>
                        <!-- key forces fresh render so correct role preselects -->
                        <select class="form-control bg-white" v-model.number="editForm.role_id" :key="selectKey">
                            <option v-for="r in rolesNormalized" :key="r.value" :value="r.value">{{ r.label }}</option>
                        </select>
                    </div>

                    <hr />

                    <div class="row">
                        <div class="col-4 mb-2">
                            <label class="form-label">Location (2 digits)</label>
                            <input class="form-control bg-white" v-model="editForm.location" inputmode="numeric"
                                pattern="\\d{2}" maxlength="2" placeholder="e.g. 02" />
                        </div>
                        <div class="col-4 mb-2">
                            <label class="form-label">Class (2 digits)</label>
                            <input class="form-control bg-white" v-model="editForm.class" inputmode="numeric"
                                pattern="\\d{2}" maxlength="2" placeholder="e.g. 10" />
                        </div>
                        <div class="col-4 mb-2">
                            <label class="form-label">Pay type</label>
                            <select class="form-control bg-white" v-model.number="editForm.pay_rate">
                                <option :value="0">Hourly</option>
                                <option :value="1">Salary</option>
                            </select>
                        </div>
                    </div>

                    <hr />

                    <div class="mb-2">
                        <label class="form-label">New Password</label>
                        <input type="password" class="form-control bg-white" v-model="editForm.new_password" />
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Confirm Password</label>
                        <input type="password" class="form-control bg-white"
                            v-model="editForm.new_password_confirmation" />
                    </div>
                </div>

                <div class="modal-footer">
                    <button class="btn btn-secondary" @click="hideModal">Close</button>
                    <button class="btn btn-warning" @click="save" :disabled="saving">
                        {{ saving ? 'Saving...' : 'Save' }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import axios from 'axios'
import { ref, onMounted, nextTick, computed } from 'vue'
import DataTable from 'datatables.net-vue3'
import DataTablesCore from 'datatables.net-bs5'
import { useToast } from 'vue-toastification'
const toast = useToast()
DataTable.use(DataTablesCore)

const props = defineProps({
    roles: { type: Array, default: () => [] }
})

const rolesNormalized = computed(() =>
    (props.roles || []).map(r => ({
        value: Number(r.value ?? r.id),
        label: r.label ?? r.name ?? String(r.value ?? r.id)
    }))
)

axios.defaults.headers.common['X-CSRF-TOKEN'] =
    document.querySelector('meta[name="csrf-token"]').getAttribute('content')

const dataTableRef = ref(null)
const saving = ref(false)
const selectKey = ref(0) // force select re-render between opens

const editForm = ref({
    id: null,
    name: '',
    role_id: '',
    location: '',
    class: '',
    pay_rate: 0,
    new_password: '',
    new_password_confirmation: ''
})

let modal
function showModal() {
    modal = new bootstrap.Modal(document.getElementById('editUserModal'))
    modal.show()
}
function hideModal() { modal?.hide() }

const tableOptions = ref({
    processing: true,
    serverSide: true,
    ajax: {
        url: '/admin/users/list',
        type: 'GET',
        dataType: 'json',
        error: function (xhr, textStatus, error) {
            console.error('Ajax error:', error)
        },
        complete: function () {

            nextTick(() => {
                document.querySelectorAll('.edit-user-btn').forEach(btn => {
                    btn.addEventListener('click', async (e) => {
                        const id = e.currentTarget.getAttribute('data-id')
                        try {
                            const { data } = await axios.get(`/admin/users/${id}`)
                            editForm.value = {
                                id: data.id,
                                name: data.name ?? '',
                                role_id: Number(data.role_id),
                                location: data.location ?? '',
                                class: data.class ?? '',
                                pay_rate: Number(data.pay_rate ?? 0),
                                new_password: '',
                                new_password_confirmation: ''
                            }
                            selectKey.value++   // refresh the select so preselect is correct
                            await nextTick()
                            showModal()
                        } catch {
                            toast.error('Failed to load user')
                        }
                    })
                })
            })
        }
    },
    columns: [
        {
            data: 'id', title: 'ID',
            render: (d) => `<small><small><small>${d}</small></small></small>`
        },
        { data: 'name', title: 'Name' },
        { data: 'email', title: 'Email', },
        { data: 'role_id', title: 'Role' },
        { data: 'location', title: 'Loc' },
        { data: 'class', title: 'Class' },
        { data: 'pay_rate', title: 'PR' },
        {
            data: 'created_at', title: 'Created',
            render: (d) => `<small><small><small>${d || ''}</small></small></small>`
        },
        {
            data: null, title: 'Action', orderable: false,
            render: (_, __, row) =>
                `<button class="btn btn-sm btn-dark edit-user-btn" data-id="${row.id}">Edit</button>`
        }
    ],
    order: [[0, 'desc']]
})


onMounted(async () => {
    try {
        dataTableRef.value = new DataTable(dataTableRef.value.$el, tableOptions.value)
    } catch (error) {
        console.error('Error initializing DataTable:', error)
    }
})

// Save handler
async function save() {
    try {
        saving.value = true
        const id = editForm.value.id
        const payload = {
            name: editForm.value.name,
            role_id: Number(editForm.value.role_id),
            location: editForm.value.location || null,
            class: editForm.value.class || null,
            pay_rate: editForm.value.pay_rate !== '' ? Number(editForm.value.pay_rate) : null,
            new_password: editForm.value.new_password || null,
            new_password_confirmation: editForm.value.new_password_confirmation || null
        }
        const { data } = await axios.put(`/admin/users/${id}`, payload)
        if (data.success) {
            toast.success('User updated')
            hideModal()
            // reload table without resetting pagination
            dataTableRef.value?.dt?.ajax?.reload(null, false)
        } else {
            toast.error('Update failed')
        }
    } catch (e) {
        toast.error(e?.response?.data?.message || 'Validation error')
    } finally {
        saving.value = false
    }
}
</script>
