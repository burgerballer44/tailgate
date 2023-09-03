<script setup>
import { ref,   } from 'vue'

const teams = ref([
    {designation: 'chicago', mascot: 'pizza'}
])
const error = ref(null)

async function getTeams() {
    const result = await fetch('api/v1/teams')
        .then(response => response.json())
        .then(json => (teams.value = json.data))
        .catch(err => (error.value = err))
    console.log(result)
}

getTeams() 

</script>

<template>
    <div v-if="teams.length">
        <ul>
            <li v-for="team in teams" :key="team.ulid">{{ team.designation }} {{ team.mascot }}</li>
        </ul>
    </div>
</template>