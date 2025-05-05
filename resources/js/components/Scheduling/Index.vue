<template>
    <div class="row">
        <!-- Left Side: Superintendents -->
        <div class="col-md-9 users-container">
            <div class="search-bar">
                <label for="managerSearch">Manager:</label>
                <select v-model="selectedManager" class="form-control dropdown w-25 custom-search-input"
                    @change="fetchSuperintendentsAndTasks">
                    <option v-for="manager in managers" :key="manager.id" :value="manager.id">
                        {{ manager.name }}
                    </option>
                </select>
            </div>

            <div class="user-list">
                <div class="single-entry-tasks card" v-for="user in superintendents" :key="user.id">
                    <div class="row">
                        <div class="col-md-2 user-name">
                            <div class="name">
                                <strong>{{ user.name }}</strong>
                            </div>
                        </div>
                        <div class="col-md-10">

                            <draggable v-model="user.tasks" group="tasks" class="assigned-tasks" :sort="true"
                                itemKey="id" @change="handleTaskChange($event, user)">
                                <template #item="{ element }">
                                    <div :class="getTaskColor(element)" class="task-card single-task p-1">

                                        <!-- Job Number & Phase (Inline) -->
                                        <div class="task-header d-flex justify-content-between align-items-center">
                                            <div class="d-flex align-items-center overflow-hidden">
                                                <a :href="`/jobs/${element.job_id}/overview`"
                                                    class="text-bright-white text-decoration-none job-number"
                                                    target="_blank">
                                                    {{ element.job_number }}
                                                </a>
                                                <span class="text-bright-white fw-bold ms-1 task-phase">
                                                    - {{ element.crew_type }}
                                                </span>
                                            </div>

                                            <!-- Icons -->
                                            <div class="d-flex align-items-center gap-1">
                                                <span v-if="element.traffic_shift === 1"
                                                    class="traffic-icon text-bright-white" title="Traffic Shift Task">
                                                    <i class="fa-solid fa-light-emergency"></i>
                                                </span>
                                                <span v-if="element.notes" class="notes-icon text-bright-white"
                                                    :title="element.notes">
                                                    <i class="fa-solid fa-note"></i>
                                                </span>
                                            </div>
                                        </div>

                                        <!-- Contractor Name -->
                                        <div class="text-bright-white fw-bold contractor-name">
                                            {{ element.contractor }}
                                        </div>

                                        <!-- Dates at the Bottom (Now Closer Together) -->
                                        <div
                                            class="d-flex justify-content-between align-items-center text-bright-white dates">
                                            <span class="small-date">{{ element.timein_date }}</span>
                                            <span class="small-date">[{{ element.timeout_date }}]</span>

                                            <!-- Completion Checkmark ✅ (Bottom Right) -->
                                            <span class="complete-icon ms-2" title="Mark as Complete"
                                                @click="openCompletionPopup(element)">
                                                <i class="fa-solid fa-check-circle"></i>
                                            </span>
                                        </div>

                                    </div>
                                </template>
                            </draggable>


                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Side: Overflow Items (Tasks) -->
        <div class="col-md-3 task-container">
            <h6 class="task-header">Available Tasks</h6>
            <div class="search-bar">
                <input v-model="searchQuery" type="text" class="form-control mb-2 custom-search-input"
                    placeholder="Search tasks...">
            </div>
            <div class="mb-2">
                <input type="checkbox" id="trafficShiftFilter" v-model="filterTrafficShift">
                <label for="trafficShiftFilter">Show Only Traffic Shift Tasks</label>
            </div>
            <div class="mb-2">
                <input type="checkbox" id="startDateFilter" v-model="filterStartDate">
                <label for="startDateFilter">Sort by Start Date</label>
            </div>

            <draggable v-model="overflowItems" group="tasks" class="task-list" itemKey="id"
                @change="handleTaskChange($event, null)">
                <template #item="{ element }">
                    <div v-if="filteredTasks.includes(element)" :class="getTaskColor(element)"
                        class="single-task card p-2 mb-2 fw-bold">
                        <div class="d-flex align-items-center justify-content-between">
                            <a :href="`/jobs/${element.job_id}/overview`" class="text-white text-decoration-none"
                                target="_blank">
                                {{ element.job_number }}
                            </a> - {{ element.crew_type }}
                            <span v-if="element.traffic_shift === 1" :class="getFlashingClass(element)"
                                class="traffic-icon" title="Traffic Shift Task">
                                <i class="fa-solid fa-light-emergency"></i>
                            </span>
                            <span v-if="element.notes" class="notes-icon" :title="element.notes">
                                <i class="fa-solid fa-note"></i>
                            </span>
                            <span class="copy-icon ms-2" title="Duplicate Task" @click.stop="copyTask(element)">
                                <i class="fa-solid fa-copy"></i>
                            </span>
                            <span v-if="element.duplicated_from" class="badge bg-secondary px-1 py-0"
                                :title="`Duplicated from ID #${element.duplicated_from}`">Copy</span>

                        </div>
                        <div>
                            <span>{{ element.contractor }}</span> <br>
                            <div class="d-flex justify-content-between fw-normal">
                                <span>{{ element.timein_date }}
                                </span>
                                <span>
                                    [{{
                                        element.timeout_date
                                    }}]
                                </span>
                            </div>
                        </div>
                    </div>
                </template>
            </draggable>





        </div>
    </div>


    <!-- Completion Popup -->
    <div v-if="showCompletionPopup" class="modal-overlay">
        <div class="bg-[#1e1e2f] text-black rounded-lg shadow-lg w-full max-w-md mx-4 p-6" style="max-height: 90vh;">
            <!-- Modal Body -->
            <div>
                <h2 class="text-2xl font-bold mb-4">Complete Task</h2>
                <p class="mb-4">
                    <strong>Job:</strong> {{ selectedTask?.job_number }} - {{ selectedTask?.crew_type }}
                </p>

                <label class="block mb-2">Notes (Optional):</label><br>
                <textarea v-model="completionNote"
                    class="w-full p-2 rounded border border-gray-600 bg-[#2a2a3d] text-white focus:outline-none focus:ring-2 focus:ring-orange-500"
                    rows="3"></textarea>

                <div class="mt-6 flex justify-end space-x-2">
                    <button class="btn btn-warning btn-block mt-4" @click="submitCompletion">
                        Submit
                    </button>&nbsp&nbsp&nbsp
                    <button class="btn btn-danger btn-block mt-4" @click="closeCompletionPopup">
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    </div>



