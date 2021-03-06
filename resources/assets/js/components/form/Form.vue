<template>
    <div class="SharpForm">
        <template v-if="ready">
            <div class="container">
                <div v-show="hasErrors" class="SharpNotification SharpNotification--error" role="alert">
                    <div class="SharpNotification__details">
                        <div class="SharpNotification__text-wrapper">
                            <p class="SharpNotification__title">{{ l('form.validation_error.title') }}</p>
                            <p class="SharpNotification__subtitle">{{ l('form.validation_error.description') }}</p>
                        </div>
                    </div>
                </div>
                <sharp-tabbed-layout :layout="layout" ref="tabbedLayout">
                    <!-- Tab -->
                    <template slot-scope="tab">
                        <sharp-grid :rows="[tab.columns]" ref="columnsGrid">
                            <!-- column -->
                            <template slot-scope="column">
                                <sharp-fields-layout v-if="fields" :layout="column.fields" :visible="fieldVisible" ref="fieldLayout">
                                    <!-- field -->
                                    <template slot-scope="fieldLayout">
                                        <sharp-field-display
                                            :field-key="fieldLayout.key"
                                            :context-fields="transformedFields"
                                            :context-data="data"
                                            :field-layout="fieldLayout"
                                            :locale="fieldLocale[fieldLayout.key]"
                                            :error-identifier="fieldLayout.key"
                                            :config-identifier="fieldLayout.key"
                                            :update-data="updateData"
                                            :update-visibility="updateVisibility"
                                            @locale-change="updateLocale"
                                            ref="field"
                                        />
                                    </template>
                                </sharp-fields-layout>
                            </template>
                        </sharp-grid>
                    </template>
                </sharp-tabbed-layout>
            </div>
        </template>
    </div>
</template>

