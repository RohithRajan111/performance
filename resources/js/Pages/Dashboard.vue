<script setup>
// NO FullCalendar CSS imports are needed. The JS plugins handle style injection.

import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Modal from '@/Components/Modal.vue';
import InputError from '@/Components/InputError.vue';
import { Head, Link, useForm, router, usePage } from '@inertiajs/vue3';
import { ref, computed, onMounted, onUnmounted, watch } from 'vue';
import { formatDistanceToNowStrict, format } from 'date-fns';

import FullCalendar from '@fullcalendar/vue3';
import dayGridPlugin from '@fullcalendar/daygrid';
import interactionPlugin from '@fullcalendar/interaction';
import { Doughnut } from 'vue-chartjs';
import { Chart as ChartJS, ArcElement, Tooltip, Legend } from 'chart.js';

ChartJS.register(ArcElement, Tooltip, Legend);

// --- PROPS ---
const props = defineProps({
    user: { type: Object, required: true },
    attendance: { type: Object, required: true },
    calendarEvents: { type: Array, default: () => [] },
    greeting: { type: Object, required: true },
    projects: { type: Array, default: () => [] },
    myTasks: { type: Array, default: () => [] },
});

// --- ROLE-BASED VISIBILITY & HELPERS ---
const page = usePage();
const authUser = computed(() => page.props.auth.user);

const hasPermission = (permission) => {
    if (!authUser.value || !Array.isArray(authUser.value.permissions)) {
        return false;
    }
    return authUser.value.permissions.includes(permission);
};

const canViewAttendanceStats = computed(() => hasPermission('manage employees'));

// --- TASK MANAGEMENT ---
const updateTaskStatus = (task, newStatus) => {
    // This sends a PATCH request to your backend.
    // Ensure you have a route like: Route::patch('/tasks/{task}/status', ...)->name('tasks.updateStatus');
    router.patch(route('tasks.updateStatus', task.id), {
        status: newStatus
    }, {
        preserveScroll: true,
    });
};

// --- HELPER FUNCTIONS FOR THE 'MY ASSIGNED TASKS' PANEL ---

// Determines the background/border color for a task item based on its status.
const getTaskStatusColor = (status) => {
    if (status === 'completed' || status === 'done') return 'bg-green-50 border-green-200';
    if (status === 'in_progress') return 'bg-blue-50 border-blue-200';
    // Default for 'todo', 'pending', etc.
    return 'bg-gray-50 border-gray-200';
};

// Determines the color for the small status badge.
const getStatusBadgeColor = (status) => {
    if (status === 'completed' || status === 'done') return 'bg-green-100 text-green-800';
    if (status === 'in_progress') return 'bg-blue-100 text-blue-800';
    return 'bg-gray-100 text-gray-800';
};

// Converts snake_case status from the DB (e.g., 'in_progress') to a more readable format.
const getStatusDisplayName = (status) => {
    return (status || 'todo').replace(/_/g, ' ');
};

// Logic to show the "Start" button. Now robustly handles 'todo' or 'pending'.
const canStartTask = (status) => status === 'todo' || status === 'pending';

// Logic to show the "Done" button.
const canCompleteTask = (status) => status === 'in_progress';


// --- OTHER EXISTING SCRIPT LOGIC ---
const companyExperience = computed(() => {
    if (!props.user.hire_date) return 'N/A';
    return formatDistanceToNowStrict(new Date(props.user.hire_date));
});

