<script setup>
import { ref, onMounted } from 'vue'
import Modal from "@/components/tailwindui/Modal.vue";
import GroupForm from "@/components/groups/GroupForm.vue";

const props = defineProps({
    // list of users that can own a group
    users: {
        type: Array,
        default: [],
    }
});

// list of groups
const groups = ref([])
// group we are viewing or editing
const selectedGroup = ref({})
// url for api
let apiBase = '/api/v1/groups'
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

// get all the groups
async function getGroups() {
    const result = await fetch(apiBase)
        .then(response => response.json())
        .then(json => (groups.value = json.data))
        .catch(err => (apiError.value = err))

        console.log(groups);
}

// add a group
const addGroup = async (values) => {
    // post the values
    let response = axios.post(apiBase, values, requestConfig)
        .then(function (response) {
            // reload the groups
            getGroups();
            return true;
        }).catch(function (error) {
            return error;
        });

    return response;
}

// edit a group
const editGroup = async (values) => {
    // patch the values
    let response = axios.patch(apiBase + '/' + values['group_ulid'], values, requestConfig)
        .then(function (response) {
            // reload the groups
            getGroups();
            return true;
        }).catch(function (error) {
            return error;
        });

    return response;
}

// delete the group
const deleteGroup = async (groupId) => {

    let result = confirm('Are you sure you want to delete this group?');
    
    if (result) {
        axios.delete(apiBase + '/' + groupId, requestConfig)
            .then(function (response) {
                // reload the groups
                getGroups();
            }).catch(function (error) {
                console.log(error);
            });
    }
}

onMounted(() => {
    getGroups();
});

</script>

<template>

    <Modal :open=showModal @close="showModal = false; selectedGroup = {};">
        <template #body>
            <Group-Form
                :users="users"
                :group="selectedGroup"
                :addGroup="addGroup"
                :editGroup="editGroup"
            />
        </template>
    </Modal>

    <div class="p-4">
      <div class="sm:flex sm:items-center">
        <div class="sm:flex-auto">
          <h1 class="text-base font-semibold leading-6 text-gray-900">Groups</h1>
          <p class="mt-2 text-sm text-gray-700">A list of all the groups.</p>
        </div>
        <div class="mt-4 sm:ml-16 sm:mt-0 sm:flex-none">
          <button @click="showModal = true;" type="button" class="block rounded-md bg-carolina px-3 py-2 text-center text-sm font-semibold text-white shadow-sm hover:bg-navy focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">Add group</button>
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
                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Owner</th>
                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Player Limit</th>
                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Member Limit</th>
                    <th scope="col" class="relative py-3.5 pl-3 pr-4 sm:pr-6">
                      <span class="sr-only">Edit</span>
                    </th>
                  </tr>
                </thead>

                <tbody v-if="groups.length" class="divide-y divide-gray-200 bg-white">
                  <tr v-for="group in groups" :key="group.ulid">
                    <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-gray-900 sm:pl-6">{{ group.name }}</td>
                    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">{{ group.owner.name }}</td>
                    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">{{ group.player_limit }}</td>
                    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">{{ group.member_limit }}</td>
                    <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-6">
                      <a href="#" @click.prevent="selectedGroup = group; showModal = true;" class="px-2 text-indigo-600 hover:text-indigo-900">Edit</a>
                      <a href="#" @click.prevent="deleteGroup(group.ulid)" class="px-2 text-indigo-600 hover:text-indigo-900">Delete</a>
                    </td>
                  </tr>
                </tbody>

                <tbody v-else>
                    <tr>
                        <td>No groups</td>
                    </tr>
                </tbody>

              </table>
            </div>
          </div>
        </div>
      </div>
    </div>

</template>