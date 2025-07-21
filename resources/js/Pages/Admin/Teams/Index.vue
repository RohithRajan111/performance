<script setup>
import { ref } from 'vue'; // Import ref for modal state
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue'; // Good for Cancel buttons
import DangerButton from '@/Components/DangerButton.vue'; // Good for Delete buttons
import TextInput from '@/Components/TextInput.vue';
import Modal from '@/Components/Modal.vue'; // A reusable modal is great here
import { Head, useForm, router } from '@inertiajs/vue3';

// The props are passed from our TeamController@index method
const props = defineProps({
    teams: Array,
    potentialLeads: Array,
});

// --- Create Form ---
const createForm = useForm({
    name: '',
    team_lead_id: '',
});

const submitCreate = () => {
    createForm.post(route('teams.store'), {
        onSuccess: () => createForm.reset(),
    });
};

// --- Edit & Delete Logic ---
const editingTeam = ref(null);

const editForm = useForm({
    name: '',
    team_lead_id: '',
});

// Function to open the edit modal and populate it with team data
const openEditModal = (team) => {
    editingTeam.value = team;
    editForm.name = team.name;
    editForm.team_lead_id = team.team_lead.id; // Correctly get the lead's ID
};

// Function to close the modal
const closeModal = () => {
    editingTeam.value = null;
    editForm.reset();
    editForm.clearErrors();
};

// Function to submit the update request
const submitUpdate = () => {
    editForm.put(route('teams.update', editingTeam.value.id), {
        onSuccess: () => closeModal(),
        preserveScroll: true,
    });
};

// Function to handle team deletion
const deleteTeam = (teamId) => {
    if (confirm('Are you sure you want to delete this team? This action cannot be undone.')) {
        router.delete(route('teams.destroy', teamId), {
            preserveScroll: true,
        });
    }
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

                        <form @submit.prevent="submitCreate" class="mt-6 space-y-6">
                            <div>
                                <InputLabel for="name" value="Team Name" />
                                <TextInput id="name" type="text" class="mt-1 block w-full" v-model="createForm.name" required />
                                <InputError class="mt-2" :message="createForm.errors.name" />
                            </div>

                            <div>
                                <InputLabel for="team_lead_id" value="Assign Team Lead" />
                                <select id="team_lead_id" v-model="createForm.team_lead_id" required class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                                    <option value="" disabled>-- Select a Team Lead --</option>
                                    <option v-for="lead in potentialLeads" :key="lead.id" :value="lead.id">
                                        {{ lead.name }}
                                    </option>
                                </select>
                                <InputError class="mt-2" :message="createForm.errors.team_lead_id" />
                            </div>

                            <div class="flex items-center gap-4">
                                <PrimaryButton :disabled="createForm.processing">Create Team</PrimaryButton>
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
                            <!-- Updated Loop with Edit/Delete Buttons -->
                            <div v-for="team in props.teams" :key="team.id" class="px-4 py-3 bg-gray-50 rounded-md border flex justify-between items-center">
                                <div>
                                    <h3 class="font-semibold">{{ team.name }}</h3>
                                    <p class="text-sm text-gray-600">Lead: <span class="font-medium">{{ team.team_lead.name }}</span></p>
                                    <p class="text-sm text-gray-600">Members: <span class="font-medium">{{ team.members_count }}</span></p>
                                </div>
                                <div class="flex items-center gap-2">
                                     <SecondaryButton @click="openEditModal(team)">
                                        Edit
                                    </SecondaryButton>
                                    <DangerButton @click="deleteTeam(team.id)">
                                        Delete
                                    </DangerButton>
                                </div>
                            </div>
                        </div>
                    </section>
                </div>
            </div>
        </div>

        <!-- Edit Team Modal -->
        <Modal :show="editingTeam !== null" @close="closeModal">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900">
                    Edit Team: {{ editingTeam?.name }}
                </h2>
                <p class="mt-1 text-sm text-gray-600">
                    Update the team's name or assign a different team lead.
                </p>

                <form @submit.prevent="submitUpdate" class="mt-6 space-y-6">
                    <div>
                        <InputLabel for="edit_name" value="Team Name" />
                        <TextInput id="edit_name" type="text" class="mt-1 block w-full" v-model="editForm.name" required />
                        <InputError class="mt-2" :message="editForm.errors.name" />
                    </div>

                    <div>
                        <InputLabel for="edit_team_lead_id" value="Assign Team Lead" />
                        <select id="edit_team_lead_id" v-model="editForm.team_lead_id" required class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                            <option v-for="lead in potentialLeads" :key="lead.id" :value="lead.id">
                                {{ lead.name }}
                            </option>
                        </select>
                        <InputError class="mt-2" :message="editForm.errors.team_lead_id" />
                    </div>

                    <div class="mt-6 flex justify-end gap-4">
                        <SecondaryButton @click="closeModal"> Cancel </SecondaryButton>
                        <PrimaryButton :disabled="editForm.processing"> Save Changes </PrimaryButton>
                    </div>
                </form>
            </div>
        </Modal>

    </AuthenticatedLayout>
</template>