const isNoteModalVisible = ref(false);
const modalMode = ref('create');
const editingNoteId = ref(null);
const noteForm = useForm({ note: '', date: '' });
const now = ref(new Date());
let timeUpdater = null;
const liveTime = computed(() => now.value.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', hour12: true }));

onMounted(() => { timeUpdater = setInterval(() => { now.value = new Date(); }, 1000); });
onUnmounted(() => { clearInterval(timeUpdater); });

const calendar = ref(null);

watch(() => props.calendarEvents, (newEvents) => {
    if (calendar.value) {
        const calendarApi = calendar.value.getApi();
        calendarApi.removeAllEvents();
        calendarApi.addEventSource(newEvents);
    }
}, { deep: true });

const currentCalendarView = ref('dayGridMonth');

function handleDateClick(arg) {
    modalMode.value = 'create';
    editingNoteId.value = null;
    noteForm.date = arg.dateStr;
    isNoteModalVisible.value = true;
}

function handleEventClick(arg) {
    if (arg.event.extendedProps.type === 'note') {
        modalMode.value = 'edit';
        editingNoteId.value = arg.event.extendedProps.note_id;
        noteForm.note = arg.event.title;
        noteForm.date = arg.event.startStr;
        isNoteModalVisible.value = true;
    }
}

const calendarOptions = ref({
    plugins: [dayGridPlugin, interactionPlugin],
    initialView: 'dayGridMonth',
    headerToolbar: false,
    events: props.calendarEvents,
    height: 'auto',
    selectable: true,
    dateClick: handleDateClick,
    eventClick: handleEventClick,
    dayHeaderClassNames: 'text-xs font-semibold text-slate-500 uppercase',
    dayCellClassNames: 'border-slate-200',
    eventDisplay: 'block',
    eventClassNames: 'p-1 rounded-md font-medium cursor-pointer border-none text-xs',
});

function changeCalendarView(view) {
    if(calendar.value) {
        calendar.value.getApi().changeView(view);
        currentCalendarView.value = view;
    }
}

function saveNote() {
    const action = modalMode.value === 'create' ? route('calendar-notes.store') : route('calendar-notes.update', editingNoteId.value);
    const method = modalMode.value === 'create' ? 'post' : 'put';
    noteForm[method](action, {
        preserveScroll: true,
        onSuccess: () => {
            closeModal();
            router.reload({ only: ['calendarEvents'] });
        },
    });
}

function deleteNote() {
    if (confirm('Are you sure you want to delete this note?')) {
        router.delete(route('calendar-notes.destroy', editingNoteId.value), {
            preserveScroll: true,
            onSuccess: () => {
                closeModal();
                router.reload({ only: ['calendarEvents'] });
            },
        });
    }
}

function closeModal() {
    isNoteModalVisible.value = false;
    noteForm.reset();
    editingNoteId.value = null;
}

const chartData = computed(() => ({
    labels: ['Present', 'Absent'],
    datasets: [{
        backgroundColor: ['#3b82f6', '#1f2937'],
        data: [props.attendance.present, props.attendance.absent],
        borderWidth: 0,
    }],
}));

const chartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    cutout: '80%',
    plugins: { legend: { display: false }, tooltip: { enabled: true } },
};
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>

        <!-- Calendar Note Modal -->
        <Modal :show="isNoteModalVisible" @close="closeModal">
            <div class="p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-bold text-slate-900">{{ modalMode === 'create' ? `Add a Note for ${noteForm.date}` : 'Edit Note' }}</h3>
                    <button @click="closeModal" class="p-1 rounded-full text-slate-400 hover:bg-slate-200 hover:text-slate-600 transition">×</button>
                </div>
                <form @submit.prevent="saveNote">
                    <div class="space-y-4">
                        <div>
                            <label for="note" class="block text-sm font-medium text-slate-700">Note</label>
                            <textarea v-model="noteForm.note" id="note" rows="4" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" placeholder="Team meeting at 10 AM..."></textarea>
                            <InputError class="mt-1 text-xs" :message="noteForm.errors.note" />
                        </div>
                        <div class="flex justify-between items-center">
                            <div><button v-if="modalMode === 'edit'" type="button" @click="deleteNote" class="text-sm font-semibold text-red-600 hover:text-red-800">Delete Note</button></div>
                            <div class="flex justify-end space-x-3">
                                <button type="button" @click="closeModal" class="px-4 py-2 text-sm font-semibold bg-white border border-slate-300 text-slate-700 rounded-lg hover:bg-slate-50">Cancel</button>
                                <button type="submit" :disabled="noteForm.processing" class="px-4 py-2 text-sm font-semibold bg-slate-900 text-white rounded-lg hover:bg-slate-700 disabled:opacity-50">{{ noteForm.processing ? 'Saving...' : 'Save' }}</button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </Modal>

        <div class="p-4 sm:p-6 lg:p-8 font-sans">
            <div class="max-w-7xl mx-auto space-y-6">
                <!-- Dashboard Header -->
                <div class="flex items-center justify-between">
                    <h1 class="text-3xl font-bold text-slate-900">Dashboard</h1>
                    <div class="flex items-center space-x-3">
                        <Link :href="route('leave.index')" class="px-4 py-2 text-sm font-semibold bg-slate-900 text-white rounded-lg hover:bg-slate-700 transition-colors shadow-sm">Create Leave Request</Link>
                    </div>
                </div>

                <!-- Grid for Top Row Cards -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- User Welcome Card -->
                    <div class="lg:col-span-2 bg-white p-6 rounded-xl shadow-sm border border-slate-200">
                        <div class="flex items-start justify-between">
                            <div class="flex items-center space-x-4">
                                <img v-if="user.avatar_url" class="h-16 w-16 rounded-full" :src="user.avatar_url" alt="User Avatar" />
                                <div v-else class="h-16 w-16 rounded-full bg-slate-200 flex items-center justify-center font-bold text-slate-600 text-2xl">{{ user.name.charAt(0) }}</div>
                                <div>
                                    <h3 class="text-lg font-bold text-slate-800">{{ user.name }} <span class="text-sm font-medium text-slate-400 ml-2">#{{ user.employee_id }}</span></h3>
                                    <p class="text-sm text-slate-500">{{ user.email }}</p>
                                </div>
                            </div>
                            <Link :href="route('profile.edit')" class="text-sm font-medium text-blue-600 hover:text-blue-800 flex items-center">Edit <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.5L16.732 3.732z"></path></svg></Link>
                        </div>
                        <div class="mt-6 grid grid-cols-2 md:grid-cols-4 gap-x-4 gap-y-6 border-t border-slate-100 pt-6">
                            <div><p class="text-xs text-slate-500">Designation</p><p class="text-sm font-semibold text-slate-800">{{ user.designation }}</p></div>
                            <div><p class="text-xs text-slate-500">Reporting to</p><p class="text-sm font-semibold text-slate-800">{{ user.parent ? user.parent.name : 'N/A' }}</p></div>
                            <div><p class="text-xs text-slate-500">Total Experience</p><p class="text-sm font-semibold text-slate-800">{{ user.total_experience_years }} Years</p></div>
                            <div><p class="text-xs text-slate-500">Company Experience</p><p class="text-sm font-semibold text-slate-800">{{ companyExperience }}</p></div>
                        </div>
                    </div>

                    <!-- Greeting Card -->
                    <div class="bg-white p-6 rounded-xl shadow-sm border border-slate-200 flex flex-col justify-between">
                        <h3 class="font-semibold text-slate-800">Good {{ greeting.message }}</h3>
                        <div class="flex items-center my-auto"><span class="text-4xl mr-4">{{ greeting.icon }}</span><span class="text-3xl font-bold text-slate-900">{{ liveTime }}</span></div>
                        <div class="text-sm text-slate-500 text-right border-t pt-2 border-slate-100">Today, {{ greeting.date }}</div>
                    </div>
                </div>

                <!-- Projects and Tasks Panels -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Panel: Active Projects -->
                    <div v-if="projects && projects.length > 0" class="bg-white p-6 rounded-xl shadow-sm border border-slate-200">
                        <h3 class="text-lg font-bold text-gray-900">Active Projects</h3>
                        <ul class="mt-4 space-y-3">
                            <li v-for="project in projects" :key="project.id" class="p-4 bg-gray-50 rounded-lg border border-gray-200 flex justify-between items-center">
                                <div>
                                    <p class="font-semibold text-gray-800">{{ project.name }}</p>
                                    <span class="text-sm text-gray-600 block capitalize">Status: {{ project.status }}</span>
                                </div>
                                <div>
                                    <Link :href="route('projects.show', project.id)" class="px-4 py-2 text-sm font-semibold text-white bg-indigo-600 rounded-md hover:bg-indigo-700 shadow-sm">
                                        <span v-if="hasPermission('assign tasks')">View / Assign Tasks</span>
                                        <span v-else>View Progress</span>
                                    </Link>
                                </div>
                            </li>
                        </ul>
                    </div>

                    <!-- Panel: My Assigned Tasks -->
                     <div v-if="myTasks && myTasks.length > 0" class="bg-white p-6 rounded-xl shadow-sm border border-slate-200">
                         <div class="flex justify-between items-center mb-4">
                            <h3 class="text-lg font-semibold text-gray-900 flex items-center">My Assigned Tasks</h3>
                            <span class="text-sm text-gray-500">{{ myTasks.length }} total tasks</span>
                        </div>
                        <div class="space-y-3 max-h-96 overflow-y-auto">
                            <div v-for="task in myTasks" :key="task.id"
                                 class="p-3 rounded-lg border transition-all duration-200 hover:shadow-sm"
                                 :class="getTaskStatusColor(task.status)">
                                <div class="flex justify-between items-center">
                                    <div class="flex-1">
                                        <div class="flex items-center space-x-2">
                                            <h4 class="text-sm font-medium text-gray-800">{{ task.name }}</h4>
                                            <span class="px-1.5 py-0.5 text-xs font-medium rounded capitalize"
                                                  :class="getStatusBadgeColor(task.status)">
                                                {{ getStatusDisplayName(task.status) }}
                                            </span>
                                        </div>
                                        <p class="text-xs text-gray-500 mt-1">{{ task.project?.name || 'No Project' }}</p>
                                    </div>
                                    <div class="flex gap-1 ml-3">
                                        <button v-if="canStartTask(task.status)"
                                                @click="updateTaskStatus(task, 'in_progress')"
                                                class="px-2 py-1 text-xs font-medium text-white bg-blue-500 rounded hover:bg-blue-600 transition-colors">
                                            Start
                                        </button>
                                        <button v-if="canCompleteTask(task.status)"
                                                @click="updateTaskStatus(task, 'completed')"
                                                class="px-2 py-1 text-xs font-medium text-white bg-green-500 rounded hover:bg-green-600 transition-colors">
                                            Done
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>


                <!-- Calendar and Attendance Grid -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Calendar Card -->
                    <div class="bg-white p-6 rounded-xl shadow-sm border border-slate-200" :class="canViewAttendanceStats ? 'lg:col-span-2' : 'lg:col-span-3'">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-bold text-slate-900">My Calendar</h3>
                            <div class="flex items-center space-x-1 bg-slate-100 p-1 rounded-lg">
                                <button @click="changeCalendarView('dayGridMonth')" :class="[currentCalendarView === 'dayGridMonth' ? 'bg-white text-blue-600 shadow-sm' : 'text-slate-600 hover:text-slate-900']" class="px-3 py-1 text-sm font-medium rounded-md transition-all">Month</button>
                                <button @click="changeCalendarView('dayGridWeek')" :class="[currentCalendarView === 'dayGridWeek' ? 'bg-white text-blue-600 shadow-sm' : 'text-slate-600 hover:text-slate-900']" class="px-3 py-1 text-sm font-medium rounded-md transition-all">Week</button>
                                <button @click="changeCalendarView('dayGridDay')" :class="[currentCalendarView === 'dayGridDay' ? 'bg-white text-blue-600 shadow-sm' : 'text-slate-600 hover:text-slate-900']" class="px-3 py-1 text-sm font-medium rounded-md transition-all">Day</button>
                            </div>
                        </div>
                        <FullCalendar :options="calendarOptions" ref="calendar" />
                    </div>

                    <!-- Attendance Stats Card -->
                    <div v-if="canViewAttendanceStats" class="bg-white p-6 rounded-xl shadow-sm border border-slate-200">
                        <h3 class="text-lg font-bold text-slate-900 mb-4">Team Attendance</h3>
                        <div class="relative h-48 mb-4">
                            <Doughnut :data="chartData" :options="chartOptions" />
                            <div class="absolute inset-0 flex flex-col items-center justify-center pointer-events-none"><span class="text-4xl font-bold text-slate-900">{{ attendance.total }}</span></div>
                        </div>
                        <div class="flex items-center justify-center space-x-6 text-sm mb-6">
                            <div class="flex items-center"><span class="w-3 h-3 rounded-full bg-blue-500 mr-2"></span>Present</div>
                            <div class="flex items-center"><span class="w-3 h-3 rounded-full bg-slate-800 mr-2"></span>Absent</div>
                        </div>
                        <div class="space-y-4 border-t border-slate-100 pt-4">
                             <h4 class="font-semibold text-slate-800">Absent Today</h4>
                             <div v-if="attendance.absent_list.length > 0" class="space-y-3">
                                <div v-for="absentee in attendance.absent_list" :key="absentee.id" class="flex items-center justify-between">
                                    <div class="flex items-center space-x-3">
                                        <img class="h-9 w-9 rounded-full" :src="absentee.avatar_url || `https://ui-avatars.com/api/?name=${absentee.name.replace(' ', '+')}&background=random`" :alt="absentee.name" />
                                        <div><p class="text-sm font-medium text-slate-800">{{ absentee.name }}</p><p class="text-xs text-slate-500">{{ absentee.designation }}</p></div>
                                    </div>
                                    <span class="text-xs font-medium text-red-600 bg-red-100 px-2 py-1 rounded-full">Fullday</span>
                                </div>
                             </div>
                             <div v-else class="text-center text-sm text-slate-500 py-4">Everyone is present today! 🎉</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
<style>
/* Global styles for FullCalendar - needed for basic rendering */
.fc .fc-daygrid-day-number {
  color: #1e293b !important;
  font-weight: 600 !important;
  padding: 0.25rem !important;
  user-select: none;
}
.fc-theme-standard .fc-scrollgrid {
  border-color: #e2e8f0 !important;
}
.fc .fc-event-main {
    white-space: normal !important; /* Allow event text to wrap */
}
</style>