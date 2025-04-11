<template>
    <div class="col-11 mt-4">
        <div class="card shadow-lg border-0">
            <!-- Card Header -->
            <div class="card-header bg-dark text-white d-flex align-items-center">
                <div class="icon icon-lg icon-shape bg-gradient-warning shadow text-center border-radius-xl me-3">
                    <i class="material-icons opacity-10">calendar_month</i>
                </div>
                <h5 class="mb-0 fw-bold">Open Overflow Items</h5>
            </div>

            <!-- Card Body with Scrollable Table -->
            <div class="card-body p-4" style="overflow-y: auto; max-height: 400px;">
                <div class="table-responsive">
                    <table id="superintendentTaskList" class="table table-hover table-striped align-middle">
                        <thead class="bg-gradient-dark text-white">
                            <tr>
                                <th class="text-center fw-bold">Start Date</th>
                                <th class="text-center fw-bold">Phase</th>
                                <th class="text-center fw-bold">Branch</th>
                                <th class="text-center fw-bold">Timeout Date</th>
                                <th class="text-center fw-bold">Superintendent</th>
                                <th class="text-center fw-bold">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="task in overflowItems" :key="task.id" @click="editItem(task.id)">
                                <td class="text-center">{{ task.timein_date || 'N/A' }}</td>
                                <td class="text-center">{{ task.phase }}</td>
                                <td class="text-center">{{ task.branch }}</td>
                                <td class="text-center">{{ task.timeout_date || 'N/A' }}</td>
                                <td class="text-center">{{ task.superintendent || 'Not Assigned' }}</td>
                                <td class="text-center">
                                    <button class="btn btn-sm btn-danger" @click.stop="deleteItem(task.id)">
                                        <i class="fas fa-trash-alt"></i> Delete
                                    </button>
                                </td>
                            </tr>
                            <tr v-if="overflowItems.length === 0">
                                <td colspan="5" class="text-center text-muted">No pending tasks</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <CreateEditOverflow ref="editPopup" :job_id="job_id" :showCreateButton="false" />

</template>

<script setup>
import { ref, onMounted } from 'vue';
import CreateEditOverflow from './CreateEditOverflow.vue'


const props = defineProps({
    job_id: Number,
})

const overflowItems = ref([]);
const editPopup = ref(null);

const fetchOverflowItems = async () => {
    try {
        const response = await axios.get(`/scheduling/overflow/items/${props.job_id}`);
        overflowItems.value = response.data;
    } catch (error) {
        console.error('Error fetching overflow items:', error);
    }
};

const deleteItem = async (id) => {
    if (!confirm("Are you sure you want to delete this item?")) return;

    try {
        await axios.delete(`/scheduling/overflow/items/${id}`);
        overflowItems.value = overflowItems.value.filter(item => item.id !== id);
        alert("Item deleted successfully!");
    } catch (error) {
        console.error("Error deleting item:", error);
        alert("Failed to delete item.");
    }
};


// Open edit modal with data
const editItem = (id) => {
    editPopup.value.openPopup(id);
};

onMounted(() => {
    fetchOverflowItems();
});
</script>