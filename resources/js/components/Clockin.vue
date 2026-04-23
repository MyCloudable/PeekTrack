<template>

    <LoadingOverlay />

    <!-- Trigger icon in sidenav -->
    <a href="javascript:;" class="nav-link text-body p-0" data-bs-toggle="modal" data-bs-target="#clockin"
        @click="getCrewMembers" v-if="iconVisible">
        <span class="sidenav-normal ms-2 ps-1">
            <h2><i class="fas fa-business-time"></i></h2>
        </span>
    </a>

    <!-- ═══════════════════════════════════════════════════════════════════
         CLOCK IN MODAL
         ═══════════════════════════════════════════════════════════════════ -->
    <div class="modal fade" id="clockin" tabindex="-1" role="dialog" aria-labelledby="clockinModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-fullscreen" role="document">
            <div class="modal-content clk-shell"
                :class="{ 'clk-shell--phone': isPhone, 'clk-shell--locked': needsRotation }">

                <!-- ══════════════════════════════════════════════════════
                     ROTATION PROMPT (phones in portrait mode)
                     Covers the entire modal until the user rotates.
                     ══════════════════════════════════════════════════════ -->
                <div class="clk-rotate" v-if="needsRotation" role="alertdialog" aria-labelledby="clk-rotate-title"
                    aria-describedby="clk-rotate-desc">
                    <button type="button" class="clk-rotate__close" data-bs-dismiss="modal" aria-label="Close">
                        <i class="fas fa-times"></i>
                    </button>

                    <div class="clk-rotate__inner">
                        <div class="clk-rotate__icon" aria-hidden="true">
                            <svg viewBox="0 0 120 120" width="96" height="96">
                                <!-- rotating phone illustration -->
                                <g class="clk-rotate__phone">
                                    <rect x="44" y="18" width="32" height="60" rx="5" ry="5" fill="none"
                                        stroke="currentColor" stroke-width="3" />
                                    <line x1="54" y1="24" x2="66" y2="24" stroke="currentColor" stroke-width="2"
                                        stroke-linecap="round" />
                                    <circle cx="60" cy="72" r="1.5" fill="currentColor" />
                                </g>
                                <!-- curved arrow indicating rotation -->
                                <path d="M 20 95 Q 60 110 100 95" fill="none" stroke="currentColor" stroke-width="2.5"
                                    stroke-linecap="round" stroke-dasharray="4 4" />
                                <polyline points="95,88 100,95 93,100" fill="none" stroke="currentColor"
                                    stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                        </div>
                        <h2 id="clk-rotate-title" class="clk-rotate__title">Rotate Your Device</h2>
                        <p id="clk-rotate-desc" class="clk-rotate__desc">
                            For the best clock-in experience, please turn your phone sideways to landscape mode.
                        </p>
                    </div>
                </div>

                <!-- ── TOP BAR ───────────────────────────────────────── -->
                <header class="clk-topbar">
                    <div class="clk-topbar__title">
                        <i class="fas fa-business-time"></i>
                        <span>Crew Clock</span>
                    </div>
                    <button type="button" class="clk-topbar__close" data-bs-dismiss="modal" aria-label="Close">
                        <i class="fas fa-times"></i>
                    </button>
                </header>

                <!-- ── STATUS STRIP ──────────────────────────────────── -->
                <div class="clk-status" :data-state="currentStage">
                    <div class="clk-status__pill">
                        <span class="clk-status__dot"></span>
                        <span class="clk-status__label">{{ stageLabel }}</span>
                    </div>
                    <div class="clk-status__meta" v-if="status">{{ status }}</div>
                </div>

                <!-- ══════════════════════════════════════════════════════
                     PHONE LAYOUT (≤ 480px OR iOS Safari detection)
                     Compact, single-column, big tap targets, minimal chrome
                     ══════════════════════════════════════════════════════ -->
                <template v-if="isPhone">

                    <div class="clk-body clk-body--phone">

                        <!-- LEFT COLUMN (in landscape): controls and settings.
                             In portrait this behaves identically to a normal stack. -->
                        <div class="clk-phone-left">

                            <!-- Late Entry (phone: compact pill button) -->
                            <button class="clk-phone-late-entry" @click="toggleLateEntryTime" type="button"
                                :class="{ 'is-open': isLateEntryTimeVisible, 'is-disabled': isBusy }">
                                <i class="fa fa-clock-o"></i>
                                <span>{{ isLateEntryTimeVisible ? 'Hide Late Entry' : 'Late Entry Time' }}</span>
                                <i class="fa ms-auto"
                                    :class="isLateEntryTimeVisible ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
                            </button>
                            <div class="clk-phone-late-entry-body" v-if="isLateEntryTimeVisible">
                                <VueDatePicker v-model="lateEntryTime" :enable-time="true" :format="dateTimeFormat"
                                    teleport="body" class="clk-datepicker"></VueDatePicker>
                            </div>

                            <!-- Crew Type (phone) -->
                            <div class="clk-phone-field" v-if="!isAlreadyVerified || enableCrewTypeId">
                                <label class="clk-label">Crew Type</label>
                                <select class="clk-select" v-model="crewTypeId">
                                    <option v-for="crewType in crewTypes" :key="crewType.id" :value="crewType.id">
                                        {{ crewType.name }}
                                    </option>
                                </select>
                            </div>

                            <!-- Add Crew (phone) -->
                            <div class="clk-phone-field" v-if="isAlreadyClockedin && !isAlreadyClockedout">
                                <div class="clk-phone-addcrew-head">
                                    <span class="clk-label">Add Crew Member</span>
                                    <add-crew-member @get-all-users="GetAllUsers" />
                                </div>
                                <div class="clk-phone-addcrew-body" v-if="allUsers.length > 0" ref="departWrapper">
                                    <Select2 v-model="createNewCrewForm[0].crew_member_id" :options="allUsers"
                                        :settings="select2Settings" class="clk-select2" />
                                    <VueDatePicker v-model="createNewCrewForm[0].clockin_time" :enable-time="true"
                                        :format="dateTimeFormat" teleport="body" class="clk-datepicker">
                                    </VueDatePicker>
                                    <button class="clk-btn clk-btn--success" @click="addNewCrew"
                                        :disabled="isBusy || !createNewCrewForm[0].crew_member_id || !createNewCrewForm[0].clockin_time">
                                        Add to Crew
                                    </button>
                                </div>
                            </div>

                            <!-- Depart / Travel (phone) -->
                            <div class="clk-phone-field" v-if="isAlreadyClockedin && !isAlreadyClockedout">
                                <label class="clk-label">Travel &amp; Production</label>
                                <depart :crewId="crewId" :travelTime="travelTime" :crewTypeId="crewTypeId"
                                    :key="departKey" @track-time-done="trackTimeDone"
                                    @is-mobilization="enableCrewTypeId = !enableCrewTypeId"
                                    :is-late-entry-time-visible="isLateEntryTimeVisible"
                                    :late-entry-time="lateEntryTime ? format(lateEntryTime, dateTimeFormat) : lateEntryTime"
                                    @last-entry-time-done="lastEntryTimeDone" :time-types="timeTypes" />
                            </div>

                            <!-- Time Type selector (phone, before clock in) -->
                            <div class="clk-phone-field" v-if="isAlreadyVerified && !isAlreadyClockedin">
                                <label class="clk-label">Select Time Type</label>
                                <select class="clk-select" v-model="selectedClockinTypeId" :disabled="isBusy">
                                    <option :value="null" disabled>Select time type…</option>
                                    <option v-for="t in timeTypes" :key="t.id" :value="t.id">{{ t.display_name }}
                                    </option>
                                </select>
                            </div>

                            <!-- Switch Time Type (phone) -->
                            <div class="clk-phone-field" v-if="canSwitchTypes">
                                <label class="clk-label">Switch Time Type</label>
                                <select class="clk-select" v-model="selectedSwitchTypeId" :disabled="isBusy">
                                    <option :value="null" disabled>Select time type…</option>
                                    <option v-for="t in timeTypes" :key="t.id" :value="t.id">{{ t.display_name }}
                                    </option>
                                </select>
                                <button class="clk-btn clk-btn--outline mt-2" @click="switchTimeType"
                                    :disabled="isBusy">
                                    Apply
                                </button>
                            </div>

                        </div><!-- /clk-phone-left -->

                        <!-- CREW list (phone) -->
                        <div class="clk-phone-crew-section">
                            <div class="clk-phone-crew-head">
                                <span class="clk-phone-crew-title">
                                    <i class="fas fa-users me-2"></i>Crew
                                    <span class="clk-badge clk-badge--count">{{ CrewMembersTobeVerified.length }}</span>
                                </span>
                                <div v-if="isAlreadyClockedin" class="clk-phone-crew-head-right">
                                    <half-full-per-diem :timesheetId="allPerDiemTimesheetIds"
                                        :perDiem="allPerDiemStatus" @hf-per-diem-done="hfPerDiemDone" />
                                </div>
                            </div>

                            <div class="clk-select-all" v-if="!isAlreadyVerified && CrewMembersTobeVerified.length">
                                <label class="clk-check">
                                    <input type="checkbox" v-model="isCheckAll" @click="toggleCheckboxes">
                                    <span>Select All</span>
                                </label>
                            </div>

                            <div class="clk-empty" v-if="!CrewMembersTobeVerified.length">
                                <i class="fas fa-user-friends"></i>
                                <p>No crew members yet.</p>
                            </div>

                            <div class="clk-phone-crew-list" v-else>
                                <div v-for="(member, index) in CrewMembersTobeVerified" :key="member.id"
                                    class="clk-phone-crew-card" :class="{
                                        'is-checked': member.isChecked,
                                        'is-out': member.status === 'Out',
                                        'is-in': member.status === 'In'
                                    }">

                                    <!-- Top row: checkbox + name + actions -->
                                    <div class="clk-phone-crew-card__top">
                                        <label class="clk-phone-crew-card__check" v-if="!isAlreadyVerified">
                                            <input type="checkbox" :checked="member.isChecked"
                                                @click="toggleSingleCheckbox(index)">
                                        </label>

                                        <div class="clk-phone-crew-card__name">{{ member.name }}</div>

                                        <div class="clk-phone-crew-card__actions" v-if="isAlreadyClockedin">
                                            <button class="clk-icon-btn"
                                                :class="{ 'is-active': member.isMenualClockinout }" :disabled="isBusy"
                                                @click="enableMenualClock(member.id)"
                                                :aria-label="'Edit times for ' + member.name">
                                                <i class="fa fa-pencil"></i>
                                            </button>
                                            <half-full-per-diem :timesheetId="member.timesheet_id"
                                                :perDiem="member.per_diem" @hf-per-diem-done="hfPerDiemDone" />
                                        </div>
                                    </div>

                                    <!-- Bottom row: chips -->
                                    <div class="clk-phone-crew-card__meta"
                                        v-if="member.total_time_all || member.status || member.total_time">
                                        <span v-if="member.total_time_all" class="clk-chip">
                                            <i class="fas fa-hourglass-half me-1"></i>{{ member.total_time_all }}
                                        </span>
                                        <span v-if="member.status" class="clk-chip"
                                            :class="'clk-chip--' + member.status.toLowerCase()">
                                            {{ member.status === 'In' ? 'Clocked In' : 'Clocked Out' }}
                                        </span>
                                        <span v-if="member.total_time" class="clk-chip clk-chip--muted">
                                            {{ member.total_time }}
                                        </span>
                                    </div>

                                    <!-- Times: read-only -->
                                    <div class="clk-phone-crew-card__times"
                                        v-if="(isAlreadyClockedin || isAlreadyClockedout) && !member.isMenualClockinout">
                                        <i class="far fa-clock me-1"></i>
                                        {{ member.clockout_time ? member.clockout_time : member.clockin_time }}
                                    </div>

                                    <!-- Times: editable -->
                                    <div class="clk-phone-crew-card__edit" v-if="member.isMenualClockinout">
                                        <div class="clk-time-edit__field">
                                            <span class="clk-time-edit__label">In</span>
                                            <VueDatePicker v-model="member.clockin_time_edit" :enable-time="true"
                                                :format="dateTimeFormat" teleport="body"
                                                @update:model-value="menualClockinout($event, member.timesheet_id, 'clockin')"
                                                class="clk-datepicker"></VueDatePicker>
                                        </div>
                                        <div class="clk-time-edit__field">
                                            <span class="clk-time-edit__label">Out</span>
                                            <VueDatePicker v-model="member.clockout_time_edit" :enable-time="true"
                                                :format="dateTimeFormat" teleport="body"
                                                @update:model-value="menualClockinout($event, member.timesheet_id, 'clockout')"
                                                class="clk-datepicker"></VueDatePicker>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="clk-body__spacer"></div>
                    </div>
                </template>

                <!-- ══════════════════════════════════════════════════════
                     DESKTOP / TABLET LAYOUT
                     ══════════════════════════════════════════════════════ -->
                <template v-else>

                    <div class="clk-body">

                        <!-- LATE ENTRY (desktop: card) -->
                        <section class="clk-card clk-card--compact">
                            <button class="clk-collapse-toggle" @click="toggleLateEntryTime" type="button"
                                :class="{ 'is-open': isLateEntryTimeVisible, 'is-disabled': isBusy }"
                                :aria-expanded="isLateEntryTimeVisible">
                                <span class="clk-collapse-toggle__left">
                                    <i class="fa fa-clock-o"></i>
                                    <span>Late Entry Time</span>
                                </span>
                                <i class="fa" :class="isLateEntryTimeVisible ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
                            </button>
                            <div class="clk-collapse-body" v-if="isLateEntryTimeVisible">
                                <VueDatePicker v-model="lateEntryTime" :enable-time="true" :format="dateTimeFormat"
                                    teleport="body" class="clk-datepicker"></VueDatePicker>
                                <p class="clk-hint">Set a backdated timestamp for your next action.</p>
                            </div>
                        </section>

                        <!-- CREW TYPE -->
                        <section class="clk-card" v-if="!isAlreadyVerified || enableCrewTypeId">
                            <label class="clk-label">Crew Type</label>
                            <select class="clk-select" v-model="crewTypeId">
                                <option v-for="crewType in crewTypes" :key="crewType.id" :value="crewType.id">
                                    {{ crewType.name }}
                                </option>
                            </select>
                        </section>

                        <!-- ADD CREW MEMBER -->
                        <section class="clk-card" v-if="isAlreadyClockedin && !isAlreadyClockedout">
                            <div class="clk-section-head">
                                <span class="clk-section-head__title">Add Crew Member</span>
                                <add-crew-member @get-all-users="GetAllUsers" />
                            </div>
                            <div class="clk-add-crew" v-if="allUsers.length > 0" ref="departWrapper">
                                <Select2 v-model="createNewCrewForm[0].crew_member_id" :options="allUsers"
                                    :settings="select2Settings" class="clk-select2" />
                                <VueDatePicker v-model="createNewCrewForm[0].clockin_time" :enable-time="true"
                                    :format="dateTimeFormat" teleport="body" class="clk-datepicker">
                                </VueDatePicker>
                                <button class="clk-btn clk-btn--success clk-btn--sm" @click="addNewCrew"
                                    :disabled="isBusy || !createNewCrewForm[0].crew_member_id || !createNewCrewForm[0].clockin_time">
                                    Add to Crew
                                </button>
                            </div>
                        </section>

                        <!-- DEPART / TRAVEL -->
                        <section class="clk-card" v-if="isAlreadyClockedin && !isAlreadyClockedout">
                            <label class="clk-label">Travel &amp; Production</label>
                            <depart :crewId="crewId" :travelTime="travelTime" :crewTypeId="crewTypeId" :key="departKey"
                                @track-time-done="trackTimeDone" @is-mobilization="enableCrewTypeId = !enableCrewTypeId"
                                :is-late-entry-time-visible="isLateEntryTimeVisible"
                                :late-entry-time="lateEntryTime ? format(lateEntryTime, dateTimeFormat) : lateEntryTime"
                                @last-entry-time-done="lastEntryTimeDone" :time-types="timeTypes" />
                        </section>

                        <!-- TIME TYPE selector for clock-in -->
                        <section class="clk-card" v-if="isAlreadyVerified && !isAlreadyClockedin">
                            <label class="clk-label">Select Time Type</label>
                            <select class="clk-select" v-model="selectedClockinTypeId" :disabled="isBusy">
                                <option :value="null" disabled>Select time type…</option>
                                <option v-for="t in timeTypes" :key="t.id" :value="t.id">{{ t.display_name }}</option>
                            </select>
                        </section>

                        <!-- SWITCH TIME TYPE (mid-shift, at shop) -->
                        <section class="clk-card" v-if="canSwitchTypes">
                            <label class="clk-label">Switch Time Type</label>
                            <div class="clk-inline-pair">
                                <select class="clk-select" v-model="selectedSwitchTypeId" :disabled="isBusy">
                                    <option :value="null" disabled>Select time type…</option>
                                    <option v-for="t in timeTypes" :key="t.id" :value="t.id">{{ t.display_name }}
                                    </option>
                                </select>
                                <button class="clk-btn clk-btn--outline" @click="switchTimeType" :disabled="isBusy">
                                    Apply
                                </button>
                            </div>
                        </section>

                        <!-- CREW LIST -->
                        <section class="clk-card clk-card--flush">
                            <div class="clk-section-head">
                                <span class="clk-section-head__title">
                                    <i class="fas fa-users me-2"></i>Crew
                                    <span class="clk-badge clk-badge--count">{{ CrewMembersTobeVerified.length }}</span>
                                </span>
                                <div v-if="isAlreadyClockedin" class="clk-section-head__right">
                                    <span class="clk-hint me-2">All Per Diem:</span>
                                    <half-full-per-diem :timesheetId="allPerDiemTimesheetIds"
                                        :perDiem="allPerDiemStatus" @hf-per-diem-done="hfPerDiemDone" />
                                </div>
                            </div>

                            <button type="button" class="btn btn-danger p-3" @click="clockinout('clockout')"
                                v-if="canClockOut" :disabled="isBusy">Clock out
                            </button>
                            <div class="clk-select-all" v-if="!isAlreadyVerified && CrewMembersTobeVerified.length">
                                <label class="clk-check">
                                    <input type="checkbox" v-model="isCheckAll" @click="toggleCheckboxes">
                                    <span>Select All Crew Members</span>
                                </label>
                            </div>


                            <div class="clk-empty" v-if="!CrewMembersTobeVerified.length">
                                <i class="fas fa-user-friends"></i>
                                <p>No crew members yet.</p>
                            </div>

                            <div class="clk-crew-list" v-else>
                                <div v-for="(member, index) in CrewMembersTobeVerified" :key="member.id"
                                    class="clk-crew-row" :class="{
                                        'is-checked': member.isChecked,
                                        'is-out': member.status === 'Out',
                                        'is-in': member.status === 'In'
                                    }">

                                    <label class="clk-crew-row__check" v-if="!isAlreadyVerified">
                                        <input type="checkbox" :checked="member.isChecked"
                                            @click="toggleSingleCheckbox(index)">
                                    </label>

                                    <div class="clk-crew-row__main">
                                        <div class="clk-crew-row__name">{{ member.name }}</div>
                                        <div class="clk-crew-row__meta">
                                            <span v-if="member.total_time_all" class="clk-chip">
                                                <i class="fas fa-hourglass-half me-1"></i>{{ member.total_time_all
                                                }}
                                            </span>
                                            <span v-if="member.status" class="clk-chip"
                                                :class="'clk-chip--' + member.status.toLowerCase()">
                                                {{ member.status === 'In' ? 'Clocked In' : 'Clocked Out' }}
                                            </span>
                                            <span v-if="member.total_time" class="clk-chip clk-chip--muted">
                                                {{ member.total_time }}
                                            </span>
                                        </div>

                                        <div class="clk-crew-row__times"
                                            v-if="(isAlreadyClockedin || isAlreadyClockedout)">
                                            <div v-if="!member.isMenualClockinout" class="clk-time-readout">
                                                <i class="far fa-clock me-1"></i>
                                                {{ member.clockout_time ? member.clockout_time : member.clockin_time
                                                }}
                                            </div>
                                            <div v-else class="clk-time-edit">
                                                <div class="clk-time-edit__field">
                                                    <span class="clk-time-edit__label">In</span>
                                                    <VueDatePicker v-model="member.clockin_time_edit"
                                                        :enable-time="true" :format="dateTimeFormat" teleport="body"
                                                        @update:model-value="menualClockinout($event, member.timesheet_id, 'clockin')"
                                                        class="clk-datepicker"></VueDatePicker>
                                                </div>
                                                <div class="clk-time-edit__field">
                                                    <span class="clk-time-edit__label">Out</span>
                                                    <VueDatePicker v-model="member.clockout_time_edit"
                                                        :enable-time="true" :format="dateTimeFormat" teleport="body"
                                                        @update:model-value="menualClockinout($event, member.timesheet_id, 'clockout')"
                                                        class="clk-datepicker"></VueDatePicker>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="clk-crew-row__actions" v-if="isAlreadyClockedin">
                                        <button class="clk-icon-btn" :class="{ 'is-active': member.isMenualClockinout }"
                                            :disabled="isBusy" @click="enableMenualClock(member.id)"
                                            :aria-label="'Edit times for ' + member.name">
                                            <i class="fa fa-pencil"></i>
                                        </button>
                                        <half-full-per-diem :timesheetId="member.timesheet_id"
                                            :perDiem="member.per_diem" @hf-per-diem-done="hfPerDiemDone" />
                                    </div>
                                </div>
                            </div>
                        </section>

                        <div class="clk-body__spacer"></div>
                    </div>
                </template>

                <!-- ── STICKY ACTION BAR ─────────────────────────────── -->
                <footer class="clk-actionbar">

                    <button v-if="!isAlreadyVerified" class="clk-btn clk-btn--primary clk-btn--hero" @click="verifyTeam"
                        :disabled="isBusy">
                        <i class="fas fa-user-check"></i>
                        <span>Verify Crew</span>
                    </button>

                    <template v-else-if="isAlreadyVerified && !isAlreadyClockedin && !isAlreadyClockedout">
                        <button class="clk-btn clk-btn--ghost" @click="weatherEntry" :disabled="isBusy">
                            <i class="fas fa-cloud-sun"></i>
                            <span>Weather</span>
                        </button>
                        <button class="clk-btn clk-btn--success clk-btn--hero" @click="clockinout('clockin')"
                            :disabled="isBusy">
                            <i class="fas fa-play"></i>
                            <span>Clock In</span>
                        </button>
                    </template>

                    <template v-else-if="isAlreadyClockedin && !isAlreadyClockedout">
                        <button v-if="canClockOut" class="clk-btn clk-btn--danger clk-btn--hero"
                            @click="clockinout('clockout')" :disabled="isBusy">
                            <i class="fas fa-stop"></i>
                            <span>Clock Out</span>
                        </button>
                        <div v-else class="clk-actionbar__status">
                            <i class="fas fa-road me-2"></i>
                            <span>Use travel controls above to end production</span>
                        </div>
                    </template>

                    <button v-else-if="isAlreadyClockedout" class="clk-btn clk-btn--primary clk-btn--hero"
                        @click="readyForVerification" :disabled="isBusy">
                        <i class="fas fa-clipboard-check"></i>
                        <span>Ready for Verification</span>
                    </button>

                </footer>

            </div>
        </div>
    </div>

