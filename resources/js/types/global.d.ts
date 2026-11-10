import { PageProps as InertiaPageProps } from '@inertiajs/core';
import { AxiosInstance } from 'axios';
import { route as ziggyRoute } from '../../../vendor/tightenco/ziggy';
import { AppPageProps } from './index';

declare global {
    interface Window {
        axios: AxiosInstance;
    }
    var axios: AxiosInstance;
    var route: typeof ziggyRoute;
    var Ziggy: any;
}

declare module 'vue' {
    interface ComponentCustomProperties {
        route: typeof ziggyRoute;
        $page: {
            props: InertiaPageProps & AppPageProps;
            url: string;
            component: string;
            version: string | null;
        };
    }
}

declare module '@inertiajs/core' {
    interface PageProps extends InertiaPageProps, AppPageProps { }
}
