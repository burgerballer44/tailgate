<script setup>
import { ref, onMounted } from 'vue'
import Modal from "@/components/tailwindui/Modal.vue";
import SeasonForm from "@/components/seasons/SeasonForm.vue";
import SeasonViewer from "@/components/seasons/SeasonViewer.vue";

const props = defineProps({
    // list of season types a season can be
    seasonTypes: {
        type: Array,
        default: [],
    },
    // list of sports a season can be
    sports: {
        type: Array,
        default: [],
    },
});

// list of seasons
const seasons = ref([])
// season we are viewing or editing
const selectedSeason = ref({})
const selectedSeasonTeams = ref({})
// url for api
let apiBase = '/api/v1/seasons'
// apir error
const apiError = ref(null)
// configuration
const requestConfig = {
    headers: {
        'Accept': 'application/json'
    }
}
// if we are showing the edit modal
const showAddUpdateModal = ref(false)
const showViewModal = ref(false)

// get all the seasons
async function getSeasons() {
    const result = await fetch(apiBase)
        .then(response => response.json())
        .then(json => (seasons.value = json.data))
        .catch(err => (apiError.value = err))
}

// load season data then show the modal
async function loadSeasonViewer(seasonId) {
    await getSeason(seasonId);
    showViewModal.value = true;
}

// get more detail about a single season
async function getSeason(seasonId) {
    // season data
    await fetch(apiBase + '/' + seasonId)
        .then(response => response.json())
        .then(json => (selectedSeason.value = json.data))
        .catch(err => (apiError.value = err))
    // teams in the season data
    await fetch(apiBase + '/' + seasonId + '/teams')
        .then(response => response.json())
        .then(json => (selectedSeasonTeams.value = json.data))
        .catch(err => (apiError.value = err))
}

// add a season
const addSeason = async (values) => {
    // post the values
    let response = axios.post(apiBase, values, requestConfig)
        .then(function (response) {
            // reload the seasons
            getSeasons();
            return true;
        }).catch(function (error) {
            return error;
        });

    return response;
}

// edit a season
const editSeason = async (values) => {
    // patch the values
    let response = axios.patch(apiBase + '/' + values['season_ulid'], values, requestConfig)
        .then(function (response) {
            // reload the seasons
            getSeasons();
            return true;
        }).catch(function (error) {
            return error;
        });

    return response;
}

// delete the season
const deleteSeason = async (seasonId) => {

    let result = confirm('Are you sure you want to delete this season?');
    
    if (result) {
        axios.delete(apiBase + '/' + seasonId, requestConfig)
            .then(function (response) {
                // reload the seasons
                getSeasons();
            }).catch(function (error) {
                console.log(error);
            });
    }
}

// add a game
const addGame = async (values) => {
    // post the values
    let response = axios.post(apiBase + '/' + values['season_ulid'] + '/games', values, requestConfig)
        .then(function (response) {
            loadSeasonViewer(values['season_ulid'])
            return true;
        }).catch(function (error) {
            return error;
        });

    return response;
}

// edit a game
const editGame = async (values) => {
    // patch the values
    let response = axios.patch(apiBase + '/' + values['season_ulid'] + '/games/' + values['game_ulid'], values, requestConfig)
        .then(function (response) {
            loadSeasonViewer(values['season_ulid'])
            return true;
        }).catch(function (error) {
            return error;
        });

    return response;
}

// delete the game
const deleteGame = async (seasonId, gameId) => {

    let result = confirm('Are you sure you want to delete this game?');
    
    if (result) {
        let response = axios.delete(apiBase + '/' + seasonId + '/games/' + gameId, requestConfig)
            .then(function (response) {
                loadSeasonViewer(seasonId)
                return true;
            }).catch(function (error) {
                return error;
            });

        return response;
    }
}

onMounted(() => {
    getSeasons();
});

</script>

<template>

    <Modal :open=showAddUpdateModal @close="showAddUpdateModal = false; selectedSeason = {};">
        <template #body>
            <Season-Form
                :seasonTypes="seasonTypes"
                :sports="sports"
                :season="selectedSeason"
                :addSeason="addSeason"
                :editSeason="editSeason"
            />
        </template>
    </Modal>

    <Modal :open=showViewModal @close="showViewModal = false; selectedSeason = {};">
        <template #body>
            <Season-Viewer
                :teams="selectedSeasonTeams"
                :season="selectedSeason"
                :addGame="addGame"
                :editGame="editGame"
                :deleteGame="deleteGame"
            />
        </template>
    </Modal>

    <div class="p-4">
      <div class="sm:flex sm:items-center">
        <div class="sm:flex-auto">
          <h1 class="text-base font-semibold leading-6 text-gray-900">Seasons</h1>
          <p class="mt-2 text-sm text-gray-700">A list of all the seasons.</p>
        </div>
        <div class="mt-4 sm:ml-16 sm:mt-0 sm:flex-none">
          <button @click="showAddUpdateModal = true;" type="button" class="block rounded-md bg-carolina px-3 py-2 text-center text-sm font-semibold text-white shadow-sm hover:bg-navy focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">Add Season</button>
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
                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Sport</th>
                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Type</th>
                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Start</th>
                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">End</th>
                    <th scope="col" class="relative py-3.5 pl-3 pr-4 sm:pr-6">
                      <span class="sr-only">Edit</span>
                    </th>
                  </tr>
                </thead>

                <tbody v-if="seasons.length" class="divide-y divide-gray-200 bg-white">
                  <tr v-for="season in seasons" :key="season.ulid">
                    <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-gray-900 sm:pl-6">{{ season.name }}</td>
                    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">{{ season.sport }}</td>
                    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">{{ season.season_type }}</td>
                    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">{{ season.season_start }}</td>
                    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">{{ season.season_end }}</td>
                    <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-6">
                      <a href="#" @click.prevent="loadSeasonViewer(season.ulid)" class="px-2 text-indigo-600 hover:text-indigo-900">View</a>
                      <a href="#" @click.prevent="selectedSeason = season; showAddUpdateModal = true;" class="px-2 text-indigo-600 hover:text-indigo-900">Edit</a>
                      <a href="#" @click.prevent="deleteSeason(season.ulid)" class="px-2 text-indigo-600 hover:text-indigo-900">Delete</a>
                    </td>
                  </tr>
                </tbody>

                <tbody v-else>
                    <tr>
                        <td>No seasons</td>
                    </tr>
                </tbody>

              </table>
            </div>
          </div>
        </div>
      </div>
    </div>

</template>