</template>

<script setup>
import axios from 'axios'
import { ref, onMounted, onBeforeUnmount, computed } from 'vue'
import { useToast } from 'vue-toastification'
import { format, parse } from 'date-fns'

import AddCrewMember from './AddCrewMember'
import HalfFullPerDiem from './HalfFullPerDiem'
import TimeConvert from '../composables/TimeConvert'
import Depart from './Depart'
import LoadingOverlay from './shared/LoadingOverlay.vue'
import { useLoading } from '../composables/useLoading'

const dateTimeFormat = "yyyy-MM-dd'T'HH:mm:ss"
const SCROLL_THRESHOLD = 50
const PHONE_BREAKPOINT = 480 // iPhone-ish width cutoff

const toast = useToast()
const { isLoading, setLoading } = useLoading()
const isBusy = isLoading

const departWrapper = ref(null)
const select2Settings = ref({ width: '100%', dropdownParent: departWrapper })

const now = ref('')
const iconVisible = ref(true)

// ── Phone detection + orientation (reactive) ──────────────────
const viewportWidth = ref(typeof window !== 'undefined' ? window.innerWidth : 1024)
const viewportHeight = ref(typeof window !== 'undefined' ? window.innerHeight : 768)
// Use the SHORTER dimension to classify phones — this way an iPhone in
// landscape (844×390) still registers as a phone (short side = 390 ≤ 480).
const shortSide = computed(() => Math.min(viewportWidth.value, viewportHeight.value))
const isPhone = computed(() => shortSide.value <= PHONE_BREAKPOINT)
const isPortrait = computed(() => viewportHeight.value > viewportWidth.value)
// Show the rotation prompt only on phones that are currently in portrait.
const needsRotation = computed(() => isPhone.value && isPortrait.value)

