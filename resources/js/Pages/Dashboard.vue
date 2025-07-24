<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Modal from '@/Components/Modal.vue';
import InputError from '@/Components/InputError.vue';
import { Head, Link, useForm, router, usePage } from '@inertiajs/vue3'; // Import usePage
import { ref, computed, onMounted, onUnmounted, watch } from 'vue';
import { formatDistanceToNowStrict } from 'date-fns';

import FullCalendar from '@fullcalendar/vue3';
import dayGridPlugin from '@fullcalendar/daygrid';
import interactionPlugin from '@fullcalendar/interaction';
import { Doughnut } from 'vue-chartjs';
import { Chart as ChartJS, ArcElement, Tooltip, Legend } from 'chart.js';

ChartJS.register(ArcElement, Tooltip, Legend);

const props = defineProps({
    user: { type: Object, required: true },
    attendance: { type: Object, required: true },
    calendarEvents: { type: Array, default: () => [] },
    greeting: { type: Object, required: true }
});

// --- NEW LOGIC FOR ROLE-BASED VISIBILITY ---
const page = usePage();

const canViewAttendanceStats = computed(() => {
    const authUser = page.props.auth.user;
    // Safety check: ensure user and permissions array exist
    if (!authUser || !Array.isArray(authUser.permissions)) {
        return false;
    }
    // Check if the user has the required permission
    return authUser.permissions.includes('manage employees');
});


// --- All other script logic remains the same ---
const companyExperience = computed(() => {
    if (!props.user.hire_date) return 'N/A';
    return formatDistanceToNowStrict(new Date(props.user.hire_date));
});

const isNoteModalVisible = ref(false);
const modalMode = ref('create');
const editingNoteId = ref(null);
const noteForm = useForm({ note: '', date: '' });
const isHiringAlertVisible = ref(true);
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
                <!-- Hiring Alert Banner
                <div v-if="isHiringAlertVisible" class="bg-violet-100 border border-violet-200 text-violet-800 px-4 py-3 rounded-xl relative flex items-center justify-between">
                    <div class="flex items-center"><span class="mr-3"><svg class="w-6 h-6 text-violet-600" fill="currentColor" viewBox="0 0 20 20"><path d="M10 2a6 6 0 00-6 6v3.586l-1.707 1.707A1 1 0 003 15v1a1 1 0 001 1h12a1 1 0 001-1v-1a1 1 0 00-.293-.707L16 11.586V8a6 6 0 00-6-6zM10 18a3 3 0 01-3-3h6a3 3 0 01-3 3z"></path></svg></span><span><span class="font-semibold">Alert!</span> We're hiring UI/UX Designers. 3 positions available (Required 2.5 years of relevant experience)<a href="#" class="font-bold underline ml-2 hover:text-violet-900">Refer Now</a></span></div>
                    <button @click="isHiringAlertVisible = false" class="p-1 rounded-full hover:bg-violet-200"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg></button>
                </div> -->

                <!-- Dashboard Header -->
                <div class="flex items-center justify-between">
                    <h1 class="text-3xl font-bold text-slate-900">Dashboard</h1>
                    <div class="flex items-center space-x-3">
                        <!-- <button class="px-4 py-2 text-sm font-semibold bg-white border border-slate-300 text-slate-700 rounded-lg hover:bg-slate-50 transition-colors shadow-sm">Request Permission</button> -->
                        <Link :href="route('leave.index')" class="px-4 py-2 text-sm font-semibold bg-slate-900 text-white rounded-lg hover:bg-slate-700 transition-colors shadow-sm">Create Leave Request</Link>
                    </div>
                </div>

                <!-- Grid for Top Row Cards -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
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

                    <div class="bg-white p-6 rounded-xl shadow-sm border border-slate-200 flex flex-col justify-between">
                        <h3 class="font-semibold text-slate-800">Good Morning</h3>
                        <div class="flex items-center my-auto"><span class="text-4xl mr-4">🌤️</span><span class="text-3xl font-bold text-slate-900">{{ liveTime }}</span></div>
                        <div class="text-sm text-slate-500 text-right border-t pt-2 border-slate-100">Today, {{ greeting.date }}</div>
                    </div>
                </div>

                <!-- Grid for Bottom Row Cards -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <div class="bg-white p-6 rounded-xl shadow-sm border border-slate-200" :class="canViewAttendanceStats ? 'lg:col-span-2' : 'lg:col-span-3'">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-bold text-slate-900">March 2025</h3>
                            <div class="flex items-center space-x-1 bg-slate-100 p-1 rounded-lg">
                                <button @click="changeCalendarView('dayGridMonth')" :class="[currentCalendarView === 'dayGridMonth' ? 'bg-white text-blue-600 shadow-sm' : 'text-slate-600 hover:text-slate-900']" class="px-3 py-1 text-sm font-medium rounded-md transition-all">Month</button>
                                <button @click="changeCalendarView('dayGridWeek')" :class="[currentCalendarView === 'dayGridWeek' ? 'bg-white text-blue-600 shadow-sm' : 'text-slate-600 hover:text-slate-900']" class="px-3 py-1 text-sm font-medium rounded-md transition-all">Week</button>
                                <button @click="changeCalendarView('dayGridDay')" :class="[currentCalendarView === 'dayGridDay' ? 'bg-white text-blue-600 shadow-sm' : 'text-slate-600 hover:text-slate-900']" class="px-3 py-1 text-sm font-medium rounded-md transition-all">Day</button>
                            </div>
                        </div>
                        <FullCalendar :options="calendarOptions" ref="calendar" />
                    </div>

                    <div v-if="canViewAttendanceStats" class="bg-white p-6 rounded-xl shadow-sm border border-slate-200">
                        <h3 class="text-lg font-bold text-slate-900 mb-4">Number of Total Employees</h3>
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
/* Global styles for FullCalendar */
.fc .fc-toolbar.fc-header-toolbar { margin-bottom: 1rem; }
.fc .fc-daygrid-day-number { padding: 0.5rem; font-size: 0.875rem; font-weight: 500; }
.fc .fc-day-today { background-color: #f1f5f9 !important; }
.fc-theme-standard .fc-scrollgrid, .fc-theme-standard th { border: none; }
.fc-event { white-space: normal !important; }
</style>