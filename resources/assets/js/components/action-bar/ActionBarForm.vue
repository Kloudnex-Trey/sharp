<template>
    <SharpActionBar>
        <template slot="left">
            <button class="SharpButton SharpButton--secondary-accent" @click="handleCancelButtonClicked">
                {{ cancelButtonLabel }}
            </button>

            <template v-if="showDeleteButton">
                <div class="w-100 h-100">
                    <collapse transition-class="SharpButton__collapse-transition">
                        <template slot="frame-0" slot-scope="frame">
                            <button class="SharpButton SharpButton--danger" @click="frame.next(focusDelete)">
                                <svg  width='16' height='16' viewBox='0 0 16 24' fill-rule='evenodd'>
                                    <path d='M4 0h8v2H4zM0 3v4h1v17h14V7h1V3H0zm13 18H3V8h10v13z'></path>
                                    <path d='M5 10h2v9H5zm4 0h2v9H9z'></path>
                                </svg>
                            </button>
                        </template>
                        <template slot="frame-1" slot-scope="frame">
                            <button class="SharpButton SharpButton--danger" @click="handleDeleteButtonClicked" @blur="frame.next()" ref="openDelete">
                                {{ deleteButtonLabel }}
                            </button>
                        </template>
                    </collapse>
                </div>
            </template>
        </template>
        <template slot="right">
            <template v-if="showSubmitButton">
                <button class="SharpButton SharpButton--accent" @click="handleSubmitButtonClicked">
                    {{ submitButtonLabel }}
                </button>
            </template>
        </template>
    </SharpActionBar>
</template>

<script>
    import SharpActionBar from './ActionBar';
    import ActionBarMixin from './ActionBarMixin';

    import SharpLocaleSelector from '../LocaleSelector';
    import SharpDropdown from '../dropdown/Dropdown.vue';
    import SharpDropdownItem from '../dropdown/DropdownItem.vue';

    import Collapse from '../Collapse';

    import { ActionEvents } from '../../mixins';

    import { lang } from '../../mixins/Localization';

    export default {
        name: 'SharpActionBarForm',
        mixins: [ActionBarMixin, ActionEvents],
        components: {
            SharpActionBar,
            SharpLocaleSelector,
            SharpDropdown,
            SharpDropdownItem,
            Collapse
        },
        props: {
            showSubmitButton: Boolean,
            showDeleteButton: Boolean,
            showBackButton: Boolean,
            isCreation: Boolean,
            actionsState: Object,
        },
        computed: {
            submitButtonLabel() {
                return this.label(`submit_button.${this.isCreation ? 'create' : 'update'}`);
            },
            deleteButtonLabel() {
                return this.label('delete_button');
            },
            cancelButtonLabel() {
                return this.showBackButton
                    ? this.label('back_button')
                    : this.label('cancel_button');
            },
        },
        methods: {
            handleSubmitButtonClicked() {
                this.$emit('submit');
            },
            handleDeleteButtonClicked() {
                this.$emit('delete');
            },
            handleCancelButtonClicked() {
                this.$emit('cancel');
            },
            focusDelete() {
                if(this.$refs.openDelete) {
                    this.$refs.openDelete.focus();
                }
            },
            label(element, extra) {
                let localeKey = `action_bar.form.${element}`, stateLabel;
                if(this.actionsState) {
                    let { state, modifier } = this.actionsState;
                    stateLabel = lang(`${localeKey}.${state}.${modifier}`);
                }
                if(!stateLabel && extra) localeKey+=`.${extra}`;
                return stateLabel || lang(localeKey);
            }
        },
    }
</script>