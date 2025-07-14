<!-- Basic structure for Performance/Show.vue -->
<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
defineProps({
    employee: Object,
    taskStats: Object,
    totalHours: Number,
    leaveDays: Number,
});
</script>
<template>
    <Head :title="'Performance: ' + employee.name" />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Performance Report for: {{ employee.name }}</h2>
        </template>
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Technical Performance Card -->
                <div class="bg-white p-6 shadow-sm sm:rounded-lg">
                    <h3 class="text-lg font-medium">Technical Performance</h3>
                    <p class="mt-2">Tasks Completed: {{ taskStats.completed }} / {{ taskStats.total }}</p>
                </div>
                <!-- HR Performance Card -->
                <div class="bg-white p-6 shadow-sm sm:rounded-lg">
                    <h3 class="text-lg font-medium">HR Metrics</h3>
                    <p class="mt-2">Total Hours Logged: {{ totalHours }}</p>
                    <p class="mt-1">Total Leave Days Taken: {{ leaveDays }}</p>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>