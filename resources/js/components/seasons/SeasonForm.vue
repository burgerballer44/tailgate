<script setup>
import { ref } from "vue";
import { reset } from '@formkit/core';

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
    // the season we are editing or a default season object
    season: {
      type: Object,
        default(rawProps) {
            return {ulid: null, name: '', sport: '', seasonType: '', seasonStart: '', seasonEnd: ''}
        }
    },
    // function for adding to season
    addSeason: {
        type: Function,
    },
    // function for editing a season
    editSeason: {
        type: Function,
    }
});

// determine if we are editing
// by checking a ulid
let editing = false;
if (props.season.ulid) {
    editing = true;
}

// submit the form
async function handleSubmit(formValues, form) {

    form.clearErrors()

    let response;

    // if we are editing
    if (editing) {
        formValues['season_ulid'] = props.season.ulid
        response = await props.editSeason(formValues);
    // else we are adding
    } else {
        response = await props.addSeason(formValues);
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

    <div class="mb-4 text-lg font-semibold leading-6 text-gray-900">
        <p v-if="editing"> Edit Season - {{ season.name }}</p>
        <p v-else>New Season</p>
    </div>

    <FormKit
        type="form"
        submit-label="Save"
        @submit="handleSubmit"
    >

        <FormKit
            type="text"
            name="name"
            id="name"
            label="Name"
            validation="required|length:1,255"
            :value="season.name"
        />

        <FormKit
            type="select"
            label="Which sport?"
            placeholder="Select a sport"
            name="sport"
            id="sport"
            :options="sports"
            validation="required"
            :value="season.sport"
        />

        <FormKit
            type="select"
            label="Which type?"
            placeholder="Select a type"
            name="season_type"
            id="season_type"
            :options="seasonTypes"
            validation="required"
            :value="season.season_type"
        />

        <FormKit
            type="text"
            name="season_start"
            id="season_start"
            label="Season Start"
            help="YYYY-MM-DD"
            validation="required|length:1,255"
            :value="season.season_start"
        />

        <FormKit
            type="text"
            name="season_end"
            id="season_end"
            label="Season End"
            help="YYYY-MM-DD"
            validation="required|length:1,255"
            :value="season.season_end"
        />

    </FormKit>
</template>