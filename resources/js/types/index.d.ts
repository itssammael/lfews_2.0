export interface User {
    id: number;
    name: string;
    email: string;
    email_verified_at: string;
    profile_photo_url?: string;
    profile_photo_path?: string;
    roles?: string[];
}

export interface JetstreamProps {
    canUpdateProfileInformation: boolean;
    canUpdatePassword: boolean;
    canManageTwoFactorAuthentication: boolean;
    hasAccountDeletionFeatures: boolean;
    hasApiFeatures: boolean;
    hasTeamFeatures: boolean;
    managesProfilePhotos: boolean;
    hasEmailVerification: boolean;
}

export type PageProps<T extends Record<string, unknown> = Record<string, unknown>> = T & {
    auth: {
        user: User;
        can: {
            admin: boolean;
            manage: boolean;
            create: boolean;
            read: boolean;
            update: boolean;
            delete: boolean;
        };
    };
    jetstream: JetstreamProps;
    flash: {
        modbusResult?: any;
        weatherResult?: any;
        [key: string]: any;
    };
};

export interface LocationType {
    id: number;
    name: string;
    description: string;
}

export interface Location {
    id: number;
    latitude: number;
    longitude: number;
    location_type?: LocationType;
}

export interface WeatherStation {
    id?: number;
    name: string;
    station_id: string;
    mode: string;
    state: number;
    location_id?: number | string;
    location?: Location;
    created_at?: string;
    updated_at?: string;
}
