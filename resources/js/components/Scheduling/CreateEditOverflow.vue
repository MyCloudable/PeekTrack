<template>
  <div class="col-md-6" v-if="showCreateButton && !showPopup">
    <button v-if="!editMode" class="btn btn-info" @click="openPopup(null)">Create Overflow Item</button>
  </div>

  <div v-if="showPopup" class="modal-overlay" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <button type="button" class="btn-close" data-bs-dismiss="modal" @click="closePopup" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">

          <h5 class="mb-3">{{ editMode ? 'Edit Overflow Item' : 'Create Overflow Item' }}</h5>


          <div class="input-group-outline mt-4">
            <label for="">Job phases (Hold CTRL for multi-select)</label>
<Multiselect
  v-model="formData.phases"
  :options="phases"
  :multiple="true"
  :close-on-select="false"
  label="text"
  track-by="id"
  placeholder="Select job phases"
/>

          </div>

          <div class="input-group-outline mt-4">
            <label>Branch</label>
			<select class="form-control custom-white-select mt-3" v-model="formData.branch_id">
				<option v-for="b in branches" :key="b.id" :value="b.id">{{ b.text }}</option>
			</select>
          </div>

          <div class="input-group-outline mt-4">
            <label>Notes</label>
            <textarea v-model="formData.notes" class="form-control bg-white" rows="3"> </textarea>
          </div>

          <div class="input-group-outline mt-4">
            <label>Start Date</label>
            <input type="date" v-model="formData.timein_date" class="form-control bg-white" />
          </div>
          <div class="input-group-outline mt-4">
            <label>Timeout Date</label>
            <input type="date" v-model="formData.timeout_date" class="form-control bg-white" />
          </div>

          <div class="form-check form-switch mt-4">
            <input class="form-check-input" type="checkbox" v-model="formData.traffic_shift" id="trafficShift">
            <label class="form-check-label" for="trafficShift">Traffic Shift</label>
          </div>

          <div class="col-md-12 mt-4">
            <button class="btn btn-warning btn-md" @click="submit">{{ editMode ? 'Update' : 'Create' }}</button>
            <button class="btn btn-secondary btn-md ms-2" @click="closePopup">Cancel</button>
          </div>

        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useToast } from "vue-toastification"
import Multiselect from 'vue-multiselect'
import 'vue-multiselect/dist/vue-multiselect.min.css'



const props = defineProps({
  job_id: Number,
  editItem: Object, // Optional, passed when editing
  showCreateButton: {
    type: Boolean,
    default: true,
  },
})

let select2SettingsPhases = ref({ 'width': '100%', multiple: true })
let select2SettingsBranches = ref({ 'width': '100%' })

const toast = useToast()

const showPopup = ref(false);
const editMode = ref(false);

let phases = ref([])
let branches = ref([])


const formData = ref({
  id: null, // For editing
  phases: [],
  branch_id: null,
  notes: '',
  timein_date: '',
  timeout_date: '',
  traffic_shift: false
});

// Open popup in create or edit mode
const openPopup = async (itemId = null) => {
  console.log(itemId)
  if (itemId instanceof Event) {
    console.log('create')
    return; // Ignore event object coming when click on Create button
  }

  showPopup.value = true;
  console.log('itemdId ' + itemId)

  if (itemId) {
    editMode.value = true;
    console.log('edit')
    await fetchOverflowItem(itemId);
  } else {
    console.log('reset')
    editMode.value = false;
    resetForm();
  }
};




// Fetch job phases
const fetchPhases = async () => {
  try {
    const response = await axios.get(`/scheduling/overflow/phases/${props.job_id}`);
    phases.value = response.data.map(p => ({ id: p.id, text: p.name }));
  } catch (error) {
    console.error('Error fetching job phases:', error);
    toast.error("Failed to load job phases");
  }
};

// Fetch branches
const fetchBranches = async () => {
  try {
    const response = await axios.get('/scheduling/overflow/branches');
    branches.value = response.data.map(b => ({ id: b.id, text: `${b.department} - ${b.branch} - ${b.description}` }));
  } catch (error) {
    console.error('Error fetching branches:', error);
    toast.error("Failed to load branches");
  }
};

// Fetch job branch and pre populate this
const fetchJobBranch = async () => {
  try {
    const response = await axios.get(`/scheduling/job-branch/${props.job_id}`);
    if (response.data && response.data.id) {
      formData.value.branch_id = response.data.id; // Set default branch
    }
  } catch (error) {
    console.error("Error fetching job branch:", error);
  }
};

