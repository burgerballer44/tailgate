<script setup>
import { ref, onMounted } from 'vue'
import Modal from "@/components/tailwindui/Modal.vue";
import UserForm from "@/components/users/UserForm.vue";

const props = defineProps({
    // list of statuses a user can be
    statuses: {
        type: Array,
        default: [],
    },
    // list of roles a user can be
    roles: {
        type: Array,
        default: [],
    },
});

// list of users
const users = ref([])
// user we are viewing or editing
const selectedUser = ref({})
// url for api
let apiBase = '/api/v1/users'
// apir error
const apiError = ref(null)
// configuration
const requestConfig = {
    headers: {
        'Accept': 'application/json'
    }
}
// if we are showing the modal
const showModal = ref(false)

// get all the users
async function getUsers() {
    const result = await fetch(apiBase)
        .then(response => response.json())
        .then(json => (users.value = json.data))
        .catch(err => (apiError.value = err))
}

// add a user
const addUser = async (values) => {
    // post the values
    let response = axios.post(apiBase, values, requestConfig)
        .then(function (response) {
            // reload the users
            getUsers();
            return true;
        }).catch(function (error) {
            return error;
        });

    return response;
}

// edit a user
const editUser = async (values) => {
    // patch the values
    let response = axios.patch(apiBase + '/' + values['user_ulid'], values, requestConfig)
        .then(function (response) {
            // reload the users
            getUsers();
            return true;
        }).catch(function (error) {
            return error;
        });

    return response;
}

// delete the user
const deleteUser = async (userId) => {

    let result = confirm('Are you sure you want to delete this user?');
    
    if (result) {
        axios.delete(apiBase + '/' + userId, requestConfig)
            .then(function (response) {
                // reload the users
                getUsers();
            }).catch(function (error) {
                console.log(error);
            });
    }
}

onMounted(() => {
    getUsers();
});

</script>

<template>

    <Modal :open=showModal @close="showModal = false; selectedUser = {};">
        <template #body>
            <User-Form
                :statuses="statuses"
                :roles="roles"
                :user="selectedUser"
                :addUser="addUser"
                :editUser="editUser"
            />
        </template>
    </Modal>

    <div class="p-4">
      <div class="sm:flex sm:items-center">
        <div class="sm:flex-auto">
          <h1 class="text-base font-semibold leading-6 text-gray-900">Users</h1>
          <p class="mt-2 text-sm text-gray-700">A list of all the users.</p>
        </div>
        <div class="mt-4 sm:ml-16 sm:mt-0 sm:flex-none">
          <button @click="showModal = true;" type="button" class="block rounded-md bg-carolina px-3 py-2 text-center text-sm font-semibold text-white shadow-sm hover:bg-navy focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">Add User</button>
        </div>
      </div>
      <div class="mt-8 flow-root">
        <div class="-mx-4 -my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
          <div class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8">
            <div class="overflow-hidden shadow ring-1 ring-black ring-opacity-5 sm:rounded-lg">
              <table class="min-w-full divide-y divide-gray-300">
                <thead class="bg-gray-50">
                  <tr>
                    <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 sm:pl-6">Name</th>
                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Email</th>
                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Status</th>
                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Role</th>
                    <th scope="col" class="relative py-3.5 pl-3 pr-4 sm:pr-6">
                      <span class="sr-only">Edit</span>
                    </th>
                  </tr>
                </thead>

                <tbody v-if="users.length" class="divide-y divide-gray-200 bg-white">
                  <tr v-for="user in users" :key="user.ulid">
                    <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-gray-900 sm:pl-6">{{ user.name }}</td>
                    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">{{ user.email }}</td>
                    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">{{ user.status }}</td>
                    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">{{ user.role }}</td>
                    <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-6">
                      <a href="#" @click.prevent="selectedUser = user; showModal = true;" class="px-2 text-indigo-600 hover:text-indigo-900">Edit</a>
                      <a href="#" @click.prevent="deleteUser(user.ulid)" class="px-2 text-indigo-600 hover:text-indigo-900">Delete</a>
                    </td>
                  </tr>
                </tbody>

                <tbody v-else>
                    <tr>
                        <td>No users</td>
                    </tr>
                </tbody>

              </table>
            </div>
          </div>
        </div>
      </div>
    </div>

</template>