</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import axios from 'axios';
import draggable from 'vuedraggable';


const managers = ref([]);
const selectedManager = ref(null);
const superintendents = ref([]);
const overflowItems = ref([]);

const searchQuery = ref('');
const filterTrafficShift = ref(false);
const filterStartDate = ref(false);

// completion a task
const showCompletionPopup = ref(false);
const selectedTask = ref(null);
const completionNote = ref('');

// Fetch Managers and Set Logged-in Manager on Load
onMounted(async () => {
    try {
        const response = await axios.get('/scheduling/managers');
        managers.value = response.data.managers;
        selectedManager.value = response.data.logged_in_manager_id;

        fetchSuperintendentsAndTasks();
    } catch (error) {
        console.error('Error fetching managers:', error);
    }
});

// Fetch Superintendents & Tasks Based on Selected Manager
const fetchSuperintendentsAndTasks = async () => {
    if (!selectedManager.value) return;

    try {
        const response = await axios.get(`/scheduling/tasks-and-superintendents?manager_id=${selectedManager.value}`);


        // Ensure each superintendent has a tasks array
        superintendents.value = response.data.superintendents.map(superintendent => ({
            ...superintendent,
            // tasks: superintendent.tasks || [] // Keep existing tasks
            tasks: (superintendent.tasks || []).sort((a, b) => (a.task_order ?? 0) - (b.task_order ?? 0)) // Sort tasks by task_order 
        }));

        overflowItems.value = response.data.overflowItems;
    } catch (error) {
        console.error('Error fetching superintendents and tasks:', error);
    }
};


// Computed property to filter overflow items
const filteredTasks = computed(() => {
    let filtered = overflowItems.value;

    // If checkbox is checked, show only traffic shift tasks
    if (filterTrafficShift.value) {
        filtered = filtered.filter(task => task.traffic_shift === 1);
    }

    // If there's a search query, filter by job number, phase, or contractor
    if (searchQuery.value) {
        filtered = filtered.filter(task =>
            task.job_number.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
            task.crew_type.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
            task.contractor.toLowerCase().includes(searchQuery.value.toLowerCase())
        );
    }


    return filtered.sort((a, b) => {
        // Priority sort: traffic_shift first
        if (a.traffic_shift === 1 && b.traffic_shift !== 1) return -1;
        if (a.traffic_shift !== 1 && b.traffic_shift === 1) return 1;

        // Secondary sort: by selected date field
        const dateA = new Date(filterStartDate.value ? a.timein_date : a.timeout_date);
        const dateB = new Date(filterStartDate.value ? b.timein_date : b.timeout_date);

        return dateA - dateB;
    });

});

