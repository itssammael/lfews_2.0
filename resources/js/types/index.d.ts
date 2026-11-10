export interface User {
    id: number;
    name: string;
    username?: string;
    email: string;
    email_verified_at?: string;
    profile_photo_url?: string;
    profile_photo_path?: string;
    roles?: string[];
    two_factor_enabled?: boolean;
    current_team?: any;
    current_team_id?: number;
    all_teams?: any[];
}

export interface JetstreamProps {
    canCreateTeams: boolean;
    canManageTwoFactorAuthentication: boolean;
    canUpdatePassword: boolean;
    canUpdateProfileInformation: boolean;
    hasEmailVerification: boolean;
    flash: any;
    hasAccountDeletionFeatures: boolean;
    hasApiFeatures: boolean;
    hasTeamFeatures: boolean;
    hasTermsAndPrivacyPolicyFeature: boolean;
    managesProfilePhotos: boolean;
}

export interface AppPageProps {
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
    system_settings?: {
        system_logo?: string | null;
        system_favicon?: string | null;
        [key: string]: any;
    };
    flash: {
        modbusResult?: any;
        weatherResult?: any;
        success?: string;
        error?: string;
        warning?: string;
        message?: string;
        [key: string]: any;
    };
    errorBags: Record<string, Record<string, string[]>>;
    errors: Record<string, string>;
}

export type PageProps<T extends Record<string, unknown> = Record<string, unknown>> = T & AppPageProps;

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
