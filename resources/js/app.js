require('./bootstrap');

import { createApp } from 'vue'
import Clockin from './components/Clockin.vue'

const app = createApp({})

app.component('clockin', Clockin)

app.mount('#app')
