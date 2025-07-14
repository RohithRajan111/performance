<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, useForm } from '@inertiajs/vue3';

// The props are passed from our TeamController@index method
const props = defineProps({
    teams: Array,
    potentialLeads: Array,
});

// Set up the form for creating a new team
const form = useForm({
    name: '',
    team_lead_id: '',
});

const submit = () => {
    form.post(route('teams.store'), {
        onSuccess: () => form.reset(),
    });
};
</script>

<template>
    <Head title="Manage Teams" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Manage Teams</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 grid md:grid-cols-2 gap-6">

                <!-- Left Column: Form to Add New Team -->
                <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                    <section>
                        <header>
                            <h2 class="text-lg font-medium text-gray-900">Create New Team</h2>
                            <p class="mt-1 text-sm text-gray-600">
                                Create a new team and assign an existing user with the 'Team Lead' role.
                            </p>
                        </header>

                        <form @submit.prevent="submit" class="mt-6 space-y-6">
                            <div>
                                <InputLabel for="name" value="Team Name" />
                                <TextInput id="name" type="text" class="mt-1 block w-full" v-model="form.name" required />
                                <InputError class="mt-2" :message="form.errors.name" />
                            </div>

                            <div>
                                <InputLabel for="team_lead_id" value="Assign Team Lead" />
                                <select id="team_lead_id" v-model="form.team_lead_id" required class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                                    <option value="" disabled>-- Select a Team Lead --</option>
                                    <option v-for="lead in potentialLeads" :key="lead.id" :value="lead.id">
                                        {{ lead.name }}
                                    </option>
                                </select>
                                <InputError class="mt-2" :message="form.errors.team_lead_id" />
                            </div>

                            <div class="flex items-center gap-4">
                                <PrimaryButton :disabled="form.processing">Create Team</PrimaryButton>
                            </div>
                        </form>
                    </section>
                </div>

                <!-- Right Column: List of Existing Teams -->
                <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                    <section>
                        <header>
                            <h2 class="text-lg font-medium text-gray-900">Existing Teams</h2>
                        </header>
                        <div class="mt-4 space-y-4">
                            <div v-if="!teams.length" class="text-center text-gray-500 py-4">No teams have been created yet.</div>
                            <div v-for="team in props.teams" :key="team.id" class="px-4 py-3 bg-gray-50 rounded-md border">
                                <h3 class="font-semibold">{{ team.name }}</h3>
                                <p class="text-sm text-gray-600">Lead: <span class="font-medium">{{ team.team_lead.name }}</span></p>
                                <p class="text-sm text-gray-600">Members: <span class="font-medium">{{ team.members_count }}</span></p>
                            </div>
                        </div>
                    </section>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>