<script>
    import * as util from '../../util';
    import { API_PATH, BASE_URL } from '../../consts';

    import { ActionEvents, ReadOnlyFields, Localization } from '../../mixins';

    import DynamicView from '../DynamicViewMixin';

    import SharpTabbedLayout from '../TabbedLayout'
    import SharpGrid from '../Grid';
    import SharpFieldsLayout from './FieldsLayout.vue';
    // import SharpLocaleSelector from '../LocaleSelector.vue';

    import localize from '../../mixins/localize/form';
    import { getDependantFieldsResetData, transformFields } from "../../util/form";
    import { getBackUrl, getListBackUrl } from "../../util/url";

    const noop = ()=>{};

    export default {
        name:'SharpForm',
        extends: DynamicView,

        mixins: [ActionEvents, ReadOnlyFields('fields'), Localization, localize('fields')],

        components: {
            SharpTabbedLayout,
            SharpFieldsLayout,
            SharpGrid,
            // SharpLocaleSelector
        },


        props:{
            entityKey: String,
            instanceId: String,

            /// Extras props for customization
            independant: {
                type:Boolean,
                default: false
            },
            ignoreAuthorizations: Boolean,
            props: Object
        },

        inject:['actionsBus'],

        provide() {
            return {
                $form:this
            }
        },

        data() {
            return {
                ready: false,

                fields: null,
                authorizations: null,
                breadcrumb: null,
                config: null,

                errors:{},
                fieldLocale: {},
                locales: null,

                fieldVisible: {},
                curFieldsetId:0,

                pendingJobs: []
            }
        },
        computed: {
            apiPath() {
                let path = `form/${this.entityKey}`;
                if(this.instanceId) path+=`/${this.instanceId}`;
                return path;
            },
            localized() {
                return Array.isArray(this.locales) && !!this.locales.length;
            },
            isSingle() {
                return this.config
                    ? this.config.isSingle
                    : false
            },
            isCreation() {
                return !this.isSingle && !this.instanceId;
            },
            isReadOnly() {
                if(this.ignoreAuthorizations) {
                    return false;
                }
                return this.isCreation
                    ? !this.authorizations.create
                    : !this.authorizations.update;
            },
            // don't show loading on creation
            synchronous() {
                return this.independant;
            },
            hasErrors() {
                return Object.keys(this.errors).some(errorKey => !this.errors[errorKey].cleared);
            },

            baseEntityKey() {
                return this.entityKey.split(':')[0];
            },

            downloadLinkBase() {
                return `/download/${this.entityKey}/${this.instanceId}`;
            },
            listUrl() {
                return `${BASE_URL}/list/${this.baseEntityKey}?restore-context=1`;
            },

            localeSelectorErrors() {
                return Object.keys(this.errors).reduce((res,errorKey)=>{
                    let errorLocale = this.locales.find(l=>errorKey.endsWith(`.${l}`));
                    if(errorLocale) {
                        res[errorLocale] = true;
                    }
                    return res;
                },{})
            },

            transformedFields() {
                const fields = this.isReadOnly
                    ? this.readOnlyFields
                    : this.fields;
                return transformFields(fields, this.data);
            },
        },
        methods: {
            async updateData(key, value, { forced } = {}) {
                this.data = {
                    ...this.data,
                    ...(!forced ? getDependantFieldsResetData(this.fields, key,
                        field => this.fieldLocalizedValue(field.key, null),
                    ) : null),
                    [key]: this.fieldLocalizedValue(key, value),
                }
            },
            updateVisibility(key, visibility) {
                this.$set(this.fieldVisible, key, visibility);
            },
            updateLocale(key, locale) {
                this.$set(this.fieldLocale, key, locale);
            },
            mount({ fields, layout, data={}, authorizations={}, locales, breadcrumb, config }) {
                this.fields = fields;
                this.data = data;
                this.layout = this.patchLayout(layout);
                this.locales = locales;
                this.authorizations = authorizations;
                this.breadcrumb = breadcrumb;
                this.config = config;

                if(fields) {
                    this.fieldVisible = Object.keys(this.fields).reduce((res, fKey) => {
                        res[fKey] = true;
                        return res;
                    },{});
                    this.fieldLocale = this.defaultFieldLocaleMap({ fields, locales });
                }
                this.validate();
            },
            validate() {
                const localizedFields = Object.keys(this.fieldLocale);
                const alert = text => this.actionsBus.$emit('showMainModal', {
                    title: 'Data error',
                    text,
                    isError: true,
                    okCloseOnly: true,
                });
                if(localizedFields.length > 0 && !this.locales.length) {
                    alert("Some fields are localized but the form hasn't any locales configured");
                }
            },
            handleError({response}) {
                if(response.status===422)
                    this.errors = response.data.errors || {};
            },

            patchLayout(layout) {
                if(!layout)return;
                let curFieldsetId = 0;
                let mapFields = layout => {
                    if(layout.legend)
                        layout.id = `${curFieldsetId++}#${layout.legend}`;
                    else if(layout.fields)
                        layout.fields.forEach(row => {
                            row.forEach(mapFields);
                        });
                };
                layout.tabs.forEach(t => t.columns.forEach(mapFields));
                return layout;
            },

            async init() {
                if(this.independant) {
                    this.mount(this.props);
                    this.ready = true;
                }
                else {
                    if(this.entityKey) {
                        await this.get();
                        this.setupActionBar();
                        this.ready = true;
                    }
                    else util.error('no entity key provided');
                }
            },

            setupActionBar() {
                this.actionsBus.$emit('setup', {
                    showSubmitButton: this.isCreation
                        ? this.authorizations.create
                        : this.authorizations.update,
                    showDeleteButton: !this.isCreation && !this.isSingle && this.authorizations.delete,
                    showBackButton: this.isReadOnly,
                    opType: this.isCreation ? 'create' : 'update'
                });
            },
            redirectToList() {
                location.href = getListBackUrl(this.breadcrumb);
            },
            redirectToParentPage() {
                location.href = getBackUrl(this.breadcrumb);
            },
            async submit({ postFn }={}) {
                if(this.pendingJobs.length) {
                    return;
                }
                try {
                    const response = postFn ? await postFn(this.data) : await this.post();
                    if(this.independant) {
                        this.$emit('submit', response);
                        return response;
                    }
                    else if(response.data.ok) {
                        this.mainLoading.$emit('show');
                        this.redirectToParentPage();
                    }
                }
                catch(error) {
                    this.handleError(error);
                    return Promise.reject(error);
                }
            }
        },
        actions: {
            submit() {
                this.submit().catch(()=>{});
            },
            async 'delete'() {
                try {
                    await this.axiosInstance.delete(this.apiPath);
                    this.redirectToList();
                }
                catch(error) {

                }
            },
            cancel() {
                this.redirectToParentPage();
            },

            setPendingJob({ key, origin, value:isPending }) {
                if(isPending)
                    this.pendingJobs.push(key);
                else
                    this.pendingJobs = this.pendingJobs.filter(jobKey => jobKey !== key);

                if(this.pendingJobs.length) {
                    this.actionsBus.$emit('updateActionsState', {
                        state: 'pending',
                        modifier: origin
                    })
                }
                else {
                    this.actionsBus.$emit('updateActionsState', null);
                }
            }
        },
        created() {
            this.$on('error-cleared', errorId => {
                if(this.errors[errorId])
                    this.$set(this.errors[errorId],'cleared',true);
            })
        },
        mounted() {
            this.init();
        }
    }
</script>