const isAlreadyVerified = ref(false)
const crewId = ref('')
const CrewMembersTobeVerified = ref([])
const isCheckAll = ref(false)
const submitCrewMembersToVerify = ref([])

const isAlreadyClockedin = ref(false)
const isAlreadyClockedout = ref(false)
const timesheet = ref([])
const travelTime = ref('')

const allUsers = ref([])
const createNewCrewForm = ref([{ crew_member_id: '', clockin_time: '' }])

const isMenualClockinout = ref(false)
const allPerDiemTimesheetIds = ref([])
const allPerDiemStatus = ref(null)

const status = ref('')
const crewTypes = ref([])
const crewTypeId = ref('')
const enableCrewTypeId = ref(false)

const departKey = ref(0)

const lateEntryTime = ref('')
const isLateEntryTimeVisible = ref(false)

const timeTypes = ref([])
const selectedClockinTypeId = ref(null)
const selectedSwitchTypeId = ref(null)
const shopTypeId = ref(null)

let initialLoad = true

const canSwitchTypes = computed(() => {
    if (!isAlreadyClockedin.value || isAlreadyClockedout.value) return false
    return !travelTime.value
})

const canClockOut = computed(() => {
    if (!isAlreadyClockedin.value || isAlreadyClockedout.value) return false
    const tt = travelTime.value
    return !tt || (tt.type === 'depart_for_office' && !!tt.arrive)
})

