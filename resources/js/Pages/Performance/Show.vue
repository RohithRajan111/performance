<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { ref, computed, onMounted } from 'vue';

// Import Chart.js components
import { Bar, Doughnut } from 'vue-chartjs';
import { Chart as ChartJS, Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale, ArcElement } from 'chart.js';

ChartJS.register(Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale, ArcElement);

const props = defineProps({
    employee: Object,
    taskStats: Object,
    timeStats: Object,
    weeklyHours: Array,
    projectHours: Array,
    leaveStats: Object,
});

// Corrected performanceScore computed property
const performanceScore = computed(() => {
    const taskScore = props.taskStats.completion_rate;
    const timeScore = Math.min(100, (props.timeStats.current_month / 160) * 100); // Assuming 160 hours is a standard full-time month
    
    // --- THE FIX IS HERE ---
    // Use the dynamic leave balance from the database props.
    // Default to 1 if balance is 0 to avoid division by zero errors.
    const totalLeaveAllowance = props.leaveStats.balance > 0 ? props.leaveStats.balance : 1;
    const leaveScore = Math.max(0, 100 - (props.leaveStats.current_year / totalLeaveAllowance) * 100);
    
    return Math.round((taskScore + timeScore + leaveScore) / 3);
});

const getPerformanceGrade = (score) => {
    if (score >= 90) return { grade: 'A+', color: 'text-green-600', bgColor: 'bg-green-100' };
    if (score >= 80) return { grade: 'A', color: 'text-green-600', bgColor: 'bg-green-100' };
    if (score >= 70) return { grade: 'B+', color: 'text-blue-600', bgColor: 'bg-blue-100' };
    if (score >= 60) return { grade: 'B', color: 'text-blue-600', bgColor: 'bg-blue-100' };
    if (score >= 50) return { grade: 'C', color: 'text-yellow-600', bgColor: 'bg-yellow-100' };
    return { grade: 'D', color: 'text-red-600', bgColor: 'bg-red-100' };
};

// --- Chart.js Data Configurations ---
const taskChartData = computed(() => ({
    labels: ['Completed', 'In Progress', 'Pending'],
    datasets: [{
        backgroundColor: ['#10b981', '#f59e0b', '#3b82f6'],
        data: [props.taskStats.completed, props.taskStats.in_progress, props.taskStats.pending],
        borderWidth: 0,
    }]
}));

const weeklyHoursChartData = computed(() => ({
    labels: props.weeklyHours.map(w => w.week),
    datasets: [{
        label: 'Hours Worked',
        backgroundColor: '#3b82f6',
        borderRadius: 4,
        data: props.weeklyHours.map(w => w.hours)
    }]
}));

const chartOptions = {
    responsive: true,
    maintainAspectRatio: false,
};

const barChartOptions = {
    ...chartOptions,
    plugins: { legend: { display: false } },
    scales: {
        y: { beginAtZero: true, grid: { drawBorder: false } },
        x: { grid: { display: false } }
    }
};

const doughnutChartOptions = {
    ...chartOptions,
    cutout: '75%',
    plugins: {
        legend: {
            position: 'right',
            labels: { boxWidth: 12, padding: 15 }
        }
    }
};
</script>

