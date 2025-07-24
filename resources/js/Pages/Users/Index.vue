<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Modal from '@/Components/Modal.vue';
import InputError from '@/Components/InputError.vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import { debounce } from 'lodash';

const props = defineProps({
    users: Object, // Laravel paginated object
    roles: Array,
    teams: Array,
    potential_managers: Object,
    theAdmin: Object,
    filters: Object,
});

const isModalVisible = ref(false);
const modalMode = ref('create'); // 'create' or 'edit'
const editingUser = ref(null);

const form = useForm({
    _method: 'put', // Required for form submissions with files on 'edit'
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    role: '',
    team_id: '',
    parent_id: '',
    image: null, // Avatar file
    imagePreview: null, // Preview URL
});

// Watch for role changes to automatically set the manager
watch(() => form.role, (newRole) => {
    if (newRole === 'project-manager' && props.theAdmin) {
        form.parent_id = props.theAdmin.id;
    } else {
        // Only clear parent_id if the role has actually changed from what it was
        if (editingUser.value && editingUser.value.roles[0]?.name === newRole) {
           return; // Do nothing if role is the same as the original
        }
        form.parent_id = '';
    }
});

const openCreateModal = () => {
    form.reset();
    form.image = null;
    form.imagePreview = null;
    modalMode.value = 'create';
    editingUser.value = null;
    isModalVisible.value = true;
};

const openEditModal = (user) => {
    // Reset the form to clear any previous state/errors
    form.reset();
    
    form.name = user.name;
    form.email = user.email;
    form.role = user.roles[0]?.name || '';
    form.team_id = user.team_id || '';
    form.parent_id = user.parent_id || '';
    form.password = ''; // Clear password fields for edit mode
    form.password_confirmation = '';
    form.image = null; // Start with no new file
    form.imagePreview = user.image ? `/storage/${user.image}` : null;

    modalMode.value = 'edit';
    editingUser.value = user;
    isModalVisible.value = true;
};

const closeModal = () => {
    isModalVisible.value = false;
    form.reset();
    form.clearErrors();
};

const submit = () => {
    if (modalMode.value === 'create') {
        form.post(route('users.store'), {
            onSuccess: () => closeModal(),
            preserveScroll: true,
        });
    } else {
        // Use form.post with _method: 'put' for multipart/form-data updates
        form.post(route('users.update', editingUser.value.id), {
            onSuccess: () => closeModal(),
            preserveScroll: true,
        });
    }
};

const deleteUser = (userId) => {
    if (confirm('Are you sure you want to delete this user?')) {
        router.delete(route('users.destroy', userId), {
            preserveScroll: true,
        });
    }
};

const search = ref(props.filters?.search || '');
const searchUsers = debounce(() => {
    router.get(route('users.index'), { search: search.value }, { preserveState: true, replace: true });
}, 300);

const paginationLinks = computed(() => props.users.links);

// --- IMAGE UPLOAD LOGIC ---
function handleImageUpload(e) {
    const file = e.target.files[0];
    if (file) {
        form.image = file;
        form.imagePreview = URL.createObjectURL(file);
    }
}
</script>