const currentStage = computed(() => {
    if (isAlreadyClockedout.value) return 'done'
    if (isAlreadyClockedin.value) return 'working'
    if (isAlreadyVerified.value) return 'verified'
    return 'unverified'
})

const stageLabel = computed(() => {
    switch (currentStage.value) {
        case 'done': return 'Clocked Out'
        case 'working': return 'On the Clock'
        case 'verified': return 'Crew Verified'
        default: return 'Awaiting Verification'
    }
})

const setCurrentDateTime = () => {
    const d = new Date()
    const pad = n => String(n).padStart(2, '0')
    now.value = `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}T${pad(d.getHours())}:${pad(d.getMinutes())}`
}

const handleScroll = () => {
    iconVisible.value = window.scrollY <= SCROLL_THRESHOLD
}

const handleResize = () => {
    viewportWidth.value = window.innerWidth
    viewportHeight.value = window.innerHeight
}

const setLocalStorageFlag = () => {
    localStorage.setItem('crewMembersUpdated', Date.now())
    getCrewMembers()
}

window.addEventListener('storage', (event) => {
    if (event.key === 'crewMembersUpdated' && !initialLoad) {
        getCrewMembers()
    }
})

onMounted(() => {
    window.addEventListener('scroll', handleScroll)
    window.addEventListener('resize', handleResize)
    window.addEventListener('orientationchange', handleResize)
    setCurrentDateTime()
    initialLoad = false
    getTimeTypes()
})

onBeforeUnmount(() => {
    window.removeEventListener('scroll', handleScroll)
    window.removeEventListener('resize', handleResize)
    window.removeEventListener('orientationchange', handleResize)
})

const getTimeTypes = () => {
    axios.get('/time-types').then(res => {
        timeTypes.value = res.data
        const shop = timeTypes.value.find(t => t.name?.toLowerCase().includes('shop'))
        if (shop) {
            shopTypeId.value = shop.id
            selectedClockinTypeId.value = shop.id
            selectedSwitchTypeId.value = shop.id
        } else {
            selectedClockinTypeId.value = null
            selectedSwitchTypeId.value = null
        }
    })
}

const getCrewMembers = () => {
    axios.get('/crew-members')
        .then(res => {
            isAlreadyVerified.value = res.data.isAlreadyVerified
            isAlreadyClockedin.value = res.data.isAlreadyClockedin
            isAlreadyClockedout.value = res.data.isAlreadyClockedout
            crewId.value = res.data.crewId
            CrewMembersTobeVerified.value = res.data.crewMembers
            timesheet.value = res.data.timesheet
            travelTime.value = res.data.travelTime
            status.value = res.data.status
            crewTypes.value = res.data.crewTypes
            crewTypeId.value = res.data.crewTypeId

            allPerDiemTimesheetIds.value = []
            const totalTimes = new Map()

            timesheet.value.forEach(time => {
                const member = CrewMembersTobeVerified.value.find(m => m.id === time.user_id)
                if (member) {
                    member.clockin_time = time.clockin_time
                    member.clockout_time = time.clockout_time
                    member.timesheet_id = time.id
                    member.per_diem = time.per_diem
                    member.status = time.clockout_time ? 'Out' : 'In'
                    member.total_time = TimeConvert(time.total_time)
                    member.clockin_time_edit = parse(time.clockin_time, 'yyyy-MM-dd HH:mm', new Date())
                    member.clockout_time_edit = time.clockout_time
                        ? parse(time.clockout_time, 'yyyy-MM-dd HH:mm', new Date())
                        : now.value
                }

                allPerDiemTimesheetIds.value.push(time.id)
                totalTimes.set(time.user_id, (totalTimes.get(time.user_id) ?? 0) + time.total_time)
            })

            CrewMembersTobeVerified.value.forEach(member => {
                member.total_time_all = totalTimes.has(member.id)
                    ? TimeConvert(totalTimes.get(member.id))
                    : '0'
            })

            departKey.value++
        })
        .catch(err => console.error('[getCrewMembers]', err))
}

const toggleCheckboxes = () => {
    isCheckAll.value = !isCheckAll.value
    CrewMembersTobeVerified.value.forEach(m => { m.isChecked = isCheckAll.value })
}

const toggleSingleCheckbox = (index) => {
    const member = CrewMembersTobeVerified.value[index]
    member.isChecked = !member.isChecked
}

const verifyTeam = () => {
    if (isBusy.value) return
    if (!confirm('Are you sure you want to verify the crew?')) return

    setLoading(true)

    submitCrewMembersToVerify.value = CrewMembersTobeVerified.value
        .filter(m => m.isChecked)
        .map(m => m.id)

    axios.post('/verify-crew-members', {
        crewId: crewId.value,
        crewMembers: submitCrewMembersToVerify.value,
        crewTypeId: crewTypeId.value,
    })
        .then(() => setLocalStorageFlag())
        .catch(err => console.error(err))
        .finally(() => setLoading(false))
}

const clockinout = (type) => {
    if (isBusy.value) return

    if (isLateEntryTimeVisible.value && !lateEntryTime.value) {
        toast.error('Please select the late entry time or toggle it off')
        return
    }

    if (type === 'clockin' && !selectedClockinTypeId.value) {
        toast.error('Please select a time type for Clock In')
        return
    }

    if (!confirm(`Are you sure you want to ${type}?`)) return

    setLoading(true)

    axios.post('/clockinout-crew-members', {
        crewId: crewId.value,
        type,
        isMenual: false,
        lateEntryTime: lateEntryTime.value ? format(lateEntryTime.value, dateTimeFormat) : lateEntryTime.value,
        timeTypeId: selectedClockinTypeId.value,
    })
        .then(() => {
            setLocalStorageFlag()
            lastEntryTimeDone()
        })
        .catch(error => {
            const msg = error?.response?.data?.message
            toast.error(msg || 'Something went wrong')
        })
        .finally(() => setLoading(false))
}

const enableMenualClock = (id) => {
    let anyEnabled = false

    CrewMembersTobeVerified.value.forEach(member => {
        if (member.id === id) {
            member.isMenualClockinout = !member.isMenualClockinout
        }
        if (member.isMenualClockinout) anyEnabled = true
    })

    isMenualClockinout.value = anyEnabled
}

const menualClockinout = (event, timesheetId, type) => {
    if (isBusy.value) return

    setLoading(true)

    axios.post('/clockinout-crew-members', {
        crewId: crewId.value,
        type,
        isMenual: true,
        timesheetId,
        time: format(event, dateTimeFormat),
    })
        .then(() => setLocalStorageFlag())
        .catch(error => {
            const msg = error?.response?.data?.message
            toast.error(msg || 'Something went wrong')
        })
        .finally(() => setLoading(false))
}

const GetAllUsers = (users) => {
    allUsers.value = users
        .filter(user => [3, 6, 7].includes(user.role_id))
        .map(user => ({ id: user.id, text: `${user.id} - ${user.name}` }))

    createNewCrewForm.value[0].clockin_time = now.value
}

const addNewCrew = () => {
    if (isBusy.value) return

    setLoading(true)

    const clockinTime = format(createNewCrewForm.value[0].clockin_time, dateTimeFormat)

    axios.post('/add-new-crew-members', {
        crewId: crewId.value,
        createNewCrewForm: { ...createNewCrewForm.value[0], clockin_time: clockinTime },
    })
        .then(() => {
            allUsers.value = []
            createNewCrewForm.value = [{ crew_member_id: '', clockin_time: '' }]
            setLocalStorageFlag()
        })
        .catch(error => {
            const msg = error?.response?.data?.message
            toast.error(msg || 'Something went wrong')
        })
        .finally(() => setLoading(false))
}

const hfPerDiemDone = (status) => {
    allPerDiemStatus.value = status
    setLocalStorageFlag()
}

const trackTimeDone = () => setLocalStorageFlag()

const readyForVerification = () => {
    if (isBusy.value) return
    if (!confirm('Are you sure you are ready for verification?')) return

    setLoading(true)

    axios.post('/ready-for-verification', { crewId: crewId.value })
        .then(() => setLocalStorageFlag())
        .catch(err => console.error(err))
        .finally(() => setLoading(false))
}

const weatherEntry = () => {
    if (isBusy.value) return
    if (!confirm('Are you sure you want to add weather time for this crew?')) return

    setLoading(true)

    axios.post('/wather-entry', { crewId: crewId.value })
        .then(() => setLocalStorageFlag())
        .catch(err => console.error(err))
        .finally(() => setLoading(false))
}

