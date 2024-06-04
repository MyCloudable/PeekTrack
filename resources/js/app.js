require('./bootstrap');

import { createApp } from 'vue'
import Clockin from './components/Clockin.vue'

import Select2 from 'vue3-select2-component';

const app = createApp({})

app.component('clockin', Clockin)
app.component('Select2', Select2)

app.mount('#app')