<template>
    <Head title="Manage Users" />

    <AuthenticatedLayout>
        <div class="p-4 sm:p-6 lg:p-8 font-sans">
            <div class="max-w-7xl mx-auto space-y-6">

                <!-- Page Header -->
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <h1 class="text-3xl font-bold text-slate-900">Manage Users</h1>
                    <button @click="openCreateModal" class="inline-flex items-center justify-center rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-slate-700">
                        + Add User
                    </button>
                </div>

                <!-- Users List Card -->
                <div class="bg-white rounded-xl shadow-sm border border-slate-200">
                     <div class="p-4 sm:px-6 border-b border-slate-200">
                        <input v-model="search" @input="searchUsers" type="text" placeholder="Search by name or email..."
                               class="block w-full max-w-xs rounded-md border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" />
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th scope="col" class="py-3.5 px-6 text-left text-xs font-semibold text-slate-500 uppercase">Name</th>
                                    <th scope="col" class="py-3.5 px-6 text-left text-xs font-semibold text-slate-500 uppercase">Role</th>
                                    <th scope="col" class="py-3.5 px-6 text-left text-xs font-semibold text-slate-500 uppercase">Joined On</th>
                                    <th scope="col" class="relative py-3.5 px-6"><span class="sr-only">Actions</span></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 bg-white">
                                <tr v-if="!users.data.length">
                                    <td colspan="4" class="px-6 py-8 text-center text-slate-500">No users found.</td>
                                </tr>
                                <tr v-for="user in users.data" :key="user.id" class="hover:bg-slate-50">
                                    <td class="whitespace-nowrap py-4 px-6 text-sm">
                                        <div class="flex items-center gap-3">
                                            <img
                                                class="h-10 w-10 rounded-full object-cover"
                                                :src="user.image ? `/storage/${user.image}` : `https://ui-avatars.com/api/?name=${user.name}&background=random`"
                                                alt="Profile avatar"
                                            >
                                            <div>
                                                <div class="font-medium text-slate-900">{{ user.name }}</div>
                                                <div class="text-slate-500">{{ user.email }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="whitespace-nowrap py-4 px-6 text-sm text-slate-600 capitalize">{{ user.roles[0]?.name.replace('-', ' ') || 'N/A' }}</td>
                                    <td class="whitespace-nowrap py-4 px-6 text-sm text-slate-600">{{ new Date(user.created_at).toLocaleDateString() }}</td>
                                    <td class="whitespace-nowrap py-4 px-6 text-right text-sm font-medium">
                                        <div class="flex items-center justify-end space-x-4">
                                            <button @click="openEditModal(user)" class="text-indigo-600 hover:text-indigo-900">Edit</button>
                                            <button @click="deleteUser(user.id)" class="text-red-600 hover:text-red-900">Delete</button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                     <!-- Pagination Links -->
                    <div v-if="paginationLinks.length > 3" class="p-4 sm:px-6 border-t border-slate-200 flex items-center justify-between">
                        <div class="text-sm text-slate-600">Showing {{ users.from }} to {{ users.to }} of {{ users.total }} results</div>
                        <nav class="isolate inline-flex -space-x-px rounded-md shadow-sm">
                             <Link v-for="(link, index) in paginationLinks" :key="index"
                                  :href="link.url"
                                  v-html="link.label"
                                  :class="{ 'bg-slate-900 text-white': link.active, 'text-slate-900 ring-1 ring-inset ring-slate-300 hover:bg-slate-50': !link.active, 'rounded-l-md': index === 0, 'rounded-r-md': index === paginationLinks.length - 1 }"
                                  class="relative inline-flex items-center px-4 py-2 text-sm font-semibold focus:z-20"
                                  :disabled="!link.url"
                                  preserve-scroll />
                        </nav>
                    </div>
                </div>
            </div>
        </div>

        <!-- Create / Edit User Modal -->
        <Modal :show="isModalVisible" @close="closeModal">
            <div class="p-6">
                <h2 class="text-lg font-bold text-slate-900">{{ modalMode === 'create' ? 'Create New User' : 'Edit User' }}</h2>
                <form @submit.prevent="submit" class="mt-6 space-y-6">
                    <div>
                        <label for="name" class="block text-sm font-medium text-slate-700">Name</label>
                        <input v-model="form.name" id="name" type="text" required class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" />
                        <InputError class="mt-2" :message="form.errors.name" />
                    </div>
                    <div>
                        <label for="email" class="block text-sm font-medium text-slate-700">Email</label>
                        <input v-model="form.email" id="email" type="email" required class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" />
                        <InputError class="mt-2" :message="form.errors.email" />
                    </div>
                    <div>
                        <label for="image" class="block text-sm font-medium text-slate-700">Profile Image</label>
                        <div class="mt-2 flex items-center gap-4">
                            <img v-if="form.imagePreview" :src="form.imagePreview" alt="Preview" class="w-16 h-16 rounded-full object-cover border" />
                             <img v-else class="w-16 h-16 rounded-full object-cover border bg-slate-100 text-slate-400 flex items-center justify-center" src="data:image/gif;base64,R0lGODlhAQABAAD/ACwAAAAAAQABAAACADs=" alt="No Image">
                            <input
                                id="image"
                                type="file"
                                accept="image/*"
                                class="block w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100"
                                @change="handleImageUpload"
                            />
                        </div>
                        <InputError class="mt-2" :message="form.errors.image" />
                    </div>

                    <div>
                        <label for="role" class="block text-sm font-medium text-slate-700">Role</label>
                        <select v-model="form.role" id="role" required class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm capitalize">
                            <option value="" disabled>-- Assign a role --</option>
                            <option v-for="role in roles" :key="role" :value="role" class="capitalize">{{ role.replace('-', ' ') }}</option>
                        </select>
                        <InputError class="mt-2" :message="form.errors.role" />
                    </div>

                     <!-- DYNAMIC MANAGER (PARENT) SECTION -->
                    <div>
                        <div v-if="form.role === 'project-manager'">
                            <label class="block text-sm font-medium text-slate-700">Reports To</label>
                            <div v-if="theAdmin" class="mt-1 p-2 bg-slate-100 border border-slate-200 rounded-md">
                                <p class="text-sm font-medium text-slate-700">{{ theAdmin.name }} (Auto-assigned)</p>
                            </div>
                        </div>
                        <div v-if="form.role === 'team-lead'">
                            <label for="parent_id_pm" class="block text-sm font-medium text-slate-700">Reports To (Project Manager)</label>
                            <select id="parent_id_pm" v-model="form.parent_id" required class="mt-1 block w-full border-slate-300 rounded-md shadow-sm">
                                <option value="" disabled>Select a Project Manager</option>
                                <option v-for="manager in potential_managers.project_managers" :key="manager.id" :value="manager.id">{{ manager.name }}</option>
                            </select>
                        </div>
                        <div v-if="form.role === 'employee'">
                            <label for="parent_id_lead" class="block text-sm font-medium text-slate-700">Reports To (Team Lead)</label>
                            <select id="parent_id_lead" v-model="form.parent_id" required class="mt-1 block w-full border-slate-300 rounded-md shadow-sm">
                                <option value="" disabled>Select a Team Lead</option>
                                <option v-for="manager in potential_managers.team_leads" :key="manager.id" :value="manager.id">{{ manager.name }}</option>
                            </select>
                        </div>
                        <InputError class="mt-2" :message="form.errors.parent_id" />
                    </div>

                    <div v-if="form.role === 'employee'">
                         <label for="team_id" class="block text-sm font-medium text-slate-700">Team</label>
                        <select v-model="form.team_id" id="team_id" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            <option value="">-- Assign a team --</option>
                            <option v-for="team in teams" :key="team.id" :value="team.id">{{ team.name }}</option>
                        </select>
                        <InputError class="mt-2" :message="form.errors.team_id" />
                    </div>

                    <!-- Flexible Password Section -->
                    <div class="border-t border-slate-200 pt-6">
                         <h3 class="text-base font-semibold text-slate-800">
                            {{ modalMode === 'create' ? 'Set Password' : 'Change Password (Optional)' }}
                        </h3>
                        <div class="mt-4 space-y-6">
                           <div>
                                <label for="password" class="block text-sm font-medium text-slate-700">New Password</label>
                                <input v-model="form.password" id="password" type="password" :required="modalMode === 'create'" autocomplete="new-password" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" />
                                <InputError class="mt-2" :message="form.errors.password" />
                            </div>
                            <div>
                                <label for="password_confirmation" class="block text-sm font-medium text-slate-700">Confirm New Password</label>
                                <input v-model="form.password_confirmation" id="password_confirmation" type="password" :required="modalMode === 'create'" autocomplete="new-password" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" />
                            </div>
                        </div>
                    </div>

                    <div class="mt-6 flex justify-end gap-3">
                        <button type="button" @click="closeModal" class="inline-flex items-center justify-center rounded-lg bg-white px-4 py-2 text-sm font-semibold text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 hover:bg-slate-50">Cancel</button>
                        <button type="submit" :disabled="form.processing" class="inline-flex items-center justify-center rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-slate-700 disabled:opacity-50">
                            {{ modalMode === 'create' ? 'Create User' : 'Save Changes' }}
                        </button>
                    </div>
                </form>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>