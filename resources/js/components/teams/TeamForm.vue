<script setup>
import { ref } from "vue";
import { reset } from '@formkit/core';

const props = defineProps([]);

async function handleSubmit (formValues, form) {

    showSuccessAlert.value = false;
    form.clearErrors()

    let apiUrl = '/api/datamodelmanager/v1/taxonomies/' + props.taxonomy_id + '/attributes'
    let apiMethod = 'POST'

    if (editingForm) {
        formValues['attribute_id'] = selectedAttribute.id
        apiUrl = '/api/datamodelmanager/v1/taxonomies/' + props.taxonomy_id + '/attributes/' + formValues['attribute_id']
        apiMethod = 'PATCH'
    }

    axios({
        method: apiMethod,
        url: apiUrl,
        data: formValues,
        headers: {
            'Accept': 'application/json'
        },
        params: {
            org_key: plezio.current_org_key,
        }
    })
    .then(function (response) {
        showSuccessAlert.value = true
        props.getAttributes()
        if (!editingForm) {
            reset(form);
        }
    }).catch(function (error) {
        form.setErrors('', error.response.data.data)
    });
}

</script>

<template>

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
        />

        <FormKit
            type="textarea"
            name="description"
            id="description"
            label="Description"
            validation="length:0,999"
        />

        <FormKit
            type="text"
            name="group"
            id="group"
            label="Group"
            validation="length:0,255"
        />

    </FormKit>
</template>