<script setup>
import { ref } from "vue";
import { reset } from '@formkit/core';

const props = defineProps({
    // list of sports a team can be
    sports: {
        type: Array,
        default: [],
    },
    // the team we are editing or a default team object
    team: {
      type: Object,
        default(rawProps) {
            return {ulid: null, designation: '', mascot: '', sport: ''}
        }
    },
    // function for adding to team
    addTeam: {
        type: Function,
    },
    // function for editing a team
    editTeam: {
        type: Function,
    }
});

// determine if we are editing
// by checking a ulid
let editing = false;
if (props.team.ulid) {
    editing = true;
}

// submit the form
async function handleSubmit(formValues, form) {

    form.clearErrors()

    let response;

    // if we are editing
    if (editing) {
        formValues['team_ulid'] = props.team.ulid
        response = await props.editTeam(formValues);
    // else we are adding
    } else {
        response = await props.addTeam(formValues);
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
        <p v-if="editing"> Edit Team - {{ team.designation }} {{ team.mascot }}</p>
        <p v-else>New Team</p>
    </div>

    <FormKit
        type="form"
        submit-label="Save"
        @submit="handleSubmit"
    >

        <FormKit
            type="text"
            name="designation"
            id="designation"
            label="Designation"
            validation="required|length:1,255"
            :value="team.designation"
        />

        <FormKit
            type="text"
            name="mascot"
            id="mascot"
            label="Mascot"
            validation="required|length:1,255"
            :value="team.mascot"
        />

        <FormKit
            type="select"
            label="Which sport?"
            placeholder="Select a sport"
            name="sport"
            id="sport"
            :options="sports"
            validation="required"
            :value="team.sport"
        />

    </FormKit>
</template>