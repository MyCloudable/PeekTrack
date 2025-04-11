<template>
  <div v-if="urgentMessage && !urgentMessage.acknowledged" class="urgent-modal-overlay">
    <div class="urgent-modal-container">
      <h2 class="urgent-modal-title">🚨 {{ urgentMessage.title }}</h2>
      <p class="urgent-modal-message">{{ urgentMessage.message }}</p>
      <button class="urgent-modal-button" @click="acknowledgeNotification">Acknowledge</button>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import axios from 'axios'

const urgentMessage = ref(null)
const showUrgentPopup = ref(false)
const hasAcknowledged = ref(false)

const fetchNotification = async () => {
  try {
    await axios.get('/sanctum/csrf-cookie');

    const response = await axios.get('/vue-test-notification', {
      withCredentials: true,
    });

    const notification = response.data.notification;

    if (notification && !notification.acknowledged) {
      console.log("Fetched notification:", notification);
      urgentMessage.value = notification;
      showUrgentPopup.value = true;
    } else {
      console.log("No active or unacknowledged notification.");
    }
  } catch (error) {
    console.error('Failed to fetch urgent notification:', error);
  }
}

const acknowledgeNotification = async () => {
  try {
    await axios.post('/notifications/acknowledge', {
      notification_id: urgentMessage.value.id
    }, {
      withCredentials: true
    });

    hasAcknowledged.value = true;
    showUrgentPopup.value = false;
    urgentMessage.value = null; // 🔥 This removes it from the DOM
    window.location.reload();
  } catch (error) {
    console.error('Failed to acknowledge:', error.response?.data || error);
  }
};


onMounted(fetchNotification)
</script>

<style scoped>
.urgent-modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  width: 100vw;
  height: 100vh;
  background-color: rgba(0, 0, 0, 0.7);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 99999; /* increased to be intentional */
}

.urgent-modal-overlay.hidden {
  display: none !important;
  z-index: -1 !important;
}


.urgent-modal-container {
  background-color: #1a1a2e;
  color: #fff;
  padding: 2rem 3rem;
  border-radius: 12px;
  max-width: 500px;
  width: 90%;
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.4);
  text-align: center;
}

.urgent-modal-title {
  font-size: 1.8rem;
  font-weight: 700;
  margin-bottom: 1rem;
}

.urgent-modal-message {
  font-size: 1rem;
  margin-bottom: 1.5rem;
}

.urgent-modal-button {
  background-color: #ff3b3f;
  color: white;
  padding: 0.6rem 1.4rem;
  font-weight: bold;
  border: none;
  border-radius: 8px;
  cursor: pointer;
  transition: background 0.3s ease;
}

.urgent-modal-button:hover {
  background-color: #e33639;
}
</style>