// Format Dates for Display
const formatDate = (dateString) => {
    const date = new Date(dateString);
    return date.toLocaleDateString();
};


const handleTaskChange = (event, superintendent) => {

    if (superintendent) {

        console.log('handleTaskChange');
        console.log(event);
        console.log(superintendent);

        if (event.added) {
            console.log("task added", event.added.element.id)
            saveTaskAssignment(superintendent.id, event.added.element.id, 'assign')
        }

        if (event.removed) {
            console.log("task removed")
            saveTaskAssignment(superintendent.id, event.removed.element.id, 'unassign')
        }

        if (event.moved) {
            console.log("task moved", event.moved.element.id, event.moved.newIndex)
            updateTaskOrder(superintendent.id, superintendent.tasks)
        }



    }

};





// // Send API request to save/remove assigned task
const saveTaskAssignment = async (superintendentId, taskId, action) => {
    try {
        await axios.post('/scheduling/update-task-assignment', {
            superintendent_id: superintendentId,
            task_id: taskId,
            action: action
        });
    } catch (error) {
        console.error('Error saving task assignment:', error);
    }
};


const updateTaskOrder = async (superintendentId, tasks) => {
    try {
        const taskOrderData = tasks.map((task, index) => ({
            task_id: task.id,
            task_order: index + 1
        }));

        await axios.post('/scheduling/update-task-order', {
            superintendent_id: superintendentId,
            tasks: taskOrderData
        });

        console.log('Task order updated successfully');
    } catch (error) {
        console.error('Error updating task order:', error);
    }
};


const getTaskColor = (task) => {
    if (!task.timeout_date) return 'bg-secondary'; // Default color

    const today = new Date();
    const timeoutDate = new Date(task.timeout_date);
    const diffInDays = Math.ceil((timeoutDate - today) / (1000 * 60 * 60 * 24));

    if (diffInDays < 30) return 'bg-danger';  // Red for urgent tasks
    if (diffInDays < 90) return 'bg-warning';  // Yellow for medium urgency
    return ''; // Normal color for tasks due in 90+ days
};

const getFlashingClass = (task) => {
    if (!task.timeout_date) return '';

    const today = new Date();
    const timeoutDate = new Date(task.timeout_date);
    const daysDifference = (timeoutDate - today) / (1000 * 60 * 60 * 24); // Convert ms to days

    if (daysDifference <= 90) {
        return 'flashing-white';
    }

    return 'flashing-orange';
};




const openCompletionPopup = (task) => {
    selectedTask.value = task;
    showCompletionPopup.value = true;
};

const closeCompletionPopup = () => {
    showCompletionPopup.value = false;
};

const submitCompletion = async () => {
    if (!selectedTask.value) return;

    try {
        await axios.post('/scheduling/complete-task', {
            task_id: selectedTask.value.id,
            completion_note: completionNote.value,
        });


        // Remove the completed task from the assigned tasks list
        superintendents.value.forEach(superintendent => {
            superintendent.tasks = superintendent.tasks.filter(t => t.id !== selectedTask.value.id);
        });


        showCompletionPopup.value = false;

    } catch (error) {
        console.error('Error completing task:', error);
    }
};

const copyTask = async (task) => {
    try {
        const response = await axios.post(`/scheduling/overflow/copy/${task.id}`);
        overflowItems.value.push(response.data); // Add new item to task list
    } catch (error) {
        console.error("Error duplicating task:", error);
        alert("Failed to duplicate task.");
    }
};






watch(showCompletionPopup, (val) => {
    document.body.classList.toggle('modal-open', val)
})




</script>

<style>
.custom-search-input {
    background-color: #ffffff !important;
    color: #000000 !important;
    font-weight: bold;
    border: 1px solid #ccc;
    border-radius: 6px;
}

.custom-search-input:focus {
    outline: none;
    border-color: #f3b700;
    box-shadow: 0 0 5px rgba(243, 183, 0, 0.5);
}


.modal-overlay {
    position: fixed !important;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    display: flex !important;
    align-items: center;
    justify-content: center;
    background-color: rgba(0, 0, 0, 0.5);
    z-index: 9999;
    outline: 2px solid white;
}

