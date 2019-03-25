<template>
    <div class="SharpFormPage">
        <template v-if="ready">
            <SharpActionBarForm />
            <SharpForm />
        </template>
    </div>
</template>

<script>
    import SharpActionBarForm from '../action-bar/ActionBarForm';
    import SharpForm from '../form/Form';
    import { BASE_URL } from "../../consts";
    import { getForm, postForm } from "../../api";

    export default {
        name: 'SharpFormPage',
        components: {
            SharpForm,
            SharpActionBarForm,
        },

        data() {
            return {
                ready: false,

                data: null,
                layout: null,
                fields: null,
                authorizations: null,

                errors: {},
                fieldLocale: {},
                locales: null,

                fieldVisible: {},
                curFieldsetId: 0,

                pendingJobs: []
            }
        },

        computed: {
            entityKey() {
                return this.$route.params.entityKey;
            },
            instanceId() {
                return this.$route.params.instanceId;
            },
            isCreation() {
                return !this.instanceId;
            },
            isReadOnly() {
                return this.isCreation
                    ? !this.authorizations.create
                    : !this.authorizations.update;
            },
            baseEntityKey() {
                return this.entityKey.split(':')[0];
            },
            entityListUrl() {
                return `${BASE_URL}/list/${this.baseEntityKey}?restore-context=1`;
            },
            downloadLinkBase() {
                return `/download/${this.entityKey}/${this.instanceId}`;
            },
        },

        methods: {
            handleSubmitRequested() {
                return postForm({
                    entityKey: this.entityKey,
                    instanceId: this.instanceId,
                    data: this.data,
                }).then(() => {
                    this.mainLoading.$emit('show');
                    location.href = this.entityListUrl;
                }).catch(error => {
                    const { data } = error.response;
                    if(error.response.status === 422) {
                        this.errors = data.errors || {};
                    }
                });
            },
            mount({fields, layout, data={}, authorizations={}, locales,}) {
                this.fields = fields;
                this.layout = layout;
                this.data = data;
                this.locales = locales;
                // this.locale = locales && locales[0];
                this.authorizations = authorizations;

                if(fields) {
                    this.fieldVisible = Object.keys(this.fields).reduce((res, fKey) => {
                        res[fKey] = true;
                        return res;
                    },{});
                    this.fieldLocale = this.defaultFieldLocaleMap({ fields, locales });
                }
                this.validate();
            },
            async init() {
                const form = await getForm({
                    entityKey: this.entityKey,
                    instanceId: this.instanceId,
                });
                this.mount(form);
            },
        }
    }
</script>