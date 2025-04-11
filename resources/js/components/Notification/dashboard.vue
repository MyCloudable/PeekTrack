<template>
  <div v-if="showUrgentPopup" class="modal-overlay">
    <div class="modal-dialog modal-sm">
      <div class="modal-content bg-white text-black p-4 rounded shadow-lg">

        <div class="modal-header border-0">
          <h5 class="modal-title fw-bold text-danger">
            ⚠️ {{ urgentMessage?.title || 'Urgent Notification' }}
          </h5>
        </div>

        <div class="modal-body">
          <p class="mb-4">{{ urgentMessage?.message }}</p>
          <button class="btn btn-warning w-100 fw-bold" @click="acknowledgeUrgent">
            Acknowledge
          </button>
        </div>

      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import axios from 'axios'

const showUrgentPopup = ref(false)
const urgentMessage = ref(null)

const fetchUrgentMessage = async () => {
  try {
    const response = await axios.get('/api/notifications/urgent')
    if (response.data) {
      urgentMessage.value = response.data
      showUrgentPopup.value = true
    }
  } catch (error) {
    console.error('Failed to fetch urgent message:', error)
  }
}

const acknowledgeUrgent = async () => {
  try {
    await axios.post('/api/notifications/acknowledge', {
      notification_id: urgentMessage.value.id
    })
    showUrgentPopup.value = false
  } catch (error) {
    console.error('Failed to acknowledge notification:', error)
  }
}

onMounted(() => {
  fetchUrgentMessage()
})
</script>

<style scoped>
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  justify-content: center;
  align-items: center;
  z-index: 9999;
}

.modal-dialog {
  background: white;
  border-radius: 12px;
  max-width: 450px;
  width: 90%;
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
}

.modal-header .modal-title {
  font-size: 1.25rem;
  color: #d9534f;
}

.modal-body {
  font-size: 1rem;
  color: #000;
}
</style>