const toggleLateEntryTime = () => {
    isLateEntryTimeVisible.value = !isLateEntryTimeVisible.value
    lateEntryTime.value = ''
}

const lastEntryTimeDone = () => {
    isLateEntryTimeVisible.value = false
    lateEntryTime.value = ''
}

const switchTimeType = () => {
    if (isBusy.value) return

    if (!selectedSwitchTypeId.value) {
        toast.error('Please select a time type')
        return
    }

    if (isLateEntryTimeVisible.value && !lateEntryTime.value) {
        toast.error('Please select the late entry time or toggle it off')
        return
    }

    if (!confirm('Apply new time type now?')) return

    setLoading(true)

    axios.post('/switch-time-type', {
        crewId: crewId.value,
        timeTypeId: selectedSwitchTypeId.value,
        lateEntryTime: lateEntryTime.value ? format(lateEntryTime.value, dateTimeFormat) : null,
    })
        .then(() => {
            selectedSwitchTypeId.value = shopTypeId.value ?? null
            setLocalStorageFlag()
            lastEntryTimeDone()
        })
        .catch(error => {
            const msg = error?.response?.data?.message || 'Something went wrong'
            toast.error(msg)
        })
        .finally(() => setLoading(false))
}
</script>

<style scoped>
/* ═══════════════════════════════════════════════════════════════════════
   CLOCK-IN MODAL — FIELD-OPS REDESIGN
   Desktop/tablet layout + dedicated iPhone-sized layout
   ═══════════════════════════════════════════════════════════════════════ */

/* Design tokens */
.clk-shell {
    --clk-bg: #0f1626;
    --clk-surface: #1a2138;
    --clk-surface-2: #222c47;
    --clk-border: #2e3a5c;
    --clk-text: #ffffff;
    --clk-muted: #8895b3;
    --clk-primary: #3b82f6;
    --clk-success: #22c55e;
    --clk-danger: #ef4444;
    --clk-warn: #f59e0b;
    --clk-accent: #ec4899;
    --clk-radius: 14px;
    --clk-radius-sm: 8px;
    --clk-gap: 12px;

    background: var(--clk-bg);
    color: var(--clk-text);
    height: 100%;
    border-radius: 0;
    display: flex;
    flex-direction: column;
    overflow: hidden;
    position: relative;
    /* anchor for the rotation overlay */
}

/* Modal sizing rules are in a non-scoped <style> block below —
   scoped selectors can't match Bootstrap's `.modal-dialog`. */

/* ── TOP BAR ───────────────────────────────────────────── */
.clk-topbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 14px 18px;
    background: var(--clk-surface);
    border-bottom: 1px solid var(--clk-border);
    flex-shrink: 0;
    padding-top: max(14px, env(safe-area-inset-top));
}

.clk-topbar__title {
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 1rem;
    font-weight: 800;
    /* bold */
    letter-spacing: 0.5px;
    text-transform: uppercase;
    color: #ffffff;
    /* bright white */
}

.clk-topbar__title i {
    font-size: 1.2rem;
    color: var(--clk-primary);
}

.clk-topbar__close {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    border: none;
    background: var(--clk-surface-2);
    color: var(--clk-text);
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: background 0.15s;
}

.clk-topbar__close:hover,
.clk-topbar__close:focus-visible {
    background: var(--clk-border);
    outline: none;
}

/* ── STATUS STRIP ─────────────────────────────────────── */
.clk-status {
    padding: 14px 18px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    flex-shrink: 0;
    background:
        linear-gradient(135deg, rgba(59, 130, 246, 0.08) 0%, transparent 60%),
        var(--clk-bg);
    border-bottom: 1px solid var(--clk-border);
}

.clk-status__pill {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    padding: 9px 16px;
    background: var(--clk-surface);
    border: 1px solid var(--clk-border);
    border-radius: 999px;
    font-weight: 800;
    /* bold */
    font-size: 0.95rem;
    color: #ffffff;
    /* bright white */
    letter-spacing: 0.3px;
}

.clk-status__dot {
    width: 10px;
    height: 10px;
    border-radius: 50%;
    background: var(--clk-muted);
    box-shadow: 0 0 0 3px rgba(255, 255, 255, 0.05);
}

.clk-status[data-state="unverified"] .clk-status__dot {
    background: var(--clk-warn);
    box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.2);
}

.clk-status[data-state="verified"] .clk-status__dot {
    background: var(--clk-primary);
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.25);
}

.clk-status[data-state="working"] .clk-status__dot {
    background: var(--clk-success);
    box-shadow: 0 0 0 3px rgba(34, 197, 94, 0.25);
    animation: clk-pulse 1.6s ease-in-out infinite;
}

.clk-status[data-state="done"] .clk-status__dot {
    background: var(--clk-muted);
}

@keyframes clk-pulse {

    0%,
    100% {
        box-shadow: 0 0 0 3px rgba(34, 197, 94, 0.25);
    }

    50% {
        box-shadow: 0 0 0 8px rgba(34, 197, 94, 0);
    }
}

.clk-status__meta {
    font-size: 0.85rem;
    color: var(--clk-muted);
    text-align: right;
    font-weight: 500;
}

/* ── BODY ─────────────────────────────────────────────── */
.clk-body {
    flex: 1;
    min-height: 0;
    /* allow the flex child to shrink so overflow-y actually kicks in */
    overflow-y: auto;
    -webkit-overflow-scrolling: touch;
    padding: 16px 14px;
    display: flex;
    flex-direction: column;
    gap: var(--clk-gap);
}

.clk-body--phone {
    padding: 12px 12px;
    gap: 10px;
}

/* In portrait (default phone orientation), the left-column wrapper is
   transparent to layout — its children stack inside the body directly.
   In landscape, a media query below reassigns it to a real flex column. */
.clk-phone-left {
    display: contents;
}

.clk-body__spacer {
    height: 12px;
}

/* ── CARD ─────────────────────────────────────────────── */
.clk-card {
    background: var(--clk-surface);
    border: 1px solid var(--clk-border);
    border-radius: var(--clk-radius);
    padding: 14px;
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.clk-card--compact {
    padding: 0;
    overflow: hidden;
}

.clk-card--flush {
    padding: 14px 0;
}

.clk-card--flush>.clk-section-head,
.clk-card--flush>.clk-select-all,
.clk-card--flush>.clk-empty {
    padding-left: 14px;
    padding-right: 14px;
}

.clk-label {
    font-size: 0.75rem;
    font-weight: 700;
    letter-spacing: 0.8px;
    text-transform: uppercase;
    color: var(--clk-muted);
    margin: 0;
}

.clk-hint {
    font-size: 0.8rem;
    color: var(--clk-muted);
    margin: 0;
}

.clk-section-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
}

.clk-section-head__title {
    font-weight: 800;
    /* bold */
    font-size: 1rem;
    color: #ffffff;
    /* bright white */
    display: inline-flex;
    align-items: center;
}

.clk-section-head__right {
    display: flex;
    align-items: center;
    color: var(--clk-muted);
    font-size: 0.85rem;
}

.clk-badge--count {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 22px;
    height: 22px;
    padding: 0 6px;
    margin-left: 8px;
    background: var(--clk-surface-2);
    border: 1px solid var(--clk-border);
    border-radius: 999px;
    font-size: 0.75rem;
    font-weight: 700;
    color: #ffffff;
}

/* ── COLLAPSE (Late Entry) ────────────────────────────── */
.clk-collapse-toggle {
    width: 100%;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 14px 16px;
    background: transparent;
    border: none;
    color: var(--clk-text);
    font-size: 0.95rem;
    font-weight: 700;
    cursor: pointer;
    min-height: 52px;
    transition: background 0.15s;
}

.clk-collapse-toggle:hover {
    background: rgba(255, 255, 255, 0.02);
}

.clk-collapse-toggle.is-open {
    background: rgba(59, 130, 246, 0.08);
}

.clk-collapse-toggle.is-disabled {
    opacity: 0.5;
    pointer-events: none;
}

.clk-collapse-toggle__left {
    display: flex;
    align-items: center;
    gap: 10px;
}

.clk-collapse-toggle__left i {
    color: var(--clk-primary);
}

.clk-collapse-body {
    padding: 14px 16px 16px;
    display: flex;
    flex-direction: column;
    gap: 8px;
    border-top: 1px solid var(--clk-border);
}

/* ── FORM CONTROLS ────────────────────────────────────── */
.clk-select,
.clk-shell select {
    width: 100%;
    min-height: 48px;
    padding: 10px 14px;
    padding-right: 36px;
    background: var(--clk-surface-2);
    border: 1px solid var(--clk-border);
    border-radius: var(--clk-radius-sm);
    color: var(--clk-text);
    font-size: 16px !important;
    font-weight: 500;
    appearance: none;
    -webkit-appearance: none;
    background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 12 8'%3e%3cpath fill='none' stroke='%238895b3' stroke-width='2' d='M1 1l5 5 5-5'/%3e%3c/svg%3e");
    background-repeat: no-repeat;
    background-position: right 14px center;
    background-size: 12px;
    cursor: pointer;
    transition: border-color 0.15s, box-shadow 0.15s;
}

