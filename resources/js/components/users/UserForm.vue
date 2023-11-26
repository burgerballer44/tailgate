<script setup>
import { ref } from "vue";
import { reset } from '@formkit/core';

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
    // the user we are editing or a default user object
    user: {
      type: Object,
        default(rawProps) {
            return {ulid: null, name: '', email: '', status: '', role: ''}
        }
    },
    // function for adding to user
    addUser: {
        type: Function,
    },
    // function for editing a user
    editUser: {
        type: Function,
    }
});

// determine if we are editing
// by checking a ulid
let editing = false;
if (props.user.ulid) {
    editing = true;
}

// submit the form
async function handleSubmit(formValues, form) {

    form.clearErrors()

    let response;

    // if we are editing
    if (editing) {
        formValues['user_ulid'] = props.user.ulid
        response = await props.editUser(formValues);
    // else we are adding
    } else {
        response = await props.addUser(formValues);
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
        <p v-if="editing"> Edit User - {{ user.name }} ({{ user.email }})</p>
        <p v-else>New User</p>
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
            :value="user.name"
        />

        <FormKit
            type="email"
            name="email"
            id="email"
            label="Email"
            validation="required|length:1,255"
            :value="user.email"
        />

        <FormKit
            v-if="!editing"
            type="password"
            name="password"
            id="password"
            label="Password"
            help="Enter a new password"
            validation="required"
            validation-visibility="live"
        />

        <FormKit
            v-if="!editing"
            type="password"
            name="password_confirmation"
            label="Confirm password"
            help="Confirm your new password"
            validation="required|confirm:password"
            validation-visibility="live"
            validation-label="Password confirmation"
          />

        <FormKit
            type="select"
            label="Which status?"
            placeholder="Select a status"
            name="status"
            id="status"
            :options="statuses"
            validation="required"
            :value="user.status"
        />

        <FormKit
            type="select"
            label="Which roles?"
            placeholder="Select a roles"
            name="role"
            id="roles"
            :options="roles"
            validation="required"
            :value="user.role"
        />

    </FormKit>
</template>