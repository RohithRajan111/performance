<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, useForm, Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    timeLogs: Object, // Laravel pagination object
    canViewAll: Boolean,
    assignableProjects: Array,
});

const form = useForm({
    work_date: new Date().toISOString().split('T')[0], // Defaults to today
    hours_worked: '', // Default to empty for cleaner user input
    description: '',
    project_id: '',
});

const submitHours = () => {
    form.post(route('hours.store'), {
        preserveScroll: true, // Keep user at the same spot on the page
        onSuccess: () => {
            // Reset fields but keep the selected date and project for quick multi-logging
            form.reset('hours_worked', 'description');
        },
    });
};

// Computed property to format the pagination data for easier looping
const logs = computed(() => props.timeLogs.data);
</script>

<template>
    <Head title="Working Hours" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Working Hours</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

                <!-- Employee Form to Add Hours -->
                <div v-if="!canViewAll" class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                    <section>
                        <header>
                            <h2 class="text-lg font-medium text-gray-900">Log Your Working Hours</h2>
                        </header>
                        <form @submit.prevent="submitHours" class="mt-6 space-y-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <InputLabel for="project_id" value="Project" />
                                    <select id="project_id" v-model="form.project_id" required class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                                        <option value="" disabled>-- Select a project --</option>
                                        <option v-for="project in assignableProjects" :key="project.id" :value="project.id">
                                            {{ project.name }}
                                        </option>
                                    </select>
                                    <InputError class="mt-2" :message="form.errors.project_id" />
                                </div>
                                <div>
                                    <InputLabel for="work_date" value="Work Date" />
                                    <TextInput id="work_date" type="date" class="mt-1 block w-full" v-model="form.work_date" required />
                                    <InputError class="mt-2" :message="form.errors.work_date" />
                                </div>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <InputLabel for="hours_worked" value="Hours Worked (e.g., 4.5)" />
                                    <TextInput id="hours_worked" type="number" step="0.25" min="0.25" max="24" class="mt-1 block w-full" v-model="form.hours_worked" required placeholder="e.g., 8 or 4.5" />
                                    <InputError class="mt-2" :message="form.errors.hours_worked" />
                                </div>
                                 <div class="md:pt-7">
                                    <PrimaryButton :disabled="form.processing">Log Hours</PrimaryButton>
                                </div>
                            </div>
                             <div>
                                <InputLabel for="description" value="Description / Notes (Optional)" />
                                <textarea id="description" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" v-model="form.description" rows="3"></textarea>
                                <InputError class="mt-2" :message="form.errors.description" />
                            </div>
                        </form>
                    </section>
                </div>

                <!-- List of Time Logs -->
                <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                    <section>
                         <header>
                            <h2 class="text-lg font-medium text-gray-900">{{ canViewAll ? 'All Employee Hours' : 'My Logged Hours' }}</h2>
                        </header>
                        <div class="mt-6 overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th v-if="canViewAll" scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Employee</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Project</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Hours</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <tr v-if="!logs.length"><td :colspan="canViewAll ? 5 : 4" class="px-6 py-4 text-center text-gray-500">No hours have been logged.</td></tr>
                                    <tr v-for="log in logs" :key="log.id">
                                        <td v-if="canViewAll" class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ log.user.name }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ log.project ? log.project.name : 'N/A' }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ log.work_date }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 font-semibold">{{ log.hours_worked }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ log.description }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <!-- PAGINATION to be added later if needed -->
                    </section>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>