// Fetch overflow item for update
const fetchOverflowItem = async (id) => {
  try {
    const response = await axios.get(`/scheduling/overflow/item/${id}`);
    let data = response.data;

    // Convert crew_type_id to an array
    data.phases = data.phases ? [data.phases] : [];

    // Convert traffic_shift to Boolean (0 → false, 1 → true)
    data.traffic_shift = !!data.traffic_shift;

    formData.value = data;
  } catch (error) {
    console.error("Error fetching overflow item:", error);
  }
};




// Submit form
// const submit = async () => {
//   try {
//     const payload = {
//       job_id: props.job_id,
//       phases: formData.value.phases,
//       branch_id: formData.value.branch_id,
//       notes: formData.value.notes,
//       timein_date: formData.value.timein_date,
//       timeout_date: formData.value.timeout_date,
//       traffic_shift: formData.value.traffic_shift
//     };

//     await axios.post('/scheduling/overflow/create', payload);
//     toast.success("Overflow item created successfully!");

//     resetForm();

//     closePopup();
//   } catch (error) {
//     console.error('Error submitting form:', error);
//     toast.error("Failed to create overflow item");
//   }
// };

// Submit Form (Create or Edit)
const submit = async () => {
  try {
    const payload = {
      ...formData.value,
      job_id: props.job_id,
      phases: formData.value.phases.map(p => p.id), // Only send the ids!
    };

    if (editMode.value) {
      await axios.put(`/scheduling/overflow/update/${formData.value.id}`, payload);
      toast.success("Overflow item updated successfully!");
    } else {
      await axios.post('/scheduling/overflow/create', payload);
      toast.success("Overflow item created successfully!");
    }

    closePopup();
  } catch (error) {
    if (error.response?.status === 422) {
      const errorMessages = Object.values(error.response.data.errors).flat().join("\n");
      toast.error(errorMessages);
    } else {
      toast.error(error.response?.data?.message || "Something went wrong. Please try again.");
    }
  }
};


// Close Popup
const closePopup = () => {
  showPopup.value = false;
};

const resetForm = () => {
  formData.value = {
    id: null,
    phases: [],
    branch_id: null,
    notes: '',
    timein_date: '',
    timeout_date: '',
    traffic_shift: false
  };
};


// Fetch data when component is mounted
onMounted(() => {
  fetchPhases();
  fetchBranches();
  fetchJobBranch();
});

defineExpose({ openPopup });

</script>

<style>
.clean-multiselect {
  background-color: white !important;
  color: black !important;
  font-weight: bold;
  border: 1px solid #ccc;
  border-radius: 6px;
  padding: 8px;
  min-height: 120px;
  max-height: 200px;
  overflow-y: auto;
  appearance: none;
  -webkit-appearance: none;
  -moz-appearance: none;
}

.clean-multiselect option {
  padding: 5px 10px;
}


.custom-white-select {
  border-radius: 6px;
  border: 1px solid #ccc;
  background-color: white !important;
  color: black !important;
}

.custom-white-select option {
  background-color: #ffffff;
  color: black !important;
  font-weight: bold;
}



.modal-dialog {

  width: 100vw !important;
  border-radius: 10px;
  box-shadow: 0 8px 30px rgba(0, 0, 0, 0.5);
  display: flex;
  flex-direction: column;
  
  animation: slideIn 0.3s ease-in-out;
}

.modal-content {
  background-color: #1A2035;
  color: #fff;
  overflow-y: auto;
  padding: 1rem 1.25rem;
  width: 50vw !important;
}

.modal-header {
  display: flex;
  justify-content: flex-end;
  border-bottom: 1px solid #333;
  padding-bottom: 0.25rem;
}

.btn-close {
  background: none;
  border: none;
  color: #fff;
  font-size: 1.25rem;
  cursor: pointer;
}

h5 {
  color: #fff;
  font-size: 1.2rem;
  margin-bottom: 1rem;
}

.input-group-outline {
  margin-bottom: 0.8rem;
}

.input-group-outline label {
  color: #ccc;
  font-weight: 600;
  font-size: 0.9rem;
  margin-bottom: 0.25rem;
  display: block;
}

.form-control,
select,
textarea {
  background-color: #2b2f4c;
  border: 1px solid #555;
  border-radius: 6px;
  padding: 0.45rem 0.6rem;
  font-size: 0.9rem;
  width: 100%;
  max-height: 150px;
}

textarea {
  resize: vertical;
}

.form-check-label {
  font-size: 0.9rem;
  margin-left: 0.4rem;
  color: #ccc;
}

button.btn {
  min-width: 100px;
  font-size: 0.85rem;
  padding: 0.5rem 1rem;
  margin-top: 1rem;
}

@keyframes slideIn {
  from {
    transform: translateY(-20px);
    opacity: 0;
  }

  to {
    transform: translateY(0);
    opacity: 1;
  }
}



</style>
