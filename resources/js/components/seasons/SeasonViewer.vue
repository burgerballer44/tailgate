<script setup>
import { ref, computed  } from "vue";
import { reset } from '@formkit/core';

const props = defineProps({
    // list of teams available for the season
    teams: {
        type: Array,
        default: [],
    },
    // the season we are viewing
    season: {
      type: Object,
        default(rawProps) {
            return {
                ulid: null,
                name: '',
                sport: '',
                seasonType: '',
                seasonStart: '',
                seasonEnd: ''
            }
        }
    },
    // function for adding a game
    addGame: {
        type: Function,
    },
    // function for editing a game
    editGame: {
        type: Function,
    },
    // function for deleting a game
    deleteGame: {
        type: Function,
    }
});

// turn teams into list for form
const teamsList = computed(() => {
    return props.teams.map(function(team) {
        return {label: team.designation + ' ' + team.mascot, value: team.id};
    });
})

// if the form should be showing
const showForm = ref(false)
// games we are viewing or editing
const selectedGame = ref({})
// determine if we are editing the game form
let editing = false;

// submit the form
async function handleSubmit(formValues, form) {

    form.clearErrors()

    let response;

    formValues['season_ulid'] = props.season.ulid

    // if we are editing
    if (editing) {
        formValues['game_ulid'] = selectedGame.value.ulid
        response = await props.editGame(formValues);
    // else we are adding
    } else {
        response = await props.addGame(formValues);
    }

    if (true == response) {
        if (!editing) {
            reset(form);
        }
    } else {
        form.setErrors('', response.response.data.data)
    }
}

</script>

<template>

    <div v-if="showForm">
        <div class="mb-4 text-lg font-semibold leading-6 text-gray-900">
            <p v-if="editing">
                Edit Game - 
                {{ selectedGame.home_team.designation }} {{ selectedGame.home_team.mascot }}
                vs
                {{ selectedGame.away_team.designation }} {{ selectedGame.away_team.mascot }}
            </p>
            <p v-else>New Game</p>
        </div>

        <FormKit
            type="form"
            submit-label="Save"
            @submit="handleSubmit"
        >
            <FormKit
                type="select"
                label="Home Team"
                placeholder="Select the home team"
                name="home_team_id"
                id="home_team_id"
                :options="teamsList"
                validation="required"
                :value="selectedGame.home_team_id"
            />

            <FormKit
                type="select"
                label="Away Team"
                placeholder="Select the away team"
                name="away_team_id"
                id="away_team_id"
                :options="teamsList"
                validation="required"
                :value="selectedGame.away_team_id"
            />

            <FormKit
                type="text"
                name="home_team_score"
                id="home_team_score"
                label="Home Team Score"
                validation="required|min:0"
                :value="selectedGame.home_team_score"
            />

            <FormKit
                type="text"
                name="away_team_score"
                id="away_team_score"
                label="Away Team Score"
                validation="required|min:0"
                :value="selectedGame.away_team_score"
            />

            <FormKit
                type="text"
                name="start_date"
                id="start_date"
                label="Start Date"
                help="YYYY-MM-DD"
                validation="required|length:1,255"
                :value="selectedGame.start_date"
            />

            <FormKit
                type="text"
                name="start_time"
                id="start_time"
                label="Start Time"
                help="YYYY-MM-DD or string describing like TBD"
                validation="required|length:1,255"
                :value="selectedGame.start_time"
            />

        </FormKit>
    </div>

    <div>
        {{ season.name }} - {{ season.sport }} <a href="#" @click.prevent="showForm = false; editing = false; selectedGame = {}; showForm = true;" class="px-2 text-indigo-600 hover:text-indigo-900">Add Game</a>
    </div>

    <div class="mt-8 flow-root">
      <div class="-mx-4 -my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
        <div class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8">
          <div class="overflow-hidden shadow ring-1 ring-black ring-opacity-5 sm:rounded-lg">
            <table class="min-w-full divide-y divide-gray-300">
                <thead class="bg-gray-50">
                <tr>
                  <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 sm:pl-6">Vs</th>
                  <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Home Team Score</th>
                  <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Away Team Score</th>
                  <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Start Date</th>
                  <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Start Time</th>
                  <th scope="col" class="relative py-3.5 pl-3 pr-4 sm:pr-6">
                    <span class="sr-only">Edit</span>
                  </th>
                </tr>
                </thead>
                <tbody v-if="season.games && season.games.length" class="divide-y divide-gray-200 bg-white">
                  <tr v-for="game in season.games" :key="game.ulid">
                    <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-gray-900 sm:pl-6">
                        {{ game.home_team.designation }} {{ game.home_team.mascot }}
                        vs
                        {{ game.away_team.designation }} {{ game.away_team.mascot }}
                    </td>
                    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">{{ game.home_team_score }}</td>
                    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">{{ game.away_team_score }}</td>
                    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">{{ game.start_date }}</td>
                    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">{{ game.start_time }}</td>
                    <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-6">
                      <a href="#" @click.prevent="showForm = false; editing = true; selectedGame = game; showForm = true;" class="px-2 text-indigo-600 hover:text-indigo-900">Edit</a>
                      <a href="#" @click.prevent="deleteGame(season.ulid, game.ulid)" class="px-2 text-indigo-600 hover:text-indigo-900">Delete</a>
                    </td>
                  </tr>
                </tbody>
                <tbody v-else>
                  <tr>
                        <td>No games</td>
                  </tr>
                </tbody>

            </table>
          </div>
        </div>
      </div>
    </div>
    
</template>