require("./bootstrap");

import { createApp } from "vue";

import Clockin from "./components/Clockin.vue";
import TimesheetIndex from "./components/TimesheetManagement/TimesheetIndex.vue";
import CrewCreate from "./components/CrewsManagement/Create.vue";
import CrewEdit from "./components/CrewsManagement/Edit.vue";
import JobHistory from "./components/Jobs/History.vue";
import JobIndex from "./components/Jobs/Index.vue";
import CrewIndex from "./components/CrewsManagement/Index.vue";
import OverflowItems from "./components/Scheduling/OverflowItems.vue";
import CreateEditOverflow from "./components/Scheduling/CreateEditOverflow.vue";
import SchedulingIndex from "./components/Scheduling/Index.vue";
import UrgentNotificationPopup from "./components/UrgentNotificationPopup.vue";
import OverflowApproval from "./components/jobs/OverflowApproval.vue";

import Select2 from "vue3-select2-component";

// Use the toast plugin
import Toast from "vue-toastification";
// Import the CSS or use your own!
import "vue-toastification/dist/index.css";

import VueDatePicker from "@vuepic/vue-datepicker";
import "@vuepic/vue-datepicker/dist/main.css";

const app = createApp({});

app.component("clockin", Clockin);
app.component("timesheet", TimesheetIndex);
app.component("Select2", Select2);
app.component("crewcreate", CrewCreate);
app.component("crewedit", CrewEdit);
app.component("jobhistory", JobHistory);
app.component("jobindex", JobIndex);
app.component("crewindex", CrewIndex);

app.component("overflowitems", OverflowItems);
app.component("createeditoverflow", CreateEditOverflow);
app.component("schedulingindex", SchedulingIndex);
app.component("urgentnotificationpopup", UrgentNotificationPopup);
app.component("OverflowApproval", OverflowApproval);

const options = {
    // You can set your default options here
};

app.use(Toast, options);

app.component("VueDatePicker", VueDatePicker);

app.mount("#app");

// import App from "./components/App.vue";
// createApp(App).mount("#app");
