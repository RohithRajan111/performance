<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Modal from '@/Components/Modal.vue';
import InputError from '@/Components/InputError.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    projects: Array,
    teams: Array,
});

// A ref to control the modal's visibility
const isCreateModalVisible = ref(false);

const openCreateModal = () => {
    isCreateModalVisible.value = true;
};

const closeModal = () => {
    isCreateModalVisible.value = false;
    form.reset();
};

const form = useForm({
    name: '',
    description: '',
    team_id: '',
    end_date: '',
    total_hours_required: 100,
});

const submit = () => {
    form.post(route('projects.store'), {
        onSuccess: () => closeModal(),
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Projects" />

    <AuthenticatedLayout>
        <div class="p-4 sm:p-6 lg:p-8 font-sans">
            <div class="max-w-7xl mx-auto space-y-6">

                <!-- Page Header with "Create Project" Button -->
                <div class="flex items-center justify-between">
                    <h1 class="text-3xl font-bold text-slate-900">Projects</h1>
                    <button @click="openCreateModal"
                            class="inline-flex items-center justify-center rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-slate-700">
                        + Create Project
                    </button>
                </div>

                <!-- Projects List Card -->
                <div class="bg-white rounded-xl shadow-sm border border-slate-200">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th scope="col" class="py-3.5 px-6 text-left text-xs font-semibold text-slate-500 uppercase">Project Name</th>
                                    <th scope="col" class="py-3.5 px-6 text-left text-xs font-semibold text-slate-500 uppercase">Team</th>
                                    <th scope="col" class="py-3.5 px-6 text-left text-xs font-semibold text-slate-500 uppercase">Manager</th>
                                    <th scope="col" class="py-3.5 px-6 text-left text-xs font-semibold text-slate-500 uppercase">Tasks</th>
                                    <th scope="col" class="py-3.5 px-6 text-left text-xs font-semibold text-slate-500 uppercase">Due Date</th>
                                    <th scope="col" class="relative py-3.5 px-6"><span class="sr-only">View</span></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 bg-white">
                                <tr v-if="!projects.length">
                                    <td colspan="6" class="px-6 py-8 text-center text-slate-500">No projects have been created yet.</td>
                                </tr>
                                <tr v-for="project in projects" :key="project.id" class="hover:bg-slate-50">
                                    <td class="whitespace-nowrap py-4 px-6 text-sm font-medium text-slate-900">{{ project.name }}</td>
                                    <td class="whitespace-nowrap py-4 px-6 text-sm text-slate-600">{{ project.team.name }}</td>
                                    <td class="whitespace-nowrap py-4 px-6 text-sm text-slate-600">{{ project.project_manager.name }}</td>
                                    <td class="whitespace-nowrap py-4 px-6 text-sm text-slate-600">{{ project.tasks_count }}</td>
                                    <td class="whitespace-nowrap py-4 px-6 text-sm text-slate-600">{{ project.end_date }}</td>
                                    <td class="whitespace-nowrap py-4 px-6 text-right text-sm font-medium">
                                        <Link :href="route('projects.show', project.id)" class="text-indigo-600 hover:text-indigo-900">View</Link>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Create Project Modal -->
        <Modal :show="isCreateModalVisible" @close="closeModal">
            <div class="p-6">
                <h2 class="text-lg font-bold text-slate-900">Create New Project</h2>
                <form @submit.prevent="submit" class="mt-6 space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="name" class="block text-sm font-medium text-slate-700">Project Name</label>
                            <input v-model="form.name" id="name" type="text" required class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" />
                            <InputError class="mt-2" :message="form.errors.name" />
                        </div>
                        <div>
                            <label for="team_id" class="block text-sm font-medium text-slate-700">Assign to Team</label>
                            <select v-model="form.team_id" id="team_id" required class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                <option value="" disabled>-- Select a team --</option>
                                <!-- The 'teams' prop is now available for the modal -->
                                <option v-for="team in teams" :key="team.id" :value="team.id">{{ team.name }}</option>
                            </select>
                            <InputError class="mt-2" :message="form.errors.team_id" />
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="end_date" class="block text-sm font-medium text-slate-700">Expected End Date</label>
                            <input v-model="form.end_date" id="end_date" type="date" required class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" />
                            <InputError class="mt-2" :message="form.errors.end_date" />
                        </div>
                        <div>
                            <label for="total_hours_required" class="block text-sm font-medium text-slate-700">Total Hours Required</label>
                            <input v-model="form.total_hours_required" id="total_hours_required" type="number" min="1" required class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" />
                            <InputError class="mt-2" :message="form.errors.total_hours_required" />
                        </div>
                    </div>
                    <div>
                        <label for="description" class="block text-sm font-medium text-slate-700">Description (Optional)</label>
                        <textarea v-model="form.description" id="description" rows="4" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"></textarea>
                        <InputError class="mt-2" :message="form.errors.description" />
                    </div>
                    
                    <div class="mt-6 flex justify-end gap-3">
                        <button type="button" @click="closeModal" class="inline-flex items-center justify-center rounded-lg bg-white px-4 py-2 text-sm font-semibold text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 hover:bg-slate-50">
                            Cancel
                        </button>
                        <button type="submit" :disabled="form.processing" class="inline-flex items-center justify-center rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-slate-700 disabled:opacity-50">
                            Create Project
                        </button>
                    </div>
                </form>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>