.user-list {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.single-entry-tasks {
    background: #1e1e1e;
    color: white;
    padding: 15px;
    border-radius: 8px;
}

.user-name {
    display: flex;
    align-items: center;
    justify-content: center;
    background: #333;
    padding: 10px;
    border-radius: 8px;
    font-weight: bold;
    color: white;
    font-size: 16px;
    text-align: center;
}

.assigned-tasks {
    display: flex;
    flex-wrap: wrap;
    gap: 5px;
    min-height: 60px;
    /* padding: 10px; */
    background: #222;
    border-radius: 8px;
}

.task-container,
.users-container {
    display: flex;
    flex-direction: column;
    max-height: 80vh;
    overflow-y: auto;
    padding: 10px;
    border-left: 2px solid #555;
}

.task-header {
    position: sticky;
    top: 0;
    background: black;
    padding: 10px;
    color: white;
    z-index: 10;
}

.task-list {
    overflow-y: auto;
    padding-top: 10px;
}

.single-task {
    background: #555;
    color: white;
    padding: 10px;
    border-radius: 8px;
    cursor: pointer;
}

.search-bar {
    margin-bottom: 15px;
}

.dropdown {
    color: black;
    background-color: white;
    width: 100%;
    margin-bottom: 1rem;
}

/* Flashing traffic shift icon*/
.flashing-orange {
    animation: flashOrange 1s infinite alternate;
}

@keyframes flashOrange {
    0% {
        color: orange;
    }

    100% {
        color: rgb(255, 140, 0);
    }
}

.flashing-white {
    animation: flashWhite 1s infinite alternate;
}

@keyframes flashWhite {
    0% {
        color: white;
    }

    100% {
        color: rgba(255, 255, 255, 0.7);
    }
}

.task-card {
    background: #3a3a3a;
    color: #ffffff;
    padding: 4px;
    border-radius: 5px;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    height: 75px;
    min-width: 190px;
    /* Ensures enough space */
    max-width: 190px;
    overflow: hidden;
}

.small-date {
    font-size: 9px;
    /* Makes dates smaller */
    white-space: nowrap;
}

/* Prevents text wrapping and keeps job number & phase inline */
.task-header {
    display: flex;
    align-items: center;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    width: 100%;
}

/* Job number normal weight, phase bold */
.job-number {
    color: #ffffff !important;
    text-decoration: none;
    font-size: 10px;
    font-weight: normal;
}

.task-phase {
    font-size: 10px;
    font-weight: bold;
}

/* Contractor name smaller */
.contractor-name {
    font-size: 8px !important;
    margin-top: 2px;
}

/* Dates font smaller */
.dates {
    font-size: 9px;
    margin-top: 2px;
}

.complete-icon {
    color: #28a745;
    /* Green checkmark */
    font-size: 14px;
    cursor: pointer;
    transition: transform 0.2s ease-in-out;
    align-self: flex-end;
    /* Pushes to bottom right */
}

.complete-icon:hover {
    transform: scale(1.2);
}

/* Ensures the dates and checkmark align at the bottom */
.dates {
    font-size: 9px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: auto;
    /* Pushes dates to bottom */
}

/* Icons */
.task-card .traffic-icon,
.task-card .notes-icon {
    font-size: 10px;
    display: inline-block;
    margin-left: 2px;
    color: #ffffff !important;
}

/* Pulsating effect for the traffic shift icon */
@keyframes pulsate {
    0% {
        opacity: 1;
        transform: scale(1);
    }

    50% {
        opacity: 0.5;
        transform: scale(1.2);
    }

    100% {
        opacity: 1;
        transform: scale(1);
    }
}

.traffic-icon i {
    animation: pulsate 1s infinite ease-in-out;
}

/* Keeps assigned tasks in one row and removes vertical scrollbar */
.assigned-tasks {
    display: flex;
    flex-wrap: nowrap;
    overflow-x: auto;
    overflow-y: hidden;
    gap: 5px;
    align-items: stretch;
}

:deep(.search-bar .dropdown),
:deep(.search-bar .form-control) {
    color: #fff !important;
    /* White text */
    border: 2px solid #fff !important;
    /* White outline */
    background-color: transparent !important;
    /* Keep background transparent */
    padding: 8px;
}

/* Ensure dropdown options are readable */
:deep(.search-bar .dropdown option) {
    color: #000 !important;
    /* Black text for dropdown options */
    background-color: #fff !important;
    /* White background */
}

/* Add hover effect */
:deep(.search-bar .dropdown:hover),
:deep(.search-bar .form-control:hover) {
    border-color: #ddd !important;
    /* Lighter white on hover */
}

/* Ensure placeholder text is visible */
:deep(.search-bar .form-control::placeholder) {
    color: rgba(255, 255, 255, 0.7) !important;
    /* Light white placeholder */
    opacity: 1 !important;
}

/* Ensure input text remains white when typing */
:deep(.search-bar .form-control) {
    caret-color: #fff !important;
    /* White blinking cursor */
}
</style>
