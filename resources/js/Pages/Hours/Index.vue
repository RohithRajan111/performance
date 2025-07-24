<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import { Head, useForm, Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    timeLogs: Object, // Laravel pagination object
    canViewAll: Boolean,
    assignableProjects: Array,
});

// Your form logic is great, no changes needed here.
const form = useForm({
    work_date: new Date().toISOString().split('T')[0], // Defaults to today
    hours_worked: '',
    description: '',
    project_id: '',
});

const submitHours = () => {
    form.post(route('hours.store'), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset('hours_worked', 'description');
        },
    });
};

// Computed properties to simplify template logic
const logs = computed(() => props.timeLogs.data);
const paginationLinks = computed(() => props.timeLogs.links);
</script>

<template>
    <Head title="Working Hours" />

    <AuthenticatedLayout>
        <div class="p-4 sm:p-6 lg:p-8 font-sans">
            <div class="max-w-7xl mx-auto space-y-6">

                <!-- Page Header -->
                <div class="flex items-center justify-between">
                    <h1 class="text-3xl font-bold text-slate-900">Working Hours</h1>
                </div>

                <!-- Card 1: Form to Add Hours (Only for standard employees) -->
                <div v-if="!canViewAll" class="bg-white p-6 rounded-xl shadow-sm border border-slate-200">
                    <header>
                        <h2 class="text-lg font-semibold text-slate-900">Log Your Working Hours</h2>
                    </header>
                    <form @submit.prevent="submitHours" class="mt-6 space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Project Select -->
                            <div>
                                <label for="project_id" class="block text-sm font-medium text-slate-700">Project</label>
                                <select v-model="form.project_id" id="project_id" required
                                        class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                    <option value="" disabled>-- Select a project --</option>
                                    <option v-for="project in assignableProjects" :key="project.id" :value="project.id">
                                        {{ project.name }}
                                    </option>
                                </select>
                                <InputError class="mt-2" :message="form.errors.project_id" />
                            </div>
                            <!-- Work Date Input -->
                            <div>
                                <label for="work_date" class="block text-sm font-medium text-slate-700">Work Date</label>
                                <input v-model="form.work_date" id="work_date" type="date" required
                                       class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" />
                                <InputError class="mt-2" :message="form.errors.work_date" />
                            </div>
                            <!-- Hours Worked Input -->
                             <div>
                                <label for="hours_worked" class="block text-sm font-medium text-slate-700">Hours Worked</label>
                                <input v-model="form.hours_worked" id="hours_worked" type="number" step="0.25" min="0.25" max="24" required placeholder="e.g., 8 or 4.5"
                                       class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" />
                                <InputError class="mt-2" :message="form.errors.hours_worked" />
                            </div>
                        </div>

                        <!-- Description Textarea -->
                        <div>
                            <label for="description" class="block text-sm font-medium text-slate-700">Description / Notes (Optional)</label>
                            <textarea v-model="form.description" id="description" rows="3"
                                      class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"></textarea>
                            <InputError class="mt-2" :message="form.errors.description" />
                        </div>
                        
                        <!-- Submit Button -->
                        <div class="flex items-center justify-end">
                             <button type="submit" :disabled="form.processing"
                                     class="inline-flex items-center justify-center rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-slate-700 disabled:opacity-50">
                                Log Hours
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Card 2: List of Time Logs -->
                <div class="bg-white p-6 rounded-xl shadow-sm border border-slate-200">
                    <header>
                        <h2 class="text-lg font-semibold text-slate-900">{{ canViewAll ? 'All Employee Hours' : 'My Logged Hours' }}</h2>
                    </header>
                    <div class="mt-4 flow-root">
                        <div class="-mx-6 -my-2 overflow-x-auto">
                            <div class="inline-block min-w-full py-2 align-middle">
                                <table class="min-w-full divide-y divide-slate-200">
                                    <thead>
                                        <tr>
                                            <th v-if="canViewAll" scope="col" class="py-3.5 px-6 text-left text-xs font-semibold text-slate-500 uppercase">Employee</th>
                                            <th scope="col" class="py-3.5 px-6 text-left text-xs font-semibold text-slate-500 uppercase">Project</th>
                                            <th scope="col" class="py-3.5 px-6 text-left text-xs font-semibold text-slate-500 uppercase">Date</th>
                                            <th scope="col" class="py-3.5 px-6 text-left text-xs font-semibold text-slate-500 uppercase">Hours</th>
                                            <th scope="col" class="py-3.5 px-6 text-left text-xs font-semibold text-slate-500 uppercase">Description</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100 bg-white">
                                        <tr v-if="!logs.length">
                                            <td :colspan="canViewAll ? 5 : 4" class="px-6 py-8 text-center text-slate-500">No hours have been logged.</td>
                                        </tr>
                                        <tr v-for="log in logs" :key="log.id" class="hover:bg-slate-50">
                                            <td v-if="canViewAll" class="whitespace-nowrap py-4 px-6 text-sm font-medium text-slate-900">{{ log.user.name }}</td>
                                            <td class="whitespace-nowrap py-4 px-6 text-sm text-slate-600">{{ log.project ? log.project.name : 'N/A' }}</td>
                                            <td class="whitespace-nowrap py-4 px-6 text-sm text-slate-600">{{ new Date(log.work_date).toLocaleDateString() }}</td>
                                            <td class="whitespace-nowrap py-4 px-6 text-sm text-slate-900 font-semibold">{{ log.hours_worked }}</td>
                                            <td class="whitespace-nowrap py-4 px-6 text-sm text-slate-600 max-w-xs truncate">{{ log.description }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Pagination Links -->
                    <div v-if="paginationLinks.length > 3" class="mt-6 flex items-center justify-between">
                        <div class="text-sm text-slate-600">
                            Showing {{ timeLogs.from }} to {{ timeLogs.to }} of {{ timeLogs.total }} results
                        </div>
                        <nav class="isolate inline-flex -space-x-px rounded-md shadow-sm" aria-label="Pagination">
                             <Link v-for="(link, index) in paginationLinks" :key="index"
                                  :href="link.url"
                                  v-html="link.label"
                                  :class="{
                                      'bg-slate-900 text-white': link.active,
                                      'text-slate-900 ring-1 ring-inset ring-slate-300 hover:bg-slate-50': !link.active,
                                      'rounded-l-md': index === 0,
                                      'rounded-r-md': index === paginationLinks.length - 1
                                  }"
                                  class="relative inline-flex items-center px-4 py-2 text-sm font-semibold focus:z-20"
                                  :disabled="!link.url"
                                  preserve-scroll
                            />
                        </nav>
                    </div>

                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>