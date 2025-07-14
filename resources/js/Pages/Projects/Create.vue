<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps({
    teams: Array, // This is passed from ProjectController@create
});

const form = useForm({
    name: '',
    description: '',
    team_id: '',
    end_date: '', // New field
    total_hours_required: 100, // New field with a default
});

const submit = () => {
    form.post(route('projects.store'));
};
</script>

<template>
    <Head title="Create Project" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Create New Project</h2>
        </template>

        <div class="py-12">
            <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
                <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                    <form @submit.prevent="submit" class="space-y-6">
                        <div>
                            <InputLabel for="name" value="Project Name" />
                            <TextInput id="name" type="text" class="mt-1 block w-full" v-model="form.name" required autofocus />
                            <InputError class="mt-2" :message="form.errors.name" />
                        </div>

                        <div>
                            <InputLabel for="team_id" value="Assign to Team" />
                            <!-- This dropdown is populated by the 'teams' prop -->
                            <select id="team_id" v-model="form.team_id" required class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                                <option value="" disabled>-- Select a team --</option>
                                <option v-for="team in teams" :key="team.id" :value="team.id">
                                    {{ team.name }} (Lead: {{ team.team_lead.name }})
                                </option>
                            </select>
                            <InputError class="mt-2" :message="form.errors.team_id" />
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-4">
                            <div>
                                <InputLabel for="end_date" value="Expected End Date" />
                                <TextInput id="end_date" type="date" class="mt-1 block w-full" v-model="form.end_date" required />
                                <InputError class="mt-2" :message="form.errors.end_date" />
                            </div>
                            <div>
                                <InputLabel for="total_hours_required" value="Total Hours Required" />
                                <TextInput id="total_hours_required" type="number" min="1" class="mt-1 block w-full" v-model="form.total_hours_required" required />
                                <InputError class="mt-2" :message="form.errors.total_hours_required" />
                            </div>
                        </div>

                        <div>
                            <InputLabel for="description" value="Description (Optional)" />
                            <textarea id="description" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" v-model="form.description" rows="4"></textarea>
                            <InputError class="mt-2" :message="form.errors.description" />
                        </div>

                        <div class="flex items-center justify-end">
                            <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                                Create Project
                            </PrimaryButton>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>