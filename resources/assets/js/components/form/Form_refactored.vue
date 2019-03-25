<template>
    <div class="SharpForm">
        <template v-if="ready">
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
                                        :context-fields="isReadOnly ? readOnlyFields : fields"
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
    import cloneDeep from 'lodash/cloneDeep';
    import { error } from "../../util";

    import localize from '../../mixins/localize/form';

    const noop = ()=>{};

    export default {
        name:'SharpForm',
        // extends: DynamicView,

        mixins: [ReadOnlyFields('fields'), Localization, localize('fields')],

        components: {
            SharpTabbedLayout,
            SharpFieldsLayout,
            SharpGrid,
        },


        props: {
            // entityKey: String,
            // instanceId: String,

            /// Extras props for customization
            // independant: {
            //     type: Boolean,
            //     default: false
            // },
            // ignoreAuthorizations: Boolean,
            fields: Object,
            layout: Object,
            data: Object,
            locales: Array,
            errors: Object,
            readonly: Boolean,
            downloadBaseUrl: String,
            // authorizations: Object,
            // props: Object
        },

        // inject:['actionsBus'],

        provide() {
            return {
                $form: this
            }
        },

        data() {
            return {
                // ready: false,
                //
                // fields: null,
                // authorizations: null,

                // errors: {},
                fieldLocale: {},
                locales: null,

                fieldVisible: {},

                pendingJobs: []
            }
        },
        computed: {
            // apiPath() {
            //     let path = `form/${this.entityKey}`;
            //     if(this.instanceId) path+=`/${this.instanceId}`;
            //     return path;
            // },
            localized() {
                return Array.isArray(this.locales) && !!this.locales.length;
            },
            isReadOnly() {
                return !!this.readonly;
            },
            // don't show loading on creation
            // synchronous() {
            //     return this.independant;
            // },
            hasErrors() {
                return Object.keys(this.errors).some(errorKey => !this.errors[errorKey].cleared);
            },
            patchedLayout() {
                let curFieldsetId = 0;
                let mapFieldLayout = layout => layout.legend ? {
                    ...layout,
                    id: `${curFieldsetId++}#${layout.legend}`,
                } : layout;
                return {
                    tabs: layout.tabs.map(tab => ({
                        ...tab,
                        columns: tab.columns.map(column => ({
                            ...column,
                            fields: column.fields.map(mapFieldLayout)
                        }))
                    })),
                };
            },
            // listUrl() {
            //     return `${BASE_URL}/list/${this.baseEntityKey}?restore-context=1`;
            // },

            // localeSelectorErrors() {
            //     return Object.keys(this.errors).reduce((res,errorKey)=>{
            //         let errorLocale = this.locales.find(l=>errorKey.endsWith(`.${l}`));
            //         if(errorLocale) {
            //             res[errorLocale] = true;
            //         }
            //         return res;
            //     },{})
            // }
        },
        methods: {
            updateData(key, value) {
                this.$emit('change', {
                    ...this.data,
                    [key]: this.fieldLocalizedValue(key, value),
                });
            },
            updateVisibility(key, visibility) {
                this.$set(this.fieldVisible, key, visibility);
            },
            updateLocale(key, locale) {
                this.$set(this.fieldLocale, key, locale);
            },
            mount({ fields, layout, data={}, authorizations={}, locales, }) {
                this.fields = fields;
                this.layout = this.patchLayout(layout);
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
            validate() {
                const localizedFields = Object.keys(this.fieldLocale);
                if(localizedFields.length > 0 && !this.locales.length) {
                    error("SharpForm: Some fields are localized but the form hasn't any locales configured");
                }
            },

            // async init() {
            //     if(this.independant) {
            //         this.mount(this.props);
            //         this.ready = true;
            //     }
            //     else {
            //         if(this.entityKey) {
            //             await this.get();
            //             this.setupActionBar();
            //             this.ready = true;
            //         }
            //         else util.error('no entity key provided');
            //     }
            // },

            // setupActionBar({ disable=false }={}) {
            //     const showSubmitButton = this.isCreation ? this.authorizations.create : this.authorizations.update;
            //
            //     this.actionsBus.$emit('setup', {
            //         showSubmitButton: showSubmitButton && !disable,
            //         showDeleteButton: !this.isCreation && this.authorizations.delete && !disable,
            //         showBackButton: this.isReadOnly,
            //         opType: this.isCreation ? 'create' : 'update'
            //     });
            // },
            // redirectToList() {
            //     location.href = this.listUrl;
            // },
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
                        this.redirectToList();
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
            // async 'delete'() {
            //     try {
            //         await this.axiosInstance.delete(this.apiPath);
            //         this.redirectToList();
            //     }
            //     catch(error) {
            //
            //     }
            // },
            cancel() {
                this.redirectToList();
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
            // this.$on('error-cleared', errorId => {
            //     if(this.errors[errorId])
            //         this.$set(this.errors[errorId], 'cleared', true);
            // });
        },
        mounted() {
            this.init();
        }
    }
</script>