.clk-select:focus,
.clk-shell select:focus {
    outline: none;
    border-color: var(--clk-primary);
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.2);
}

.clk-select:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.clk-inline-pair {
    display: flex;
    gap: 8px;
    align-items: stretch;
}

.clk-inline-pair .clk-select {
    flex: 1;
}

/* Datepicker */
.clk-datepicker {
    width: 100%;
}

.clk-datepicker :deep(.dp__input) {
    min-height: 48px;
    background: var(--clk-surface-2);
    border: 1px solid var(--clk-border);
    border-radius: var(--clk-radius-sm);
    color: var(--clk-text);
    font-size: 16px;
}

.clk-datepicker :deep(.dp__input:hover),
.clk-datepicker :deep(.dp__input:focus) {
    border-color: var(--clk-primary);
}

.clk-datepicker :deep(.dp__input_icon),
.clk-datepicker :deep(.dp__icon) {
    color: var(--clk-muted);
}

:global(.dp--menu-wrapper) {
    z-index: 1080 !important;
}

:global(.dp--clear-btn) {
    display: none !important;
}

/* ── BUTTONS ──────────────────────────────────────────── */
.clk-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 12px 20px;
    min-height: 52px;
    border-radius: var(--clk-radius-sm);
    border: 1px solid transparent;
    font-size: 0.95rem;
    font-weight: 700;
    letter-spacing: 0.3px;
    cursor: pointer;
    transition: transform 0.08s, background 0.15s, border-color 0.15s, box-shadow 0.15s;
    text-decoration: none;
    white-space: nowrap;
}

.clk-btn:active:not(:disabled) {
    transform: scale(0.97);
}

.clk-btn:disabled {
    opacity: 0.55;
    cursor: not-allowed;
}

.clk-btn:focus-visible {
    outline: none;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.35);
}

.clk-btn--sm {
    min-height: 44px;
    padding: 8px 14px;
    font-size: 0.85rem;
}

.clk-btn--hero {
    flex: 1;
    min-height: 60px;
    font-size: 1.05rem;
    letter-spacing: 0.5px;
    text-transform: uppercase;
    padding: 14px 22px;
    font-weight: 800;
}

.clk-btn--primary {
    background: var(--clk-primary);
    color: #fff;
}

.clk-btn--primary:hover:not(:disabled) {
    background: #2563eb;
}

.clk-btn--success {
    background: var(--clk-success);
    color: #fff;
}

.clk-btn--success:hover:not(:disabled) {
    background: #16a34a;
}

.clk-btn--danger {
    background: var(--clk-danger);
    color: #fff;
}

.clk-btn--danger:hover:not(:disabled) {
    background: #dc2626;
}

.clk-btn--ghost {
    background: var(--clk-surface-2);
    border-color: var(--clk-border);
    color: var(--clk-text);
}

.clk-btn--ghost:hover:not(:disabled) {
    background: var(--clk-border);
}

.clk-btn--outline {
    background: transparent;
    border-color: var(--clk-border);
    color: var(--clk-text);
}

.clk-btn--outline:hover:not(:disabled) {
    background: var(--clk-surface-2);
    border-color: var(--clk-primary);
}

/* ── ADD CREW (desktop/tablet) ────────────────────────── */
.clk-add-crew {
    display: grid;
    grid-template-columns: 1fr;
    gap: 8px;
}

.clk-add-crew :deep(.select2-container) {
    width: 100% !important;
}

.clk-add-crew :deep(.select2-selection) {
    min-height: 48px !important;
    background: var(--clk-surface-2) !important;
    border: 1px solid var(--clk-border) !important;
    border-radius: var(--clk-radius-sm) !important;
    padding: 6px 10px !important;
    color: var(--clk-text) !important;
}

.clk-add-crew :deep(.select2-selection__rendered) {
    color: var(--clk-text) !important;
    line-height: 36px !important;
}

.clk-add-crew :deep(.select2-selection__arrow) {
    height: 46px !important;
}

/* ── SELECT ALL ROW ───────────────────────────────────── */
.clk-select-all {
    padding: 12px 14px;
    border-top: 1px solid var(--clk-border);
    border-bottom: 1px solid var(--clk-border);
    background: var(--clk-surface-2);
}

.clk-check {
    display: flex;
    align-items: center;
    gap: 12px;
    cursor: pointer;
    font-weight: 700;
    font-size: 0.95rem;
    margin: 0;
    color: #ffffff;
}

.clk-check input[type="checkbox"] {
    width: 22px;
    height: 22px;
    accent-color: var(--clk-primary);
    cursor: pointer;
}

/* ── EMPTY ────────────────────────────────────────────── */
.clk-empty {
    padding: 32px 14px;
    text-align: center;
    color: var(--clk-muted);
}

.clk-empty i {
    font-size: 2rem;
    margin-bottom: 8px;
    opacity: 0.5;
}

.clk-empty p {
    margin: 0;
    font-size: 0.9rem;
}

/* ── CREW ROW (desktop/tablet) ────────────────────────── */
.clk-crew-list {
    display: flex;
    flex-direction: column;
}

.clk-crew-row {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    padding: 14px;
    border-top: 1px solid var(--clk-border);
    transition: background 0.15s;
}

.clk-crew-row:first-child {
    border-top: none;
}

.clk-crew-row.is-checked {
    background: rgba(59, 130, 246, 0.06);
}

.clk-crew-row.is-out {
    opacity: 0.7;
}

.clk-crew-row__check {
    padding-top: 2px;
    margin: 0;
}

.clk-crew-row__check input[type="checkbox"] {
    width: 22px;
    height: 22px;
    accent-color: var(--clk-primary);
    cursor: pointer;
}

