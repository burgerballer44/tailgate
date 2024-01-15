<script setup>
import { ref, computed } from "vue";
import { reset } from '@formkit/core';

const props = defineProps({
    // list of users that can own the group
    users: {
        type: Array,
        default: [],
    },
    // the group we are editing or a default group object
    group: {
      type: Object,
        default(rawProps) {
            return {
                ulid: null,
                name: '',
                owner: '',
                player_limit: '',
                member_limit: ''
            }
        }
    },
    // function for adding to group
    addGroup: {
        type: Function,
    },
    // function for editing a group
    editGroup: {
        type: Function,
    }
});

// turn users into list for form
const usersList = computed(() => {
    return props.users.map(function(user) {
        return {label: user.name, value: user.id};
    });
})

// determine if we are editing
// by checking a ulid
let editing = false;
if (props.group.ulid) {
    editing = true;
}

// submit the form
async function handleSubmit(formValues, form) {

    form.clearErrors()

    let response;

    // if we are editing
    if (editing) {
        formValues['group_ulid'] = props.group.ulid
        response = await props.editGroup(formValues);
    // else we are adding
    } else {
        response = await props.addGroup(formValues);
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
        <p v-if="editing"> Edit group - {{ group.name }}</p>
        <p v-else>New group</p>
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
            :value="group.name"
        />

        <FormKit
            type="select"
            label="Owner"
            placeholder="Select the owner"
            name="owner_id"
            id="owner_id"
            :options="usersList"
            validation="required"
            :value="group.owner_id"
        />

        <div v-if="editing">
            <FormKit
                type="text"
                name="player_limit"
                id="player_limit"
                label="Player Limit"
                validation="required|min:0"
                :value="group.player_limit"
            />

            <FormKit
                type="text"
                name="member_limit"
                id="member_limit"
                label="Member Limit"
                validation="required|min:0"
                :value="group.member_limit"
            />
        </div>

    </FormKit>
</template>