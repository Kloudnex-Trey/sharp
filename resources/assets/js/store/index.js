import dashboard from './modules/dashboard';
import entityList from './modules/entity-list';
import showView from './modules/show';
import globalFilters from './modules/global-filters';

export default {
    modules: {
        'dashboard': dashboard,
        'entity-list': entityList,
        'global-filters': globalFilters,
        'show': showView,
    },
}