.clk-crew-row__main {
    flex: 1;
    min-width: 0;
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.clk-crew-row__name {
    font-weight: 800;
    font-size: 1rem;
    color: #ffffff;
    word-break: break-word;
}

.clk-crew-row__meta {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
}

.clk-crew-row__times {
    margin-top: 4px;
}

.clk-time-readout {
    font-family: ui-monospace, 'SF Mono', Menlo, monospace;
    font-size: 0.85rem;
    color: var(--clk-muted);
}

.clk-time-edit {
    display: grid;
    grid-template-columns: 1fr;
    gap: 8px;
    margin-top: 6px;
}

.clk-time-edit__field {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.clk-time-edit__label {
    font-size: 0.7rem;
    letter-spacing: 0.8px;
    text-transform: uppercase;
    color: var(--clk-muted);
    font-weight: 700;
}

.clk-crew-row__actions {
    display: flex;
    align-items: center;
    gap: 8px;
    padding-top: 2px;
}

.clk-icon-btn {
    width: 40px;
    height: 40px;
    border-radius: var(--clk-radius-sm);
    border: 1px solid var(--clk-border);
    background: var(--clk-surface-2);
    color: var(--clk-muted);
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.15s;
    flex-shrink: 0;
}

.clk-icon-btn:hover:not(:disabled) {
    color: var(--clk-text);
    border-color: var(--clk-primary);
}

.clk-icon-btn.is-active {
    background: var(--clk-primary);
    border-color: var(--clk-primary);
    color: #fff;
}

.clk-icon-btn:disabled {
    opacity: 0.4;
    cursor: not-allowed;
}

/* Per-diem stars */
.clk-crew-row__actions :deep(.fa-star),
.clk-crew-row__actions :deep(.fa-star-half),
.clk-crew-row__actions :deep(.fa-star-o),
.clk-phone-crew-card__actions :deep(.fa-star),
.clk-phone-crew-card__actions :deep(.fa-star-half),
.clk-phone-crew-card__actions :deep(.fa-star-o) {
    font-size: 1.25rem;
    color: var(--clk-warn);
    padding: 8px;
    margin: 0 !important;
    cursor: pointer;
}

/* ── CHIPS ────────────────────────────────────────────── */
.clk-chip {
    display: inline-flex;
    align-items: center;
    padding: 3px 10px;
    border-radius: 999px;
    font-size: 0.75rem;
    font-weight: 700;
    background: var(--clk-surface-2);
    border: 1px solid var(--clk-border);
    color: #ffffff;
}

.clk-chip--muted {
    color: var(--clk-muted);
}

.clk-chip--in {
    background: rgba(34, 197, 94, 0.15);
    border-color: rgba(34, 197, 94, 0.35);
    color: #86efac;
}

.clk-chip--out {
    background: rgba(239, 68, 68, 0.12);
    border-color: rgba(239, 68, 68, 0.3);
    color: #fca5a5;
}

/* ── ACTION BAR (sticky bottom) ───────────────────────── */
.clk-actionbar {
    flex-shrink: 0;
    padding: 12px 14px;
    padding-bottom: max(12px, env(safe-area-inset-bottom));
    background: var(--clk-surface);
    border-top: 1px solid var(--clk-border);
    display: flex;
    gap: 10px;
    align-items: stretch;
    box-shadow: 0 -10px 30px rgba(0, 0, 0, 0.25);
}

.clk-actionbar__status {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 16px;
    background: var(--clk-surface-2);
    border: 1px dashed var(--clk-border);
    border-radius: var(--clk-radius-sm);
    color: var(--clk-muted);
    font-size: 0.85rem;
    text-align: center;
}

/* ── TABLET/DESKTOP (≥ 768px) ─────────────────────────── */
@media (min-width: 768px) {
    .clk-body:not(.clk-body--phone) {
        padding: 20px;
    }

    .clk-add-crew {
        grid-template-columns: 1.5fr 1.3fr auto;
        align-items: center;
    }

    .clk-time-edit {
        grid-template-columns: 1fr 1fr;
    }
}

/* Child component (Depart.vue) overrides */
.clk-card :deep(.btn),
.clk-phone-field :deep(.btn) {
    border-radius: var(--clk-radius-sm);
    min-height: 48px;
    font-weight: 700;
}

.clk-card :deep(select),
.clk-phone-field :deep(select) {
    min-height: 48px;
    background-color: var(--clk-surface-2) !important;
    color: var(--clk-text);
    border-color: var(--clk-border);
    font-size: 16px !important;
}

/* ═══════════════════════════════════════════════════════════════════════
   PHONE-SPECIFIC LAYOUT (≤ 480px)
   Tighter spacing, flat sections (no card borders), compact crew cards
   ═══════════════════════════════════════════════════════════════════════ */

.clk-shell--phone .clk-topbar {
    padding: 12px 14px;
    padding-top: max(12px, env(safe-area-inset-top));
}

.clk-shell--phone .clk-topbar__title {
    font-size: 0.9rem;
    gap: 8px;
}

.clk-shell--phone .clk-topbar__title i {
    font-size: 1.05rem;
}

.clk-shell--phone .clk-topbar__close {
    width: 36px;
    height: 36px;
}

.clk-shell--phone .clk-status {
    padding: 12px 14px;
    flex-wrap: wrap;
}

.clk-shell--phone .clk-status__pill {
    padding: 8px 14px;
    font-size: 0.9rem;
}

.clk-shell--phone .clk-status__meta {
    flex-basis: 100%;
    text-align: left;
    margin-top: 4px;
}

/* Phone: late-entry pill toggle */
.clk-phone-late-entry {
    display: flex;
    align-items: center;
    gap: 10px;
    width: 100%;
    padding: 12px 14px;
    min-height: 48px;
    background: var(--clk-surface);
    border: 1px solid var(--clk-border);
    border-radius: var(--clk-radius);
    color: #ffffff;
    font-size: 0.9rem;
    font-weight: 700;
    cursor: pointer;
    transition: background 0.15s;
}

.clk-phone-late-entry i:first-child {
    color: var(--clk-primary);
}

.clk-phone-late-entry.is-open {
    background: rgba(59, 130, 246, 0.12);
    border-color: var(--clk-primary);
}

.clk-phone-late-entry.is-disabled {
    opacity: 0.5;
    pointer-events: none;
}

.clk-phone-late-entry-body {
    background: var(--clk-surface);
    border: 1px solid var(--clk-border);
    border-radius: var(--clk-radius);
    padding: 12px;
}

/* Phone: field containers (no heavy card, just label + control) */
.clk-phone-field {
    background: var(--clk-surface);
    border: 1px solid var(--clk-border);
    border-radius: var(--clk-radius);
    padding: 12px;
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.clk-phone-addcrew-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
}

.clk-phone-addcrew-body {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.clk-phone-addcrew-body :deep(.select2-container) {
    width: 100% !important;
}

.clk-phone-addcrew-body :deep(.select2-selection) {
    min-height: 48px !important;
    background: var(--clk-surface-2) !important;
    border: 1px solid var(--clk-border) !important;
    border-radius: var(--clk-radius-sm) !important;
    padding: 6px 10px !important;
    color: var(--clk-text) !important;
}

.clk-phone-addcrew-body :deep(.select2-selection__rendered) {
    color: var(--clk-text) !important;
    line-height: 36px !important;
}

/* Phone: crew section */
.clk-phone-crew-section {
    background: var(--clk-surface);
    border: 1px solid var(--clk-border);
    border-radius: var(--clk-radius);
    overflow: hidden;
    min-height: 120px;
    /* always reserve space for at least one crew card + head */
    flex-shrink: 0;
    /* never collapse below min-content */
}

.clk-phone-crew-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 12px 14px;
    border-bottom: 1px solid var(--clk-border);
}

.clk-phone-crew-title {
    font-size: 1rem;
    font-weight: 800;
    color: #ffffff;
    display: inline-flex;
    align-items: center;
}

.clk-phone-crew-head-right {
    display: flex;
    align-items: center;
}

.clk-phone-crew-list {
    display: flex;
    flex-direction: column;
}

.clk-phone-crew-card {
    padding: 10px 12px;
    border-bottom: 1px solid var(--clk-border);
    display: flex;
    flex-direction: column;
    gap: 6px;
    transition: background 0.15s;
}

.clk-phone-crew-card:last-child {
    border-bottom: none;
}

.clk-phone-crew-card.is-checked {
    background: rgba(59, 130, 246, 0.08);
}

.clk-phone-crew-card.is-out {
    opacity: 0.75;
}

.clk-phone-crew-card__top {
    display: flex;
    align-items: center;
    gap: 10px;
}

.clk-phone-crew-card__check {
    margin: 0;
    flex-shrink: 0;
}

.clk-phone-crew-card__check input[type="checkbox"] {
    width: 22px;
    height: 22px;
    accent-color: var(--clk-primary);
    cursor: pointer;
}

.clk-phone-crew-card__name {
    flex: 1;
    min-width: 0;
    font-weight: 800;
    font-size: 0.95rem;
    color: #ffffff;
    word-break: break-word;
    line-height: 1.25;
}

.clk-phone-crew-card__actions {
    display: flex;
    align-items: center;
    gap: 4px;
    flex-shrink: 0;
}

.clk-phone-crew-card__meta {
    display: flex;
    flex-wrap: wrap;
    gap: 4px;
    padding-left: 32px;
    /* align with name, past checkbox */
}

.clk-phone-crew-card.is-in .clk-phone-crew-card__meta,
.clk-phone-crew-card.is-out .clk-phone-crew-card__meta,
.clk-phone-crew-card:not(.is-checked) .clk-phone-crew-card__meta {
    padding-left: 0;
}

.clk-phone-crew-card__times {
    font-family: ui-monospace, 'SF Mono', Menlo, monospace;
    font-size: 0.8rem;
    color: var(--clk-muted);
}

.clk-phone-crew-card__edit {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 6px;
    margin-top: 4px;
}

/* Phone: action bar tweaks */
.clk-shell--phone .clk-actionbar {
    padding: 10px 12px;
    padding-bottom: max(10px, env(safe-area-inset-bottom));
    gap: 8px;
}

.clk-shell--phone .clk-btn--hero {
    min-height: 56px;
    font-size: 1rem;
    padding: 12px 16px;
}

.clk-shell--phone .clk-btn--ghost:not(.clk-btn--hero) {
    min-height: 56px;
    flex: 0 0 auto;
    padding: 10px 14px;
}

/* Phone: very small (iPhone SE, 320-360px) */
@media (max-width: 360px) {
    .clk-shell--phone .clk-topbar__title {
        font-size: 0.85rem;
    }

    .clk-shell--phone .clk-status__pill {
        font-size: 0.85rem;
        padding: 7px 12px;
    }

    .clk-shell--phone .clk-btn--hero {
        font-size: 0.9rem;
        min-height: 52px;
    }

    .clk-shell--phone .clk-phone-crew-card__name {
        font-size: 0.9rem;
    }

    .clk-shell--phone .clk-phone-crew-card__edit {
        grid-template-columns: 1fr;
    }

    .clk-shell--phone .clk-body--phone {
        padding: 10px 10px;
        gap: 8px;
    }
}

/* Phone landscape: let the action bar show alongside content better */
/* ═══════════════════════════════════════════════════════════════════════
   PHONE LANDSCAPE (short viewport height)
   Two-column layout: controls on the left, crew list on the right.
   The wider-than-tall viewport is ideal for a side-by-side arrangement.
   ═══════════════════════════════════════════════════════════════════════ */
@media (orientation: landscape) and (max-height: 500px) {

    /* ── Chrome compression ─────────────────────────────────── */

    /* Topbar: minimal height */
    .clk-shell--phone .clk-topbar {
        padding: 5px 12px;
        padding-top: max(5px, env(safe-area-inset-top));
    }

    .clk-shell--phone .clk-topbar__title {
        font-size: 0.8rem;
    }

    .clk-shell--phone .clk-topbar__title i {
        font-size: 0.95rem;
    }

    .clk-shell--phone .clk-topbar__close {
        width: 30px;
        height: 30px;
        font-size: 0.85rem;
    }

    /* Status: single line, compact */
    .clk-shell--phone .clk-status {
        padding: 5px 12px;
        flex-wrap: nowrap;
    }

    .clk-shell--phone .clk-status__pill {
        padding: 4px 10px;
        font-size: 0.78rem;
    }

    .clk-shell--phone .clk-status__meta {
        flex-basis: auto;
        text-align: right;
        margin-top: 0;
        font-size: 0.72rem;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        min-width: 0;
    }

    /* ── TWO-COLUMN BODY ────────────────────────────────────── */
    /* Left column holds all controls (stacks and scrolls inside),
       right column is the crew section (fills full height, scrolls inside). */
    .clk-shell--phone .clk-body--phone {
        display: flex;
        flex-direction: row;
        gap: 8px;
        padding: 8px 10px;
        overflow: hidden;
    }

    /* Left column wrapper: controls stack inside, scrolls if needed */
    .clk-shell--phone .clk-body--phone>.clk-phone-left {
        flex: 0 0 42%;
        max-width: 42%;
        min-width: 0;
        display: flex;
        flex-direction: column;
        gap: 6px;
        overflow-y: auto;
        -webkit-overflow-scrolling: touch;
        padding-right: 2px;
        /* breathing room for scrollbar */
    }

    /* Crew section: right column, fills remaining width, scrolls internally */
    .clk-shell--phone .clk-body--phone>.clk-phone-crew-section {
        flex: 1 1 auto;
        min-width: 0;
        min-height: 0;
        display: flex;
        flex-direction: column;
        overflow: hidden;
    }

    .clk-shell--phone .clk-body--phone>.clk-phone-crew-section>.clk-phone-crew-head {
        flex-shrink: 0;
    }

    .clk-shell--phone .clk-body--phone>.clk-phone-crew-section>.clk-phone-crew-list,
    .clk-shell--phone .clk-body--phone>.clk-phone-crew-section>.clk-empty {
        overflow-y: auto;
        -webkit-overflow-scrolling: touch;
        flex: 1;
        min-height: 0;
    }

    /* Hide body spacer in landscape */
    .clk-shell--phone .clk-body--phone>.clk-body__spacer {
        display: none;
    }

    /* ── Card sizing compression ────────────────────────────── */
    .clk-shell--phone .clk-phone-field {
        padding: 8px 10px;
        gap: 6px;
    }

    .clk-shell--phone .clk-phone-late-entry {
        padding: 6px 12px;
        min-height: 38px;
        font-size: 0.85rem;
    }

    .clk-shell--phone .clk-phone-late-entry-body {
        padding: 8px;
    }

    .clk-shell--phone .clk-select,
    .clk-shell--phone .clk-phone-field :deep(select) {
        min-height: 38px;
        padding: 5px 12px;
    }

    .clk-shell--phone .clk-datepicker :deep(.dp__input) {
        min-height: 38px;
    }

    /* Crew list visuals */
    .clk-shell--phone .clk-phone-crew-head {
        padding: 7px 12px;
    }

    .clk-shell--phone .clk-phone-crew-title {
        font-size: 0.9rem;
    }

    .clk-shell--phone .clk-phone-crew-card {
        padding: 7px 10px;
        gap: 3px;
    }

    .clk-shell--phone .clk-phone-crew-card__name {
        font-size: 0.88rem;
    }

    .clk-shell--phone .clk-phone-crew-card__meta {
        padding-left: 0;
    }

    /* Action bar: short, nowrap */
    .clk-shell--phone .clk-actionbar {
        padding: 5px 10px;
        padding-bottom: max(5px, env(safe-area-inset-bottom));
        gap: 6px;
        flex-wrap: nowrap;
    }

    .clk-shell--phone .clk-btn--hero {
        min-height: 40px;
        font-size: 0.82rem;
        padding: 6px 14px;
        letter-spacing: 0.3px;
    }

    .clk-shell--phone .clk-btn--ghost:not(.clk-btn--hero) {
        min-height: 40px;
        padding: 6px 12px;
        font-size: 0.78rem;
    }
}

/* ═══════════════════════════════════════════════════════════════════════
   ROTATION PROMPT OVERLAY
   Shown on phone-sized devices when in portrait orientation.
   Covers the entire modal so the user can't interact with stale UI.
   ═══════════════════════════════════════════════════════════════════════ */

/* When the lock is active, hide the normal UI behind the overlay */
.clk-shell--locked>.clk-topbar,
.clk-shell--locked>.clk-status,
.clk-shell--locked>.clk-body,
.clk-shell--locked>.clk-actionbar {
    visibility: hidden;
    pointer-events: none;
}

.clk-rotate {
    position: absolute;
    inset: 0;
    z-index: 10;
    background: linear-gradient(180deg,
            var(--clk-bg) 0%,
            var(--clk-surface) 100%);
    color: #ffffff;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 24px;
    padding-top: max(24px, env(safe-area-inset-top));
    padding-bottom: max(24px, env(safe-area-inset-bottom));
    text-align: center;
    -webkit-tap-highlight-color: transparent;
}

.clk-rotate__close {
    position: absolute;
    top: max(14px, env(safe-area-inset-top));
    right: 14px;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    border: none;
    background: var(--clk-surface-2);
    color: #ffffff;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: background 0.15s;
    font-size: 1rem;
}

.clk-rotate__close:hover,
.clk-rotate__close:focus-visible {
    background: var(--clk-border);
    outline: none;
}

.clk-rotate__inner {
    max-width: 320px;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 18px;
}

.clk-rotate__icon {
    width: 112px;
    height: 112px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--clk-primary);
    background: rgba(59, 130, 246, 0.1);
    border: 1px solid rgba(59, 130, 246, 0.25);
    border-radius: 50%;
}

.clk-rotate__phone {
    transform-origin: 60px 50px;
    animation: clk-rotate-spin 2.4s cubic-bezier(0.65, 0, 0.35, 1) infinite;
}

@keyframes clk-rotate-spin {
    0% {
        transform: rotate(0deg);
    }

    20% {
        transform: rotate(0deg);
    }

    55% {
        transform: rotate(-90deg);
    }

    75% {
        transform: rotate(-90deg);
    }

    100% {
        transform: rotate(0deg);
    }
}

.clk-rotate__title {
    font-size: 1.35rem;
    font-weight: 800;
    letter-spacing: 0.3px;
    color: #ffffff;
    margin: 0;
}

.clk-rotate__desc {
    font-size: 0.95rem;
    line-height: 1.5;
    color: var(--clk-muted);
    margin: 0;
    font-weight: 500;
}

/* Extra-small screens (iPhone SE in portrait = 375×667, after safe-area trim) */
@media (max-height: 600px) {
    .clk-rotate__icon {
        width: 88px;
        height: 88px;
    }

    .clk-rotate__icon svg {
        width: 72px;
        height: 72px;
    }

    .clk-rotate__title {
        font-size: 1.15rem;
    }

    .clk-rotate__desc {
        font-size: 0.85rem;
    }

    .clk-rotate__inner {
        gap: 14px;
    }
}
</style>

<!-- ═══════════════════════════════════════════════════════════════════════
     GLOBAL (non-scoped) STYLES
     Bootstrap's .modal-dialog lives outside our scoped selectors, so any
     rule targeting it must be global. These force the modal to always
     fill the entire viewport — critical for phones.
     ═══════════════════════════════════════════════════════════════════════ -->
<style>
/* Kill ALL Bootstrap modal sizing for this specific modal. */
#clockin.modal {
    padding: 0 !important;
}

#clockin .modal-dialog {
    width: 100vw !important;
    max-width: 100vw !important;
    height: 100vh !important;
    height: 100dvh !important;
    /* modern mobile-safe viewport unit */
    margin: 0 !important;
    display: flex !important;
    align-items: stretch !important;
    justify-content: stretch !important;
}

#clockin .modal-content {
    width: 100% !important;
    height: 100% !important;
    max-height: 100% !important;
    border-radius: 0 !important;
    border: 0 !important;
}

/* Desktop: on wide screens, dock the modal to the right at 560px
   so users can still see their dashboard behind it.
   Phones in landscape (short side ≤ 500px) keep the full-width lock. */
@media (min-width: 992px) and (min-height: 501px) {
    #clockin .modal-dialog {
        width: min(560px, 100vw) !important;
        max-width: 560px !important;
        margin-left: auto !important;
        margin-right: 0 !important;
    }
}
</style>
