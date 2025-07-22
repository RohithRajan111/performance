<!-- resources/js/Pages/Performance/Show.vue -->

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    employee: Object,
    taskStats: Object,
    timeStats: Object,
    weeklyHours: Array,
    projectHours: Array,
    leaveStats: Object,
    recentTimeLogs: Array,
    recentTasks: Array,
});

// Computed properties
const performanceScore = computed(() => {
    const taskScore = props.taskStats.completion_rate;
    const timeScore = Math.min(100, (props.timeStats.current_month / 160) * 100);
    const leaveScore = Math.max(0, 100 - (props.leaveStats.current_year / 12) * 100);
    return Math.round((taskScore + timeScore + leaveScore) / 3);
});

const getStatusColor = (status) => {
    switch (status?.toLowerCase()) {
        case 'completed': return 'text-green-600 bg-green-100';
        case 'in_progress': return 'text-yellow-600 bg-yellow-100';
        case 'pending': return 'text-blue-600 bg-blue-100';
        default: return 'text-gray-600 bg-gray-100';
    }
};

const getScoreColor = (score) => {
    if (score >= 80) return 'text-green-600';
    if (score >= 60) return 'text-yellow-600';
    return 'text-red-600';
};
</script>

<template>
    <Head :title="'Performance: ' + employee.name" />
    
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="font-semibold text-xl text-gray-800">Performance Report</h2>
                    <p class="text-sm text-gray-600">{{ employee.name }} • {{ employee.email }}</p>
                </div>
                <div class="text-right">
                    <div class="text-sm text-gray-500">Overall Score</div>
                    <div class="text-2xl font-bold" :class="getScoreColor(performanceScore)">
                        {{ performanceScore }}%
                    </div>
                </div>
            </div>
        </template>

        <div class="py-6">
            <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-6">
                
                <!-- Key Metrics -->
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div class="bg-white p-4 rounded-lg shadow-sm border">
                        <div class="text-sm text-gray-500">Tasks</div>
                        <div class="text-xl font-semibold">{{ taskStats.completed }}/{{ taskStats.total }}</div>
                        <div class="text-sm" :class="getScoreColor(taskStats.completion_rate)">
                            {{ taskStats.completion_rate }}% complete
                        </div>
                    </div>
                    
                    <div class="bg-white p-4 rounded-lg shadow-sm border">
                        <div class="text-sm text-gray-500">Hours (Month)</div>
                        <div class="text-xl font-semibold">{{ timeStats.current_month }}h</div>
                        <div class="text-sm text-gray-600">{{ timeStats.total_hours }}h total</div>
                    </div>
                    
                    <div class="bg-white p-4 rounded-lg shadow-sm border">
                        <div class="text-sm text-gray-500">Leave Days</div>
                        <div class="text-xl font-semibold">{{ leaveStats.current_year }}/12</div>
                        <div class="text-sm text-gray-600">{{ leaveStats.remaining }} remaining</div>
                    </div>
                    
                    <div class="bg-white p-4 rounded-lg shadow-sm border">
                        <div class="text-sm text-gray-500">Projects</div>
                        <div class="text-xl font-semibold">{{ projectHours.length }}</div>
                        <div class="text-sm text-gray-600">Active projects</div>
                    </div>
                </div>

                <!-- Charts Row -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Task Breakdown -->
                    <div class="bg-white p-4 rounded-lg shadow-sm border">
                        <h3 class="font-medium text-gray-900 mb-3">Task Breakdown</h3>
                        <div class="space-y-2">
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-gray-600">Completed</span>
                                <div class="flex items-center space-x-2">
                                    <div class="w-16 bg-gray-200 rounded-full h-2">
                                        <div class="bg-green-500 h-2 rounded-full" 
                                             :style="{ width: taskStats.total > 0 ? (taskStats.completed / taskStats.total * 100) + '%' : '0%' }"></div>
                                    </div>
                                    <span class="text-sm font-medium w-6">{{ taskStats.completed }}</span>
                                </div>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-gray-600">In Progress</span>
                                <div class="flex items-center space-x-2">
                                    <div class="w-16 bg-gray-200 rounded-full h-2">
                                        <div class="bg-yellow-500 h-2 rounded-full" 
                                             :style="{ width: taskStats.total > 0 ? (taskStats.in_progress / taskStats.total * 100) + '%' : '0%' }"></div>
                                    </div>
                                    <span class="text-sm font-medium w-6">{{ taskStats.in_progress }}</span>
                                </div>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-gray-600">Pending</span>
                                <div class="flex items-center space-x-2">
                                    <div class="w-16 bg-gray-200 rounded-full h-2">
                                        <div class="bg-blue-500 h-2 rounded-full" 
                                             :style="{ width: taskStats.total > 0 ? (taskStats.pending / taskStats.total * 100) + '%' : '0%' }"></div>
                                    </div>
                                    <span class="text-sm font-medium w-6">{{ taskStats.pending }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Weekly Hours -->
                    <div class="bg-white p-4 rounded-lg shadow-sm border">
                        <h3 class="font-medium text-gray-900 mb-3">Weekly Hours</h3>
                        <div class="space-y-2">
                            <div v-for="week in weeklyHours" :key="week.week" class="flex items-center">
                                <span class="text-sm text-gray-600 w-12">{{ week.week }}</span>
                                <div class="flex-1 mx-2 bg-gray-200 rounded-full h-2">
                                    <div class="bg-blue-500 h-2 rounded-full" 
                                         :style="{ width: Math.min(100, (week.hours / 40 * 100)) + '%' }"></div>
                                </div>
                                <span class="text-sm font-medium w-8">{{ week.hours }}h</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Data Tables -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Project Hours -->
                    <div class="bg-white p-4 rounded-lg shadow-sm border" v-if="projectHours.length > 0">
                        <h3 class="font-medium text-gray-900 mb-3">Project Hours</h3>
                        <div class="space-y-2">
                            <div v-for="project in projectHours" :key="project.project" 
                                 class="flex items-center justify-between py-1">
                                <span class="text-sm text-gray-700 truncate">{{ project.project }}</span>
                                <span class="text-sm font-medium">{{ project.hours }}h</span>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Activity -->
                    <div class="bg-white p-4 rounded-lg shadow-sm border">
                        <h3 class="font-medium text-gray-900 mb-3">Recent Activity</h3>
                        <div class="space-y-2">
                            <!-- Recent Time Logs -->
                            <div v-if="recentTimeLogs.length > 0">
                                <h4 class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Time Logs</h4>
                                <div v-for="log in recentTimeLogs.slice(0, 3)" :key="log.date + log.project" 
                                     class="flex items-center justify-between py-1">
                                    <div class="flex-1">
                                        <span class="text-sm text-gray-700">{{ log.project }}</span>
                                        <span class="text-xs text-gray-500 ml-2">{{ log.date }}</span>
                                    </div>
                                    <span class="text-sm font-medium">{{ log.hours }}h</span>
                                </div>
                            </div>

                            <!-- Recent Tasks -->
                            <div v-if="recentTasks.length > 0" class="mt-3">
                                <h4 class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Tasks</h4>
                                <div v-for="task in recentTasks.slice(0, 3)" :key="task.name" 
                                     class="flex items-center justify-between py-1">
                                    <div class="flex-1">
                                        <span class="text-sm text-gray-700 truncate">{{ task.name }}</span>
                                        <span class="text-xs text-gray-500 ml-2">{{ task.updated }}</span>
                                    </div>
                                    <span class="px-2 py-0.5 text-xs rounded-full" :class="getStatusColor(task.status)">
                                        {{ task.status }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Summary -->
                <div class="bg-gray-50 p-4 rounded-lg border">
                    <h3 class="font-medium text-gray-900 mb-3">Performance Summary</h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                        <div>
                            <strong>Task Performance:</strong>
                            <span :class="getScoreColor(taskStats.completion_rate)">
                                {{ taskStats.completion_rate >= 80 ? 'Excellent' : taskStats.completion_rate >= 60 ? 'Good' : 'Needs Improvement' }}
                            </span>
                        </div>
                        <div>
                            <strong>Time Management:</strong>
                            <span :class="getScoreColor(Math.min(100, (timeStats.current_month / 160) * 100))">
                                {{ timeStats.current_month >= 128 ? 'On Track' : 'Below Target' }}
                            </span>
                        </div>
                        <div>
                            <strong>Work-Life Balance:</strong>
                            <span class="text-gray-700">
                                {{ leaveStats.current_year <= 5 ? 'Take More Breaks' : leaveStats.current_year >= 15 ? 'Good Balance' : 'Healthy Usage' }}
                            </span>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.transition-all {
    transition: all 0.3s ease;
}
</style>
