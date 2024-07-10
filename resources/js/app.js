require('./bootstrap');

import { createApp } from 'vue'
import Clockin from './components/Clockin.vue'
import TimesheetIndex from './components/TimesheetManagement/TimesheetIndex.vue'

import Select2 from 'vue3-select2-component';

// Use the toast plugin
import Toast from "vue-toastification";
// Import the CSS or use your own!
import "vue-toastification/dist/index.css";

const app = createApp({})

app.component('clockin', Clockin)
app.component('timesheet', TimesheetIndex)
app.component('Select2', Select2)



const options = {
    // You can set your default options here
};

app.use(Toast, options);

app.mount('#app')

// import App from "./components/App.vue";
// createApp(App).mount("#app");


