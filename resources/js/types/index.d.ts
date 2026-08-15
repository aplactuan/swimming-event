export interface User {
    id: number;
    name: string;
    email: string;
    email_verified_at?: string;
}

export interface AgeBracket {
    id: string;
    name: string;
    start_birthday: string | null;
    end_birthday: string | null;
    sort_order: number;
}

export interface Classification {
    id: string;
    name: string;
    parent_id: string | null;
    sort_order: number;
    inherits_age_brackets: boolean;
    age_brackets: AgeBracket[];
    children: Classification[];
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
    classifications?: Classification[];
}

export type PageProps<
    T extends Record<string, unknown> = Record<string, unknown>,
> = T & {
    auth: {
        user: User;
    };
};
