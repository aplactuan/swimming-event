export interface User {
    id: number;
    name: string;
    email: string;
    email_verified_at?: string;
}

export interface Competition {
    id: string;
    name: string;
    venue: string;
    competition_date: string;
    warm_up_time: string | null;
    coaches_meeting_time: string | null;
    registration_deadline: string;
    entry_fee: number;
}

export type PageProps<
    T extends Record<string, unknown> = Record<string, unknown>,
> = T & {
    auth: {
        user: User;
    };
};
