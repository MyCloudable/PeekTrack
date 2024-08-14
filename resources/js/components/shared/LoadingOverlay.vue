<template>
    <div v-if="isLoading" class="overlay">
      <div class="spinner"></div>
    </div>
  </template>
  
  <script setup>
  import { useLoading } from '../../composables/useLoading'
  
  // Destructure the loading state and the setLoading function (whatever needs to use here)
  const { isLoading } = useLoading()

  </script>
  
  <style scoped>
  .overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5); /* Dimmed screen effect */
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 9999; /* Ensure it sits on top of other content */
  }
  
  .spinner {
    border: 8px solid #f3f3f3; /* Light grey background */
    border-top: 8px solid #3498db; /* Blue spinner */
    border-radius: 50%;
    width: 60px;
    height: 60px;
    animation: spin 1s linear infinite;
  }
  
  @keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
  }
  </style>
  