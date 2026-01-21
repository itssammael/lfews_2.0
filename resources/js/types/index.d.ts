export interface User {
    id: number;
    name: string;
    email: string;
    email_verified_at: string;
    profile_photo_url?: string;
    profile_photo_path?: string;
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
    };
    jetstream: JetstreamProps;
};