<template>
    <Head :title="'Performance: ' + employee.name" />
    
    <AuthenticatedLayout>
        <div class="p-4 sm:p-6 lg:p-8 font-sans">
            <div class="max-w-7xl mx-auto space-y-6">

                <!-- Page Header -->
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <div>
                        <h1 class="text-3xl font-bold text-slate-900">Performance Review</h1>
                        <p class="mt-1 text-sm text-slate-500">{{ employee.name }} • {{ employee.email }}</p>
                    </div>
                    <div class="flex items-center space-x-2 text-right">
                        <div>
                            <div class="text-xs text-slate-500">Overall Score</div>
                            <div class="text-3xl font-bold" :class="getPerformanceGrade(performanceScore).color">
                                {{ performanceScore }}%
                            </div>
                        </div>
                        <div :class="getPerformanceGrade(performanceScore).bgColor" class="px-3 py-1 rounded-full text-sm font-semibold" >
                           <span :class="getPerformanceGrade(performanceScore).color">Grade {{ getPerformanceGrade(performanceScore).grade }}</span>
                        </div>
                    </div>
                </div>

                <!-- Key Metrics Cards -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                    <!-- Task Completion -->
                    <div class="bg-white p-5 rounded-xl shadow-sm border border-slate-200">
                        <p class="text-sm font-medium text-slate-500">Task Completion</p>
                        <p class="mt-2 text-3xl font-bold text-slate-900">{{ taskStats.completion_rate }}%</p>
                        <p class="text-xs text-slate-400 mt-1">{{ taskStats.completed }} of {{ taskStats.total }} tasks completed</p>
                    </div>
                    <!-- Total Hours Logged -->
                    <div class="bg-white p-5 rounded-xl shadow-sm border border-slate-200">
                        <p class="text-sm font-medium text-slate-500">Total Hours Logged</p>
                        <p class="mt-2 text-3xl font-bold text-slate-900">{{ timeStats.total_hours }}h</p>
                        <p class="text-xs text-slate-400 mt-1">{{ timeStats.current_month }}h this month</p>
                    </div>
                    <!-- Leave Days Used -->
                    <div class="bg-white p-5 rounded-xl shadow-sm border border-slate-200">
                        <p class="text-sm font-medium text-slate-500">Leave Days (This Year)</p>
                        <!-- Corrected to use dynamic props -->
                        <p class="mt-2 text-3xl font-bold text-slate-900">{{ leaveStats.current_year }} <span class="text-lg font-medium text-slate-400">/ {{ leaveStats.balance }}</span></p>
                        <p class="text-xs text-slate-400 mt-1">{{ leaveStats.remaining }} days remaining</p>
                    </div>
                    <!-- Daily Average -->
                    <div class="bg-white p-5 rounded-xl shadow-sm border border-slate-200">
                        <p class="text-sm font-medium text-slate-500">Avg. Daily Hours</p>
                        <p class="mt-2 text-3xl font-bold text-slate-900">{{ timeStats.daily_average }}h</p>
                        <p class="text-xs text-slate-400 mt-1">Average over all logged days</p>
                    </div>
                </div>

                <!-- Chart Cards -->
                <div class="grid grid-cols-1 lg:grid-cols-5 gap-6">
                    <!-- Task Distribution Chart -->
                    <div class="lg:col-span-2 bg-white p-6 rounded-xl shadow-sm border border-slate-200">
                        <h3 class="text-lg font-semibold text-slate-900 mb-4">Task Distribution</h3>
                        <div class="h-64">
                             <Doughnut :data="taskChartData" :options="doughnutChartOptions" />
                        </div>
                    </div>
                    <!-- Hours Tracking Chart -->
                    <div class="lg:col-span-3 bg-white p-6 rounded-xl shadow-sm border border-slate-200">
                        <h3 class="text-lg font-semibold text-slate-900 mb-4">Weekly Hours (Last 8 Weeks)</h3>
                        <div class="h-64">
                             <Bar :data="weeklyHoursChartData" :options="barChartOptions" />
                        </div>
                    </div>
                </div>

                <!-- Hours by Project Table -->
                <div class="bg-white rounded-xl shadow-sm border border-slate-200">
                    <header class="p-4 sm:px-6 border-b border-slate-200">
                        <h3 class="text-lg font-semibold text-slate-900">Hours by Project</h3>
                    </header>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th scope="col" class="py-3.5 px-6 text-left text-xs font-semibold text-slate-500 uppercase">Project Name</th>
                                    <th scope="col" class="py-3.5 px-6 text-left text-xs font-semibold text-slate-500 uppercase">Total Hours</th>
                                    <th scope="col" class="py-3.5 px-6 text-left text-xs font-semibold text-slate-500 uppercase">Percentage of Time</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 bg-white">
                                <tr v-if="!projectHours.length">
                                    <td colspan="3" class="px-6 py-8 text-center text-slate-500">No time logged against specific projects.</td>
                                </tr>
                                <tr v-for="project in projectHours" :key="project.project" class="hover:bg-slate-50">
                                    <td class="whitespace-nowrap py-4 px-6 text-sm font-medium text-slate-900">{{ project.project }}</td>
                                    <td class="whitespace-nowrap py-4 px-6 text-sm text-slate-600 font-semibold">{{ project.hours }}h</td>
                                    <td class="whitespace-nowrap py-4 px-6 text-sm text-slate-600">
                                        <div class="flex items-center">
                                            <div class="w-full bg-slate-200 rounded-full h-2 mr-4">
                                                <div class="bg-blue-500 h-2 rounded-full" :style="{ width: (project.hours / timeStats.total_hours * 100) + '%' }"></div>
                                            </div>
                                            <span>{{ Math.round(project.hours / timeStats.total_hours * 100) }}%</span>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>